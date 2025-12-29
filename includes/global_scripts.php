<!-- Floating Show Sidebar Button (Left Center or Bottom Left) -->
<button id="floatingSidebarBtn" onclick="toggleSidebarInternal()" 
    class="fixed bottom-20 left-6 w-12 h-12 bg-slate-800 text-white rounded-full shadow-lg shadow-slate-900/30 hidden items-center justify-center transition-all duration-300 z-50 hover:bg-slate-700 hover:scale-110" title="Tampilkan Sidebar">
    <i class="fa-solid fa-bars text-lg"></i>
</button>

<script>
// Global Sidebar Toggle Logic with Cool Refresh
function toggleSidebarInternal() {
    // Get current state
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    // Toggle state
    localStorage.setItem('sidebarCollapsed', isCollapsed ? 'false' : 'true');
    
    // Add blur overlay for cool effect
    const overlay = document.createElement('div');
    overlay.id = 'pageTransitionOverlay';
    overlay.style.cssText = `
        position: fixed;
        inset: 0;
        background: rgba(255,255,255,0.9);
        backdrop-filter: blur(10px);
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    `;
    overlay.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-4xl text-blue-500"></i>';
    document.body.appendChild(overlay);
    
    // Trigger animation
    requestAnimationFrame(() => {
        overlay.style.opacity = '1';
    });
    
    // Refresh after animation
    setTimeout(() => {
        location.reload();
    }, 300);
}

// Apply sidebar state on load (before paint)
(function() {
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        const sidebar = document.getElementById('sidebar');
        const floatBtn = document.getElementById('floatingSidebarBtn');
        if (sidebar) {
            sidebar.classList.add('md:hidden');
        }
        if (floatBtn) {
            floatBtn.classList.remove('hidden');
            floatBtn.classList.add('flex');
        }
    }
})();
</script>

<script>
// --- GLOBAL CSRF PROTECTION FOR AJAX ---
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // 1. Intercept Fetch API
    const originalFetch = window.fetch;
    window.fetch = function(url, options) {
        options = options || {};
        // Only add headers if method is not GET/HEAD
        if (options.method && options.method.toUpperCase() !== 'GET' && options.method.toUpperCase() !== 'HEAD') {
            options.headers = options.headers || {};
            // If headers is an object, regular assignment
            if (options.headers instanceof Headers) {
                options.headers.append('X-CSRF-TOKEN', csrfToken);
            } else {
                options.headers['X-CSRF-TOKEN'] = csrfToken;
            }
        }
        return originalFetch(url, options);
    };

    // 2. Intercept FormData/XHR (if used anywhere manually)
    // Most modern apps use fetch, but just in case of jQuery or raw XHR
    const originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, url) {
        this._method = method;
        return originalOpen.apply(this, arguments);
    };
    
    const originalSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.send = function(data) {
        if (this._method && this._method.toUpperCase() !== 'GET' && this._method.toUpperCase() !== 'HEAD') {
            this.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        }
        return originalSend.apply(this, data);
    };
});
</script>
