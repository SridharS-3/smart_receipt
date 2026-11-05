// assets/js/contacts-manager.js - Single & Bulk Contact Management + Live Search

let parsedContactsToImport = [];

// ============================================
// SINGLE CONTACT ACTIONS
// ============================================

function openAddContactModal() {
    document.getElementById('contactModalTitle').innerText = 'Add Single Contact';
    document.getElementById('c_action').value = 'add';
    document.getElementById('c_id').value = '0';
    document.getElementById('contactForm').reset();
    document.getElementById('contactFormModal').classList.remove('hidden');
}

async function openEditContactModal(id) {
    document.getElementById('contactModalTitle').innerText = 'Edit Contact';
    document.getElementById('c_action').value = 'edit';
    document.getElementById('c_id').value = id;

    try {
        const res = await fetch(`api/contact_actions.php?action=get&id=${id}`);
        const data = await res.json();
        if (data.success && data.contact) {
            const c = data.contact;
            document.getElementById('c_name').value = c.name || '';
            document.getElementById('c_phone').value = c.phone || '';
            document.getElementById('c_email').value = c.email || '';
            document.getElementById('c_account').value = c.account_number || '';
            document.getElementById('c_ifsc').value = c.ifsc_code || '';
            document.getElementById('c_bank').value = c.bank_name || '';
            document.getElementById('c_notes').value = c.notes || '';
            document.getElementById('contactFormModal').classList.remove('hidden');
        } else {
            showNotification(data.message || 'Failed to fetch contact details', 'error');
        }
    } catch (e) {
        console.error(e);
        showNotification('Error fetching contact details', 'error');
    }
}

function closeContactModal() {
    document.getElementById('contactFormModal').classList.add('hidden');
}

async function submitContactForm(e) {
    e.preventDefault();
    const form = document.getElementById('contactForm');
    const formData = new FormData(form);
    const submitBtn = document.getElementById('c_submitBtn');
    const origText = submitBtn.innerText;

    submitBtn.innerText = 'Saving...';
    submitBtn.disabled = true;

    try {
        const response = await fetch('api/contact_actions.php', {
            method: 'POST',
            body: formData
        });
        const res = await response.json();

        if (res.success) {
            showNotification(res.message, 'success');
            closeContactModal();
            setTimeout(() => location.reload(), 700);
        } else {
            showNotification(res.message || 'Operation failed', 'error');
        }
    } catch (err) {
        console.error(err);
        showNotification('Network communication error', 'error');
    } finally {
        submitBtn.innerText = origText;
        submitBtn.disabled = false;
    }
}

