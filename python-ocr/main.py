from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.responses import JSONResponse
import pytesseract
import cv2
import numpy as np
import os
import platform
import re
import logging

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Configure Tesseract path for Windows
if platform.system() == "Windows":
    tesseract_path = r"C:\Program Files\Tesseract-OCR\tesseract.exe"
    if os.path.exists(tesseract_path):
        pytesseract.pytesseract.tesseract_cmd = tesseract_path

app = FastAPI(
    title="FinApp OCR Service",
    description="OCR microservice for receipt text extraction using Tesseract",
    version="1.2.0",
)


# ── Image Preprocessing ──────────────────────────────────────────────

def decode_image(image_bytes: bytes) -> np.ndarray:
    """Decode image bytes to OpenCV BGR image."""
    nparr = np.frombuffer(image_bytes, np.uint8)
    img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    if img is None:
        raise ValueError("Could not decode image")
    return img


def upscale_image(gray: np.ndarray, target_width: int = 1500) -> np.ndarray:
    """Upscale image so small receipt text becomes readable by Tesseract."""
    height, width = gray.shape
    if width < target_width:
        scale = target_width / width
        gray = cv2.resize(gray, None, fx=scale, fy=scale, interpolation=cv2.INTER_CUBIC)
    return gray


def preprocess_gentle(image_bytes: bytes) -> np.ndarray:
    """
    Gentle preprocessing — CLAHE contrast enhancement only.
    Best for decent-quality photos with good lighting.
    """
    img = decode_image(image_bytes)
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    gray = upscale_image(gray)

    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
    gray = clahe.apply(gray)

    return gray


def preprocess_moderate(image_bytes: bytes) -> np.ndarray:
    """
    Moderate preprocessing — denoise + Otsu threshold.
    Good for slightly noisy or uneven lighting.
    """
    img = decode_image(image_bytes)
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    gray = upscale_image(gray)

    # Light denoise
    gray = cv2.fastNlMeansDenoising(gray, None, 8, 7, 21)

    # Otsu's threshold — auto-picks the best threshold
    _, thresh = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)

    return thresh


def preprocess_sharpen(image_bytes: bytes) -> np.ndarray:
    """
    Sharpen-focused preprocessing — good for slightly blurred photos.
    Sharpens then applies Otsu threshold.
    """
    img = decode_image(image_bytes)
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    gray = upscale_image(gray)

    # Sharpen
    kernel = np.array([[-1, -1, -1],
                       [-1,  9, -1],
                       [-1, -1, -1]])
    gray = cv2.filter2D(gray, -1, kernel)

    # CLAHE
    clahe = cv2.createCLAHE(clipLimit=3.0, tileGridSize=(8, 8))
    gray = clahe.apply(gray)

    # Otsu
    _, thresh = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)

    return thresh


def preprocess_header_region(image_bytes: bytes) -> np.ndarray:
    """
    Extract and preprocess only the top 30% of the image (header region).
    Merchant name is typically at the very top of the receipt.
    Uses extra upscaling for decorative/stylized fonts.
    """
    img = decode_image(image_bytes)
    height, width = img.shape[:2]

    # Crop top 30% of the image
    header_img = img[0:int(height * 0.30), :]

    gray = cv2.cvtColor(header_img, cv2.COLOR_BGR2GRAY)

    # Extra upscale for header — decorative fonts need higher resolution
    h, w = gray.shape
    if w < 2000:
        scale = 2000 / w
        gray = cv2.resize(gray, None, fx=scale, fy=scale, interpolation=cv2.INTER_CUBIC)

    # CLAHE
    clahe = cv2.createCLAHE(clipLimit=2.5, tileGridSize=(8, 8))
    gray = clahe.apply(gray)

    return gray


# ── OCR Utilities ─────────────────────────────────────────────────────

def run_ocr(image: np.ndarray, config: str) -> str:
    """Run Tesseract OCR with the given config, with fallback."""
    try:
        return pytesseract.image_to_string(image, config=config)
    except (pytesseract.TesseractNotFoundError, pytesseract.TesseractError):
        fallback_config = re.sub(r'-l\s+\S+', '', config).strip()
        return pytesseract.image_to_string(image, config=fallback_config)


def get_ocr_confidence(image: np.ndarray, config: str) -> float:
    """Get the average OCR confidence for an image."""
    try:
        data = pytesseract.image_to_data(image, config=config, output_type=pytesseract.Output.DICT)
        confidences = [int(c) for c in data['conf'] if int(c) > 0]
        return sum(confidences) / len(confidences) if confidences else 0
    except Exception:
        return 0


def post_process_text(text: str) -> str:
    """
    Clean up common OCR artifacts and normalize receipt text.
    """
    lines = text.split('\n')
    cleaned_lines = []

    for line in lines:
        line = line.strip()
        if not line:
            continue

        # Remove excessive whitespace but keep single spaces
        line = re.sub(r' {2,}', '  ', line)

        # Fix common OCR errors in numbers
        # "32. 000" -> "32.000"  (space after dot in amounts)
        line = re.sub(r'(\d+)\.\s+(\d{3})', r'\1.\2', line)

        # "3b" -> "36" when near price context (b often misread for 6)
        # Only do this near Rp or in number-heavy lines
        if re.search(r'Rp|total|bayar|subtotal|\d{2,}', line, re.IGNORECASE):
            line = re.sub(r'(\d)b', r'\g<1>6', line)
            line = re.sub(r'(\d)B', r'\g<1>8', line)

        # "{ 3" -> "3" when near prices (curly brace misread)
        line = re.sub(r'[{}\[\]](\s*\d)', r'\1', line)

        cleaned_lines.append(line)

    return '\n'.join(cleaned_lines)


