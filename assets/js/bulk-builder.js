// assets/js/bulk-builder.js - Excel/CSV Drag-and-Drop & Batch Slip Generator

let parsedRows = [];

document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('excelFileInput');

    if (!dropZone || !fileInput) return;

    // Drag-and-drop events
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('dragover');
        });
    });

    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFile(e.target.files[0]);
        }
    });
});

function handleFile(file) {
    const reader = new FileReader();
    const fileName = file.name;
    const isCSV = fileName.endsWith('.csv');

    const batchFileNameEl = document.getElementById('batchFileName');
    if (batchFileNameEl) batchFileNameEl.innerText = fileName;
    
    const uploadedFileTitleEl = document.getElementById('uploadedFileTitle');
    if (uploadedFileTitleEl) uploadedFileTitleEl.innerText = fileName;

    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            const rawJson = XLSX.utils.sheet_to_json(worksheet, { defval: '' });

            if (!rawJson || rawJson.length === 0) {
                showNotification('The uploaded file contains no data rows.', 'error');
                return;
            }

            processRawRows(rawJson);
        } catch (err) {
            console.error('Error parsing sheet:', err);
            showNotification('Error reading file. Ensure valid .xlsx, .xls or .csv.', 'error');
        }
    };

    reader.readAsArrayBuffer(file);
}

// Robust column finder that handles exact and flexible substring matches
function getRowField(normalized, aliases, fallback = '') {
    // 1. Exact match
    for (const a of aliases) {
        if (normalized[a] !== undefined && normalized[a] !== '') {
            return normalized[a];
        }
    }
    // 2. Substring/fuzzy match
    const keys = Object.keys(normalized);
    for (const a of aliases) {
        const found = keys.find(k => k.includes(a));
        if (found && normalized[found] !== undefined && normalized[found] !== '') {
            return normalized[found];
        }
    }
    return fallback;
}

function processRawRows(rawJson) {
    parsedRows = [];
    let totalAmt = 0;

    rawJson.forEach((row, index) => {
        // Normalize column keys
        const normalized = {};
        for (const [key, value] of Object.entries(row)) {
            const cleanKey = key.trim().toLowerCase();
            normalized[cleanKey] = String(value).trim();
        }

        const name = getRowField(normalized, ['beneficiary name', 'beneficiary', 'party name', 'party', 'banking name', 'name', 'account name', 'customer name', 'payee'], `Beneficiary ${index + 1}`);
        const account_no = getRowField(normalized, ['account number', 'account no', 'account no.', 'a/c no', 'ac no', 'account', 'a/c', 'acc no'], 'XXXXXXXX0000');
        const amountRaw = getRowField(normalized, ['amount', 'transaction amount', 'amt', 'amount (inr)', 'transaction amount (inr)', 'amount (₹)', 'amount inr', 'inr', 'total amount', 'net amount', 'paid amount', 'transfer amount', 'sum', 'value'], '0');
        const amount = parseFloat(String(amountRaw).replace(/,/g, '').replace(/[^0-9.]/g, '')) || 0;
        const utr = getRowField(normalized, ['utr / reference', 'utr / ref', 'utr', 'utr number', 'reference', 'reference no', 'ref no', 'transaction id', 'txn id', 'rrn', 'ref'], `${Math.floor(100000000000 + Math.random() * 900000000000)}`);
        const ifsc = (getRowField(normalized, ['ifsc code', 'ifsc', 'bank ifsc', 'ifsc_code'], 'HDFC0001535')).toUpperCase();
        const type = (getRowField(normalized, ['transaction type', 'payment type', 'txn type', 'transfer type', 'payment mode', 'mode', 'type'], 'IMPS')).toUpperCase();
        const status = getRowField(normalized, ['transaction status', 'payment status', 'status', 'txn status'], 'Successful');
        const date = getRowField(normalized, ['date & time', 'payment date', 'date', 'transaction date', 'datetime', 'time'], '');

        totalAmt += amount;

        parsedRows.push({
            id: index + 1,
            selected: true,
            name,
            account_no,
            amount,
            utr,
            ifsc,
            type,
            status,
            date
        });
    });

    renderParsedTable();

    // Hide preUploadSection (large dropzone & tip) to give full focus and space to parsed data
    const preUploadSection = document.getElementById('preUploadSection');
    if (preUploadSection) {
        preUploadSection.classList.add('hidden');
    }

    // Show compact post-upload summary bar with filename, count badge and replace file button
    const postUploadSummaryBar = document.getElementById('postUploadSummaryBar');
    if (postUploadSummaryBar) {
        postUploadSummaryBar.classList.remove('hidden');
    }

    const uploadedFileBadge = document.getElementById('uploadedFileBadge');
    if (uploadedFileBadge) {
        uploadedFileBadge.innerText = `${parsedRows.length} Slips Loaded`;
    }

    const batchProcessCard = document.getElementById('batchProcessCard');
    if (batchProcessCard) {
        batchProcessCard.classList.remove('hidden');
    }

    const batchCountBadge = document.getElementById('batchCountBadge');
    if (batchCountBadge) {
        batchCountBadge.innerText = `${parsedRows.length} Slips`;
    }

    const batchTotalAmount = document.getElementById('batchTotalAmount');
    if (batchTotalAmount) {
        batchTotalAmount.innerText = '₹ ' + totalAmt.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }

    showNotification(`Successfully parsed ${parsedRows.length} payment slips!`, 'success');
}