async function deleteContact(id) {
    if (!confirm('Are you sure you want to delete this contact? This action cannot be undone.')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    try {
        const response = await fetch('api/contact_actions.php', {
            method: 'POST',
            body: formData
        });
        const res = await response.json();

        if (res.success) {
            showNotification('Contact deleted successfully', 'success');
            const row = document.getElementById(`contact-row-${id}`);
            if (row) {
                row.remove();
                // Update live search and count display
                const searchInput = document.getElementById('contactsSearchInput');
                if (searchInput) {
                    searchInput.dispatchEvent(new Event('input'));
                } else {
                    const countEl = document.getElementById('contactsCountDisplay');
                    if (countEl) {
                        const cur = parseInt(countEl.innerText) || 1;
                        countEl.innerText = Math.max(0, cur - 1);
                    }
                }
            }
        } else {
            showNotification(res.message || 'Error deleting contact', 'error');
        }
    } catch (e) {
        console.error(e);
        showNotification('Error deleting contact', 'error');
    }
}

// ============================================
// BULK CONTACT IMPORT (EXCEL / CSV)
// ============================================

function openBulkImportModal() {
    parsedContactsToImport = [];
    document.getElementById('contactImportPreviewWrapper').classList.add('hidden');
    document.getElementById('btnExecuteContactImport').disabled = true;
    document.getElementById('bulkImportModal').classList.remove('hidden');
}

function closeBulkImportModal() {
    document.getElementById('bulkImportModal').classList.add('hidden');
}

// ============================================
// INSTANT LIVE SEARCH
// ============================================

function initContactsLiveSearch() {
    const searchInput = document.getElementById('contactsSearchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    const countDisplay = document.getElementById('contactsCountDisplay');
    const noResultsRow = document.getElementById('noLiveSearchResultsRow');
    const querySpan = document.getElementById('liveSearchQueryText');
    const table = document.getElementById('contactsTable');

    if (!searchInput || !table) return;

    function applySearch() {
        const query = (searchInput.value || '').trim().toLowerCase();
        const rows = table.querySelectorAll('tbody tr.contact-data-row');

        // Toggle clear button
        if (clearBtn) {
            if (query.length > 0) {
                clearBtn.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
            }
        }

        let visibleCount = 0;
        rows.forEach(row => {
            const haystack = (row.getAttribute('data-search') || row.innerText || '').toLowerCase();
            if (!query || haystack.includes(query)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update count display
        if (countDisplay) {
            countDisplay.innerText = visibleCount;
        }

        // Toggle "No contacts match" placeholder
        if (noResultsRow) {
            if (visibleCount === 0 && rows.length > 0) {
                noResultsRow.classList.remove('hidden');
                if (querySpan) {
                    querySpan.innerText = searchInput.value;
                }
            } else {
                noResultsRow.classList.add('hidden');
            }
        }
    }

    searchInput.addEventListener('input', applySearch);
    searchInput.addEventListener('keyup', (e) => {
        if (e.key === 'Escape') {
            clearContactsSearch();
        } else {
            applySearch();
        }
    });

    // If already has query
    if (searchInput.value.trim().length > 0) {
        applySearch();
    }
}

function clearContactsSearch() {
    const searchInput = document.getElementById('contactsSearchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    if (searchInput) {
        searchInput.value = '';
    }
    if (clearBtn) clearBtn.classList.add('hidden');
    if (searchInput) {
        searchInput.dispatchEvent(new Event('input'));
        searchInput.focus();
    }

    // Clean URL query param without reload
    if (window.location.search.includes('q=')) {
        const url = new URL(window.location.href);
        url.searchParams.delete('q');
        window.history.replaceState({}, document.title, url.pathname + (url.search ? url.search : ''));
    }
}

// Contact Drag and Drop Setup & Page Init
document.addEventListener('DOMContentLoaded', () => {
    const cDrop = document.getElementById('contactDropZone');
    const cInput = document.getElementById('contactFileInput');

    if (cDrop && cInput) {
        ['dragenter', 'dragover'].forEach(name => {
            cDrop.addEventListener(name, (e) => {
                e.preventDefault();
                cDrop.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(name => {
            cDrop.addEventListener(name, (e) => {
                e.preventDefault();
                cDrop.classList.remove('dragover');
            });
        });

        cDrop.addEventListener('drop', (e) => {
            if (e.dataTransfer.files.length > 0) {
                parseContactFile(e.dataTransfer.files[0]);
            }
        });

        cInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                parseContactFile(e.target.files[0]);
            }
        });
    }

    // Initialize instant live search
    initContactsLiveSearch();

    // Auto open add modal if URL has ?action=add
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'add') {
        openAddContactModal();
    } else if (urlParams.get('action') === 'import') {
        openBulkImportModal();
    }
});

function parseContactFile(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const sheet = workbook.Sheets[workbook.SheetNames[0]];
            const rawJson = XLSX.utils.sheet_to_json(sheet, { defval: '' });

            if (!rawJson || rawJson.length === 0) {
                showNotification('The uploaded contact file contains no rows.', 'error');
                return;
            }

            parsedContactsToImport = [];

            rawJson.forEach(row => {
                const norm = {};
                for (const [k, v] of Object.entries(row)) {
                    norm[k.trim().toLowerCase()] = String(v).trim();
                }

                const name = norm['name'] || norm['beneficiary name'] || norm['beneficiary'] || norm['full name'];
                const phone = norm['mobile'] || norm['mobile number'] || norm['phone'] || norm['contact no'] || norm['phone number'] || norm['mobile no'] || '';
                const email = norm['email'] || norm['email address'] || '';
                const account_number = norm['account number'] || norm['account no'] || norm['account no.'] || norm['account'] || norm['a/c no'];
                const ifsc_code = (norm['ifsc code'] || norm['ifsc'] || '').toUpperCase();
                const bank_name = norm['bank name'] || norm['bank'] || '';
                const notes = norm['notes'] || norm['description'] || 'Bulk imported';

                if (name && account_number) {
                    parsedContactsToImport.push({
                        name,
                        phone,
                        email,
                        account_number,
                        ifsc_code,
                        bank_name,
                        notes
                    });
                }
            });

            if (parsedContactsToImport.length === 0) {
                showNotification('Could not find Name and Account Number columns in the sheet.', 'error');
                return;
            }

            // Render Preview Table
            const tbody = document.getElementById('contactImportPreviewBody');
            tbody.innerHTML = '';
            parsedContactsToImport.forEach(c => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50';
                tr.innerHTML = `
                    <td class="py-2 px-3 font-semibold text-slate-800">${escapeHtml(c.name)}</td>
                    <td class="py-2 px-3 font-mono text-slate-600">${escapeHtml(c.phone || '—')}</td>
                    <td class="py-2 px-3 text-slate-500">${escapeHtml(c.email || '—')}</td>
                    <td class="py-2 px-3 font-mono text-slate-600">${escapeHtml(c.account_number)}</td>
                    <td class="py-2 px-3 font-mono text-slate-500 uppercase">${escapeHtml(c.ifsc_code || '—')}</td>
                    <td class="py-2 px-3 text-slate-600">${escapeHtml(c.bank_name || '—')}</td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('importCountBadge').innerText = `${parsedContactsToImport.length} contacts found`;
            document.getElementById('contactImportPreviewWrapper').classList.remove('hidden');
            document.getElementById('btnExecuteContactImport').disabled = false;
            showNotification(`Parsed ${parsedContactsToImport.length} contacts from sheet!`, 'success');

        } catch (err) {
            console.error('Contact file parsing error:', err);
            showNotification('Failed to read Excel/CSV file.', 'error');
        }
    };
    reader.readAsArrayBuffer(file);
}

async function executeContactImport() {
    if (parsedContactsToImport.length === 0) return;

    const btn = document.getElementById('btnExecuteContactImport');
    const orig = btn.innerText;
    btn.innerText = 'Importing...';
    btn.disabled = true;

    try {
        const response = await fetch('api/bulk_import_contacts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ contacts: parsedContactsToImport })
        });
        const res = await response.json();

        if (res.success) {
            showNotification(`Successfully imported ${res.imported} contacts!`, 'success');
            closeBulkImportModal();
            setTimeout(() => location.reload(), 800);
        } else {
            showNotification(res.message || 'Error during contact import', 'error');
        }
    } catch (err) {
        console.error(err);
        showNotification('Network error while importing contacts', 'error');
    } finally {
        btn.innerText = orig;
        btn.disabled = false;
    }
}

// Export Contacts to CSV
function exportContactsCSV() {
    const table = document.getElementById('contactsTable');
    if (!table) {
        showNotification('No contacts to export.', 'error');
        return;
    }

    const rows = table.querySelectorAll('tbody tr.contact-data-row');
    if (rows.length === 0) {
        showNotification('No contacts to export.', 'error');
        return;
    }

    const csvData = [];
    csvData.push(['Beneficiary Name', 'Mobile Number', 'Email Address', 'Account Number', 'IFSC Code', 'Bank Name', 'Notes']);

    rows.forEach(tr => {
        // If visible
        if (tr.style.display !== 'none') {
            const cols = tr.querySelectorAll('td');
            if (cols.length >= 6) {
                const name = cols[0].querySelector('.font-bold').innerText.trim();
                const emailEl = cols[0].querySelector('span');
                const email = emailEl ? emailEl.innerText.trim() : '';
                const mobile = cols[1].innerText.trim();
                const account = cols[2].innerText.trim();
                const ifsc = cols[3].innerText.trim();
                const bank = cols[4].innerText.trim();
                const notes = cols[5].innerText.trim();
                csvData.push([name, mobile, email, account, ifsc, bank, notes]);
            }
        }
    });

    const worksheet = XLSX.utils.aoa_to_sheet(csvData);
    const csvOutput = XLSX.utils.sheet_to_csv(worksheet);
    const blob = new Blob([csvOutput], { type: 'text/csv;charset=utf-8;' });
    saveAs(blob, `SmartReceipt_Contacts_${new Date().toISOString().slice(0, 10)}.csv`);
    showNotification('Contacts exported to CSV!', 'success');
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
