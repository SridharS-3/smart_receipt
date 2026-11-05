<?php
// includes/sidebar.php
$nav_items = [
    [
        'title' => 'Dashboard',
        'url' => 'dashboard.php',
        'icon' => 'fa-solid fa-chart-pie',
        'badge' => null
    ],
    [
        'title' => 'Build a Receipt',
        'url' => 'receipt-generator.php',
        'icon' => 'fa-solid fa-file-invoice-dollar',
        'badge' => null
    ],
    [
        'title' => 'Contacts Directory',
        'url' => 'contacts.php',
        'icon' => 'fa-solid fa-address-book',
        'badge' => null
    ],
    [
        'title' => 'Receipt History',
        'url' => 'history.php',
        'icon' => 'fa-solid fa-clock-rotate-left',
        'badge' => null
    ]
];
?>

<!-- Desktop Sidebar / Mobile Drawer with Light & Dark Theme Support -->
<aside id="appSidebar" class="w-64 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-r border-slate-200 dark:border-slate-800 flex-shrink-0 flex flex-col justify-between hidden md:flex z-30 transition-all duration-200 h-screen sticky top-0 overflow-hidden">
    <div class="p-5 flex flex-col h-full justify-between">
        <div>
            <!-- Logo Branding -->
            <div class="flex items-center space-x-3 pb-6 border-b border-slate-100 dark:border-slate-800">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-600 to-cyan-400 flex items-center justify-center text-white text-xl shadow-md shadow-sky-600/30">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div>
                    <span class="font-extrabold text-slate-900 dark:text-white text-lg tracking-tight block">SmartReceipt</span>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium uppercase tracking-wider">Payment System</span>
                </div>
            </div>

            <!-- Navigation Menu -->
            <nav class="mt-6 space-y-1.5">
                <div class="text-[11px] uppercase font-bold text-slate-400 dark:text-slate-500 px-3 pb-2 tracking-wider">Main Navigation</div>
                <?php foreach ($nav_items as $item): 
                    $is_active = ($current_page === $item['url']);
                ?>
                    <a href="<?= $item['url'] ?>" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150 <?= $is_active ? 'bg-sky-600 text-white shadow-md shadow-sky-600/30 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80' ?>">
                        <div class="flex items-center space-x-3">
                            <i class="<?= $item['icon'] ?> text-base w-5 text-center <?= $is_active ? 'text-white' : 'text-slate-400 dark:text-slate-500' ?>"></i>
                            <span><?= htmlspecialchars($item['title']) ?></span>
                        </div>
                        <?php if (!empty($item['badge'])): ?>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full <?= $is_active ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700' ?>">
                                <?= $item['badge'] ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <!-- Bottom Area: Clean Logout Button (Engine Status removed) -->
        <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
            <!-- Sign Out Button -->
            <a href="logout.php" onclick="return confirm('Do you want to log out of the admin panel?');" class="flex items-center justify-center space-x-2 w-full py-2.5 px-3 rounded-xl text-sm font-bold text-rose-600 dark:text-rose-300 bg-rose-50 dark:bg-rose-500/10 hover:bg-rose-100 dark:hover:bg-rose-500/20 border border-rose-200 dark:border-rose-500/20 transition shadow-sm">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</aside>

<!-- Mobile Overlay Backdrop -->
<div id="sidebarBackdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-20 hidden md:hidden"></div>
