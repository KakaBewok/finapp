# FinApp - Financial Management App

FinApp is a modern, comprehensive financial management web application that helps you track income and expenses, manage budgets, and automatically extract transaction data from physical receipts using an AI-powered OCR microservice.

## 🚀 Features

*   **Dashboard & Analytics**: Overview of your financial health, recent transactions, and spending summaries.
*   **Income & Expense Tracking**: Manually record transactions or automate them via receipt scanning.
*   **Category & Budget Management**: Organize transactions by categories and set monthly budgets to control spending.
*   **Smart Receipt Scanner**: Upload photos of your receipts, and the system automatically extracts the merchant name, date, and total amount.
*   **Modern UI**: Beautiful, responsive, and intuitive interface built with TailwindCSS and Livewire.

## 🛠️ Tech Stack

### Frontend & Backend (Monolith)
*   [Laravel](https://laravel.com/) (PHP framework)
*   [Livewire](https://livewire.laravel.com/) (Dynamic UI components)
*   [TailwindCSS v4](https://tailwindcss.com/) (Utility-first CSS framework)
*   [Flux](https://fluxui.dev/) (UI Components for Laravel Livewire)
*   [Vite](https://vitejs.dev/) (Frontend tooling)

### OCR Microservice
*   [Python](https://www.python.org/)
*   [FastAPI](https://fastapi.tiangolo.com/) (High-performance API framework)
*   [Tesseract OCR](https://github.com/tesseract-ocr/tesseract) (Optical Character Recognition engine)
*   [OpenCV](https://opencv.org/) (Image preprocessing)

---

## 📋 Prerequisites

Before you begin, ensure you have the following installed:
*   PHP >= 8.2 & Composer
*   Node.js & NPM
*   MySQL or SQLite
*   Python >= 3.9
*   **Tesseract OCR** (Must be installed on your system. For Windows, install it to `C:\Program Files\Tesseract-OCR\tesseract.exe` or add it to your system PATH).

---

## ⚙️ Installation

### 1. Main Laravel Application
Clone the repository, then navigate to the project root:

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Setup environment variables
cp .env.example .env
php artisan key:generate

# Configure your database in the .env file, then run migrations:
php artisan migrate --seed
```

### 2. Python OCR Microservice
The OCR service lives in the `python-ocr` directory.

```bash
cd python-ocr

# Create a virtual environment (recommended)
python -m venv venv

# Activate the virtual environment
# On Windows:
venv\Scripts\activate
# On macOS/Linux:
source venv/bin/activate

# Install required Python packages
pip install -r requirements.txt

# Go back to the root directory
cd ..
```

---

## 🏃‍♂️ Running the Application Locally

We have configured a convenient npm script to run all required services concurrently. From the root of the project, simply run:

```bash
npm run dev:all
```

**This single command will start:**
1.  `npm run dev` (Vite frontend compiler)
2.  `php artisan serve` (Laravel local development server)
3.  `php artisan queue:listen` (Laravel queue worker for asynchronous OCR processing)
4.  `uvicorn main:app --reload --port 8001` (Python FastAPI OCR service)

*Note: If you don't use the concurrent script, you will need to open 4 separate terminal tabs to run these commands individually.*

Access the app at `http://127.0.0.1:8000`.

## 📂 Project Structure Highlights

*   `app/Livewire/` - Contains the frontend logic for screens like the Dashboard, Receipt Scanner, etc.
*   `app/Services/` - Core business logic, including `OCRService.php` (HTTP client to python app) and `ReceiptParserService.php` (Cleans and parses raw text).
*   `app/Jobs/` - Background jobs (e.g., `ProcessReceiptJob.php` for asynchronous image parsing).
*   `python-ocr/` - The dedicated FastAPI microservice handling Tesseract OCR.
