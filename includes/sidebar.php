<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: /santeplus/login.php');
    exit;
}

// Récupérer le rôle de l'utilisateur
$role = $_SESSION['role'];

// Récupérer la page courante pour le menu actif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <img src="/santeplus/assets/img/logo.png" alt="Logo SantéPlus" class="logo-img">
            <h1 class="logo-text">SantéPlus</h1>
        </div>
        <div class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </div>
    </div>

    <div class="sidebar-search">
        <div class="search-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Rechercher...">
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <?php if ($role === 'admin'): ?>
                <li>
                    <a href="/santeplus/admin/dashboard.php" 
                       class="sidebar-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>"
                       data-tooltip="Tableau de bord">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="nav-text">Tableau de bord</span>
                    </a>
                </li>
                <li>
                    <a href="/santeplus/admin/gestion_comptes.php" 
                       class="sidebar-link <?php echo $current_page === 'gestion_comptes.php' ? 'active' : ''; ?>"
                       data-tooltip="Gestion des comptes">
                        <i class="fas fa-users-cog"></i>
                        <span class="nav-text">Gestion des comptes</span>
                    </a>
                </li>
                <li>
                    <a href="/santeplus/admin/rapports_hemodialyse.php" 
                       class="sidebar-link <?php echo $current_page === 'rapports_hemodialyse.php' ? 'active' : ''; ?>"
                       data-tooltip="Rapports d'hémodialyse">
                        <i class="fas fa-file-medical"></i>
                        <span class="nav-text">Rapports d'hémodialyse</span>
                    </a>
                </li>
                <li>
                    <a href="/santeplus/admin/messagerie.php" 
                       class="sidebar-link <?php echo $current_page === 'messagerie.php' ? 'active' : ''; ?>"
                       data-tooltip="Messagerie">
                        <i class="fas fa-envelope"></i>
                        <span class="nav-text">Messages</span>
                    </a>
                </li>
                <li>
                    <a href="#" 
                       class="sidebar-link notifications-link"
                       data-tooltip="Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="nav-text">Notifications</span>
                        <?php if (isset($notifications_count) && $notifications_count > 0): ?>
                            <span class="badge"><?php echo $notifications_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php elseif ($role === 'medecin'): ?>
                <li>
                    <a href="/santeplus/medecin/dashboard.php" 
                       class="sidebar-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>"
                       data-tooltip="Tableau de bord">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="nav-text">Tableau de bord</span>
                    </a>
                </li>
                <li>
                    <a href="/santeplus/medecin/rapports.php" 
                       class="sidebar-link <?php echo $current_page === 'rapports.php' ? 'active' : ''; ?>"
                       data-tooltip="Mes rapports">
                        <i class="fas fa-file-medical"></i>
                        <span class="nav-text">Mes rapports</span>
                    </a>
                </li>
                <li>
                    <a href="/santeplus/medecin/messages.php" 
                       class="sidebar-link <?php echo $current_page === 'messages.php' ? 'active' : ''; ?>"
                       data-tooltip="Messages">
                        <i class="fas fa-envelope"></i>
                        <span class="nav-text">Messages</span>
                        <?php if (isset($notifications_count) && $notifications_count > 0): ?>
                            <span class="badge"><?php echo $notifications_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Notifications Panel -->
    <div class="notifications-panel">
        <div class="notifications-header">
            <h6>Notifications</h6>
            <button class="close-notifications">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="notifications-content">
            <?php if (isset($notifications_count) && $notifications_count > 0): ?>
                <a href="/santeplus/<?php echo $_SESSION['role']; ?>/messagerie.php" class="notification-item">
                    <i class="fas fa-envelope"></i>
                    <span>Vous avez <?php echo $notifications_count; ?> message(s) non lu(s)</span>
                </a>
            <?php else: ?>
                <div class="notifications-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>Aucune nouvelle notification</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="sidebar-footer">
        <div class="user-info" onclick="toggleUserMenu(event)">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['prenom'], 0, 1) . substr($_SESSION['nom'], 0, 1)); ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></div>
                <div class="user-role"><?php echo ucfirst($role); ?></div>
            </div>
            <i class="fas fa-chevron-up user-menu-arrow"></i>
        </div>
        
        <div class="user-menu">
            <a href="/santeplus/profile.php" class="user-menu-item">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            <a href="/santeplus/settings.php" class="user-menu-item">
                <i class="fas fa-cog"></i>
                <span>Paramètres</span>
            </a>
            <div class="divider"></div>
            <a href="/santeplus/logout.php" class="user-menu-item text-danger">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </div>
