<?php
// contacts.php - Contact Directory & Bulk Importer
require_once __DIR__ . '/config/db.php';
require_auth();

define('PAGE_TITLE', 'Contacts Directory');

// Fetch contacts
$search = trim($_GET['q'] ?? '');
$query = "SELECT * FROM contacts";
$params = [];

if (!empty($search)) {
    $query .= " WHERE name LIKE ? OR account_number LIKE ? OR bank_name LIKE ? OR ifsc_code LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$contacts = $stmt->fetchAll();

include_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">

    <!-- Top Action Header -->
    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">Beneficiary Contacts</h2>
            <p class="text-xs text-slate-500 mt-1">Manage single contacts or import hundreds from Excel &amp; CSV</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <button type="button" onclick="openAddContactModal()" 
                class="px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center space-x-2">
                <i class="fa-solid fa-user-plus"></i>
                <span>Add Single Contact</span>
            </button>

            <button type="button" onclick="openBulkImportModal()" 
                class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center space-x-2">
                <i class="fa-solid fa-file-excel"></i>
                <span>Bulk Import Contacts</span>
            </button>

            <button type="button" onclick="exportContactsCSV()" 
                class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition flex items-center space-x-1.5" title="Export contacts to CSV">
                <i class="fa-solid fa-download"></i>
                <span>Export CSV</span>
            </button>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="relative w-full sm:w-96">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" id="contactsSearchInput" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, mobile, email, account, or IFSC..."
                autocomplete="off"
                class="w-full pl-9 pr-10 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
            <button type="button" id="clearSearchBtn" onclick="clearContactsSearch()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 <?= empty($search) ? 'hidden' : '' ?>" title="Clear search">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>

        <div class="text-xs text-slate-500 font-medium">
            Showing <strong class="text-slate-800" id="contactsCountDisplay"><?= count($contacts) ?></strong> contacts
        </div>
    </div>

    <!-- Contacts Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <?php if (empty($contacts)): ?>
            <div class="p-12 text-center">
                <div class="w-14 h-14 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center mx-auto text-2xl mb-3 shadow-sm">
                    <i class="fa-regular fa-address-book"></i>
                </div>
                <h4 class="text-base font-bold text-slate-800">No contacts found</h4>
                <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1">
                    <?= !empty($search) ? 'No contacts match your search query.' : 'You have not added any beneficiary contacts yet. Add one manually or upload an Excel file.' ?>
                </p>
                <div class="mt-4 flex justify-center space-x-3">
                    <button type="button" onclick="openAddContactModal()" class="px-4 py-2 bg-sky-600 text-white font-bold text-xs rounded-xl shadow">
                        + Add First Contact
                    </button>
                    <button type="button" onclick="openBulkImportModal()" class="px-4 py-2 bg-emerald-600 text-white font-bold text-xs rounded-xl shadow">
                        Import Excel / CSV
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs" id="contactsTable">
                    <thead class="bg-slate-50 text-slate-500 uppercase font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-4">Beneficiary Name</th>
                            <th class="py-3.5 px-4">Mobile Number</th>
                            <th class="py-3.5 px-4">Account Number</th>
                            <th class="py-3.5 px-4">IFSC Code</th>
                            <th class="py-3.5 px-4">Bank Name</th>
                            <th class="py-3.5 px-4">Notes</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($contacts as $c): 
                            $searchHaystack = strtolower(trim(($c['name'] ?? '') . ' ' . ($c['phone'] ?? '') . ' ' . ($c['email'] ?? '') . ' ' . ($c['account_number'] ?? '') . ' ' . ($c['ifsc_code'] ?? '') . ' ' . ($c['bank_name'] ?? '') . ' ' . ($c['notes'] ?? '')));
                        ?>
                            <tr class="hover:bg-slate-50/80 transition contact-data-row" id="contact-row-<?= $c['id'] ?>" data-search="<?= htmlspecialchars($searchHaystack) ?>">
                                <td class="py-3.5 px-4 font-bold text-slate-900">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-xs uppercase flex-shrink-0">
                                            <?= strtoupper(substr($c['name'], 0, 2)) ?>
                                        </div>
                                        <div>
                                            <div class="text-slate-900 font-bold"><?= htmlspecialchars($c['name']) ?></div>
                                            <?php if (!empty($c['email'])): ?>
                                                <div class="text-[11px] text-slate-500 font-normal flex items-center space-x-1 mt-0.5">
                                                    <i class="fa-regular fa-envelope text-[10px] text-slate-400"></i>
                                                    <span><?= htmlspecialchars($c['email']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-700">
                                    <?= !empty($c['phone']) ? htmlspecialchars($c['phone']) : '<span class="text-slate-300">—</span>' ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-semibold text-slate-700">
                                    <?= htmlspecialchars($c['account_number']) ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-600 uppercase">
                                    <?= htmlspecialchars($c['ifsc_code'] ?? '—') ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700">
                                    <?= htmlspecialchars($c['bank_name'] ?? '—') ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-400 max-w-xs truncate">
                                    <?= htmlspecialchars($c['notes'] ?? '—') ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center space-x-1.5">
                                        <!-- Edit Contact -->
                                        <button type="button" onclick="openEditContactModal(<?= $c['id'] ?>)" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="Edit Contact">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <!-- Delete Contact -->
                                        <button type="button" onclick="deleteContact(<?= $c['id'] ?>)" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Delete Contact">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="noLiveSearchResultsRow" class="hidden">
                            <td colspan="7" class="py-10 text-center text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-2xl text-slate-300 mb-2 block"></i>
                                <p class="font-semibold text-slate-600 text-xs">No contacts match "<span id="liveSearchQueryText" class="font-bold text-slate-800"></span>"</p>
                                <button type="button" onclick="clearContactsSearch()" class="mt-2 text-sky-600 hover:underline text-xs font-semibold">Clear Search</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- ============================================== -->
<!-- MODAL: ADD / EDIT SINGLE CONTACT               -->
<!-- ============================================== -->
<div id="contactFormModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 relative">
        <button type="button" onclick="closeContactModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-lg">
            <i class="fa-solid fa-times"></i>
        </button>

        <h3 id="contactModalTitle" class="text-base font-bold text-slate-900 mb-4">Add Single Contact</h3>

        <form id="contactForm" onsubmit="submitContactForm(event)" class="space-y-3.5">
            <input type="hidden" id="c_id" name="id" value="0">
            <input type="hidden" id="c_action" name="action" value="add">

            <div>
                <label for="c_name" class="block text-xs font-semibold text-slate-700 mb-1">Beneficiary Name *</label>
                <input type="text" id="c_name" name="name" required placeholder="e.g. PERAMBATH EXPORTS IMPORTS"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="c_phone" class="block text-xs font-semibold text-slate-700 mb-1">Mobile Number</label>
                    <input type="text" id="c_phone" name="phone" placeholder="+91 9876543210"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                <div>
                    <label for="c_email" class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                    <input type="email" id="c_email" name="email" placeholder="contact@example.com"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
            </div>

            <div>
                <label for="c_account" class="block text-xs font-semibold text-slate-700 mb-1">Account Number *</label>
                <input type="text" id="c_account" name="account_number" required placeholder="e.g. 50100584372675"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="c_ifsc" class="block text-xs font-semibold text-slate-700 mb-1">IFSC Code</label>
                    <input type="text" id="c_ifsc" name="ifsc_code" placeholder="HDFC0001535"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono uppercase text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                <div>
                    <label for="c_bank" class="block text-xs font-semibold text-slate-700 mb-1">Bank Name</label>
                    <input type="text" id="c_bank" name="bank_name" placeholder="HDFC Bank"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
            </div>

            <div>
                <label for="c_notes" class="block text-xs font-semibold text-slate-700 mb-1">Notes / Description</label>
                <textarea id="c_notes" name="notes" rows="2" placeholder="e.g. Primary vendor for office supplies"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
            </div>

            <div class="mt-5 flex justify-end space-x-2 pt-2">
                <button type="button" onclick="closeContactModal()" class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                <button type="submit" id="c_submitBtn" class="px-4 py-2 text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 rounded-xl shadow-sm">Save Contact</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: BULK IMPORT CONTACTS (EXCEL / CSV)      -->
<!-- ============================================== -->
<div id="bulkImportModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-7 shadow-2xl border border-slate-100 relative max-h-[90vh] flex flex-col">
        <button type="button" onclick="closeBulkImportModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-lg">
            <i class="fa-solid fa-times"></i>
        </button>

        <div class="pb-3 border-b border-slate-100 mb-4">
            <h3 class="text-lg font-bold text-slate-900 flex items-center space-x-2">
                <i class="fa-solid fa-file-excel text-emerald-600"></i>
                <span>Bulk Import Contacts</span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">Upload an Excel (.xlsx, .xls) or CSV sheet to import beneficiaries in bulk</p>
        </div>

        <!-- Sample format help -->
        <div class="bg-sky-50 border border-sky-200 rounded-xl p-3 text-xs text-sky-900 mb-4 flex items-center justify-between">
            <div>
                <strong>Supported Columns:</strong> Name &bull; Mobile &bull; Email &bull; Account Number &bull; IFSC Code &bull; Bank Name &bull; Notes
            </div>
            <a href="assets/sample/sample_contacts.csv" download="sample_contacts.csv" class="font-bold text-sky-700 hover:underline flex items-center space-x-1">
                <i class="fa-solid fa-download"></i>
                <span>Sample CSV</span>
            </a>
        </div>

        <!-- File Upload Area -->
        <div id="contactDropZone" class="dropzone-dashed p-6 text-center cursor-pointer mb-4">
            <input type="file" id="contactFileInput" accept=".xlsx, .xls, .csv" class="hidden">
            <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-arrow-up-from-bracket"></i>
            </div>
            <p class="text-xs font-bold text-slate-800">Choose Excel/CSV file or drag &amp; drop</p>
            <p class="text-[11px] text-slate-400 mt-1">.xlsx, .xls, .csv up to 10MB</p>
            <button type="button" onclick="document.getElementById('contactFileInput').click()" class="mt-3 px-3 py-1.5 bg-slate-800 text-white text-xs font-bold rounded-lg shadow-sm">
                Browse File
            </button>
        </div>

        <!-- Preview Table of Contacts to Import -->
        <div id="contactImportPreviewWrapper" class="hidden flex-1 overflow-y-auto mb-4 border border-slate-200 rounded-xl">
            <div class="bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 border-b border-slate-200 flex justify-between items-center">
                <span id="importCountBadge">0 contacts detected</span>
                <span class="text-emerald-600 font-semibold text-[11px]">Ready to import</span>
            </div>
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-100 text-slate-500 font-bold uppercase sticky top-0">
                    <tr>
                        <th class="py-2 px-3">Name</th>
                        <th class="py-2 px-3">Mobile</th>
                        <th class="py-2 px-3">Email</th>
                        <th class="py-2 px-3">Account No</th>
                        <th class="py-2 px-3">IFSC</th>
                        <th class="py-2 px-3">Bank</th>
                    </tr>
                </thead>
                <tbody id="contactImportPreviewBody" class="divide-y divide-slate-100">
                    <!-- Dynamic -->
                </tbody>
            </table>
        </div>

        <div class="pt-2 border-t border-slate-100 flex justify-end space-x-2 mt-auto">
            <button type="button" onclick="closeBulkImportModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Close</button>
            <button type="button" id="btnExecuteContactImport" onclick="executeContactImport()" disabled 
                class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                Confirm &amp; Import All
            </button>
        </div>
    </div>
</div>

<!-- Contacts Manager JavaScript -->
<script src="assets/js/contacts-manager.js"></script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>

