<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?php echo getCsrfToken(); ?>">
<title><?php echo isset($page_title) ? $page_title . ' - Jamiah Abat' : 'Jamiah Abat'; ?></title>

<!-- Fonts: Inter & Arabic -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <!-- PWA Manifest & Meta -->
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Jami'ah Abat">
    <link rel="apple-touch-icon" href="assets/img/icon-192.png">
    <meta name="theme-color" content="#10b981">

    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('service-worker.js').then(function(registration) {
                    console.log('ServiceWorker registration successful');
                }, function(err) {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Global Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>if(localStorage.getItem('sidebarCollapsed')==='true'){document.write('<style>#sidebar{display:none!important}</style>');}</script>
    
    <script src="https://cdn.tailwindcss.com"></script>

<style>
    /* Clean Background */
    body {
        background-color: #f8fafc; /* Slate-50 */
    }

    body.dark {
        background-color: #0f172a; /* Slate-900 */
    }

    /* Glassmorphism Utilities */
    .glass {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.5);
    }
    
    .glass-dark {
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(156, 163, 175, 0.5); border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: rgba(107, 114, 128, 0.8); }

    /* Fluent Transitions */
    .fluent-transition { transition: all 0.2s cubic-bezier(0, 0, 0.2, 1); }
</style>

<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: { 
                    primary: '#064E3B', 
                    secondary: '#059669', 
                    accent: '#D97706',
                    glass: 'rgba(255, 255, 255, 0.7)',
                    'glass-dark': 'rgba(15, 23, 42, 0.6)',
                },
                fontFamily: { 
                    sans: ['Inter', 'sans-serif'],
                    arabic: ['Amiri', 'serif']
                },
                boxShadow: {
                    'glass': '0 8px 32px 0 rgba(31, 38, 135, 0.07)',
                }
            }
        }
    }
</script>