def score_receipt_text(text: str) -> float:
    """
    Score how 'receipt-like' the extracted text is.
    Higher score = better OCR result for receipt parsing.
    """
    score = 0.0
    upper_text = text.upper()

    # Receipt keywords
    keywords = [
        'TOTAL', 'GRAND TOTAL', 'SUBTOTAL', 'SUB TOTAL',
        'RP', 'BAYAR', 'PEMBAYARAN', 'TUNAI', 'CASH',
        'DEBIT', 'CREDIT', 'KREDIT',
        'ITEM', 'QTY', 'JUMLAH', 'HARGA',
        'KASIR', 'CASHIER', 'STRUK', 'RECEIPT',
        'TERIMA KASIH', 'THANK YOU',
        'PPN', 'TAX', 'PAJAK', 'DISKON', 'DISCOUNT',
        'DINE', 'TAKE AWAY', 'DELIVERY',
        'MANDIRI', 'BCA', 'BRI', 'BNI', 'BANK',
    ]

    for kw in keywords:
        if kw in upper_text:
            score += 2.0

    # Price patterns
    price_matches = re.findall(r'(?:Rp\.?\s*)?(\d{1,3}[.,]\d{3})', text, re.IGNORECASE)
    score += len(price_matches) * 1.5

    # Date patterns
    if re.search(r'\d{1,2}[/\-]\d{1,2}[/\-]\d{2,4}', text):
        score += 3.0
    if re.search(r'\d{1,2}\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)', text, re.IGNORECASE):
        score += 3.0

    # Readability factor
    alnum_count = sum(1 for c in text if c.isalnum() or c.isspace())
    total_count = len(text) if text else 1
    readability = alnum_count / total_count
    score *= readability

    return score


# ── API Endpoints ─────────────────────────────────────────────────────

@app.post("/ocr")
async def extract_text(file: UploadFile = File(...)):
    """
    Extract text from an uploaded receipt image using Tesseract OCR.
    Uses multiple preprocessing strategies and picks the best result.
    Also extracts the header region separately for merchant name.
    """
    allowed_types = ["image/jpeg", "image/png", "image/jpg", "image/webp", "image/bmp"]
    if file.content_type not in allowed_types:
        raise HTTPException(
            status_code=400,
            detail=f"Invalid file type: {file.content_type}. Allowed: {', '.join(allowed_types)}",
        )

    try:
        contents = await file.read()
        if len(contents) == 0:
            raise HTTPException(status_code=400, detail="Empty file uploaded")

        configs = [
            r"--oem 3 --psm 4 -l eng",
            r"--oem 3 --psm 6 -l eng",
        ]

        preprocessors = [
            ("gentle", preprocess_gentle),
            ("moderate", preprocess_moderate),
            ("sharpen", preprocess_sharpen),
        ]

        best_text = ""
        best_score = -1

        for prep_name, prep_fn in preprocessors:
            try:
                processed_img = prep_fn(contents)
            except ValueError:
                continue

            for config in configs:
                try:
                    raw_text = run_ocr(processed_img, config)
                    if not raw_text or not raw_text.strip():
                        continue

                    text = post_process_text(raw_text)
                    score = score_receipt_text(text)
                    ocr_conf = get_ocr_confidence(processed_img, config)
                    combined = score + (ocr_conf / 10.0)

                    logger.info(
                        f"[{prep_name}] config={config.split('--psm')[1][:3].strip()} "
                        f"score={score:.1f} conf={ocr_conf:.1f} combined={combined:.1f}"
                    )

                    if combined > best_score:
                        best_score = combined
                        best_text = text

                except Exception as e:
                    logger.warning(f"OCR [{prep_name}] failed: {e}")
                    continue

        # Also try header-specific OCR for merchant name
        header_text = ""
        try:
            header_img = preprocess_header_region(contents)
            # PSM 6 = uniform block; PSM 7 = single line — try both for header
            for psm in ["6", "7"]:
                try:
                    htext = run_ocr(header_img, f"--oem 3 --psm {psm} -l eng")
                    if htext and htext.strip():
                        header_text = htext.strip()
                        logger.info(f"[header psm={psm}] text: {header_text[:80]}")
                        break
                except Exception:
                    continue
        except Exception as e:
            logger.warning(f"Header extraction failed: {e}")

        if not best_text:
            raise HTTPException(status_code=400, detail="Could not extract text from image")

        # If we got header text, prepend it to help with merchant extraction
        # Only if the header text seems different from the first line of best_text
        if header_text:
            first_lines = best_text.split('\n')[:2]
            first_text_upper = ' '.join(first_lines).upper()
            header_upper = header_text.split('\n')[0].upper() if header_text else ''

            # If header has text that's not already in the first lines, prepend it
            if header_upper and header_upper not in first_text_upper:
                # Check if header text looks like a merchant name (has letters)
                letter_ratio = sum(1 for c in header_upper if c.isalpha()) / max(len(header_upper), 1)
                if letter_ratio > 0.4:
                    best_text = header_text.split('\n')[0] + '\n' + best_text

        return JSONResponse(content={
            "text": best_text,
            "confidence": round(best_score, 2),
        })

    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"OCR processing failed: {str(e)}")


@app.get("/health")
async def health_check():
    """Health check endpoint."""
    try:
        version = pytesseract.get_tesseract_version()
        return {"status": "healthy", "tesseract_version": str(version)}
    except Exception as e:
        return {"status": "unhealthy", "error": str(e)}


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
