/**
 * Script pour l'interface d'administration de SantéPlus
 */
document.addEventListener('DOMContentLoaded', function() {
    // Améliorer l'apparence des boutons d'action
    enhanceActionButtons();
    
    // Initialiser les tooltips Bootstrap si présents
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});

/**
 * Améliore l'apparence et l'interaction des boutons d'action
 */
function enhanceActionButtons() {
    // Sélectionner tous les boutons d'action
    const actionButtons = document.querySelectorAll('.btn-action');
    
    // Pour chaque bouton
    actionButtons.forEach(button => {
        // Ajouter un effet de survol avec une animation
        button.addEventListener('mouseenter', function() {
            // Effet de pulsation pour l'icône
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transition = 'transform 0.3s ease';
                icon.style.transform = 'scale(1.2)';
            }
            
            // Effet d'élévation pour le bouton
            this.style.transform = 'translateY(-3px)';
            this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.2)';
        });
        
        // Réinitialiser les effets quand la souris quitte le bouton
        button.addEventListener('mouseleave', function() {
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transform = 'scale(1)';
            }
            
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 5px rgba(0, 0, 0, 0.1)';
        });
        
        // Ajouter un effet de clic
        button.addEventListener('mousedown', function() {
            this.style.transform = 'translateY(-1px)';
            this.style.boxShadow = '0 2px 3px rgba(0, 0, 0, 0.1)';
        });
        
        // Réinitialiser après le clic
        button.addEventListener('mouseup', function() {
            this.style.transform = 'translateY(-3px)';
            this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.2)';
        });
    });
}

/**
 * Améliore les tableaux de données
 */
function enhanceDataTables() {
    // Si DataTables est disponible
    if (typeof $.fn.DataTable !== 'undefined') {
        // Options par défaut pour DataTables
        const dataTableOptions = {
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/French.json'
            },
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Tous"]],
            responsive: true,
            dom: '<"top"lf>rt<"bottom"ip>',
            initComplete: function() {
                // Styliser les éléments après l'initialisation
                $('.dataTables_filter input').addClass('form-control');
                $('.dataTables_length select').addClass('form-select');
            }
        };
        
        // Initialiser DataTables avec les options par défaut
        $('.datatable').DataTable(dataTableOptions);
    }
} 