// Reset upload to select another file
function resetBulkUpload() {
    parsedRows = [];
    const preUploadSection = document.getElementById('preUploadSection');
    if (preUploadSection) preUploadSection.classList.remove('hidden');

    const postUploadSummaryBar = document.getElementById('postUploadSummaryBar');
    if (postUploadSummaryBar) postUploadSummaryBar.classList.add('hidden');

    const batchProcessCard = document.getElementById('batchProcessCard');
    if (batchProcessCard) batchProcessCard.classList.add('hidden');

    const fileInput = document.getElementById('excelFileInput');
    if (fileInput) fileInput.value = '';

    const tbody = document.getElementById('parsedDataBody');
    if (tbody) tbody.innerHTML = '';
}

function renderParsedTable() {
    const tbody = document.getElementById('parsedDataBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    parsedRows.forEach((row, i) => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50 dark:hover:bg-slate-800/60 transition border-b border-slate-100 dark:border-slate-800/80';
        tr.innerHTML = `
            <td class="py-3 px-3 text-center">
                <input type="checkbox" ${row.selected ? 'checked' : ''} onchange="toggleRowSelect(${i}, this.checked)" class="rounded text-sky-600 focus:ring-sky-500 cursor-pointer">
            </td>
            <td class="py-3 px-3 text-center text-slate-400 font-mono text-[11px]">${row.id}</td>
            <td class="py-3 px-3 font-bold text-slate-800 dark:text-slate-100">${escapeHtml(row.name)}</td>
            <td class="py-3 px-3 font-mono text-slate-600 dark:text-slate-300 text-[11px]">${escapeHtml(row.account_no)}</td>
            <td class="py-3 px-3 font-bold text-slate-900 dark:text-white whitespace-nowrap">₹ ${row.amount.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
            <td class="py-3 px-3 font-mono text-slate-500 dark:text-slate-400 text-[11px]">${escapeHtml(row.utr)}</td>
            <td class="py-3 px-3 font-mono text-slate-500 dark:text-slate-400 text-[11px]">${escapeHtml(row.ifsc)}</td>
            <td class="py-3 px-3 text-center">
                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wider uppercase bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300 border border-sky-200 dark:border-sky-800">${escapeHtml(row.type)}</span>
            </td>
            <td class="py-3 px-3 text-center">
                <button type="button" onclick="previewSingleRow(${i})" class="inline-flex items-center justify-center w-7 h-7 text-sky-600 hover:text-white bg-sky-50 hover:bg-sky-600 dark:bg-slate-800 dark:text-sky-400 dark:hover:bg-sky-600 dark:hover:text-white rounded-lg border border-sky-200 dark:border-slate-700 transition shadow-sm" title="Preview Slip">
                    <i class="fa-solid fa-eye text-xs"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function toggleSelectAllRows(masterCheckbox) {
    const isChecked = masterCheckbox.checked;
    parsedRows.forEach(r => r.selected = isChecked);
    renderParsedTable();
}

function toggleRowSelect(index, isChecked) {
    if (parsedRows[index]) {
        parsedRows[index].selected = isChecked;
    }
}

// Download Sample XLSX dynamically
function downloadSampleXLSX() {
    const sampleData = [
        {
            "Beneficiary Name": "PERAMBATH EXPORTS IMPORTS",
            "Account Number": "XXXXXXXX0106",
            "Amount": 20000.00,
            "UTR / Reference": "251717639656",
            "IFSC Code": "HDFC0001535",
            "Transaction Type": "IMPS",
            "Status": "Successful",
            "Date": "2026-11-05"
        },
        {
            "Beneficiary Name": "ameen",
            "Account Number": "50100584372675",
            "Amount": 50000.00,
            "UTR / Reference": "2042974766",
            "IFSC Code": "HDFC0001535",
            "Transaction Type": "NEFT",
            "Status": "Successful",
            "Date": "2026-11-05"
        },
        {
            "Beneficiary Name": "Apex Solutions PVT LTD",
            "Account Number": "9180200388412",
            "Amount": 12500.50,
            "UTR / Reference": "491827401928",
            "IFSC Code": "ICIC0000047",
            "Transaction Type": "RTGS",
            "Status": "Successful",
            "Date": "2026-11-04"
        },
        {
            "Beneficiary Name": "Global Logistics Hub",
            "Account Number": "6029104882190",
            "Amount": 75000.00,
            "UTR / Reference": "772910394821",
            "IFSC Code": "SBIN0004812",
            "Transaction Type": "IMPS",
            "Status": "Successful",
            "Date": "2026-11-04"
        }
    ];

    const worksheet = XLSX.utils.json_to_sheet(sampleData);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Payment Slips");
    XLSX.writeFile(workbook, "sample_payment_slips.xlsx");
    showNotification("Sample XLSX downloaded!", "success");
}

// Preview Single Row in Modal
let currentModalRow = null;
function previewSingleRow(index) {
    const row = parsedRows[index];
    if (!row) return;
    currentModalRow = row;

    const tpl = document.getElementById('bulkTemplateChoice').value || 'template2';
    const container = document.getElementById('modalReceiptContainer');
    container.innerHTML = generateReceiptHTML(row, tpl);

    document.getElementById('modalDownloadImgBtn').onclick = () => downloadModalReceipt(row, tpl);
    document.getElementById('modalDownloadPdfBtn').onclick = () => downloadModalPDF(row, tpl);
    document.getElementById('previewModal').classList.remove('hidden');
}

function closePreviewModal() {
    document.getElementById('previewModal').classList.add('hidden');
}

async function downloadModalReceipt(row, tpl) {
    const target = document.getElementById('modalReceiptContainer').firstElementChild;
    const btn = document.getElementById('modalDownloadImgBtn');
    const orig = btn.innerHTML;
    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Generating...`;
    btn.disabled = true;

    try {
        const canvas = await html2canvas(target, { 
            scale: 3, 
            useCORS: true, 
            backgroundColor: '#ffffff',
            onclone: (clonedDoc) => {
                const el = clonedDoc.querySelector('#modalReceiptContainer > div');
                if (el) {
                    el.style.width = '440px';
                    el.style.maxWidth = '440px';
                }
            }
        });
        canvas.toBlob((blob) => {
            saveAs(blob, `Receipt_${row.utr}_${row.name.replace(/\s+/g, '_')}.png`);
            showNotification('Image receipt downloaded!', 'success');
            btn.innerHTML = orig;
            btn.disabled = false;
        });
    } catch (e) {
        console.error(e);
        showNotification('Error downloading image', 'error');
        btn.innerHTML = orig;
        btn.disabled = false;
    }
}

async function downloadModalPDF(row, tpl) {
    const target = document.getElementById('modalReceiptContainer').firstElementChild;
    const btn = document.getElementById('modalDownloadPdfBtn');
    const orig = btn.innerHTML;
    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Rendering PDF...`;
    btn.disabled = true;

    try {
        const canvas = await html2canvas(target, { 
            scale: 2.5, 
            useCORS: true, 
            backgroundColor: '#ffffff',
            onclone: (clonedDoc) => {
                const el = clonedDoc.querySelector('#modalReceiptContainer > div');
                if (el) {
                    el.style.width = '440px';
                    el.style.maxWidth = '440px';
                }
            }
        });
        const imgData = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');

        const imgWidth = 140; // mm
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        const xPos = (210 - imgWidth) / 2;
        const yPos = 25;

        pdf.addImage(imgData, 'PNG', xPos, yPos, imgWidth, imgHeight);
        pdf.save(`Receipt_${row.utr}.pdf`);
        showNotification('PDF receipt downloaded!', 'success');
    } catch (e) {
        console.error(e);
        showNotification('Error creating PDF', 'error');
    } finally {
        btn.innerHTML = orig;
        btn.disabled = false;
    }
}

// Generate Receipt HTML snippet
function formatRowDateTime(dateStr) {
    if (!dateStr) {
        const d = new Date();
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ', ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
    }
    const parsed = new Date(dateStr);
    if (isNaN(parsed.getTime())) {
        return dateStr;
    }
    const dPart = parsed.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    const tPart = dateStr.includes(':') ? parsed.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) : new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
    return dPart + ', ' + tPart;
}

function generateReceiptHTML(row, tpl) {
    const formattedAmountT1 = '₹ ' + row.amount.toLocaleString('en-IN');
    const formattedAmountT2 = 'INR ₹ ' + row.amount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const formattedAmountT3 = '₹' + row.amount.toLocaleString('en-IN');
    const formattedDate = formatRowDateTime(row.date);
    let t3Date = formattedDate;
    if (row.date) {
        const dObj = new Date(row.date);
        if (!isNaN(dObj.getTime())) {
            t3Date = dObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        }
    }
    const statusClean = String(row.status || 'Successful').trim();
    const statusDisplay = statusClean.toLowerCase().startsWith('payment') ? statusClean : ('Payment ' + statusClean);

    if (tpl === 'template1') {
        return `
            <div class="receipt-template-1">
                <div class="receipt-t1-header" style="background-color: #0284c7 !important; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;">
                    <h3 style="color: #ffffff !important; margin: 0; font-weight: 700;">Transaction Receipt</h3>
                </div>
                <div class="receipt-t1-body">
                    <div class="receipt-t1-row">
                        <span class="receipt-t1-label">Status</span>
                        <span class="receipt-t1-status-success">${escapeHtml(row.status)}</span>
                    </div>
                    <div class="receipt-t1-row">
                        <span class="receipt-t1-label">Transaction Amount</span>
                        <span class="receipt-t1-amount">${formattedAmountT1}</span>
                    </div>
                    <div class="receipt-t1-row">
                        <span class="receipt-t1-label">Payment Type</span>
                        <span class="receipt-t1-value">Send to Account (${escapeHtml(row.type)})</span>
                    </div>
                    <div class="receipt-t1-divider"></div>
                    <div class="receipt-t1-row">
                        <span class="receipt-t1-label">Transaction ID</span>
                        <span class="receipt-t1-value font-mono">${escapeHtml(row.utr)}</span>
                    </div>
                    <div class="receipt-t1-section-title">Sent To</div>
                    <div class="receipt-t1-row">
                        <span class="receipt-t1-label">Banking Name</span>
                        <span class="receipt-t1-value">${escapeHtml(row.name)}</span>
                    </div>
                    <div class="receipt-t1-row">
                        <span class="receipt-t1-label">Account Number</span>
                        <span class="receipt-t1-value font-mono">${escapeHtml(row.account_no)}</span>
                    </div>
                    <div class="receipt-t1-row" style="margin-bottom: 0;">
                        <span class="receipt-t1-label">Date &amp; Time</span>
                        <span class="receipt-t1-value font-mono">${escapeHtml(formattedDate)}</span>
                    </div>
                </div>
            </div>
        `;
    } else if (tpl === 'template3') {
        return `
            <div class="receipt-template-3">
                <div class="receipt-t3-badge-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60" fill="none">
                        <rect x="3" y="3" width="54" height="54" rx="16" fill="#ffffff" stroke="#00b65a" stroke-width="4" />
                        <polyline points="18 29.5 26 37.5 42 21.5" fill="none" stroke="#00b65a" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="receipt-t3-amount-text">${formattedAmountT3}</div>
                <div class="receipt-t3-status-sub">${escapeHtml(statusDisplay)}</div>
                <div class="receipt-t3-details-table">
                    <div class="receipt-t3-row">
                        <span class="receipt-t3-label">TOTAL AMOUNT</span>
                        <span class="receipt-t3-val">${formattedAmountT3}</span>
                    </div>
                    <div class="receipt-t3-row">
                        <span class="receipt-t3-label">UTR NO</span>
                        <span class="receipt-t3-val font-mono">${escapeHtml(row.utr)}</span>
                    </div>
                    <div class="receipt-t3-row">
                        <span class="receipt-t3-label">BENEFICIARY NAME</span>
                        <span class="receipt-t3-val">${escapeHtml(row.name)}</span>
                    </div>
                    <div class="receipt-t3-row">
                        <span class="receipt-t3-label">MODE OF TRANSFER</span>
                        <span class="receipt-t3-val">${escapeHtml(row.type)}</span>
                    </div>
                    <div class="receipt-t3-row" style="margin-bottom: 0;">
                        <span class="receipt-t3-label">TRANSACTION DATE</span>
                        <span class="receipt-t3-val font-mono">${escapeHtml(t3Date)}</span>
                    </div>
                </div>
                <button type="button" class="receipt-t3-btn-primary">Make New Payment</button>
                <button type="button" onclick="window.location.href='dashboard.php'" class="receipt-t3-btn-secondary hover:bg-slate-50 transition cursor-pointer">Go Back To Dashboard</button>
            </div>
        `;
    } else {
        return `
            <div class="receipt-template-2">
                <div class="receipt-t2-title">
                    Payment Transaction Receipt
                </div>
                <div class="receipt-t2-card">
                    <div class="receipt-t2-badge-circle">
                        <svg xmlns="http://www.w3.org/2000/svg" width="58" height="58" viewBox="0 0 58 58" fill="none">
                            <circle cx="29" cy="29" r="27.5" fill="#ffffff" stroke="#EA580C" stroke-width="2.5" />
                            <polyline points="19 28.5 25.5 35 39 21.5" fill="none" stroke="#EA580C" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <div class="receipt-t2-status-label">TRANSACTION STATUS</div>
                    <div class="receipt-t2-status-text">${escapeHtml(statusDisplay)}</div>
                    <div class="receipt-t2-amount-label">AMOUNT</div>
                    <div class="receipt-t2-amount-text">${formattedAmountT2}</div>
                </div>
                <div>
                    <div class="receipt-t2-list-row">
                        <span class="receipt-t2-list-label">Beneficiary Name</span>
                        <span class="receipt-t2-list-value">${escapeHtml(row.name)}</span>
                    </div>
                    <div class="receipt-t2-list-row">
                        <span class="receipt-t2-list-label">Account No.</span>
                        <span class="receipt-t2-list-value font-mono">${escapeHtml(row.account_no)}</span>
                    </div>
                    <div class="receipt-t2-list-row">
                        <span class="receipt-t2-list-label">IFSC</span>
                        <span class="receipt-t2-list-value font-mono">${escapeHtml(row.ifsc)}</span>
                    </div>
                    <div class="receipt-t2-list-row">
                        <span class="receipt-t2-list-label">UTR Number</span>
                        <span class="receipt-t2-list-value font-mono">${escapeHtml(row.utr)}</span>
                    </div>
                    <div class="receipt-t2-list-row">
                        <span class="receipt-t2-list-label">Date &amp; Time</span>
                        <span class="receipt-t2-list-value font-mono">${escapeHtml(formattedDate)}</span>
                    </div>
                </div>
            </div>
        `;
    }
}

// Batch Generator: Render All to ZIP
async function generateAndDownloadZip() {
    const selected = parsedRows.filter(r => r.selected);
    if (selected.length === 0) {
        showNotification('No rows selected for ZIP export.', 'error');
        return;
    }

    const tpl = document.getElementById('bulkTemplateChoice').value;
    const btn = document.getElementById('btnDownloadZip');
    const originalText = btn.innerHTML;
    btn.disabled = true;

    const progressWrapper = document.getElementById('zipProgressWrapper');
    const progressLabel = document.getElementById('zipProgressLabel');
    const progressPercent = document.getElementById('zipProgressPercent');
    const progressBar = document.getElementById('zipProgressBar');

    progressWrapper.classList.remove('hidden');

    const zip = new JSZip();
    const hiddenContainer = document.getElementById('batchHiddenContainer');

    try {
        for (let i = 0; i < selected.length; i++) {
            const row = selected[i];
            const currentCount = i + 1;
            const pct = Math.round((currentCount / selected.length) * 100);

            progressLabel.innerText = `Generating receipt PNGs: ${currentCount} / ${selected.length} (${escapeHtml(row.name)})`;
            progressPercent.innerText = `${pct}%`;
            progressBar.style.width = `${pct}%`;

            // Render HTML into hidden container
            hiddenContainer.innerHTML = generateReceiptHTML(row, tpl);
            const targetEl = hiddenContainer.firstElementChild;

            // Render canvas
            const canvas = await html2canvas(targetEl, {
                scale: 2.5,
                useCORS: true,
                backgroundColor: '#ffffff'
            });

            // Convert to blob and add to zip
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
            const safeName = row.name.replace(/[^a-zA-Z0-9_-]/g, '_');
            const safeUtr = row.utr.replace(/[^a-zA-Z0-9_-]/g, '_');
            zip.file(`Receipt_${safeUtr}_${safeName}.png`, blob);
        }

        progressLabel.innerText = 'Compressing into ZIP archive...';
        const zipBlob = await zip.generateAsync({ type: 'blob' });
        saveAs(zipBlob, `Payment_Slips_Batch_${new Date().toISOString().slice(0, 10)}.zip`);

        showNotification(`ZIP archive with ${selected.length} receipts downloaded!`, 'success');
    } catch (err) {
        console.error('ZIP generation error:', err);
        showNotification('Error creating ZIP archive.', 'error');
    } finally {
        hiddenContainer.innerHTML = '';
        btn.disabled = false;
        btn.innerHTML = originalText;
        setTimeout(() => {
            progressWrapper.classList.add('hidden');
        }, 3000);
    }
}

// Batch Generator: Render All to Multi-page PDF
async function generateAndDownloadBatchPDF() {
    const selected = parsedRows.filter(r => r.selected);
    if (selected.length === 0) {
        showNotification('No rows selected for PDF export.', 'error');
        return;
    }

    const tpl = document.getElementById('bulkTemplateChoice').value || 'template2';
    const btn = document.getElementById('btnDownloadBatchPdf');
    const originalText = btn.innerHTML;
    btn.disabled = true;

    const progressWrapper = document.getElementById('zipProgressWrapper');
    const progressLabel = document.getElementById('zipProgressLabel');
    const progressPercent = document.getElementById('zipProgressPercent');
    const progressBar = document.getElementById('zipProgressBar');

    progressWrapper.classList.remove('hidden');

    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF('p', 'mm', 'a4');
    const hiddenContainer = document.getElementById('batchHiddenContainer');

    try {
        for (let i = 0; i < selected.length; i++) {
            const row = selected[i];
            const currentCount = i + 1;
            const pct = Math.round((currentCount / selected.length) * 100);

            progressLabel.innerText = `Rendering PDF Page ${currentCount} / ${selected.length} (${escapeHtml(row.name)})`;
            progressPercent.innerText = `${pct}%`;
            progressBar.style.width = `${pct}%`;

            // Render HTML into hidden container
            hiddenContainer.innerHTML = generateReceiptHTML(row, tpl);
            const targetEl = hiddenContainer.firstElementChild;

            // Render canvas
            const canvas = await html2canvas(targetEl, {
                scale: 2.2,
                useCORS: true,
                backgroundColor: '#ffffff'
            });

            const imgData = canvas.toDataURL('image/png');
            const imgWidth = 140; // mm
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            const xPos = (210 - imgWidth) / 2;
            const yPos = 25;

            if (i > 0) {
                pdf.addPage();
            }

            pdf.addImage(imgData, 'PNG', xPos, yPos, imgWidth, imgHeight);
        }

        progressLabel.innerText = 'Finalizing PDF document...';
        pdf.save(`Payment_Slips_Batch_${new Date().toISOString().slice(0, 10)}.pdf`);
        showNotification(`Batch PDF with ${selected.length} slips downloaded!`, 'success');

    } catch (err) {
        console.error('Batch PDF generation error:', err);
        showNotification('Error creating batch PDF.', 'error');
    } finally {
        hiddenContainer.innerHTML = '';
        btn.disabled = false;
        btn.innerHTML = originalText;
        setTimeout(() => {
            progressWrapper.classList.add('hidden');
        }, 3000);
    }
}

// Batch Save to History DB
async function batchSaveReceipts() {
    const selected = parsedRows.filter(r => r.selected);
    if (selected.length === 0) {
        showNotification('No rows selected to save.', 'error');
        return;
    }

    const tpl = document.getElementById('bulkTemplateChoice').value;
    const batchId = 'BATCH-' + Date.now();

    try {
        const response = await fetch('api/bulk_save_receipts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                template_type: tpl,
                batch_id: batchId,
                rows: selected
            })
        });

        const res = await response.json();
        if (res.success) {
            showNotification(`Successfully saved ${res.count} receipts to history!`, 'success');
        } else {
            showNotification(res.message || 'Error saving batch receipts.', 'error');
        }
    } catch (err) {
        console.error(err);
        showNotification('Network error while saving batch receipts.', 'error');
    }
}

// Import All Beneficiaries into Contacts DB
async function importBatchAsContacts() {
    const selected = parsedRows.filter(r => r.selected);
    if (selected.length === 0) {
        showNotification('No rows to import as contacts.', 'error');
        return;
    }

    try {
        const contactsToImport = selected.map(r => ({
            name: r.name,
            account_number: r.account_no,
            ifsc_code: r.ifsc,
            bank_name: '',
            notes: `Imported from bulk slip file`
        }));

        const response = await fetch('api/bulk_import_contacts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ contacts: contactsToImport })
        });

        const res = await response.json();
        if (res.success) {
            showNotification(`Imported ${res.imported} contacts successfully!`, 'success');
        } else {
            showNotification(res.message || 'Error importing contacts.', 'error');
        }
    } catch (err) {
        console.error(err);
        showNotification('Network error during contact import.', 'error');
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

