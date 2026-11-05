<?php
// bulk-receipts.php - Bulk Payment Slip Generator (Image 3 Reference)
require_once __DIR__ . '/config/db.php';
require_auth();

define('PAGE_TITLE', 'Payment Slip Generator');

include_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-6">

    <!-- Header Section matching Image 3 -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
            <div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Payment Slip Generator</h2>
                <p class="text-sm text-slate-500 mt-1">Upload an Excel sheet to generate payment transfer slips</p>
            </div>
            <!-- Quick Sample Downloads -->
            <div class="flex items-center space-x-2">
                <a href="assets/sample/sample_receipts.csv" download="sample_payment_slips.csv" 
                    class="inline-flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-bold text-sky-700 bg-sky-50 border border-sky-200 hover:bg-sky-100 transition shadow-sm">
                    <i class="fa-solid fa-download"></i>
                    <span>Download Sample CSV</span>
                </a>
                <button type="button" onclick="downloadSampleXLSX()" 
                    class="inline-flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition shadow-sm">
                    <i class="fa-solid fa-file-excel"></i>
                    <span>Download Sample XLSX</span>
                </button>
            </div>
        </div>

        <!-- Pre-Upload Section: Drop Zone & Columns Guide -->
        <div id="preUploadSection" class="space-y-6">
            <!-- Dashed Drag & Drop Box -->
            <div class="mt-6">
                <div id="dropZone" class="dropzone-dashed p-10 sm:p-14 text-center cursor-pointer relative">
                    <input type="file" id="excelFileInput" accept=".xlsx, .xls, .csv" class="hidden">
                    
                    <!-- Upload Icon -->
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-3xl shadow-sm">
                        <i class="fa-solid fa-arrow-up-from-bracket"></i>
                    </div>

                    <h3 class="text-lg font-bold text-slate-800">Upload Excel Sheet</h3>
                    <p class="text-xs text-slate-400 mt-1 font-medium">or drag and drop</p>
                    <p class="text-[11px] text-slate-400 mt-2">Supported formats: .xlsx, .xls, .csv</p>

                    <button type="button" onclick="document.getElementById('excelFileInput').click()" 
                        class="mt-4 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition shadow">
                        Select File
                    </button>
                </div>
            </div>

            <!-- Tip Box: Explicit Bulk Import Columns -->
            <div class="bg-sky-50 border border-sky-200 rounded-2xl p-4 text-xs text-sky-900">
                <div class="flex items-start space-x-2.5">
                    <div class="w-6 h-6 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center flex-shrink-0 mt-0.5 font-bold">
                        <i class="fa-solid fa-circle-info text-xs"></i>
                    </div>
                    <div class="space-y-1.5 leading-relaxed w-full">
                        <p class="font-bold text-slate-900 text-xs">
                            Make sure your Excel (.xlsx, .xls) or CSV sheet includes the following columns:
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-[11px] text-slate-700 pt-0.5">
                            <div><strong class="text-slate-900">• Beneficiary Name:</strong> Name / Banking Name</div>
                            <div><strong class="text-slate-900">• Account Number:</strong> Account No / A/C No</div>
                            <div><strong class="text-slate-900">• Amount:</strong> Transaction Amount (INR ₹)</div>
                            <div><strong class="text-slate-900">• UTR / Reference:</strong> UTR / Transaction ID</div>
                            <div><strong class="text-slate-900">• IFSC Code:</strong> Bank IFSC (e.g. HDFC0001535)</div>
                            <div><strong class="text-slate-900">• Transaction Type:</strong> IMPS / NEFT / RTGS / UPI <span class="text-slate-400">(optional)</span></div>
                            <div><strong class="text-slate-900">• Status:</strong> Successful / Payment Successful <span class="text-slate-400">(optional)</span></div>
                            <div><strong class="text-slate-900">• Date &amp; Time:</strong> Payment Date <span class="text-slate-400">(optional)</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Post-Upload Compact Summary Bar -->
        <div id="postUploadSummaryBar" class="hidden bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 mt-6">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg flex-shrink-0">
                    <i class="fa-solid fa-file-excel"></i>
                </div>
                <div>
                    <div class="text-xs font-bold text-slate-900 flex items-center space-x-2">
                        <span id="uploadedFileTitle" class="truncate max-w-xs sm:max-w-md">data.xlsx</span>
                        <span id="uploadedFileBadge" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-200/80 text-emerald-900">0 Slips</span>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-0.5">Slips parsed successfully. Choose slip style, preview, and export below.</div>
                </div>
            </div>
            <button type="button" onclick="resetBulkUpload()" class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-semibold shadow-sm transition flex items-center space-x-1.5 self-start sm:self-center">
                <i class="fa-solid fa-arrow-rotate-right text-xs"></i>
                <span>Replace File</span>
            </button>
        </div>
    </div>

    <!-- Parsed Data Preview & Batch Actions Card (Hidden initially) -->
    <div id="batchProcessCard" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hidden space-y-5">
        
        <!-- Batch Control Toolbar -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-lg font-bold text-slate-900 flex items-center space-x-2">
                    <span id="batchCountBadge" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100 text-sky-800">0 Slips</span>
                    <span id="batchFileName" class="text-sm font-semibold text-slate-600 truncate max-w-xs">filename.xlsx</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Total Amount: <strong id="batchTotalAmount" class="text-slate-900">₹ 0.00</strong></p>
            </div>

            <!-- Receipt Style Selector for Bulk -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center space-x-1.5 text-xs text-slate-600 mr-2">
                    <span class="font-bold">Slip Style:</span>
                    <select id="bulkTemplateChoice" class="bg-slate-100 border border-slate-200 text-xs font-semibold rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-sky-500">
                        <option value="template3" selected>Style 3: Green Transfer Card (Default)</option>
                        <option value="template2">Style 2: Peach Slip (Peach)</option>
                        <option value="template1">Style 1: Blue Modern Receipt</option>
                    </select>
                </div>

                <!-- Batch Actions: Both Image ZIP and PDF -->
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

                <button type="button" onclick="importBatchAsContacts()" 
                    class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold rounded-xl transition flex items-center space-x-1.5" title="Save beneficiaries into Contacts directory">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>Save as Contacts</span>
                </button>
            </div>
        </div>

        <!-- Parsed Rows Table -->
        <div class="overflow-x-auto max-h-96 overflow-y-auto border border-slate-100 rounded-xl">
            <table class="w-full text-left text-xs" id="parsedDataTable">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold sticky top-0 border-b border-slate-200 shadow-sm z-10">
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
                <tbody id="parsedDataBody" class="divide-y divide-slate-100">
                    <!-- Populated dynamically via JS -->
                </tbody>
            </table>
        </div>

        <!-- Progress Bar for ZIP generation -->
        <div id="zipProgressWrapper" class="hidden bg-slate-50 p-4 rounded-xl border border-slate-200">
            <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1.5">
                <span id="zipProgressLabel">Generating receipt PNGs: 0 / 0</span>
                <span id="zipProgressPercent">0%</span>
            </div>
            <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                <div id="zipProgressBar" class="bg-sky-600 h-2.5 rounded-full transition-all duration-150" style="width: 0%"></div>
            </div>
        </div>

    </div>

</div>

<!-- Single Slip Preview Modal -->
<div id="previewModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl relative border border-slate-100">
        <button type="button" onclick="closePreviewModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-lg">
            <i class="fa-solid fa-times"></i>
        </button>
        <h3 class="text-base font-bold text-slate-900 mb-4 flex items-center space-x-2">
            <i class="fa-solid fa-magnifying-glass text-sky-600"></i>
            <span>Receipt Preview</span>
        </h3>
        <div id="modalReceiptContainer" class="p-4 bg-slate-50 rounded-xl flex justify-center overflow-x-auto">
            <!-- Dynamic Receipt Clone -->
        </div>
        <div class="mt-5 flex flex-wrap justify-end gap-2">
            <button type="button" onclick="closePreviewModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Close</button>
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
<div id="batchHiddenContainer" style="position: absolute; left: -9999px; top: -9999px; width: 440px;">
    <!-- Cloned template here during batch canvas rendering -->
</div>

<!-- Bulk Builder Javascript -->
<script src="assets/js/bulk-builder.js"></script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>

