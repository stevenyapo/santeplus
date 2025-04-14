// Gestionnaire de notifications
class NotificationManager {
    constructor() {
        this.notificationCount = 0;
        this.notifications = [];
        this.countElement = document.getElementById('notification-count');
        this.listElement = document.getElementById('notification-list');
        this.interval = null;
    }

    // Initialiser le gestionnaire de notifications
    init() {
        this.fetchNotifications();
        // Rafraîchir toutes les 30 secondes
        this.interval = setInterval(() => this.fetchNotifications(), 30000);
        
        // Gestionnaire de clic pour marquer comme lu
        if (this.listElement) {
            this.listElement.addEventListener('click', (e) => {
                const notifItem = e.target.closest('[data-notification-id]');
                if (notifItem) {
                    const notifId = notifItem.dataset.notificationId;
                    this.markAsRead(notifId);
                }
            });
        }
    }

    // Récupérer les notifications depuis le serveur
    async fetchNotifications() {
        try {
            const response = await fetch('ajax/get_notifications.php');
            const data = await response.json();
            
            if (data.success) {
                this.updateNotifications(data.notifications, data.unread_count);
            }
        } catch (error) {
            console.error('Erreur lors de la récupération des notifications:', error);
        }
    }

    // Mettre à jour l'affichage des notifications
    updateNotifications(notifications, count) {
        this.notifications = notifications;
        this.notificationCount = count;

        // Mettre à jour le compteur
        if (this.countElement) {
            this.countElement.textContent = count;
            this.countElement.style.display = count > 0 ? 'block' : 'none';
        }

        // Mettre à jour la liste
        if (this.listElement) {
            this.listElement.innerHTML = notifications.length > 0 
                ? notifications.map(notif => this.createNotificationHTML(notif)).join('')
                : '<li class="dropdown-item text-center">Aucune notification</li>';
        }
    }

    // Créer le HTML pour une notification
    createNotificationHTML(notification) {
        return `
            <li class="dropdown-item ${notification.is_read ? '' : 'unread'}" 
                data-notification-id="${notification.id}">
                <div class="d-flex align-items-center">
                    <div class="notification-icon me-3">
                        <i class="fas ${this.getIconForType(notification.type)}"></i>
                    </div>
                    <div class="notification-content">
                        <p class="mb-0">${notification.message}</p>
                        <small class="text-muted">${this.formatDate(notification.created_at)}</small>
                    </div>
                </div>
            </li>
        `;
    }

    // Obtenir l'icône en fonction du type de notification
    getIconForType(type) {
        const icons = {
            'info': 'fa-info-circle text-info',
            'success': 'fa-check-circle text-success',
            'warning': 'fa-exclamation-circle text-warning',
            'error': 'fa-times-circle text-danger',
            'default': 'fa-bell text-primary'
        };
        return icons[type] || icons.default;
    }

    // Formater la date
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Marquer une notification comme lue
    async markAsRead(notificationId) {
        try {
            const formData = new FormData();
            formData.append('notification_id', notificationId);

            const response = await fetch('ajax/mark_notification_read.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                // Mettre à jour localement
                this.fetchNotifications();
            }
        } catch (error) {
            console.error('Erreur lors du marquage de la notification:', error);
        }
    }

    // Nettoyer l'intervalle lors de la destruction
    destroy() {
        if (this.interval) {
            clearInterval(this.interval);
        }
    }
}

// Initialiser le gestionnaire de notifications au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    const notificationManager = new NotificationManager();
    notificationManager.init();
}); 