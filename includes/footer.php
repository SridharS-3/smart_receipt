<?php
// includes/footer.php
?>
        </main>
        
        <!-- Footer -->
        <footer class="bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 px-6 py-4 text-center text-xs text-slate-500 dark:text-slate-400 transition-colors duration-150">
            <div class="max-w-7xl mx-auto text-center">
                <span>&copy; <?= date('Y') ?> SmartReceipts. All rights reserved.</span>
            </div>
        </footer>
    </div>

    <!-- Mobile Drawer & Global Scripts -->
    <script>
        // Global Theme Toggle Function
        function toggleAppTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('smart_theme', isDark ? 'dark' : 'light');
            if (typeof showNotification === 'function') {
                showNotification(`Switched to ${isDark ? 'Dark' : 'Light'} theme`, 'info');
            }
        }
        // Mobile Sidebar Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const appSidebar = document.getElementById('appSidebar');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');

        if (mobileMenuBtn && appSidebar) {
            mobileMenuBtn.addEventListener('click', () => {
                const isHidden = appSidebar.classList.contains('hidden');
                if (isHidden) {
                    appSidebar.classList.remove('hidden');
                    appSidebar.classList.add('fixed', 'inset-y-0', 'left-0', 'z-50', 'w-72', 'shadow-2xl');
                    sidebarBackdrop.classList.remove('hidden');
                } else {
                    appSidebar.classList.add('hidden');
                    appSidebar.classList.remove('fixed', 'inset-y-0', 'left-0', 'z-50', 'w-72', 'shadow-2xl');
                    sidebarBackdrop.classList.add('hidden');
                }
            });

            sidebarBackdrop.addEventListener('click', () => {
                appSidebar.classList.add('hidden');
                appSidebar.classList.remove('fixed', 'inset-y-0', 'left-0', 'z-50', 'w-72', 'shadow-2xl');
                sidebarBackdrop.classList.add('hidden');
            });
        }

        // Global Toast Notification Helper
        function showNotification(message, type = 'success') {
            const container = document.getElementById('toastContainer') || createToastContainer();
            const toast = document.createElement('div');
            
            const bgClass = type === 'success' ? 'bg-emerald-600 text-white' : (type === 'error' ? 'bg-rose-600 text-white' : 'bg-slate-800 text-white');
            const iconClass = type === 'success' ? 'fa-circle-check' : (type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info');
            
            toast.className = `flex items-center space-x-3 px-4 py-3 rounded-xl shadow-xl text-sm font-medium transition-all duration-300 transform translate-y-2 opacity-0 ${bgClass}`;
            toast.innerHTML = `
                <i class="fa-solid ${iconClass} text-lg"></i>
                <div class="flex-1">${message}</div>
                <button class="opacity-75 hover:opacity-100 p-1" onclick="this.parentElement.remove()">&times;</button>
            `;
            
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            }, 10);
            
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        function createToastContainer() {
            const div = document.createElement('div');
            div.id = 'toastContainer';
            div.className = 'fixed bottom-5 right-5 z-50 flex flex-col space-y-2 max-w-sm pointer-events-auto';
            document.body.appendChild(div);
            return div;
        }
    </script>
</body>
</html>

