<?php
// includes/header.php
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Smart Receipt Generator');
}
$current_page = basename($_SERVER['PHP_SELF']);
$admin_username = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(PAGE_TITLE) ?> - SmartReceipt</title>
    
    <!-- Theme Initializer (Default: Light Theme) -->
    <script>
        if (localStorage.getItem('smart_theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS with Dark Mode Class Strategy -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
                        inter: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                        },
                        accent: {
                            500: '#f97316',
                            600: '#ea580c',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Vendor Libraries (local offline copies) -->
    <script src="assets/js/vendor/xlsx.full.min.js"></script>
    <script src="assets/js/vendor/html2canvas.min.js"></script>
    <script src="assets/js/vendor/jszip.min.js"></script>
    <script src="assets/js/vendor/FileSaver.min.js"></script>
    <script src="assets/js/vendor/jspdf.umd.min.js"></script>
    
    <!-- Custom Receipt Styles -->
    <link rel="stylesheet" href="assets/css/receipts.css">
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans antialiased h-screen flex flex-col md:flex-row overflow-hidden transition-colors duration-150">

    <!-- Mobile Top Navigation Header -->
    <div class="md:hidden bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-4 py-3 flex items-center justify-between sticky top-0 z-40 flex-shrink-0">
        <div class="flex items-center space-x-2">
            <div class="w-9 h-9 rounded-lg bg-sky-600 flex items-center justify-center text-white font-bold text-lg shadow-sm">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <span class="font-bold text-slate-900 dark:text-white text-lg">SmartReceipt</span>
        </div>
        <div class="flex items-center space-x-2">
            <!-- Mobile Theme Toggle -->
            <button type="button" onclick="toggleAppTheme()" class="p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800" title="Toggle Theme">
                <i class="fa-solid fa-moon dark:hidden text-base"></i>
                <i class="fa-solid fa-sun hidden dark:block text-amber-400 text-base"></i>
            </button>
            <button id="mobileMenuBtn" class="text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white p-2 rounded-lg focus:outline-none">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>
        </div>
    </div>

    <!-- Sidebar Component -->
    <?php include_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
        <!-- Top bar for desktop -->
        <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-6 py-3.5 hidden md:flex items-center justify-between sticky top-0 z-30 flex-shrink-0 transition-colors duration-150">
            <div class="flex items-center space-x-3">
                <h1 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight"><?= htmlspecialchars(PAGE_TITLE) ?></h1>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-xs text-slate-500 dark:text-slate-400 hidden lg:block">
                    <i class="fa-regular fa-calendar-days mr-1 text-slate-400"></i>
                    <span id="currentDateDisplay"><?= date('D, d M Y') ?></span>
                </div>
                
                <div class="h-5 w-px bg-slate-200 dark:bg-slate-800 hidden lg:block"></div>

                <!-- Theme Toggle Button -->
                <button type="button" onclick="toggleAppTheme()" class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition" title="Switch Theme (Light / Dark)">
                    <span class="dark:hidden flex items-center space-x-1.5">
                        <i class="fa-solid fa-moon text-slate-600 text-xs"></i>
                        <span>Dark</span>
                    </span>
                    <span class="hidden dark:flex items-center space-x-1.5 text-amber-400">
                        <i class="fa-solid fa-sun text-xs"></i>
                        <span class="text-slate-200">Light</span>
                    </span>
                </button>

                <div class="h-5 w-px bg-slate-200 dark:bg-slate-800 hidden lg:block"></div>

                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center font-bold text-xs uppercase">
                        <?= strtoupper(substr($admin_username, 0, 2)) ?>
                    </div>
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200"><?= htmlspecialchars($admin_username) ?></span>
                    <a href="logout.php" title="Sign Out" class="text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 p-1.5 transition rounded-md hover:bg-rose-50 dark:hover:bg-rose-950/40" onclick="return confirm('Are you sure you want to log out?');">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Body Container -->
        <main class="flex-1 p-4 md:p-6 lg:p-8">
