<?php
require_once __DIR__ . '/init.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Script pour appliquer le thème avant le chargement du CSS -->
    <script>
        (function () {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SantéPlus - Centre de Santé</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏥</text></svg>">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo url('assets/css/theme.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/background.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/themes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/dark-mode.css'); ?>">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap Bundle avec Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- GSAP for animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo url('js/theme.js'); ?>"></script>
</head>
<body>
    <!-- Preloader -->
    <div id="preloader" style="
        position: fixed;
        inset: 0;
        background: #121212;
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.3s ease;
    "></div>

    <!-- Custom Cursor Elements -->
    <div class="custom-cursor"></div>
    <div class="cursor-glow"></div>

    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Inclure la sidebar pour les utilisateurs connectés -->
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <!-- Bouton pour afficher/masquer la sidebar -->
        <div class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </div>
    <?php endif; ?>

    <!-- Bouton de basculement de thème -->
    <button id="themeSwitcher" class="theme-toggle" title="Changer le thème">
        <i class="fas fa-moon"></i>
    </button>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid py-4">

    <!-- Custom Cursor Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cursor = document.querySelector('.custom-cursor');
            const cursorGlow = document.querySelector('.cursor-glow');
            
            if (cursor && cursorGlow) {
                document.addEventListener('mousemove', function(e) {
                    // Update cursor position
                    gsap.to(cursor, {
                        x: e.clientX - 10,
                        y: e.clientY - 10,
                        duration: 0.1
                    });
                    
                    // Update glow position with slight delay
                    gsap.to(cursorGlow, {
                        x: e.clientX - 20,
                        y: e.clientY - 20,
                        duration: 0.3
                    });
                });

                // Add hover effect on interactive elements
                const interactiveElements = document.querySelectorAll('a, button, .nav-link, .dropdown-item');
                interactiveElements.forEach(element => {
                    element.addEventListener('mouseenter', () => {
                        cursor.style.transform = 'scale(1.5)';
                        cursorGlow.style.transform = 'scale(1.5)';
                    });
                    
                    element.addEventListener('mouseleave', () => {
                        cursor.style.transform = 'scale(1)';
                        cursorGlow.style.transform = 'scale(1)';
                    });
                });
            }

            // Sidebar Toggle
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const overlay = document.querySelector('.sidebar-overlay');

            if (sidebar && sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                });

                // Close sidebar on overlay click
                if (overlay) {
                    overlay.addEventListener('click', function() {
                        sidebar.classList.remove('show');
                        overlay.classList.remove('show');
                    });
                }

                // Handle responsive behavior
                function handleResponsive() {
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('collapsed');
                        mainContent.classList.remove('expanded');
                    }
                }

                window.addEventListener('resize', handleResponsive);
                handleResponsive();
            }

            // Gestion du preloader
            const preloader = document.getElementById('preloader');
            if (preloader) {
                // Afficher le preloader pendant le chargement
                preloader.style.opacity = '1';
                
                // Masquer le preloader une fois la page chargée
                window.addEventListener('load', () => {
                    preloader.style.opacity = '0';
                    setTimeout(() => {
                        preloader.remove();
                    }, 300);
                });
            }
        });
    </script>
</body>
</html> 