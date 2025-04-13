<!DOCTYPE html>
<?php
ob_start();
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    ob_end_clean();
    header('Location: /santeplus/login.php');
    exit();
}

// Récupérer la période sélectionnée
$periode = $_GET['periode'] ?? 'mois';
$date_debut = $_GET['date_debut'] ?? date('Y-m-01', strtotime('-11 months'));
$date_fin = $_GET['date_fin'] ?? date('Y-m-t');
$antenne_selectionnee = $_GET['antenne'] ?? 'Toutes';

// Récupérer la liste des antennes
$stmt = $pdo->prepare("SELECT DISTINCT antenne FROM rapports_hemodialyse ORDER BY antenne");
$stmt->execute();
$antennes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Construire la requête en fonction de la période et de l'antenne sélectionnée
$format_date = match($periode) {
    'mois' => '%Y-%m',
    'trimestre' => '%Y-Q%q',
    'semestre' => '%Y-S%q',
    'annee' => '%Y',
    default => '%Y-%m'
};

$where_antenne = $antenne_selectionnee !== 'Toutes' ? "AND antenne = :antenne" : "";
$params = [$format_date, $date_debut, $date_fin, $format_date];
if ($antenne_selectionnee !== 'Toutes') {
    $params[] = $antenne_selectionnee;
}

$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(date_rapport, ?) as periode,
        COUNT(*) as total_rapports,
        SUM(CASE WHEN statut = 'validé' THEN 1 ELSE 0 END) as rapports_valides,
        SUM(CASE WHEN statut = 'rejeté' THEN 1 ELSE 0 END) as rapports_rejetes,
        SUM(total_patients_dialyses) as total_patients,
        SUM(nb_deces) as total_deces,
        SUM(nb_seances_urgence) as total_seances_urgence,
        SUM(nb_patients_sejour) as total_patients_sejour
    FROM rapports_hemodialyse
    WHERE date_rapport BETWEEN ? AND ?
    $where_antenne
    GROUP BY DATE_FORMAT(date_rapport, ?)
    ORDER BY periode DESC
