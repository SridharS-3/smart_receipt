# SmartReceipt - PHP Payment Slip & Receipt Generator

A complete, high-performance web application built with **PHP 8**, **MySQL**, **Tailwind CSS**, and modern JavaScript to generate and manage bank transfer payment slips and receipts.

---

## 📸 Reference Designs Implemented
1. **Single Receipt Generator (Image 1 reference)**:
   - Left side: "Build a Receipt" form with Status, Amount, Transaction Type (IMPS/NEFT/RTGS/UPI), Transaction ID, and Sent To (Banking Name, Account Number).
   - Right side: Live updating preview of the **Transaction Receipt** with blue patterned header, exact fonts, badge colors, and "Download PNG" button.
2. **Mobile Slip Template (Image 2 reference)**:
   - "Payment Transaction Receipt" card with peach/warm highlight box, checkmark badge, "Payment Successful", large bold INR ₹ Amount, Beneficiary Name, Account Number, IFSC, and UTR number.
3. **Bulk Payment Slip Generator (Image 3 reference)**:
   - "Payment Slip Generator" page with drag-and-drop dashed Excel upload area (`.xlsx`, `.xls`, `.csv`).
   - Blue tip box specifying column formats:
     `• Name / Beneficiary Name • Account Number • Amount • UTR / Reference • IFSC Code • Date (optional)`
   - Instant parsing, table preview, batch PNG export zipped as `.zip`, PDF export, and database synchronization.

---

## 🚀 Key Features

### 1. 🔐 Admin Authentication & Theme Switcher
- Professional, minimalist enterprise login page with Email ID and Password.
- **Light Theme by default** with a single-click switch to **Dark Theme** (persisted across sessions via `localStorage`).
- Secure password hashing (`password_hash` with BCRYPT) and session protection on all pages.
- **Default Credentials:**
  - **Email:** `admin@example.com` (or username `admin`)
  - **Password:** `admin123`

### 2. 🧾 Receipt Generator (`receipt-generator.php`)
- **Default Style 2:** Defaulted to the modern Peach Payment Slip (Image 2 reference).
- **Dual Export:** Dedicated **Download Image (PNG)** and **Download PDF** buttons for instant high-resolution downloads.
- **Bulk Import Popup Modal:** Click the **Bulk Import** button directly on the page to open the Excel drag & drop batch generator popup without navigating away.
- **Live Sync:** Changes to any field instantly reflect in the receipt card.
- **Template Switcher:** Easily toggle between **Template 2** (Peach Slip) and **Template 1** (Blue Modern Receipt).
- **Contacts Dropdown:** Auto-fills beneficiary details directly from your saved contacts.
- **Save to Database:** Saves receipts into MySQL history for future re-download or audit.

### 3. 📁 Bulk Payment Slip Modal & Importer
- Drag-and-drop upload for `.xlsx`, `.xls`, and `.csv`.
- Instant client-side parsing powered by SheetJS with column auto-matching.
- **Default Style 2:** Generates slips using Style 2 by default.
- **Dual Export Options:**
  - **Download Images (ZIP):** Generates high-resolution PNGs for every row and zips them into a single `.zip` file download.
  - **Download All (PDF):** Compiles all selected receipts into a multi-page PDF document.
  - **Row Preview:** Preview any row with direct **Download Image** and **Download PDF** buttons.
- **Download Sample Templates:** Direct one-click download for sample CSV and sample XLSX files.
- **Bulk Save to History:** Persists all batch slips in MySQL with unique batch IDs.
- **Import as Contacts:** Convert bulk transfer recipients into saved contacts with one click.

### 4. 👥 Contacts Directory (`contacts.php`)
- **Single Contact:** Add, edit, and delete beneficiaries with Name, Account Number, IFSC, Bank Name, Phone, Email, and Notes.
- **Bulk Contact Import:** Upload an Excel or CSV file to batch import beneficiaries with duplicate account detection.
- **Export Contacts:** Export the entire contact list to CSV with one click.
- **Quick Receipt:** Click "Receipt" on any contact row to immediately jump to the single generator with pre-filled details.

### 5. 📜 Receipt History (`history.php`)
- Filter and search through generated single and bulk receipts.
- Live view modal with style switcher (Style 1 / Style 2) and instant re-download.

---

## 🛠️ Tech Stack & Architecture
- **Backend:** PHP 8.2+ with PDO MySQL
- **Database:** MySQL / MariaDB (`smart_receipt_db`)
- **Frontend:** Tailwind CSS (CDN), FontAwesome 6, Google Fonts
- **Client Libraries:**
  - `xlsx.full.min.js`: Zero-dependency client-side Excel & CSV parser
  - `html2canvas.min.js`: High-resolution HTML-to-image canvas rendering
  - `jszip.min.js`: Fast zip packaging for bulk receipt PNGs
  - `FileSaver.min.js`: Native browser file saving
  - `jspdf.umd.min.js`: Vector-quality PDF generation

---

## 🌐 Local Access URLs
- **Web App URL:** `http://localhost/smart_receipt/`
- **Login:** `http://localhost/smart_receipt/login.php`
- **Dashboard:** `http://localhost/smart_receipt/dashboard.php`
- **Single Generator:** `http://localhost/smart_receipt/receipt-generator.php`
- **Bulk Generator:** `http://localhost/smart_receipt/bulk-receipts.php`
- **Contacts:** `http://localhost/smart_receipt/contacts.php`
- **History:** `http://localhost/smart_receipt/history.php`

---

## ⚙️ Environment Configuration & Database Setup

### 1. Environment File (`.env`)
Copy `.env.example` to `.env` (or customize the pre-configured `.env`):
```bash
cp .env.example .env
```

Configure your database credentials and application settings in `.env`:
```dotenv
APP_NAME="SmartReceipt"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/smart_receipt
APP_TIMEZONE=Asia/Kolkata

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smart_receipt_db
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_SOCKET=/opt/lampp/var/mysql/mysql.sock

DEFAULT_ADMIN_USERNAME=admin
DEFAULT_ADMIN_EMAIL=admin@example.com
DEFAULT_ADMIN_PASSWORD=admin123
```

### 2. Database Migrations CLI
Run database migrations using the standalone migration runner:

```bash
# Run all pending migrations
php migrate.php

# Check status of migrations
php migrate.php status

# Optional: Seed sample contacts and receipts
php migrate.php seed

# Reset and re-run all migrations from scratch
php migrate.php fresh
```

Alternatively, you can manually import the consolidated schema:
```bash
mysql -u root -p smart_receipt_db < database/schema.sql
```