</div>

<div class="sidebar-overlay"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');
    const overlay = document.querySelector('.sidebar-overlay');
    const searchInput = document.querySelector('.search-input');
    const menuItems = document.querySelectorAll('.sidebar-link');

    if (sidebar && sidebarToggle) {
        // Restore sidebar state
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }

    // Toggle sidebar
        function toggleSidebar(e) {
            if (e) e.preventDefault();
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
            
            // Animate toggle button
            const icon = sidebarToggle.querySelector('i');
            if (icon) {
                icon.style.transform = sidebar.classList.contains('collapsed') ? 'rotate(180deg)' : 'rotate(0)';
            }
            
            // Save state to localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }

        // Add click event listener
        sidebarToggle.addEventListener('click', toggleSidebar);

        // Handle responsive behavior
        function handleResize() {
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
                localStorage.setItem('sidebarCollapsed', 'false');
            } else {
                const shouldBeCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                sidebar.classList.toggle('collapsed', shouldBeCollapsed);
                mainContent.classList.toggle('expanded', shouldBeCollapsed);
            }
        }

        window.addEventListener('resize', handleResize);
        handleResize();
    }

    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            
            menuItems.forEach(item => {
                const text = item.querySelector('.nav-text').textContent.toLowerCase();
                const shouldShow = text.includes(searchTerm);
                item.parentElement.style.display = shouldShow ? 'block' : 'none';
            });
        });
    }

    // Close sidebar on overlay click (mobile)
    if (overlay) {
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    });
    }

    // Add hover animations for tooltips
    menuItems.forEach(item => {
        item.addEventListener('mouseenter', () => {
            if (sidebar.classList.contains('collapsed')) {
                const tooltip = item.getAttribute('data-tooltip');
                if (tooltip) {
                    showTooltip(item, tooltip);
                }
            }
        });

        item.addEventListener('mouseleave', () => {
            hideTooltip();
        });
    });

    // Notifications handling
    const notificationsPanel = document.querySelector('.notifications-panel');
    const notificationLinks = document.querySelectorAll('.notifications-link');
    const closeNotificationsBtn = document.querySelector('.close-notifications');

    function toggleNotifications(event) {
        if (event) {
            event.preventDefault();
        }
        notificationsPanel.classList.toggle('show');
        
        // Close user menu if open
        const userMenu = document.querySelector('.user-menu');
        if (userMenu && userMenu.classList.contains('show')) {
            userMenu.classList.remove('show');
            document.querySelector('.user-menu-arrow').style.transform = 'rotate(0)';
        }
    }

    // Add click event to notification links
    notificationLinks.forEach(link => {
        link.addEventListener('click', toggleNotifications);
    });

    // Close notifications panel
    if (closeNotificationsBtn) {
        closeNotificationsBtn.addEventListener('click', toggleNotifications);
    }

    // Close notifications when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.notifications-panel') && 
            !event.target.closest('.notifications-link')) {
            notificationsPanel.classList.remove('show');
        }
    });
});

// User menu toggle
function toggleUserMenu(event) {
    event.stopPropagation();
    const userMenu = document.querySelector('.user-menu');
    const arrow = document.querySelector('.user-menu-arrow');
    
    userMenu.classList.toggle('show');
    arrow.style.transform = userMenu.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0)';
    
    // Close menu when clicking outside
    document.addEventListener('click', function closeMenu(e) {
        if (!e.target.closest('.user-menu') && !e.target.closest('.user-info')) {
            userMenu.classList.remove('show');
            arrow.style.transform = 'rotate(0)';
            document.removeEventListener('click', closeMenu);
        }
    });
}

// Tooltip functions
function showTooltip(element, text) {
    const tooltip = document.createElement('div');
    tooltip.className = 'sidebar-tooltip';
    tooltip.textContent = text;
    
    const rect = element.getBoundingClientRect();
    tooltip.style.top = rect.top + (rect.height / 2) - 10 + 'px';
    tooltip.style.left = rect.right + 10 + 'px';
    
    document.body.appendChild(tooltip);
    setTimeout(() => tooltip.classList.add('show'), 1);
}

function hideTooltip() {
    const tooltip = document.querySelector('.sidebar-tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}
</script>