");
$stmt->execute($params);
$donnees_periodes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la répartition par antenne
$stmt = $pdo->prepare("
    SELECT 
        antenne,
        COUNT(*) as total_rapports,
        SUM(total_patients_dialyses) as total_patients
    FROM rapports_hemodialyse
    WHERE date_rapport BETWEEN ? AND ?
    GROUP BY antenne
");
$stmt->execute([$date_debut, $date_fin]);
$donnees_antennes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la répartition par médecin
$stmt = $pdo->prepare("
    SELECT 
        m.nom, m.prenom,
        COUNT(r.id_rapport) as total_rapports,
        SUM(r.total_patients_dialyses) as total_patients
    FROM rapports_hemodialyse r
    JOIN medecins m ON r.id_medecin = m.id_medecin
    WHERE r.date_rapport BETWEEN ? AND ?
    GROUP BY m.id_medecin
");
$stmt->execute([$date_debut, $date_fin]);
$donnees_medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la distribution des patients par âge
$stmt = $pdo->prepare("
    SELECT 
        '0-17' as tranche_age,
        SUM(femmes_3_12 + femmes_13_19 + hommes_3_12 + hommes_13_19) as nombre_patients
    FROM rapports_hemodialyse
    WHERE date_rapport BETWEEN ? AND ?
    UNION ALL
    SELECT 
        '18-49' as tranche_age,
        SUM(femmes_20_49 + hommes_20_49) as nombre_patients
    FROM rapports_hemodialyse
    WHERE date_rapport BETWEEN ? AND ?
    UNION ALL
    SELECT 
        '50+' as tranche_age,
        SUM(femmes_50_plus + hommes_50_plus) as nombre_patients
    FROM rapports_hemodialyse
    WHERE date_rapport BETWEEN ? AND ?
    ORDER BY 
        CASE tranche_age
            WHEN '0-17' THEN 1
            WHEN '18-49' THEN 2
            ELSE 3
        END
");
$stmt->execute([$date_debut, $date_fin, $date_debut, $date_fin, $date_debut, $date_fin]);
$distribution_age = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la répartition hommes/femmes par période
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(date_rapport, ?) as periode,
        SUM(hommes_3_12 + hommes_13_19 + hommes_20_49 + hommes_50_plus) as total_hommes,
        SUM(femmes_3_12 + femmes_13_19 + femmes_20_49 + femmes_50_plus) as total_femmes
    FROM rapports_hemodialyse
    WHERE date_rapport BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(date_rapport, ?)
    ORDER BY periode DESC
");
$stmt->execute([$format_date, $date_debut, $date_fin, $format_date]);
$repartition_genre = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer l'évolution du taux de mortalité
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(date_rapport, ?) as periode,
        SUM(nb_deces) as total_deces,
        SUM(total_patients_dialyses) as total_patients,
        ROUND((SUM(nb_deces) * 100.0 / SUM(total_patients_dialyses)), 2) as taux_mortalite
    FROM rapports_hemodialyse
    WHERE date_rapport BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(date_rapport, ?)
    ORDER BY periode DESC
");
$stmt->execute([$format_date, $date_debut, $date_fin, $format_date]);
$taux_mortalite = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Inclure Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Graphiques des rapports d'hémodialyse</h5>
                </div>
                <div class="card-body">
                    <!-- Formulaire de filtrage -->
                    <form method="get" class="row g-3 mb-4">
                        <div class="col-md-2">
                            <label for="periode" class="form-label">Période</label>
                            <select class="form-select" id="periode" name="periode">
                                <option value="mois" <?php echo $periode === 'mois' ? 'selected' : ''; ?>>Mensuel</option>
                                <option value="trimestre" <?php echo $periode === 'trimestre' ? 'selected' : ''; ?>>Trimestriel</option>
                                <option value="semestre" <?php echo $periode === 'semestre' ? 'selected' : ''; ?>>Semestriel</option>
                                <option value="annee" <?php echo $periode === 'annee' ? 'selected' : ''; ?>>Annuel</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="antenne" class="form-label">Antenne</label>
                            <select class="form-select" id="antenne" name="antenne">
                                <option value="Toutes" <?php echo $antenne_selectionnee === 'Toutes' ? 'selected' : ''; ?>>Toutes les antennes</option>
                                <?php foreach ($antennes as $antenne): ?>
                                    <option value="<?php echo htmlspecialchars($antenne); ?>" <?php echo $antenne === $antenne_selectionnee ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($antenne); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_debut" class="form-label">Date de début</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?php echo $date_debut; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="date_fin" class="form-label">Date de fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?php echo $date_fin; ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                        </div>
                    </form>

                    <!-- Formulaire de sélection des graphiques pour le PDF -->
                    <form id="downloadForm" class="row g-3 mb-4">
                        <div class="col-12">
                            <h6>Sélectionnez les graphiques à télécharger :</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="graphiques[]" value="evolutionRapports" id="graph1">
                                        <label class="form-check-label" for="graph1">Évolution des rapports</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="graphiques[]" value="repartitionStatut" id="graph2">
                                        <label class="form-check-label" for="graph2">Répartition par statut</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="graphiques[]" value="statistiquesMedicales" id="graph5">
                                        <label class="form-check-label" for="graph5">Statistiques médicales</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="graphiques[]" value="performanceMedecins" id="graph6">
                                        <label class="form-check-label" for="graph6">Performance des médecins</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="graphiques[]" value="distributionAge" id="graph7">
                                        <label class="form-check-label" for="graph7">Distribution par âge</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="graphiques[]" value="repartitionGenre" id="graph8">
                                        <label class="form-check-label" for="graph8">Répartition hommes/femmes</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="graphiques[]" value="tauxMortalite" id="graph9">
                                        <label class="form-check-label" for="graph9">Taux de mortalité</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn" style="background-color: #007bff; color: white; border: none; padding: 10px 20px; font-weight: 500; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <i class="fas fa-download"></i> Télécharger les graphiques sélectionnés
                            </button>
                        </div>
                    </form>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <a href="graphique_global.php" class="btn btn-primary mt-4">
                                <i class="fas fa-chart-line me-2"></i>Voir le graphique global
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Graphique 1: Évolution des rapports -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Évolution des rapports</h6>
                                    <canvas id="evolutionRapports"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Graphique 2: Répartition par statut -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Répartition des rapports par statut</h6>
                                    <canvas id="repartitionStatut"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Graphique 5: Statistiques médicales -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Statistiques médicales</h6>
                                    <canvas id="statistiquesMedicales"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Graphique 6: Performance des médecins -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Performance des médecins</h6>
                                    <canvas id="performanceMedecins"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Graphique 7: Distribution des patients par âge -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Distribution des patients par âge</h6>
                                    <canvas id="distributionAge"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Graphique 8: Répartition hommes/femmes -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Répartition hommes/femmes</h6>
                                    <canvas id="repartitionGenre"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Graphique 9: Évolution du taux de mortalité -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Évolution du taux de mortalité</h6>
                                    <canvas id="tauxMortalite"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Préparer les données pour les graphiques
const labels = <?php echo json_encode(array_column($donnees_periodes, 'periode')); ?>;
const totalRapports = <?php echo json_encode(array_column($donnees_periodes, 'total_rapports')); ?>;
const rapportsValides = <?php echo json_encode(array_column($donnees_periodes, 'rapports_valides')); ?>;
const rapportsRejetes = <?php echo json_encode(array_column($donnees_periodes, 'rapports_rejetes')); ?>;
const totalPatients = <?php echo json_encode(array_column($donnees_periodes, 'total_patients')); ?>;
const totalDeces = <?php echo json_encode(array_column($donnees_periodes, 'total_deces')); ?>;
const totalSeancesUrgence = <?php echo json_encode(array_column($donnees_periodes, 'total_seances_urgence')); ?>;
const totalPatientsSejour = <?php echo json_encode(array_column($donnees_periodes, 'total_patients_sejour')); ?>;

const antennes = <?php echo json_encode(array_column($donnees_antennes, 'antenne')); ?>;
const patientsParAntenne = <?php echo json_encode(array_column($donnees_antennes, 'total_patients')); ?>;

const medecins = <?php echo json_encode(array_map(function($m) { return $m['prenom'] . ' ' . $m['nom']; }, $donnees_medecins)); ?>;
const rapportsParMedecin = <?php echo json_encode(array_column($donnees_medecins, 'total_rapports')); ?>;

// Préparer les données pour les nouveaux graphiques
const tranchesAge = <?php echo json_encode(array_column($distribution_age, 'tranche_age')); ?>;
const patientsParTranche = <?php echo json_encode(array_column($distribution_age, 'nombre_patients')); ?>;

const periodesGenre = <?php echo json_encode(array_column($repartition_genre, 'periode')); ?>;
const totalHommes = <?php echo json_encode(array_column($repartition_genre, 'total_hommes')); ?>;
const totalFemmes = <?php echo json_encode(array_column($repartition_genre, 'total_femmes')); ?>;

const periodesMortalite = <?php echo json_encode(array_column($taux_mortalite, 'periode')); ?>;
const tauxMortalite = <?php echo json_encode(array_column($taux_mortalite, 'taux_mortalite')); ?>;

// Graphique 1: Évolution des rapports
new Chart(document.getElementById('evolutionRapports'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Total des rapports',
            data: totalRapports,
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Évolution du nombre de rapports'
            }
        }
    }
});

