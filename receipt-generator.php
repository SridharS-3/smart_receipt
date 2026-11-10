<?php
// receipt-generator.php - Single Receipt Generator
require_once __DIR__ . '/config/db.php';
require_auth();

define('PAGE_TITLE', 'Build a Receipt');

// Fetch Contacts for dropdown
$contacts = [];
try {
    $stmt = $pdo->query("SELECT id, name, account_number, ifsc_code, bank_name FROM contacts ORDER BY name ASC");
    $contacts = $stmt->fetchAll();
} catch (\PDOException $e) {
    //
}

// Check if pre-filled from contact or receipt recreate
$prefill = [
    'template' => 'template2',
    'status' => 'Successful',
    'amount' => '',
    'type' => 'IMPS',
    'tx_id' => '',
    'banking_name' => '',
    'account_no' => '',
    'ifsc' => '',
    'date' => date('d M Y, h:i A')
];

if (isset($_GET['contact_id'])) {
    $c_id = (int)$_GET['contact_id'];
    $stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->execute([$c_id]);
    if ($contact = $stmt->fetch()) {
        $prefill['banking_name'] = $contact['name'];
        $prefill['account_no'] = $contact['account_number'];
        $prefill['ifsc'] = $contact['ifsc_code'] ?? '';
    }
} elseif (isset($_GET['recreate_id'])) {
    $r_id = (int)$_GET['recreate_id'];
    $stmt = $pdo->prepare("SELECT * FROM receipts WHERE id = ?");
    $stmt->execute([$r_id]);
    if ($r = $stmt->fetch()) {
        $prefill['template'] = $r['template_type'] ?? 'template1';
        $prefill['status'] = $r['status'];
        $prefill['amount'] = (string)$r['amount'];
        $prefill['type'] = $r['transaction_type'];
        $prefill['tx_id'] = $r['transaction_id'];
        $prefill['banking_name'] = $r['banking_name'];
        $prefill['account_no'] = $r['account_number'];
        $prefill['ifsc'] = $r['ifsc_code'] ?? '';
        $prefill['date'] = date('Y-m-d H:i', strtotime($r['payment_date']));
    }
}

