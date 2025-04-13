<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: /santeplus/login.php');
    exit;
}

// Récupérer le rôle de l'utilisateur
$role = $_SESSION['role'];
?>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <img src="/santeplus/assets/img/logo.png" alt="Logo SantéPlus">
            <h1>SantéPlus</h1>
        </div>
        <button class="toggle-btn">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <?php if ($role === 'admin'): ?>
                <li>
                    <a href="/santeplus/admin/dashboard.php" class="sidebar-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="nav-text">Tableau de bord</span>
                    </a>
                </li>
                <li>
                    <a href="/santeplus/admin/gestion_comptes.php" class="sidebar-link">
                        <i class="fas fa-users-cog"></i>
                        <span class="nav-text">Gestion des comptes</span>
                    </a>
                </li>
                <li>
                    <a href="/santeplus/admin/rapports_hemodialyse.php" class="sidebar-link">
                        <i class="fas fa-file-medical"></i>
                        <span class="nav-text">Rapports d'hémodialyse</span>
                    </a>
                </li>
                <li>
                    <a href="/santeplus/admin/messagerie.php" class="sidebar-link">
                        <i class="fas fa-envelope"></i>
                        <span class="nav-text">Messagerie</span>
                    </a>
                </li>
            <?php elseif ($role === 'medecin'): ?>
                <li>
                    <a href="/santeplus/medecin/dashboard.php" class="sidebar-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="nav-text">Tableau de bord</span>
                    </a>
                </li>
                <li>
                    <a href="/santeplus/medecin/rapports.php" class="sidebar-link">
                        <i class="fas fa-file-medical"></i>
                        <span class="nav-text">Mes rapports</span>
                    </a>
                </li>
                <li>
                    <a href="/santeplus/medecin/messages.php" class="sidebar-link">
                        <i class="fas fa-envelope"></i>
                        <span class="nav-text">Messages</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['prenom'], 0, 1) . substr($_SESSION['nom'], 0, 1)); ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></div>
                <div class="user-role"><?php echo ucfirst($role); ?></div>
            </div>
        </div>
        <div class="action-buttons">
            <button class="action-btn" onclick="window.location.href='/santeplus/profile.php'">
                <i class="fas fa-user"></i>
            </button>
            <button class="action-btn" onclick="window.location.href='/santeplus/settings.php'">
                <i class="fas fa-cog"></i>
            </button>
            <button class="action-btn" onclick="window.location.href='/santeplus/logout.php'">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        </div>
    </div>
</div>

<div class="sidebar-overlay"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.toggle-btn');
    const mainContent = document.querySelector('.main-content');
    const overlay = document.querySelector('.sidebar-overlay');

    // Toggle sidebar
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
    });

    // Close sidebar on overlay click (mobile)
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    });

    // Responsive behavior
    function handleResize() {
        if (window.innerWidth <= 992) {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
        }
    }

    window.addEventListener('resize', handleResize);
    handleResize();
});
</script>

<!-- Notifications Panel -->
<div class="notifications-panel">
    <div class="notifications-header">
        <h6>Notifications</h6>
        <button class="close-notifications">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="notifications-content">
        <?php if ($notifications_count > 0): ?>
            <a href="/santeplus/<?php echo $_SESSION['role']; ?>/messagerie.php" class="notification-item">
                <i class="fas fa-envelope me-2"></i>
                <span>Vous avez <?php echo $notifications_count; ?> message(s) non lu(s)</span>
            </a>
        <?php else: ?>
            <div class="notification-item text-muted">
                Aucune nouvelle notification
            </div>
        <?php endif; ?>
    </div>
</div> 