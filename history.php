<?php
// history.php - Receipt History & Re-download
require_once __DIR__ . '/config/db.php';
require_auth();

define('PAGE_TITLE', 'Receipt History');

// Handle single delete if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_receipt_id'])) {
    $del_id = (int)$_POST['delete_receipt_id'];
    $stmt = $pdo->prepare("DELETE FROM receipts WHERE id = ?");
    $stmt->execute([$del_id]);
    header("Location: history.php?deleted=1");
    exit;
}

$search = trim($_GET['q'] ?? '');
$filter_mode = trim($_GET['mode'] ?? '');
$filter_payment_type = trim($_GET['payment_type'] ?? '');
$query = "SELECT * FROM receipts WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (banking_name LIKE ? OR account_number LIKE ? OR transaction_id LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($filter_mode) && in_array($filter_mode, ['single', 'bulk'])) {
    $query .= " AND generated_type = ?";
    $params[] = $filter_mode;
}

if (!empty($filter_payment_type) && in_array($filter_payment_type, ['IMPS', 'NEFT', 'RTGS', 'UPI'])) {
    $query .= " AND transaction_type = ?";
    $params[] = $filter_payment_type;
}

$query .= " ORDER BY payment_date DESC LIMIT 100";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$receipts = $stmt->fetchAll();

include_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">

    <!-- Header & Search Bar -->
    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">Receipt History</h2>
            <p class="text-xs text-slate-500 mt-1">Review, re-print, and re-download generated payment slips</p>
        </div>

        <form method="GET" action="history.php" class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, ref, or A/C..."
                    class="pl-8 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 w-52 sm:w-64">
            </div>

            <select name="payment_type" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-700 font-medium focus:outline-none">
                <option value="">All Payment Types</option>
                <option value="IMPS" <?= $filter_payment_type === 'IMPS' ? 'selected' : '' ?>>IMPS</option>
                <option value="NEFT" <?= $filter_payment_type === 'NEFT' ? 'selected' : '' ?>>NEFT</option>
                <option value="RTGS" <?= $filter_payment_type === 'RTGS' ? 'selected' : '' ?>>RTGS</option>
                <option value="UPI" <?= $filter_payment_type === 'UPI' ? 'selected' : '' ?>>UPI</option>
            </select>

            <select name="mode" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-700 font-medium focus:outline-none">
                <option value="">All Sources</option>
                <option value="single" <?= $filter_mode === 'single' ? 'selected' : '' ?>>Single Receipts</option>
                <option value="bulk" <?= $filter_mode === 'bulk' ? 'selected' : '' ?>>Bulk Uploads</option>
            </select>

            <?php if (!empty($search) || !empty($filter_mode) || !empty($filter_payment_type)): ?>
                <a href="history.php" class="p-2 text-slate-400 hover:text-slate-700 text-xs">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Receipts Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <?php if (empty($receipts)): ?>
            <div class="p-12 text-center">
                <div class="w-14 h-14 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center mx-auto text-2xl mb-3 shadow-sm">
                    <i class="fa-regular fa-folder-open"></i>
                </div>
                <h4 class="text-base font-bold text-slate-800">No receipts found</h4>
                <p class="text-xs text-slate-400 mt-1">Generate receipts using either Single or Bulk Generator to view them here.</p>
                <div class="mt-4 flex justify-center space-x-3">
                    <a href="receipt-generator.php" class="px-4 py-2 bg-sky-600 text-white font-bold text-xs rounded-xl shadow">
                        Build a Receipt
                    </a>
                    <a href="bulk-receipts.php" class="px-4 py-2 bg-slate-800 text-white font-bold text-xs rounded-xl shadow">
                        Bulk Slip Generator
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-4">Beneficiary</th>
                            <th class="py-3.5 px-4">Account Number</th>
                            <th class="py-3.5 px-4">Amount</th>
                            <th class="py-3.5 px-4">UTR / Transaction ID</th>
                            <th class="py-3.5 px-4">Status &amp; Type</th>
                            <th class="py-3.5 px-4">Date &amp; Source</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($receipts as $r): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($r['banking_name']) ?></div>
                                    <div class="text-[11px] text-slate-400 font-mono"><?= htmlspecialchars($r['ifsc_code'] ?? '—') ?></div>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-semibold text-slate-700">
                                    <?= htmlspecialchars($r['account_number']) ?>
                                </td>
                                <td class="py-3.5 px-4 font-extrabold text-slate-900 text-sm">
                                    ₹ <?= number_format($r['amount'], 2) ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-600">
                                    <?= htmlspecialchars($r['transaction_id']) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold <?= $r['status'] === 'Successful' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">
                                        <?= htmlspecialchars($r['status']) ?>
                                    </span>
                                    <div class="text-[11px] text-slate-400 mt-0.5"><?= htmlspecialchars($r['transaction_type']) ?></div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500">
                                    <div><?= date('d M Y, h:i A', strtotime($r['payment_date'])) ?></div>
                                    <span class="inline-block mt-0.5 text-[10px] uppercase font-bold text-slate-400">
                                        <?= $r['generated_type'] === 'bulk' ? '<i class="fa-solid fa-layer-group text-sky-500 mr-1"></i>Bulk' : '<i class="fa-solid fa-file text-slate-400 mr-1"></i>Single' ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center space-x-1.5">
                                        <!-- View / Re-download Modal -->
                                        <button type="button" onclick='openReceiptModal(<?= json_encode($r) ?>)' 
                                            class="px-2.5 py-1.5 bg-sky-50 hover:bg-sky-600 text-sky-700 hover:text-white rounded-lg border border-sky-200 text-xs font-semibold transition" title="View & Download">
                                            <i class="fa-solid fa-eye mr-1"></i> View
                                        </button>
                                        <!-- Edit in Generator -->
                                        <a href="receipt-generator.php?recreate_id=<?= $r['id'] ?>" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="Clone / Edit in Generator">
                                            <i class="fa-solid fa-sliders"></i>
                                        </a>
                                        <!-- Delete -->
                                        <form method="POST" action="history.php" onsubmit="return confirm('Delete this receipt record permanently?');" class="inline">
                                            <input type="hidden" name="delete_receipt_id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Delete Receipt">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- View Receipt Modal -->
