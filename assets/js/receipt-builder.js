// assets/js/receipt-builder.js - Live Builder & Exporter with Save First Workflow

let currentTemplate = 'template3';
let isReceiptSaved = false;

document.addEventListener('DOMContentLoaded', () => {
    // Attach real-time input event listeners
    const fields = ['f_status', 'f_amount', 'f_type', 'f_txid', 'f_banking_name', 'f_account_no', 'f_ifsc', 'f_datetime'];
    fields.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', () => {
                syncLivePreview();
                markUnsaved();
            });
            el.addEventListener('change', () => {
                syncLivePreview();
                markUnsaved();
            });
        }
    });

    // Initial sync
    syncLivePreview();
});

function markUnsaved() {
    isReceiptSaved = false;
    const saveBtn = document.getElementById('btnSaveReceipt');
    if (saveBtn) {
        saveBtn.className = 'w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold rounded-xl text-sm shadow-md transition flex items-center justify-center space-x-2';
        saveBtn.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> <span id="btnSaveReceiptText">Save Receipt Record</span>`;
    }
}

function switchTemplate(tpl) {
    currentTemplate = tpl;
    const btn1 = document.getElementById('selectTpl1');
    const btn2 = document.getElementById('selectTpl2');
    const btn3 = document.getElementById('selectTpl3');
    const p1 = document.getElementById('previewTemplate1');
    const p2 = document.getElementById('previewTemplate2');
    const p3 = document.getElementById('previewTemplate3');

    // Reset all buttons to inactive state
    [btn1, btn2, btn3].forEach(b => {
        if (!b) return;
        b.className = 'template-toggle-btn px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center space-x-2 text-slate-600 hover:text-slate-900';
        const dot = b.querySelector('span:first-child');
        if (dot) dot.className = 'w-2 h-2 rounded-full bg-slate-300 inline-block';
    });

    // Hide all preview templates
    if (p1) p1.classList.add('hidden');
    if (p2) p2.classList.add('hidden');
    if (p3) p3.classList.add('hidden');

    if (tpl === 'template1') {
        if (btn1) {
            btn1.className = 'template-toggle-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 bg-white text-sky-700 shadow-sm border border-sky-200/80';
            const dot = btn1.querySelector('span:first-child');
            if (dot) dot.className = 'w-2 h-2 rounded-full bg-sky-500 inline-block';
        }
        if (p1) p1.classList.remove('hidden');
    } else if (tpl === 'template3') {
        if (btn3) {
            btn3.className = 'template-toggle-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 bg-white text-emerald-700 shadow-sm border border-emerald-200/80';
            const dot = btn3.querySelector('span:first-child');
            if (dot) dot.className = 'w-2 h-2 rounded-full bg-emerald-500 inline-block';
        }
        if (p3) p3.classList.remove('hidden');
    } else {
        // default template2
        if (btn2) {
            btn2.className = 'template-toggle-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 bg-white text-amber-700 shadow-sm border border-amber-200/80';
            const dot = btn2.querySelector('span:first-child');
            if (dot) dot.className = 'w-2 h-2 rounded-full bg-amber-500 inline-block';
        }
        if (p2) p2.classList.remove('hidden');
    }
    syncLivePreview();
}

function syncLivePreview() {
    const statusVal = document.getElementById('f_status').value || 'Successful';
    const amountVal = parseFloat(document.getElementById('f_amount').value) || 0;
    const typeVal = document.getElementById('f_type').value || 'IMPS';
    const txidVal = document.getElementById('f_txid').value.trim() || '—';
    const bankingNameVal = document.getElementById('f_banking_name').value.trim() || '—';
    const accountNoVal = document.getElementById('f_account_no').value.trim() || '—';
    const ifscRaw = document.getElementById('f_ifsc').value.trim();
    const ifscVal = ifscRaw ? ifscRaw.toUpperCase() : '—';
    const datetimeInput = document.getElementById('f_datetime');
    const datetimeVal = datetimeInput ? (datetimeInput.value.trim() || formatCurrentDateTime()) : formatCurrentDateTime();

    // Format amounts
    const formattedAmountT1 = '₹ ' + (amountVal ? amountVal.toLocaleString('en-IN') : '0.0');
    const formattedAmountT2 = 'INR ₹ ' + (amountVal ? amountVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00');
    const formattedAmountT3 = '₹' + (amountVal ? amountVal.toLocaleString('en-IN') : '0.00');

    // === Sync Template 1 ===
    const p1_status = document.getElementById('p1_status');
    if (p1_status) {
        p1_status.innerText = statusVal;
        if (statusVal.toLowerCase().includes('fail')) {
            p1_status.className = 'receipt-t1-status-failed';
        } else if (statusVal.toLowerCase().includes('pend')) {
            p1_status.className = 'receipt-t1-status-pending';
        } else {
            p1_status.className = 'receipt-t1-status-success';
        }
    }

    const p1_amt = document.getElementById('p1_amount');
    if (p1_amt) p1_amt.innerText = formattedAmountT1;
    const p1_type = document.getElementById('p1_type');
    if (p1_type) p1_type.innerText = `Send to Account (${typeVal})`;
    const p1_tx = document.getElementById('p1_txid');
    if (p1_tx) p1_tx.innerText = txidVal;
    const p1_bn = document.getElementById('p1_banking_name');
    if (p1_bn) p1_bn.innerText = bankingNameVal;
    const p1_acc = document.getElementById('p1_account_no');
    if (p1_acc) p1_acc.innerText = accountNoVal;
    const p1_dt = document.getElementById('p1_datetime');
    if (p1_dt) p1_dt.innerText = datetimeVal;

    // === Sync Template 2 ===
    const p2_status = document.getElementById('p2_status');
    if (p2_status) {
        const cleanStatus = statusVal.trim();
        p2_status.innerText = cleanStatus.toLowerCase().startsWith('payment') ? cleanStatus : ('Payment ' + cleanStatus);
    }
    const p2_amt = document.getElementById('p2_amount');
    if (p2_amt) p2_amt.innerText = formattedAmountT2;
    const p2_bn = document.getElementById('p2_banking_name');
    if (p2_bn) p2_bn.innerText = bankingNameVal;
    const p2_acc = document.getElementById('p2_account_no');
    if (p2_acc) p2_acc.innerText = accountNoVal;
    const p2_ifsc = document.getElementById('p2_ifsc');
    if (p2_ifsc) p2_ifsc.innerText = ifscVal;
    const p2_tx = document.getElementById('p2_txid');
    if (p2_tx) p2_tx.innerText = txidVal;
    const p2_dt = document.getElementById('p2_datetime');
    if (p2_dt) p2_dt.innerText = datetimeVal;

    // === Sync Template 3 (Green Bank Transfer Card) ===
    let t3Date = datetimeVal;
    const dObj = new Date(datetimeVal);
    if (!isNaN(dObj.getTime())) {
        t3Date = dObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }
    const p3_amount = document.getElementById('p3_amount');
    if (p3_amount) p3_amount.innerText = formattedAmountT3;
    const p3_total = document.getElementById('p3_total_amount');
    if (p3_total) p3_total.innerText = formattedAmountT3;
    const p3_utr = document.getElementById('p3_utr');
    if (p3_utr) p3_utr.innerText = txidVal;
    const p3_bname = document.getElementById('p3_beneficiary_name');
    if (p3_bname) p3_bname.innerText = bankingNameVal;
    const p3_mode = document.getElementById('p3_mode');
    if (p3_mode) p3_mode.innerText = typeVal;
    const p3_date = document.getElementById('p3_date');
    if (p3_date) p3_date.innerText = t3Date;
    const p3_status = document.getElementById('p3_status_text');
    if (p3_status) {
        const cleanStatus = statusVal.trim();
        p3_status.innerText = cleanStatus.toLowerCase().startsWith('payment') ? cleanStatus : ('Payment ' + cleanStatus);
    }
}

// Make New Payment: refreshes transaction fields while preserving "Sent To" (beneficiary) details
function prepareNewPayment() {
    // Clear amount
    const amtEl = document.getElementById('f_amount');
    if (amtEl) amtEl.value = '';

    // Clear transaction ID / UTR so user can enter new one
    const txidEl = document.getElementById('f_txid');
    if (txidEl) txidEl.value = '';

    // Reset status to Successful
    const stEl = document.getElementById('f_status');
    if (stEl) stEl.value = 'Successful';

    // Refresh date & time to current
    const dtEl = document.getElementById('f_datetime');
    if (dtEl) dtEl.value = formatCurrentDateTime();

    // Mark as unsaved for the new payment record
    markUnsaved();

    // Re-sync live preview
    syncLivePreview();

    // Smoothly focus on amount field
    if (amtEl) {
        amtEl.focus();
        amtEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    const bname = document.getElementById('f_banking_name').value.trim();
    if (bname && bname !== '—') {
        showNotification(`Ready for new payment to ${bname}! Beneficiary details preserved.`, 'success');
    } else {
        showNotification('Ready for new payment! Enter amount & details.', 'info');
    }
}

function formatCurrentDateTime() {
    const d = new Date();
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ', ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
}

function applySelectedContact(selectEl) {
    if (!selectEl.value) return;
    try {
        const contact = JSON.parse(selectEl.value);
        if (contact.name) document.getElementById('f_banking_name').value = contact.name;
        if (contact.account_number) document.getElementById('f_account_no').value = contact.account_number;
        if (contact.ifsc_code) document.getElementById('f_ifsc').value = contact.ifsc_code;
        markUnsaved();
        syncLivePreview();
        showNotification(`Applied contact: ${contact.name}`, 'info');
    } catch (e) {
        console.error(e);
    }
}

function clearReceiptForm() {
    document.getElementById('f_status').value = 'Successful';
    document.getElementById('f_amount').value = '';
    document.getElementById('f_type').value = 'IMPS';
    document.getElementById('f_txid').value = '';
    document.getElementById('f_banking_name').value = '';
    document.getElementById('f_account_no').value = '';
    document.getElementById('f_ifsc').value = '';
    if (document.getElementById('f_datetime')) {
        document.getElementById('f_datetime').value = formatCurrentDateTime();
    }
    const cs = document.getElementById('contactSelector');
    if (cs) cs.value = '';
    markUnsaved();
    syncLivePreview();
    showNotification('Form cleared', 'info');
}

// Manual Save Receipt Record
async function manualSaveReceipt() {
    const amountVal = document.getElementById('f_amount').value.trim();
    const txidVal = document.getElementById('f_txid').value.trim();
    const bankingNameVal = document.getElementById('f_banking_name').value.trim();

    if (!amountVal || parseFloat(amountVal) <= 0) {
        showNotification('Please enter a valid Transaction Amount.', 'error');
        document.getElementById('f_amount').focus();
        return false;
    }

    if (!txidVal) {
        showNotification('Please enter a Transaction ID / UTR.', 'error');
        document.getElementById('f_txid').focus();
        return false;
    }

    if (!bankingNameVal) {
        showNotification('Please enter the Banking / Beneficiary Name.', 'error');
        document.getElementById('f_banking_name').focus();
        return false;
    }

    const saveBtn = document.getElementById('btnSaveReceipt');
    const origHTML = saveBtn ? saveBtn.innerHTML : '';
    if (saveBtn) {
        saveBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> <span>Saving Record...</span>`;
        saveBtn.disabled = true;
    }

    const data = {
        template_type: currentTemplate,
        status: document.getElementById('f_status').value,
        amount: amountVal,
        transaction_type: document.getElementById('f_type').value,
        transaction_id: txidVal,
        banking_name: bankingNameVal,
        account_number: document.getElementById('f_account_no').value.trim(),
        ifsc_code: document.getElementById('f_ifsc').value.trim(),
        payment_date: document.getElementById('f_datetime') ? document.getElementById('f_datetime').value.trim() : '',
        generated_type: 'single'
    };

    try {
        const response = await fetch('api/save_receipt.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const res = await response.json();

        if (res.success) {
            isReceiptSaved = true;
            if (saveBtn) {
                saveBtn.className = 'w-full py-3 px-4 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl text-sm shadow-md transition flex items-center justify-center space-x-2';
                saveBtn.innerHTML = `<i class="fa-solid fa-circle-check text-emerald-400"></i> <span>Receipt Record Saved ✓</span>`;
            }
            showNotification('Receipt saved successfully! You can now download image/PDF or print.', 'success');
            return true;
        } else {
            showNotification(res.message || 'Failed to save receipt record', 'error');
            if (saveBtn) saveBtn.innerHTML = origHTML;
            return false;
        }
    } catch (err) {
        console.error(err);
        showNotification('Network error saving receipt', 'error');
        if (saveBtn) saveBtn.innerHTML = origHTML;
        return false;
    } finally {
        if (saveBtn) saveBtn.disabled = false;
    }
}

function getActiveReceiptTarget() {
    if (currentTemplate === 'template1') return document.getElementById('previewTemplate1');
    if (currentTemplate === 'template3') return document.getElementById('previewTemplate3');
    return document.getElementById('previewTemplate2');
}

// Download Active Template as PNG
async function downloadReceiptPNG() {
    if (!isReceiptSaved) {
        const saved = await manualSaveReceipt();
        if (!saved) return;
    }

    const targetEl = getActiveReceiptTarget();
    const txid = document.getElementById('f_txid').value.trim() || 'slip';
    const btn = document.getElementById('btnDownloadPng');
    const originalText = btn.innerHTML;

    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> <span>Exporting...</span>`;
    btn.disabled = true;

    try {
        const canvas = await html2canvas(targetEl, {
            scale: 3, // High DPI clarity
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false
        });

        canvas.toBlob((blob) => {
            const fileName = `Receipt_${txid}_${new Date().toISOString().slice(0, 10)}.png`;
            saveAs(blob, fileName);
            showNotification(`Image receipt downloaded!`, 'success');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    } catch (err) {
        console.error('Error exporting PNG:', err);
        showNotification('Failed to generate PNG image.', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// Download Active Template as PDF
async function downloadReceiptPDF() {
    if (!isReceiptSaved) {
        const saved = await manualSaveReceipt();
        if (!saved) return;
    }

    const targetEl = getActiveReceiptTarget();
    const txid = document.getElementById('f_txid').value.trim() || 'slip';
    const btn = document.getElementById('btnDownloadPdf');
    const originalText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> <span>Rendering PDF...</span>`;
        btn.disabled = true;
    }

    try {
        const canvas = await html2canvas(targetEl, {
            scale: 2.5,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false
        });

        const imgData = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');

        const imgWidth = 140; // mm
        const pageHeight = 295;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        const xPos = (210 - imgWidth) / 2;
        const yPos = 25;

        pdf.addImage(imgData, 'PNG', xPos, yPos, imgWidth, imgHeight);
        pdf.save(`Receipt_${txid}.pdf`);
        showNotification('PDF receipt downloaded!', 'success');
    } catch (err) {
        console.error(err);
        showNotification('Failed to create PDF.', 'error');
    } finally {
        if (btn) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
}

// Print Slip with save confirmation
async function printReceiptSlip() {
    if (!isReceiptSaved) {
        const saved = await manualSaveReceipt();
        if (!saved) return;
    }
    const targetEl = getActiveReceiptTarget();
    const printArea = document.getElementById('printableReceiptArea');
    if (printArea && targetEl) {
        printArea.innerHTML = targetEl.outerHTML;
        printArea.classList.remove('hidden');
    }
    window.print();
}
