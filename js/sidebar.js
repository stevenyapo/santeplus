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