<div id="historyViewModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl relative border border-slate-100">
        <button type="button" onclick="closeHistoryModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-lg">
            <i class="fa-solid fa-times"></i>
        </button>
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <h3 class="text-base font-bold text-slate-900">Payment Receipt Preview</h3>
            <div class="flex items-center space-x-1">
                <button type="button" id="hModalTpl1" onclick="switchHistoryModalTemplate('template1')" class="px-2.5 py-1 text-[11px] font-bold rounded-lg border bg-sky-50 text-sky-700 border-sky-300">Style 1</button>
                <button type="button" id="hModalTpl2" onclick="switchHistoryModalTemplate('template2')" class="px-2.5 py-1 text-[11px] font-bold rounded-lg border bg-white text-slate-600 border-slate-200">Style 2</button>
                <button type="button" id="hModalTpl3" onclick="switchHistoryModalTemplate('template3')" class="px-2.5 py-1 text-[11px] font-bold rounded-lg border bg-white text-slate-600 border-slate-200">Style 3</button>
            </div>
        </div>

        <div id="historyReceiptCardContainer" class="p-4 bg-slate-50 rounded-xl flex justify-center overflow-x-auto">
            <!-- Dynamic Receipt Insertion -->
        </div>

        <div class="mt-5 flex flex-wrap justify-end gap-2">
            <button type="button" onclick="closeHistoryModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Close</button>
            <button type="button" id="btnDownloadHistoryPng" class="px-4 py-2 text-xs font-bold text-white bg-[#0088cc] hover:bg-[#0077b5] rounded-xl shadow-sm flex items-center space-x-1.5">
                <i class="fa-solid fa-image"></i>
                <span>Download Image</span>
            </button>
            <button type="button" id="btnDownloadHistoryPdf" class="px-4 py-2 text-xs font-bold text-white bg-slate-800 hover:bg-slate-900 rounded-xl shadow-sm flex items-center space-x-1.5">
                <i class="fa-solid fa-file-pdf text-rose-400"></i>
                <span>Download PDF</span>
            </button>
        </div>
    </div>
</div>

<script>
let activeModalReceipt = null;
let activeModalTemplate = 'template2';

function openReceiptModal(r) {
    activeModalReceipt = r;
    activeModalTemplate = r.template_type || 'template2';
    updateHistoryModalView();
    document.getElementById('historyViewModal').classList.remove('hidden');
}

function closeHistoryModal() {
    document.getElementById('historyViewModal').classList.add('hidden');
}

function switchHistoryModalTemplate(tpl) {
    activeModalTemplate = tpl;
    updateHistoryModalView();
}