include_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-6xl mx-auto space-y-6">

    <!-- Top Action & Template Selector Bar -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1.5 flex items-center space-x-1.5">
                <i class="fa-solid fa-palette text-slate-400"></i>
                <span>Receipt Style &amp; Template</span>
            </div>
            <div class="inline-flex p-1 bg-slate-100/90 rounded-2xl border border-slate-200/60 shadow-inner">
                <button type="button" id="selectTpl3" onclick="switchTemplate('template3')" 
                    class="template-toggle-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 bg-white text-emerald-700 shadow-sm border border-emerald-200/80">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                    <span>Style 3: Green Transfer Card</span>
                </button>
                <button type="button" id="selectTpl2" onclick="switchTemplate('template2')" 
                    class="template-toggle-btn px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center space-x-2 text-slate-600 hover:text-slate-900">
                    <span class="w-2 h-2 rounded-full bg-slate-300 inline-block"></span>
                    <span>Style 2: Payment Slip (Peach)</span>
                </button>
                <button type="button" id="selectTpl1" onclick="switchTemplate('template1')" 
                    class="template-toggle-btn px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center space-x-2 text-slate-600 hover:text-slate-900">
                    <span class="w-2 h-2 rounded-full bg-slate-300 inline-block"></span>
                    <span>Style 1: Modern Receipt (Blue)</span>
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <button type="button" onclick="clearReceiptForm()" class="text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-3.5 py-2 rounded-xl transition flex items-center space-x-1.5 border border-slate-200" title="Clear all input fields">
                <i class="fa-solid fa-eraser text-slate-400"></i>
                <span>Clear Form</span>
            </button>
            <button type="button" onclick="openBulkSlipModal()" class="text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 px-4 py-2 rounded-xl shadow-sm transition flex items-center space-x-2">
                <i class="fa-solid fa-file-excel"></i>
                <span>Bulk Import</span>
            </button>
        </div>
    </div>

    <!-- Main Grid: Left Form (Build a Receipt) & Right Preview -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left Column: Form (Build a Receipt) -->
        <div class="lg:col-span-6 bg-white rounded-2xl p-6 sm:p-7 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Build a Receipt</h2>
                <span class="text-xs text-slate-400 font-medium">Live Instant Sync</span>
            </div>

            <!-- Optional: Quick Fill from Contacts -->
            <div class="mb-5 bg-sky-50/60 p-3.5 rounded-xl border border-sky-100">
                <label for="contactSelector" class="block text-xs font-bold text-sky-900 mb-1 flex items-center justify-between">
                    <span><i class="fa-solid fa-address-book text-sky-600 mr-1.5"></i> Quick Pick from Contacts</span>
                    <a href="contacts.php?action=add" class="text-[11px] text-sky-600 hover:underline">+ New Contact</a>
                </label>
                <select id="contactSelector" onchange="applySelectedContact(this)" class="w-full bg-white border border-sky-200 rounded-lg py-2 px-3 text-xs text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <option value="">-- Choose a Saved Beneficiary or type manually below --</option>
                    <?php foreach ($contacts as $c): ?>
                        <option value="<?= htmlspecialchars(json_encode($c)) ?>">
                            <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['account_number']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <form id="receiptBuilderForm" class="space-y-4" onsubmit="event.preventDefault();">
                <!-- Status -->
                <div>
                    <label for="f_status" class="block text-xs font-semibold text-slate-700 mb-1.5">Status</label>
                    <select id="f_status" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3.5 text-sm text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
                        <option value="Successful" <?= $prefill['status'] === 'Successful' ? 'selected' : '' ?>>Successful</option>
                        <option value="Payment Successful" <?= $prefill['status'] === 'Payment Successful' ? 'selected' : '' ?>>Payment Successful</option>
                        <option value="Pending" <?= $prefill['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Failed" <?= $prefill['status'] === 'Failed' ? 'selected' : '' ?>>Failed</option>
                    </select>
                </div>

                <!-- Transaction Amount -->
                <div>
                    <label for="f_amount" class="block text-xs font-semibold text-slate-700 mb-1.5">Transaction Amount</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-semibold text-sm">₹</span>
                        <input type="number" step="0.01" id="f_amount" value="<?= htmlspecialchars($prefill['amount']) ?>" placeholder="e.g. 50000"
                            class="w-full pl-8 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
                    </div>
                </div>

                <!-- Transaction Type -->
                <div>
                    <label for="f_type" class="block text-xs font-semibold text-slate-700 mb-1.5">Transaction Type</label>
                    <select id="f_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3.5 text-sm text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
                        <option value="IMPS" <?= $prefill['type'] === 'IMPS' ? 'selected' : '' ?>>IMPS</option>
                        <option value="NEFT" <?= $prefill['type'] === 'NEFT' ? 'selected' : '' ?>>NEFT</option>
                        <option value="RTGS" <?= $prefill['type'] === 'RTGS' ? 'selected' : '' ?>>RTGS</option>
                        <option value="UPI" <?= $prefill['type'] === 'UPI' ? 'selected' : '' ?>>UPI</option>
                        <option value="Bank Transfer" <?= $prefill['type'] === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                    </select>
                </div>

                <!-- Transaction ID / UTR -->
                <div>
                    <label for="f_txid" class="block text-xs font-semibold text-slate-700 mb-1.5">Transaction ID / UTR</label>
                    <input type="text" id="f_txid" value="<?= htmlspecialchars($prefill['tx_id']) ?>" placeholder="e.g. 2042974766"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
                </div>

                <!-- Sent To Section Header -->
                <div class="pt-2">
                    <h3 class="text-sm font-bold text-slate-800">Sent To</h3>
                </div>

                <!-- Banking Name -->
                <div>
                    <label for="f_banking_name" class="block text-xs font-semibold text-slate-700 mb-1.5">Banking Name / Beneficiary Name</label>
                    <input type="text" id="f_banking_name" value="<?= htmlspecialchars($prefill['banking_name']) ?>" placeholder="e.g. Enter Beneficiary Name"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
                </div>

                <!-- Account Number -->
                <div>
                    <label for="f_account_no" class="block text-xs font-semibold text-slate-700 mb-1.5">Account Number</label>
                    <input type="text" id="f_account_no" value="<?= htmlspecialchars($prefill['account_no']) ?>" placeholder="e.g. 50100584372675"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
                </div>

                <!-- IFSC Code -->
                <div>
                    <label for="f_ifsc" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">IFSC Code</label>
                    <input type="text" id="f_ifsc" value="<?= htmlspecialchars($prefill['ifsc']) ?>" placeholder="e.g. HDFC0001535"
                        class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-mono text-slate-900 dark:text-white uppercase focus:bg-white dark:focus:bg-slate-850 focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
                </div>

                <!-- Date & Time -->
                <div>
                    <label for="f_datetime" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Date &amp; Time</label>
                    <input type="text" id="f_datetime" value="<?= htmlspecialchars(date('d M Y, h:i A')) ?>" placeholder="e.g. <?= date('d M Y, h:i A') ?>"
                        class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-850 focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
                    <p class="text-[11px] text-slate-400 mt-1">Current local time shown; you can edit date and time freely.</p>
                </div>

                <!-- Buttons Area: Save First, Then Download Image & PDF & Print -->
                <div class="pt-3 space-y-3">
                    <!-- Primary Save Button -->
                    <button type="button" id="btnSaveReceipt" onclick="manualSaveReceipt()" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold rounded-xl text-sm shadow-md transition flex items-center justify-center space-x-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span id="btnSaveReceiptText">Save Receipt Record</span>
                    </button>

                    <!-- Export Actions: Download Image, PDF & Print -->
                    <div id="exportActionContainer" class="space-y-2.5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <!-- Main Download Image Button -->
                            <button type="button" id="btnDownloadPng" onclick="downloadReceiptPNG()" class="py-3 px-4 bg-[#0088cc] hover:bg-[#0077b5] text-white font-bold rounded-xl text-sm shadow-md transition flex items-center justify-center space-x-2">
                                <i class="fa-solid fa-image"></i>
                                <span>Download Image</span>
                            </button>
                            <!-- Main Download PDF Button -->
                            <button type="button" id="btnDownloadPdf" onclick="downloadReceiptPDF()" class="py-3 px-4 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl text-sm shadow-md transition flex items-center justify-center space-x-2">
                                <i class="fa-solid fa-file-pdf text-rose-400"></i>
                                <span>Download PDF</span>
                            </button>
                        </div>

                        <button type="button" id="btnPrintSlip" onclick="printReceiptSlip()" class="w-full py-2.5 px-3 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-xs font-semibold rounded-xl transition flex items-center justify-center space-x-1.5 border border-slate-200 dark:border-slate-700">
                            <i class="fa-solid fa-print"></i>
                            <span>Print Slip</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Right Column: Live Receipt Preview Card -->
        <div class="lg:col-span-6 flex flex-col items-center">
            
            <div class="w-full flex items-center justify-between mb-3 px-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Live Preview</span>
                <span class="text-xs text-sky-600 font-medium"><i class="fa-solid fa-eye mr-1"></i> What your recipient sees</span>
            </div>

            <!-- Receipt Container Wrapper for High-DPI Capture -->
            <div id="receiptCaptureWrapper" class="p-2 sm:p-4 bg-slate-100/70 dark:bg-slate-800/50 rounded-2xl border border-slate-200/80 dark:border-slate-700/60 shadow-inner w-full flex justify-center">

                <!-- ============================================ -->
                <!-- TEMPLATE 1: BLUE TRANSACTION RECEIPT (IMAGE 1) -->
                <!-- ============================================ -->
                <div id="previewTemplate1" class="receipt-template-1 hidden">
                    <!-- Top Blue Pattern Banner -->
                    <div class="receipt-t1-header" style="background-color: #0284c7 !important; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;">
                        <h3 style="color: #ffffff !important; margin: 0; font-weight: 700;">Transaction Receipt</h3>
                    </div>

                    <!-- Receipt Details Rows -->
                    <div class="receipt-t1-body">
                        <!-- Status -->
                        <div class="receipt-t1-row">
                            <span class="receipt-t1-label">Status</span>
                            <span id="p1_status" class="receipt-t1-status-success">Successful</span>
                        </div>

                        <!-- Amount -->
                        <div class="receipt-t1-row">
                            <span class="receipt-t1-label">Transaction Amount</span>
                            <span id="p1_amount" class="receipt-t1-amount">₹ 0.0</span>
                        </div>

                        <!-- Payment Type -->
                        <div class="receipt-t1-row">
                            <span class="receipt-t1-label">Payment Type</span>
                            <span id="p1_type" class="receipt-t1-value">Send to Account (IMPS)</span>
                        </div>

                        <!-- Divider -->
                        <div class="receipt-t1-divider"></div>

                        <!-- Transaction ID -->
                        <div class="receipt-t1-row">
                            <span class="receipt-t1-label">Transaction ID</span>
                            <span id="p1_txid" class="receipt-t1-value font-mono">—</span>
                        </div>

                        <!-- Sent To Subheading -->
                        <div class="receipt-t1-section-title">Sent To</div>

                        <!-- Banking Name -->
                        <div class="receipt-t1-row">
                            <span class="receipt-t1-label">Banking Name</span>
                            <span id="p1_banking_name" class="receipt-t1-value">—</span>
                        </div>

                        <!-- Account Number -->
                        <div class="receipt-t1-row">
                            <span class="receipt-t1-label">Account Number</span>
                            <span id="p1_account_no" class="receipt-t1-value font-mono">—</span>
                        </div>

                        <!-- Date & Time -->
                        <div class="receipt-t1-row" style="margin-bottom: 0;">
                            <span class="receipt-t1-label">Date &amp; Time</span>
                            <span id="p1_datetime" class="receipt-t1-value font-mono"><?= date('d M Y, h:i A') ?></span>
                        </div>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- TEMPLATE 2: PEACH PAYMENT SLIP (IMAGE 2)     -->
                <!-- ============================================ -->
                <div id="previewTemplate2" class="receipt-template-2 hidden">
                    <!-- Title (Single Line) -->
                    <div class="receipt-t2-title">
                        Payment Transaction Receipt
                    </div>

                    <!-- Inner Peach Card -->
                    <div class="receipt-t2-card">
                        <!-- Orange Check Badge (Unified vector SVG for exact centering in canvas & PDF) -->
                        <div class="receipt-t2-badge-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="58" height="58" viewBox="0 0 58 58" fill="none">
                                <circle cx="29" cy="29" r="27.5" fill="#ffffff" stroke="#EA580C" stroke-width="2.5" />
                                <polyline points="19 28.5 25.5 35 39 21.5" fill="none" stroke="#EA580C" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>

                        <div class="receipt-t2-status-label">TRANSACTION STATUS</div>
                        <div id="p2_status" class="receipt-t2-status-text">Payment Successful</div>

                        <div class="receipt-t2-amount-label">AMOUNT</div>
                        <div id="p2_amount" class="receipt-t2-amount-text">INR ₹ 0.00</div>
                    </div>

                    <!-- Details Rows -->
                    <div>
                        <div class="receipt-t2-list-row">
                            <span class="receipt-t2-list-label">Beneficiary Name</span>
                            <span id="p2_banking_name" class="receipt-t2-list-value">—</span>
                        </div>

                        <div class="receipt-t2-list-row">
                            <span class="receipt-t2-list-label">Account No.</span>
                            <span id="p2_account_no" class="receipt-t2-list-value font-mono">—</span>
                        </div>

                        <div class="receipt-t2-list-row">
                            <span class="receipt-t2-list-label">IFSC</span>
                            <span id="p2_ifsc" class="receipt-t2-list-value font-mono">—</span>
                        </div>

                        <div class="receipt-t2-list-row">
                            <span class="receipt-t2-list-label">UTR Number</span>
                            <span id="p2_txid" class="receipt-t2-list-value font-mono">—</span>
                        </div>

                        <div class="receipt-t2-list-row">
                            <span class="receipt-t2-list-label">Date &amp; Time</span>
                            <span id="p2_datetime" class="receipt-t2-list-value font-mono"><?= date('d M Y, h:i A') ?></span>
                        </div>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- TEMPLATE 3: GREEN BANK TRANSFER CARD         -->
                <!-- ============================================ -->
                <div id="previewTemplate3" class="receipt-template-3">
                    <!-- Green Squircle Check Badge -->
                    <div class="receipt-t3-badge-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60" fill="none">
                            <rect x="3" y="3" width="54" height="54" rx="16" fill="#ffffff" stroke="#00b65a" stroke-width="4" />
                            <polyline points="18 29.5 26 37.5 42 21.5" fill="none" stroke="#00b65a" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>

                    <!-- Big Green Amount -->
                    <div id="p3_amount" class="receipt-t3-amount-text">₹ 0.00</div>

                    <!-- Subtitle / Status -->
                    <div id="p3_status_text" class="receipt-t3-status-sub">Payment Successful</div>

                    <!-- Details Table -->
                    <div class="receipt-t3-details-table">
                        <div class="receipt-t3-row">
                            <span class="receipt-t3-label">TOTAL AMOUNT</span>
                            <span id="p3_total_amount" class="receipt-t3-val">₹ 0.00</span>
                        </div>
                        <div class="receipt-t3-row">
                            <span class="receipt-t3-label">UTR NO</span>
                            <span id="p3_utr" class="receipt-t3-val font-mono">—</span>
                        </div>
                        <div class="receipt-t3-row">
                            <span class="receipt-t3-label">BENEFICIARY NAME</span>
                            <span id="p3_beneficiary_name" class="receipt-t3-val">—</span>
                        </div>
                        <div class="receipt-t3-row">
                            <span class="receipt-t3-label">MODE OF TRANSFER</span>
                            <span id="p3_mode" class="receipt-t3-val">IMPS</span>
                        </div>
                        <div class="receipt-t3-row" style="margin-bottom: 0;">
                            <span class="receipt-t3-label">TRANSACTION DATE</span>
                            <span id="p3_date" class="receipt-t3-val font-mono"><?= date('d M Y') ?></span>
                        </div>
                    </div>

                    <!-- Bottom Action Buttons -->
                    <button type="button" onclick="prepareNewPayment()" class="receipt-t3-btn-primary hover:opacity-95 active:scale-[0.99] transition cursor-pointer">Make New Payment</button>
                    <button type="button" onclick="window.location.href='dashboard.php'" class="receipt-t3-btn-secondary hover:bg-slate-50 active:scale-[0.99] transition cursor-pointer">Go Back To Dashboard</button>
                </div>

            </div>

        </div>

    </div>

</div>

<!-- ======================================================== -->
<!-- MODAL: BULK PAYMENT SLIP GENERATOR POPUP                 -->
<!-- ======================================================== -->
<div id="bulkSlipModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-5xl w-full p-6 sm:p-7 shadow-2xl border border-slate-200 dark:border-slate-800 relative max-h-[92vh] flex flex-col overflow-hidden">
        
        <!-- Modal Close Button -->
        <button type="button" onclick="closeBulkSlipModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 dark:hover:text-white text-xl z-10 transition">
            <i class="fa-solid fa-times"></i>
        </button>

        <!-- Modal Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100 dark:border-slate-800 mb-4 flex-shrink-0">
            <div>
                <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2">
                    <i class="fa-solid fa-file-excel text-emerald-600"></i>
                    <span>Payment Slip Generator (Bulk)</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Upload an Excel sheet to generate and download batch payment slips</p>
            </div>
            <!-- Quick Sample Downloads -->
            <div class="flex items-center space-x-2 pr-6 sm:pr-0">
                <a href="assets/sample/sample_receipts.csv" download="sample_payment_slips.csv" 
                    class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-sky-700 bg-sky-50 dark:bg-sky-950/60 border border-sky-200 dark:border-sky-800 hover:bg-sky-100 transition shadow-sm">
                    <i class="fa-solid fa-download"></i>
                    <span>Sample CSV</span>
                </a>
                <button type="button" onclick="downloadSampleXLSX()" 
                    class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition shadow-sm">
                    <i class="fa-solid fa-file-excel"></i>
                    <span>Sample XLSX</span>
                </button>
            </div>
        </div>

        <div class="overflow-y-auto flex-1 space-y-4 pr-1">
            
            <!-- Pre-Upload Section: Drop Zone & Columns Guide (Collapses when file is loaded) -->
            <div id="preUploadSection" class="space-y-4">
                <!-- Dashed Drag & Drop Box -->
                <div id="dropZone" class="dropzone-dashed p-8 sm:p-10 text-center cursor-pointer relative rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-sky-500 transition">
                    <input type="file" id="excelFileInput" accept=".xlsx, .xls, .csv" class="hidden">
                    
                    <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center text-2xl shadow-sm">
                        <i class="fa-solid fa-arrow-up-from-bracket"></i>
                    </div>

                    <h4 class="text-base font-bold text-slate-800 dark:text-white">Upload Excel Sheet</h4>
                    <p class="text-xs text-slate-400 mt-1 font-medium">or drag and drop file here</p>
                    <p class="text-[11px] text-slate-400 mt-1">Supported formats: .xlsx, .xls, .csv</p>

                    <button type="button" onclick="document.getElementById('excelFileInput').click()" 
                        class="mt-3 px-4 py-2 bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition shadow">
                        Select File
                    </button>
                </div>

                <!-- Tip Box: Explicit Bulk Import Columns -->
                <div class="bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 rounded-2xl p-4 text-xs text-sky-900 dark:text-sky-200">
                    <div class="flex items-start space-x-2.5">
                        <div class="w-6 h-6 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center flex-shrink-0 mt-0.5 font-bold">
                            <i class="fa-solid fa-circle-info text-xs"></i>
                        </div>
                        <div class="space-y-1.5 leading-relaxed w-full">
                            <p class="font-bold text-slate-900 dark:text-white text-xs">
                                Make sure your Excel (.xlsx, .xls) or CSV sheet includes the following columns:
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-[11px] text-slate-700 dark:text-slate-300 pt-0.5">
                                <div><strong class="text-slate-900 dark:text-white">• Beneficiary Name:</strong> Name / Banking Name</div>
                                <div><strong class="text-slate-900 dark:text-white">• Account Number:</strong> Account No / A/C No</div>
                                <div><strong class="text-slate-900 dark:text-white">• Amount:</strong> Transaction Amount (INR ₹)</div>
                                <div><strong class="text-slate-900 dark:text-white">• UTR / Reference:</strong> UTR / Transaction ID</div>
                                <div><strong class="text-slate-900 dark:text-white">• IFSC Code:</strong> Bank IFSC (e.g. HDFC0001535)</div>
                                <div><strong class="text-slate-900 dark:text-white">• Transaction Type:</strong> IMPS / NEFT / RTGS / UPI <span class="text-slate-400">(optional)</span></div>
                                <div><strong class="text-slate-900 dark:text-white">• Status:</strong> Successful / Payment Successful <span class="text-slate-400">(optional)</span></div>
                                <div><strong class="text-slate-900 dark:text-white">• Date &amp; Time:</strong> Payment Date <span class="text-slate-400">(optional)</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Post-Upload Compact Summary Bar (Replaces huge dropzone once file is loaded) -->
            <div id="postUploadSummaryBar" class="hidden bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/80 rounded-2xl p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg flex-shrink-0">
                        <i class="fa-solid fa-file-excel"></i>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                            <span id="uploadedFileTitle" class="truncate max-w-xs sm:max-w-md">data.xlsx</span>
                            <span id="uploadedFileBadge" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-200/80 text-emerald-900">0 Slips</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-0.5">Slips parsed successfully. You can choose slip style, preview, and export below.</div>
                    </div>
                </div>
                <button type="button" onclick="resetBulkUpload()" class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-semibold shadow-sm transition flex items-center space-x-1.5 self-start sm:self-center">
                    <i class="fa-solid fa-arrow-rotate-right text-xs"></i>
                    <span>Replace File</span>
                </button>
            </div>

            <!-- Parsed Data Preview & Batch Actions Card -->
            <div id="batchProcessCard" class="bg-white dark:bg-slate-800/60 rounded-2xl p-4 sm:p-5 border border-slate-200 dark:border-slate-700 hidden space-y-4">
                
                <!-- Batch Control Toolbar (Clean 2-tier responsive layout) -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span id="batchCountBadge" class="px-3 py-1 rounded-full text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200">
                            0 Slips
                        </span>
                        <div class="text-xs text-slate-600 dark:text-slate-300">
                            Total: <strong id="batchTotalAmount" class="text-slate-900 dark:text-white font-extrabold text-sm ml-1">₹ 0.00</strong>
                        </div>
                        <div class="h-4 w-px bg-slate-200 dark:bg-slate-700 mx-1 hidden sm:block"></div>
                        <div class="flex items-center space-x-1.5 text-xs text-slate-600">
                            <span class="font-semibold text-slate-500">Style:</span>
                            <select id="bulkTemplateChoice" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold rounded-xl px-2.5 py-1.5 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                                <option value="template3" selected>Style 3: Green Transfer Card (Default)</option>
                                <option value="template2">Style 2: Payment Slip (Peach)</option>
                                <option value="template1">Style 1: Modern Receipt (Blue)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Batch Export Actions -->
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="btnDownloadZip" onclick="generateAndDownloadZip()" 
                            class="px-3.5 py-2 bg-[#0088cc] hover:bg-[#0077b5] text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center space-x-1.5">
                            <i class="fa-solid fa-file-zipper"></i>
                            <span>Download Images (ZIP)</span>
                        </button>

                        <button type="button" id="btnDownloadBatchPdf" onclick="generateAndDownloadBatchPDF()" 
                            class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center space-x-1.5">
                            <i class="fa-solid fa-file-pdf text-rose-400"></i>
                            <span>Download All (PDF)</span>
                        </button>

                        <button type="button" onclick="batchSaveReceipts()" 
                            class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center space-x-1.5">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save to History</span>
                        </button>
                    </div>
                </div>

                <!-- Parsed Rows Table (Height & Alignment fixed) -->
                <div class="overflow-x-auto max-h-96 overflow-y-auto border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 shadow-inner">
                    <table class="w-full text-left text-xs" id="parsedDataTable">
                        <thead class="bg-slate-50 dark:bg-slate-800 text-slate-500 uppercase font-bold sticky top-0 border-b border-slate-200 dark:border-slate-700 shadow-sm z-10">
                            <tr>
                                <th class="py-3 px-3 w-10 text-center">
                                    <input type="checkbox" id="selectAllCheckbox" checked onchange="toggleSelectAllRows(this)" class="rounded text-sky-600 focus:ring-sky-500">
                                </th>
                                <th class="py-3 px-3 text-center w-10">#</th>
                                <th class="py-3 px-3">Beneficiary Name</th>
                                <th class="py-3 px-3">Account Number</th>
                                <th class="py-3 px-3">Amount (₹)</th>
                                <th class="py-3 px-3">UTR / Ref</th>
                                <th class="py-3 px-3">IFSC</th>
                                <th class="py-3 px-3 text-center">Type</th>
                                <th class="py-3 px-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="parsedDataBody" class="divide-y divide-slate-100 dark:divide-slate-800">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Progress Bar -->
                <div id="zipProgressWrapper" class="hidden bg-slate-50 dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        <span id="zipProgressLabel">Generating receipt PNGs: 0 / 0</span>
                        <span id="zipProgressPercent">0%</span>
                    </div>
                    <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                        <div id="zipProgressBar" class="bg-sky-600 h-2 rounded-full transition-all duration-150" style="width: 0%"></div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer Close -->
        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end flex-shrink-0 mt-3">
            <button type="button" onclick="closeBulkSlipModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Single Slip Preview Modal (from within bulk popup) -->
<div id="previewModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl relative border border-slate-100 dark:border-slate-800">
        <button type="button" onclick="closePreviewModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-lg">
            <i class="fa-solid fa-times"></i>
        </button>
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center space-x-2">
            <i class="fa-solid fa-magnifying-glass text-sky-600"></i>
            <span>Slip Preview</span>
        </h3>
        <div id="modalReceiptContainer" class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-xl flex justify-center overflow-x-auto">
            <!-- Dynamic Receipt Clone -->
        </div>
        <div class="mt-5 flex flex-wrap justify-end gap-2">
            <button type="button" onclick="closePreviewModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl">Close</button>
            <button type="button" id="modalDownloadImgBtn" class="px-4 py-2 text-xs font-bold text-white bg-[#0088cc] hover:bg-[#0077b5] rounded-xl shadow-sm flex items-center space-x-1.5">
                <i class="fa-solid fa-image"></i>
                <span>Download Image</span>
            </button>
            <button type="button" id="modalDownloadPdfBtn" class="px-4 py-2 text-xs font-bold text-white bg-slate-800 hover:bg-slate-900 rounded-xl shadow-sm flex items-center space-x-1.5">
                <i class="fa-solid fa-file-pdf text-rose-400"></i>
                <span>Download PDF</span>
            </button>
        </div>
    </div>
</div>

<!-- Off-screen hidden render target for batch generation -->
<div id="batchHiddenContainer" style="position: absolute; left: -9999px; top: -9999px; width: 440px;"></div>

<!-- Print-only area clone container -->
<div id="printableReceiptArea" class="hidden"></div>

<!-- JavaScript for Live Sync, Template Switch, and Image/PDF Export -->
<script src="assets/js/receipt-builder.js"></script>
<script src="assets/js/bulk-builder.js"></script>

<script>
    function openBulkSlipModal() {
        document.getElementById('bulkSlipModal').classList.remove('hidden');
    }

    function closeBulkSlipModal() {
        document.getElementById('bulkSlipModal').classList.add('hidden');
    }

    // Auto-open if query param ?open_bulk=1 is present
    if (new URLSearchParams(window.location.search).get('open_bulk') === '1') {
        openBulkSlipModal();
    }
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>

