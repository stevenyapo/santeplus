document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    const overlay = document.querySelector('.sidebar-overlay');
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    const sidebarActionBtns = document.querySelectorAll('.sidebar-action-btn');

    let isHovering = false;
    let hoverTimeout;

    // Fonction pour basculer la sidebar
    function toggleSidebar() {
        if (!isHovering) {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Sauvegarder l'état dans localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        }
    }

    // Gérer le survol de la sidebar
    function handleSidebarHover() {
        if (sidebar.classList.contains('collapsed')) {
            isHovering = true;
            clearTimeout(hoverTimeout);
            
            // Utiliser les classes CSS au lieu de style direct
            sidebar.classList.add('hover');
            mainContent.style.marginLeft = '250px';
            
            // Afficher les éléments avec une transition
            document.querySelectorAll('.sidebar-brand span, .sidebar-link span, .user-details, .sidebar-actions').forEach(el => {
                el.style.display = 'flex';
                setTimeout(() => {
                    el.style.opacity = '1';
                }, 50);
            });
        }
    }

    function handleSidebarLeave() {
        if (sidebar.classList.contains('collapsed')) {
            isHovering = false;
            
            hoverTimeout = setTimeout(() => {
                if (!isHovering) {
                    sidebar.classList.remove('hover');
                    mainContent.style.marginLeft = '70px';
                    
                    // Cacher les éléments avec une transition
                    document.querySelectorAll('.sidebar-brand span, .sidebar-link span, .user-details, .sidebar-actions').forEach(el => {
                        el.style.opacity = '0';
                        setTimeout(() => {
                            el.style.display = 'none';
                        }, 300);
                    });
                }
            }, 200);
        }
    }

    // Fonction pour gérer l'affichage mobile
    function toggleMobileSidebar() {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
        document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
    }

    // Restaurer l'état de la sidebar au chargement
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === 'true') {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('expanded');
    }

    // Ajouter les tooltips aux liens
    sidebarLinks.forEach(link => {
        const text = link.querySelector('span')?.textContent;
        if (text) {
            link.setAttribute('data-tooltip', text);
        }
    });

    // Ajouter les tooltips aux boutons d'action
    sidebarActionBtns.forEach(btn => {
        const icon = btn.querySelector('i');
        if (icon) {
            const tooltip = icon.getAttribute('title') || icon.getAttribute('aria-label');
            if (tooltip) {
                btn.setAttribute('data-tooltip', tooltip);
            }
        }
    });

    // Gérer le clic sur le bouton de bascule
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleSidebar);
    }

    // Gérer le clic sur l'overlay en mode mobile
    if (overlay) {
        overlay.addEventListener('click', toggleMobileSidebar);
    }

    // Gérer le survol de la sidebar
    sidebar.addEventListener('mouseenter', handleSidebarHover);
    sidebar.addEventListener('mouseleave', handleSidebarLeave);

    // Gérer le clic sur les liens
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Supprimer la classe active de tous les liens
            sidebarLinks.forEach(l => l.classList.remove('active'));
            // Ajouter la classe active au lien cliqué
            this.classList.add('active');
            
            // En mode mobile, fermer la sidebar après le clic
            if (window.innerWidth <= 768) {
                toggleMobileSidebar();
            }
        });
    });

    // Gérer le survol des éléments
    const hoverableElements = document.querySelectorAll('.sidebar-link, .sidebar-action-btn, .user-info');
    hoverableElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.3s ease';
        });
    });

    // Gérer le redimensionnement de la fenêtre
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        }, 250);
    });

    // Ajouter une animation de chargement
    document.body.classList.add('sidebar-loaded');
}); 