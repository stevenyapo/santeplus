document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des graphiques
    initCharts();
    
    // Gestion du formulaire de filtres
    const filterForm = document.querySelector('.filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            updateCharts();
        });
    }
});

function initCharts() {
    // Graphique des consultations
    const consultationsCtx = document.getElementById('consultationsChart');
    if (consultationsCtx) {
        window.consultationsChart = new Chart(consultationsCtx, {
            type: 'line',
            data: {
                labels: JSON.parse(consultationsCtx.dataset.labels),
                datasets: JSON.parse(consultationsCtx.dataset.datasets)
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nombre de consultations'
                        }
                    }
                }
            }
        });
    }

    // Graphique des diagnostics
    const diagnosticsCtx = document.getElementById('diagnosticsChart');
    if (diagnosticsCtx) {
        window.diagnosticsChart = new Chart(diagnosticsCtx, {
            type: 'bar',
            data: {
                labels: JSON.parse(diagnosticsCtx.dataset.labels),
                datasets: JSON.parse(diagnosticsCtx.dataset.datasets)
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nombre de diagnostics'
                        }
                    }
                }
            }
        });
    }

    // Graphique des prescriptions
    const prescriptionsCtx = document.getElementById('prescriptionsChart');
    if (prescriptionsCtx) {
        window.prescriptionsChart = new Chart(prescriptionsCtx, {
            type: 'bar',
            data: {
                labels: JSON.parse(prescriptionsCtx.dataset.labels),
                datasets: JSON.parse(prescriptionsCtx.dataset.datasets)
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nombre de prescriptions'
                        }
                    }
                }
            }
        });
    }
}

function updateCharts() {
    const startYear = document.getElementById('startYear').value;
    const endYear = document.getElementById('endYear').value;

    // Envoyer une requête AJAX pour mettre à jour les données
    fetch(`ajax/update_comparisons.php?startYear=${startYear}&endYear=${endYear}`)
        .then(response => response.json())
        .then(data => {
            // Mettre à jour le graphique des consultations
            if (window.consultationsChart) {
                window.consultationsChart.data.labels = data.consultations.labels;
                window.consultationsChart.data.datasets = data.consultations.datasets;
                window.consultationsChart.update();
            }

            // Mettre à jour le graphique des diagnostics
            if (window.diagnosticsChart) {
                window.diagnosticsChart.data.labels = data.diagnostics.labels;
                window.diagnosticsChart.data.datasets = data.diagnostics.datasets;
                window.diagnosticsChart.update();
            }

            // Mettre à jour le graphique des prescriptions
            if (window.prescriptionsChart) {
                window.prescriptionsChart.data.labels = data.prescriptions.labels;
                window.prescriptionsChart.data.datasets = data.prescriptions.datasets;
                window.prescriptionsChart.update();
            }

            // Mettre à jour le tableau de données
            updateDataTable(data.stats);
        })
        .catch(error => {
            console.error('Erreur lors de la mise à jour des données:', error);
            showNotification('Erreur lors de la mise à jour des données', 'error');
        });
}

function updateDataTable(stats) {
    const tbody = document.querySelector('.table tbody');
    if (!tbody) return;

    tbody.innerHTML = '';
    
    stats.forEach(stat => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${stat.year}</td>
            <td>${stat.total_consultations}</td>
            <td>${stat.unique_patients}</td>
            <td>${stat.average_duration} min</td>
        `;
        tbody.appendChild(row);
    });
}

function showNotification(message, type = 'success') {
    // Créer l'élément de notification
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;

    // Ajouter la notification au document
    document.body.appendChild(notification);

    // Supprimer la notification après 3 secondes
    setTimeout(() => {
        notification.remove();
    }, 3000);
} 