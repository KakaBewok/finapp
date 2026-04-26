# FinApp OCR Microservice

Python-based OCR microservice using FastAPI + Tesseract OCR for receipt text extraction.

## Prerequisites

1. **Python 3.10+** installed
2. **Tesseract OCR** installed on your system

### Install Tesseract OCR

**Windows:**
- Download installer from: https://github.com/UB-Mannheim/tesseract/wiki
- During installation, select additional language: **Indonesian** (`ind`)
- Add Tesseract to PATH, or set the path in code

**macOS:**
```bash
brew install tesseract tesseract-lang
```

**Linux (Ubuntu/Debian):**
```bash
sudo apt install tesseract-ocr tesseract-ocr-ind
```

## Setup

```bash
cd python-ocr

# Create virtual environment
python -m venv venv

# Activate (Windows)
venv\Scripts\activate

# Activate (macOS/Linux)
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt
```

## Run the Service

```bash
# Development mode with auto-reload
uvicorn main:app --reload --host 0.0.0.0 --port 8000

# Or simply:
python main.py
```

The service will be available at: `http://127.0.0.1:8000`

## API Endpoints

### POST /ocr

Extract text from a receipt image.

**Request:**
- Method: `POST`
- Content-Type: `multipart/form-data`
- Body: `file` (image file - JPEG, PNG, WebP, BMP)

**Example (cURL):**
```bash
curl -X POST "http://127.0.0.1:8000/ocr" \
  -F "file=@receipt.jpg"
```

**Response:**
```json
{
  "text": "INDOMARET\nJl. Sudirman No.123\n\nMie Instan      3.500\nAir Mineral     4.000\nRoti Tawar      12.500\n\nTOTAL          20.000\nTUNAI          20.000\n\n25/04/2026 14:30"
}
```

### GET /health

Health check endpoint.

**Response:**
```json
{
  "status": "healthy",
  "tesseract_version": "5.3.0"
}
```

## API Docs

FastAPI auto-generates interactive API docs:
- Swagger UI: http://127.0.0.1:8000/docs
- ReDoc: http://127.0.0.1:8000/redoc

## Configuration

Set the Tesseract path in your environment if it's not in PATH:

```bash
# Windows (PowerShell)
$env:TESSERACT_CMD = "C:\Program Files\Tesseract-OCR\tesseract.exe"

# Or in Python code:
# pytesseract.pytesseract.tesseract_cmd = r'C:\Program Files\Tesseract-OCR\tesseract.exe'
```