// Graphique 2: Répartition par statut
new Chart(document.getElementById('repartitionStatut'), {
    type: 'pie',
    data: {
        labels: ['Validés', 'Rejetés', 'En attente'],
        datasets: [{
            data: [
                rapportsValides.reduce((a, b) => a + b, 0),
                rapportsRejetes.reduce((a, b) => a + b, 0),
                totalRapports.reduce((a, b) => a + b, 0) - rapportsValides.reduce((a, b) => a + b, 0) - rapportsRejetes.reduce((a, b) => a + b, 0)
            ],
            backgroundColor: ['#28a745', '#dc3545', '#ffc107']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: 2,
        plugins: {
            title: {
                display: true,
                text: 'Répartition des rapports par statut'
            },
            legend: {
                position: 'right',
                labels: {
                    boxWidth: 12
                }
            }
        }
    }
});

// Graphique 5: Statistiques médicales
new Chart(document.getElementById('statistiquesMedicales'), {
    type: 'bar',
    data: {
        labels: ['Décès', 'Séances d\'urgence', 'Patients en séjour'],
        datasets: [{
            label: 'Statistiques',
            data: [
                totalDeces.reduce((a, b) => a + b, 0),
                totalSeancesUrgence.reduce((a, b) => a + b, 0),
                totalPatientsSejour.reduce((a, b) => a + b, 0)
            ],
            backgroundColor: ['rgba(255, 99, 132, 0.5)', 'rgba(54, 162, 235, 0.5)', 'rgba(255, 206, 86, 0.5)']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Statistiques médicales globales'
            }
        }
    }
});

// Graphique 6: Performance des médecins
new Chart(document.getElementById('performanceMedecins'), {
    type: 'bar',
    data: {
        labels: medecins,
        datasets: [{
            label: 'Nombre de rapports',
            data: rapportsParMedecin,
            backgroundColor: 'rgba(75, 192, 192, 0.5)'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Nombre de rapports par médecin'
            }
        }
    }
});

// Graphique 7: Distribution des patients par âge
new Chart(document.getElementById('distributionAge'), {
    type: 'bar',
    data: {
        labels: tranchesAge,
        datasets: [{
            label: 'Nombre de patients',
            data: patientsParTranche,
            backgroundColor: 'rgba(54, 162, 235, 0.5)'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Distribution des patients par tranche d\'âge'
            }
        }
    }
});

// Graphique 8: Répartition hommes/femmes
new Chart(document.getElementById('repartitionGenre'), {
    type: 'bar',
    data: {
        labels: periodesGenre,
        datasets: [
            {
                label: 'Hommes',
                data: totalHommes,
                backgroundColor: 'rgba(54, 162, 235, 0.5)'
            },
            {
                label: 'Femmes',
                data: totalFemmes,
                backgroundColor: 'rgba(255, 99, 132, 0.5)'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Répartition hommes/femmes par période'
            }
        },
        scales: {
            x: {
                stacked: true
            },
            y: {
                stacked: true
            }
        }
    }
});

// Graphique 9: Évolution du taux de mortalité
new Chart(document.getElementById('tauxMortalite'), {
    type: 'line',
    data: {
        labels: periodesMortalite,
        datasets: [{
            label: 'Taux de mortalité (%)',
            data: tauxMortalite,
            borderColor: 'rgb(255, 99, 132)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Évolution du taux de mortalité'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Taux de mortalité (%)'
                }
            }
        }
    }
});

// Fonction pour sauvegarder un graphique en image
function saveChartAsImage(chartId) {
    try {
        // Obtenir le canvas du graphique
        const canvas = document.getElementById(chartId);
        if (!canvas) {
            console.error('Canvas non trouvé:', chartId);
            return null;
        }

        // Créer un nouveau canvas avec une résolution plus élevée
        const tempCanvas = document.createElement('canvas');
        const tempContext = tempCanvas.getContext('2d');
        const dpr = window.devicePixelRatio || 1;
        
        // Définir la taille du canvas temporaire
        tempCanvas.width = canvas.width * dpr;
        tempCanvas.height = canvas.height * dpr;
        
        // Copier le contenu du canvas original
        tempContext.scale(dpr, dpr);
        tempContext.drawImage(canvas, 0, 0);
        
        // Convertir en image PNG
        return tempCanvas.toDataURL('image/png', 1.0);
    } catch (error) {
        console.error('Erreur lors de la conversion du graphique en image:', error);
        return null;
    }
}

// Gérer la soumission du formulaire
document.getElementById('downloadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Vérifier si au moins un graphique est sélectionné
    const checkboxes = document.querySelectorAll('input[name="graphiques[]"]:checked');
    if (checkboxes.length === 0) {
        alert('Veuillez sélectionner au moins un graphique à télécharger.');
        return;
    }

    // Créer un objet FormData pour envoyer les données
    const formData = new FormData();
    
    // Ajouter les graphiques sélectionnés et leurs images
    let hasValidImages = false;
    checkboxes.forEach(checkbox => {
        const chartId = checkbox.value;
        const imageData = saveChartAsImage(chartId);
        console.log('Graphique:', chartId, 'Image:', imageData ? 'OK' : 'Erreur');
        if (imageData) {
            formData.append('graphiques[]', chartId);
            formData.append('images[]', imageData);
            hasValidImages = true;
        }
    });

    if (!hasValidImages) {
        alert('Aucun graphique valide à télécharger.');
        return;
    }

    // Afficher l'indicateur de chargement
    const submitButton = document.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Génération en cours...';

    // Envoyer les données au serveur
    fetch('download_images.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(text || 'Erreur lors du téléchargement');
            });
        }
        return response.blob();
    })
    .then(blob => {
        // Créer un lien de téléchargement
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'graphiques.zip';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert(error.message || 'Une erreur est survenue lors du téléchargement des graphiques.');
    })
    .finally(() => {
        // Réinitialiser le bouton
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 