function updateHistoryModalView() {
    if (!activeModalReceipt) return;
    const r = activeModalReceipt;
    const tpl = activeModalTemplate;

    const b1 = document.getElementById('hModalTpl1');
    const b2 = document.getElementById('hModalTpl2');
    const b3 = document.getElementById('hModalTpl3');
    [b1, b2, b3].forEach(b => {
        if (!b) return;
        b.className = 'px-2.5 py-1 text-[11px] font-bold rounded-lg border bg-white text-slate-600 border-slate-200';
    });
    if (tpl === 'template1') {
        if (b1) b1.className = 'px-2.5 py-1 text-[11px] font-bold rounded-lg border bg-sky-50 text-sky-700 border-sky-300';
    } else if (tpl === 'template3') {
        if (b3) b3.className = 'px-2.5 py-1 text-[11px] font-bold rounded-lg border bg-emerald-50 text-emerald-700 border-emerald-300';
    } else {
        if (b2) b2.className = 'px-2.5 py-1 text-[11px] font-bold rounded-lg border bg-amber-50 text-amber-700 border-amber-300';
    }

    const container = document.getElementById('historyReceiptCardContainer');
    const amt = parseFloat(r.amount) || 0;
    const formattedAmountT1 = '₹ ' + amt.toLocaleString('en-IN');
    const formattedAmountT2 = 'INR ₹ ' + amt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const formattedAmountT3 = '₹' + amt.toLocaleString('en-IN');
    const formattedDate = r.payment_date ? (new Date(r.payment_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ', ' + new Date(r.payment_date).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })) : '';
    let t3Date = formattedDate;
    if (r.payment_date) {
        const dObj = new Date(r.payment_date);
        if (!isNaN(dObj.getTime())) {
            t3Date = dObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        }
    }

    const statusClean = String(r.status || 'Successful').trim();
    const statusDisplay = statusClean.toLowerCase().startsWith('payment') ? statusClean : ('Payment ' + statusClean);

    if (tpl === 'template1') {
        container.innerHTML = `
            <div class="receipt-template-1" id="historyCaptureCard">
                <div class="receipt-t1-header" style="background-color: #0284c7 !important; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;">
                    <h3 style="color: #ffffff !important; margin: 0; font-weight: 700;">Transaction Receipt</h3>
                </div>
                <div class="receipt-t1-body">
                    <div class="receipt-t1-row">
                        <span class="receipt-t1-label">Status</span>
                        <span class="receipt-t1-status-success">${r.status}</span>
                    </div>
                    <div class="receipt-t1-row">
                        <span class="receipt-t1-label">Transaction Amount</span>
                        <span class="receipt-t1-amount">${formattedAmountT1}</span>
                    </div>
                    <div class="receipt-t1-row">
                        <span class="receipt-t1-label">Payment Type</span>
                        <span class="receipt-t1-value">Send to Account (${r.transaction_type})</span>
                    </div>
                    <div class="receipt-t1-divider"></div>
                    <div class="receipt-t1-row">
                        <span class="receipt-t1-label">Transaction ID</span>
                        <span class="receipt-t1-value font-mono">${r.transaction_id}</span>
                    </div>
                    <div class="receipt-t1-section-title">Sent To</div>
                    <div class="receipt-t1-row">
                        <span class="receipt-t1-label">Banking Name</span>
                        <span class="receipt-t1-value">${r.banking_name}</span>
                    </div>
                    <div class="receipt-t1-row">
                        <span class="receipt-t1-label">Account Number</span>
                        <span class="receipt-t1-value font-mono">${r.account_number}</span>
                    </div>
                    <div class="receipt-t1-row" style="margin-bottom: 0;">
                        <span class="receipt-t1-label">Date &amp; Time</span>
                        <span class="receipt-t1-value font-mono">${formattedDate}</span>
                    </div>
                </div>
            </div>
        `;
    } else if (tpl === 'template3') {
        container.innerHTML = `
            <div class="receipt-template-3" id="historyCaptureCard">
                <div class="receipt-t3-badge-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60" fill="none">
                        <rect x="3" y="3" width="54" height="54" rx="16" fill="#ffffff" stroke="#00b65a" stroke-width="4" />
                        <polyline points="18 29.5 26 37.5 42 21.5" fill="none" stroke="#00b65a" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="receipt-t3-amount-text">${formattedAmountT3}</div>
                <div class="receipt-t3-status-sub">${statusDisplay}</div>
                <div class="receipt-t3-details-table">
                    <div class="receipt-t3-row">
                        <span class="receipt-t3-label">TOTAL AMOUNT</span>
                        <span class="receipt-t3-val">${formattedAmountT3}</span>
                    </div>
                    <div class="receipt-t3-row">
                        <span class="receipt-t3-label">UTR NO</span>
                        <span class="receipt-t3-val font-mono">${r.transaction_id}</span>
                    </div>
                    <div class="receipt-t3-row">
                        <span class="receipt-t3-label">BENEFICIARY NAME</span>
                        <span class="receipt-t3-val">${r.banking_name}</span>
                    </div>
                    <div class="receipt-t3-row">
                        <span class="receipt-t3-label">MODE OF TRANSFER</span>
                        <span class="receipt-t3-val">${r.transaction_type}</span>
                    </div>
                    <div class="receipt-t3-row" style="margin-bottom: 0;">
                        <span class="receipt-t3-label">TRANSACTION DATE</span>
                        <span class="receipt-t3-val font-mono">${t3Date}</span>
                    </div>
                </div>
                <button type="button" onclick="window.location.href='receipt-generator.php'" class="receipt-t3-btn-primary hover:opacity-95 transition cursor-pointer">Make New Payment</button>
                <button type="button" onclick="window.location.href='dashboard.php'" class="receipt-t3-btn-secondary hover:bg-slate-50 transition cursor-pointer">Go Back To Dashboard</button>
            </div>
        `;
    } else {
        container.innerHTML = `
            <div class="receipt-template-2" id="historyCaptureCard">
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
                    <div class="receipt-t2-status-text">${statusDisplay}</div>
                    <div class="receipt-t2-amount-label">AMOUNT</div>
                    <div class="receipt-t2-amount-text">${formattedAmountT2}</div>
                </div>
                <div>
                    <div class="receipt-t2-list-row">
                        <span class="receipt-t2-list-label">Beneficiary Name</span>
                        <span class="receipt-t2-list-value">${r.banking_name}</span>
                    </div>
                    <div class="receipt-t2-list-row">
                        <span class="receipt-t2-list-label">Account No.</span>
                        <span class="receipt-t2-list-value font-mono">${r.account_number}</span>
                    </div>
                    <div class="receipt-t2-list-row">
                        <span class="receipt-t2-list-label">IFSC</span>
                        <span class="receipt-t2-list-value font-mono">${r.ifsc_code || '—'}</span>
                    </div>
                    <div class="receipt-t2-list-row">
                        <span class="receipt-t2-list-label">UTR Number</span>
                        <span class="receipt-t2-list-value font-mono">${r.transaction_id}</span>
                    </div>
                    <div class="receipt-t2-list-row">
                        <span class="receipt-t2-list-label">Date &amp; Time</span>
                        <span class="receipt-t2-list-value font-mono">${formattedDate}</span>
                    </div>
                </div>
            </div>
        `;
    }

    // Download Image (PNG)
    document.getElementById('btnDownloadHistoryPng').onclick = async () => {
        const card = document.getElementById('historyCaptureCard');
        const btn = document.getElementById('btnDownloadHistoryPng');
        const orig = btn.innerHTML;
        btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Exporting...`;
        btn.disabled = true;

        try {
            const canvas = await html2canvas(card, { scale: 3, useCORS: true, backgroundColor: '#ffffff' });
            canvas.toBlob(blob => {
                saveAs(blob, `Receipt_${r.transaction_id}.png`);
                showNotification('Image downloaded successfully!', 'success');
                btn.innerHTML = orig;
                btn.disabled = false;
            });
        } catch (e) {
            console.error(e);
            showNotification('Error exporting image.', 'error');
            btn.innerHTML = orig;
            btn.disabled = false;
        }
    };

    // Download PDF
    document.getElementById('btnDownloadHistoryPdf').onclick = async () => {
        const card = document.getElementById('historyCaptureCard');
        const btn = document.getElementById('btnDownloadHistoryPdf');
        const orig = btn.innerHTML;
        btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Rendering PDF...`;
        btn.disabled = true;

        try {
            const canvas = await html2canvas(card, { scale: 2.5, useCORS: true, backgroundColor: '#ffffff' });
            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');

            const imgWidth = 140; // mm
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            const xPos = (210 - imgWidth) / 2;
            const yPos = 25;

            pdf.addImage(imgData, 'PNG', xPos, yPos, imgWidth, imgHeight);
            pdf.save(`Receipt_${r.transaction_id}.pdf`);
            showNotification('PDF downloaded successfully!', 'success');
        } catch (e) {
            console.error(e);
            showNotification('Error creating PDF.', 'error');
        } finally {
            btn.innerHTML = orig;
            btn.disabled = false;
        }
    };
}
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>

