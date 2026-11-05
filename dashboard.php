<?php
// dashboard.php - Admin Dashboard
require_once __DIR__ . '/config/db.php';
require_auth();

define('PAGE_TITLE', 'Dashboard');

// Fetch Metrics
$total_receipts = 0;
$total_amount = 0.00;
$total_contacts = 0;
$bulk_batches = 0;

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM receipts");
    $receipt_stats = $stmt->fetch();
    $total_receipts = (int)$receipt_stats['count'];
    $total_amount = (float)$receipt_stats['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contacts");
    $total_contacts = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(DISTINCT batch_id) FROM receipts WHERE batch_id IS NOT NULL");
    $bulk_batches = (int)$stmt->fetchColumn();

    // Recent receipts
    $stmt = $pdo->query("SELECT * FROM receipts ORDER BY created_at DESC LIMIT 6");
    $recent_receipts = $stmt->fetchAll();

    // Recent contacts
    $stmt = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5");
    $recent_contacts = $stmt->fetchAll();

} catch (\PDOException $e) {
    // Fail-safe handling
}

include_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">

    <!-- Hero Welcome Banner -->
    <div class="bg-gradient-to-r from-sky-700 via-sky-600 to-cyan-600 rounded-2xl p-6 sm:p-8 text-white shadow-xl shadow-sky-600/15 relative overflow-hidden">
        <div class="relative z-10 max-w-2xl">
            <span class="inline-block bg-white/20 backdrop-blur-md text-white text-xs font-semibold px-3 py-1 rounded-full mb-3">
                <i class="fa-solid fa-sparkles mr-1"></i> Admin Portal
            </span>
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Smart Receipt & Payment Slip Generator</h2>
            <p class="text-sky-100 text-sm mt-2 leading-relaxed">
                Generate single transaction receipts with live preview, batch-process payment slips from Excel/CSV sheets, and manage beneficiary contacts with instant auto-fill.
            </p>
            <div class="flex flex-wrap gap-3 mt-5">
                <a href="receipt-generator.php" class="bg-white text-sky-700 hover:bg-sky-50 font-bold px-4 py-2.5 rounded-xl text-sm shadow-md transition flex items-center space-x-2">
                    <i class="fa-solid fa-plus-circle"></i>
                    <span>Build Receipt</span>
                </a>
                <a href="receipt-generator.php?open_bulk=1" class="bg-sky-900/40 hover:bg-sky-900/60 border border-white/20 text-white font-semibold px-4 py-2.5 rounded-xl text-sm transition flex items-center space-x-2">
                    <i class="fa-solid fa-file-excel"></i>
                    <span>Bulk Import Slips</span>
                </a>
            </div>
        </div>
        <!-- Decorative subtle icon -->
        <i class="fa-solid fa-receipt text-9xl text-white/10 absolute -right-6 -bottom-8 pointer-events-none"></i>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Total Receipts -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Receipts</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1"><?= number_format($total_receipts) ?></h3>
                <span class="text-xs text-emerald-600 font-semibold mt-1 inline-flex items-center">
                    <i class="fa-solid fa-check mr-1"></i> Generated to date
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-receipt"></i>
            </div>
        </div>

        <!-- Total Volume -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Transacted</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1">₹ <?= number_format($total_amount, 2) ?></h3>
                <span class="text-xs text-sky-600 font-semibold mt-1 inline-flex items-center">
                    <i class="fa-solid fa-indian-rupee-sign mr-1"></i> Processed amount
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-money-bill-wave"></i>
            </div>
        </div>

        <!-- Contacts -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Saved Contacts</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1"><?= number_format($total_contacts) ?></h3>
                <span class="text-xs text-indigo-600 font-semibold mt-1 inline-flex items-center">
                    <i class="fa-solid fa-users mr-1"></i> Beneficiaries
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-address-book"></i>
            </div>
        </div>

        <!-- Bulk Batches -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Bulk Batches</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1"><?= number_format($bulk_batches) ?></h3>
                <span class="text-xs text-amber-600 font-semibold mt-1 inline-flex items-center">
                    <i class="fa-solid fa-layer-group mr-1"></i> Excel uploads
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Highlights -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <a href="receipt-generator.php" class="bg-white p-5 rounded-2xl border border-slate-200 hover:border-sky-500 hover:shadow-md transition group">
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center text-lg group-hover:bg-sky-600 group-hover:text-white transition">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <h4 class="font-bold text-slate-800 group-hover:text-sky-600 transition">Single Generator</h4>
            </div>
            <p class="text-xs text-slate-500">Create professional payment receipts instantly with customizable templates, real-time previews, and seamless transaction management.</p>
        </a>

        <a href="receipt-generator.php?open_bulk=1" class="bg-white p-5 rounded-2xl border border-slate-200 hover:border-sky-500 hover:shadow-md transition group">
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg group-hover:bg-emerald-600 group-hover:text-white transition">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                </div>
                <h4 class="font-bold text-slate-800 group-hover:text-emerald-600 transition">Bulk Import Slips</h4>
            </div>
            <p class="text-xs text-slate-500">Drop an Excel (.xlsx, .xls) or CSV file to batch generate receipts and download all as Image ZIP or PDF.</p>
        </a>

        <a href="contacts.php" class="bg-white p-5 rounded-2xl border border-slate-200 hover:border-sky-500 hover:shadow-md transition group">
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-lg group-hover:bg-purple-600 group-hover:text-white transition">
                    <i class="fa-solid fa-users-gear"></i>
                </div>
                <h4 class="font-bold text-slate-800 group-hover:text-purple-600 transition">Contacts & Import</h4>
            </div>
            <p class="text-xs text-slate-500">Manage bank accounts, import contacts in bulk via Excel/CSV, and auto-populate receipts.</p>
        </a>
    </div>

    <!-- Tables Grid: Recent Receipts & Contacts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Receipts (2 cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                <div>
                    <h3 class="font-bold text-slate-800 text-base">Recent Receipts</h3>
                    <p class="text-xs text-slate-400">Latest generated payment slips</p>
                </div>
                <a href="history.php" class="text-xs font-semibold text-sky-600 hover:text-sky-700 hover:underline">
                    View All History &rarr;
                </a>
            </div>

            <?php if (empty($recent_receipts)): ?>
                <div class="text-center py-10">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl mb-3">
                        <i class="fa-regular fa-folder-open"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-600">No receipts generated yet</p>
                    <p class="text-xs text-slate-400 mt-1">Start by generating your first single or bulk receipt.</p>
                    <a href="receipt-generator.php" class="mt-4 inline-flex items-center space-x-2 text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 px-3.5 py-2 rounded-xl transition">
                        <i class="fa-solid fa-plus"></i>
                        <span>Generate Receipt Now</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 font-bold uppercase border-b border-slate-100">
                                <th class="pb-2.5">Beneficiary / Bank</th>
                                <th class="pb-2.5">Account / Ref</th>
                                <th class="pb-2.5">Amount</th>
                                <th class="pb-2.5">Status</th>
                                <th class="pb-2.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($recent_receipts as $r): ?>
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3 font-semibold text-slate-800">
                                        <div class="font-bold text-slate-900"><?= htmlspecialchars($r['banking_name']) ?></div>
                                        <div class="text-[11px] text-slate-400"><?= htmlspecialchars($r['transaction_type']) ?> • <?= date('d M Y, h:i A', strtotime($r['payment_date'])) ?></div>
                                    </td>
                                    <td class="py-3 text-slate-600 font-mono text-[11px]">
                                        <div>A/C: <?= htmlspecialchars($r['account_number']) ?></div>
                                        <div class="text-slate-400">Ref: <?= htmlspecialchars($r['transaction_id']) ?></div>
                                    </td>
                                    <td class="py-3 font-bold text-slate-900">
                                        ₹ <?= number_format($r['amount'], 2) ?>
                                    </td>
                                    <td class="py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold <?= $r['status'] === 'Successful' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">
                                            <?= htmlspecialchars($r['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 text-right">
                                        <a href="receipt-generator.php?recreate_id=<?= $r['id'] ?>" class="text-sky-600 hover:text-sky-800 font-semibold px-2 py-1 rounded bg-sky-50 border border-sky-100 hover:bg-sky-100 transition" title="Re-open in generator">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Contacts (1 col) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                    <div>
                        <h3 class="font-bold text-slate-800 text-base">Saved Contacts</h3>
                        <p class="text-xs text-slate-400">Quick access to beneficiaries</p>
                    </div>
                    <a href="contacts.php" class="text-xs font-semibold text-sky-600 hover:underline">Manage &rarr;</a>
                </div>

                <div class="space-y-3">
                    <?php if (empty($recent_contacts)): ?>
                        <p class="text-xs text-slate-400 text-center py-6">No saved contacts yet.</p>
                    <?php else: ?>
                        <?php foreach ($recent_contacts as $c): ?>
                            <div class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-200 transition">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-xs uppercase">
                                        <?= strtoupper(substr($c['name'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-slate-800 truncate max-w-[130px]"><?= htmlspecialchars($c['name']) ?></div>
                                        <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($c['account_number']) ?></div>
                                    </div>
                                </div>
                                <a href="receipt-generator.php?contact_id=<?= $c['id'] ?>" class="text-[11px] font-semibold text-sky-600 bg-sky-50 px-2.5 py-1 rounded-lg border border-sky-200 hover:bg-sky-600 hover:text-white transition">
                                    Use
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 mt-4">
                <a href="contacts.php?action=add" class="w-full flex items-center justify-center space-x-2 py-2 px-3 rounded-xl text-xs font-semibold text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200 transition">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>Add New Contact</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>

