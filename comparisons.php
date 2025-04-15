<?php
require_once 'includes/init.php';
require_once 'classes/YearlyComparison.php';

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Récupération des années de début et de fin
$currentYear = date('Y');
$startYear = isset($_GET['startYear']) ? intval($_GET['startYear']) : $currentYear - 1;
$endYear = isset($_GET['endYear']) ? intval($_GET['endYear']) : $currentYear;

// Instanciation de la classe YearlyComparison avec la bonne variable de connexion
$comparison = new YearlyComparison($pdo);

// Si c'est une requête AJAX
if (isset($_GET['ajax'])) {
    $data = $comparison->getConsultationsComparison($startYear, $endYear);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Titre de la page
$pageTitle = "Comparaisons Annuelles";
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard">Tableau de bord</a></li>
        <li class="breadcrumb-item active">Comparaisons</li>
    </ol>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filtres
        </div>
        <div class="card-body">
            <form id="comparisonForm" class="row g-3">
                <div class="col-md-4">
                    <label for="startYear" class="form-label">Année de début</label>
                    <select class="form-select" id="startYear" name="startYear">
                        <?php
                        for ($year = $currentYear; $year >= $currentYear - 5; $year--) {
                            $selected = ($year == $startYear) ? 'selected' : '';
                            echo "<option value=\"$year\" $selected>$year</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="endYear" class="form-label">Année de fin</label>
                    <select class="form-select" id="endYear" name="endYear">
                        <?php
                        for ($year = $currentYear; $year >= $currentYear - 5; $year--) {
                            $selected = ($year == $endYear) ? 'selected' : '';
                            echo "<option value=\"$year\" $selected>$year</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync-alt me-1"></i> Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="row">
        <!-- Consultations mensuelles -->
        <div class="col-xl-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i>
                    Évolution des consultations
                </div>
                <div class="card-body">
                    <canvas id="consultationsChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>

        <!-- Diagnostics -->
        <div class="col-xl-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Diagnostics les plus fréquents
                </div>
                <div class="card-body">
                    <canvas id="diagnosticsChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>

        <!-- Prescriptions -->
        <div class="col-xl-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Médicaments les plus prescrits
                </div>
                <div class="card-body">
                    <canvas id="prescriptionsChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Statistiques détaillées
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="statsTable" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Année</th>
                            <th>Total Consultations</th>
                            <th>Patients Uniques</th>
                            <th>Durée Moyenne (min)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rempli dynamiquement par JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Scripts spécifiques à la page -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables pour stocker les instances des graphiques
    let consultationsChart, diagnosticsChart, prescriptionsChart;

    // Fonction pour mettre à jour les graphiques
    async function updateCharts() {
        const startYear = document.getElementById('startYear').value;
        const endYear = document.getElementById('endYear').value;
        
        try {
            const response = await fetch(`comparisons?ajax=1&startYear=${startYear}&endYear=${endYear}`);
            const data = await response.json();
            
            // Mettre à jour le graphique des consultations
            if (consultationsChart) {
                consultationsChart.destroy();
            }
            const ctxConsultations = document.getElementById('consultationsChart').getContext('2d');
            consultationsChart = new Chart(ctxConsultations, {
                type: 'line',
                data: data.consultations,
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Évolution mensuelle des consultations'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Mettre à jour le graphique des diagnostics
            if (diagnosticsChart) {
                diagnosticsChart.destroy();
            }
            const ctxDiagnostics = document.getElementById('diagnosticsChart').getContext('2d');
            diagnosticsChart = new Chart(ctxDiagnostics, {
                type: 'bar',
                data: {
                    labels: data.diagnostics.labels,
                    datasets: data.diagnostics.datasets
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Diagnostics par année'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Mettre à jour le graphique des prescriptions
            if (prescriptionsChart) {
                prescriptionsChart.destroy();
            }
            const ctxPrescriptions = document.getElementById('prescriptionsChart').getContext('2d');
            prescriptionsChart = new Chart(ctxPrescriptions, {
                type: 'bar',
                data: {
                    labels: data.prescriptions.labels,
                    datasets: data.prescriptions.datasets
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Prescriptions par année'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Mettre à jour le tableau des statistiques
            const tbody = document.querySelector('#statsTable tbody');
            tbody.innerHTML = '';
            data.stats.forEach(stat => {
                tbody.innerHTML += `
                    <tr>
                        <td>${stat.year}</td>
                        <td>${stat.total_consultations}</td>
                        <td>${stat.unique_patients}</td>
                        <td>${Math.round(stat.average_duration)}</td>
                    </tr>
                `;
            });

        } catch (error) {
            console.error('Erreur lors de la mise à jour des graphiques:', error);
            showNotification('Erreur lors de la mise à jour des données', 'error');
        }
    }

    // Gestionnaire d'événement pour le formulaire
    document.getElementById('comparisonForm').addEventListener('submit', function(e) {
        e.preventDefault();
        updateCharts();
    });

    // Charger les graphiques au chargement de la page
    updateCharts();
});
</script> 