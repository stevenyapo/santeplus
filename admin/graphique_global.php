<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /santeplus/login.php');
    exit();
}

// Récupérer l'année sélectionnée (par défaut l'année en cours)
$annee = isset($_GET['annee']) ? intval($_GET['annee']) : date('Y');
$antenne_selectionnee = $_GET['antenne'] ?? 'Toutes';

// Récupérer la liste des années disponibles
$stmt = $pdo->query("SELECT DISTINCT YEAR(date_rapport) as annee FROM rapports_hemodialyse ORDER BY annee DESC");
$annees = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Récupérer la liste des antennes
$stmt = $pdo->query("SELECT DISTINCT antenne FROM rapports_hemodialyse ORDER BY antenne");
$antennes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Récupérer les données mensuelles
$where_antenne = $antenne_selectionnee !== 'Toutes' ? "AND antenne = ?" : "";
$params = [$annee];
if ($antenne_selectionnee !== 'Toutes') {
    $params[] = $antenne_selectionnee;
}

$stmt = $pdo->prepare("
    SELECT 
        MONTH(date_rapport) as mois,
        SUM(hommes_3_12) as hommes_0_12,
        SUM(hommes_13_19) as hommes_13_19,
        SUM(hommes_20_49) as hommes_20_49,
        SUM(hommes_50_plus) as hommes_50_plus,
        SUM(femmes_3_12) as femmes_0_12,
        SUM(femmes_13_19) as femmes_13_19,
        SUM(femmes_20_49) as femmes_20_49,
        SUM(femmes_50_plus) as femmes_50_plus
    FROM rapports_hemodialyse 
    WHERE YEAR(date_rapport) = ? 
        AND statut = 'validé'
        $where_antenne
    GROUP BY MONTH(date_rapport)
    ORDER BY mois
");
$stmt->execute($params);
$donnees_mensuelles = $stmt->fetchAll();

// Tableau des noms de mois en français
$mois_fr = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
];

// Calculer les moyennes annuelles
$moyennes = [
    'hommes_0_12' => 0, 'hommes_13_19' => 0, 'hommes_20_49' => 0, 'hommes_50_plus' => 0,
    'femmes_0_12' => 0, 'femmes_13_19' => 0, 'femmes_20_49' => 0, 'femmes_50_plus' => 0
];
$nb_mois = count($donnees_mensuelles);

foreach ($donnees_mensuelles as $donnees) {
    foreach ($moyennes as $key => $value) {
        $moyennes[$key] += $donnees[$key];
    }
}

if ($nb_mois > 0) {
    foreach ($moyennes as $key => $value) {
        $moyennes[$key] = round($value / $nb_mois);
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Statistiques des Dialyses par Sexe et Âge</h5>
                    <div class="d-flex align-items-center gap-3">
                        <select id="antenne-select" class="form-select">
                            <option value="Toutes" <?php echo $antenne_selectionnee === 'Toutes' ? 'selected' : ''; ?>>Toutes les antennes</option>
                            <?php foreach ($antennes as $antenne): ?>
                                <option value="<?php echo htmlspecialchars($antenne); ?>" <?php echo $antenne === $antenne_selectionnee ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($antenne); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <a href="export_graphique_global.php?annee=<?php echo $annee; ?>&antenne=<?php echo urlencode($antenne_selectionnee); ?>" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Exporter en XLSX
                        </a>
                    </div>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr class="text-center">
                                    <th rowspan="2" class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">MOIS</th>
                                    <th colspan="5" class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 bg-info bg-opacity-10">Hommes</th>
                                    <th colspan="5" class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 bg-danger bg-opacity-10">Femmes</th>
                                </tr>
                                <tr class="text-center">
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 bg-info bg-opacity-10">0-12 ans</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 bg-info bg-opacity-10">13-19 ans</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 bg-info bg-opacity-10">20-49 ans</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 bg-info bg-opacity-10">≥50 ans</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 bg-info bg-opacity-10">TOTAL</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 bg-danger bg-opacity-10">0-12 ans</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 bg-danger bg-opacity-10">13-19 ans</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 bg-danger bg-opacity-10">20-49 ans</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 bg-danger bg-opacity-10">≥50 ans</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 bg-danger bg-opacity-10">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($donnees_mensuelles as $donnees): ?>
                                    <tr class="text-center">
                                        <td>
                                            <p class="text-xs font-weight-bold mb-0"><?php echo $mois_fr[$donnees['mois']]; ?></p>
                                        </td>
                                        <td class="bg-info bg-opacity-10">
                                            <p class="text-xs font-weight-bold mb-0"><?php echo $donnees['hommes_0_12']; ?></p>
                                        </td>
                                        <td class="bg-info bg-opacity-10">
                                            <p class="text-xs font-weight-bold mb-0"><?php echo $donnees['hommes_13_19']; ?></p>
                                        </td>
                                        <td class="bg-info bg-opacity-10">
                                            <p class="text-xs font-weight-bold mb-0"><?php echo $donnees['hommes_20_49']; ?></p>
                                        </td>
                                        <td class="bg-info bg-opacity-10">
                                            <p class="text-xs font-weight-bold mb-0"><?php echo $donnees['hommes_50_plus']; ?></p>
                                        </td>
                                        <td class="bg-info bg-opacity-10">
                                            <p class="text-xs font-weight-bold mb-0">
                                                <?php echo $donnees['hommes_0_12'] + $donnees['hommes_13_19'] + $donnees['hommes_20_49'] + $donnees['hommes_50_plus']; ?>
                                            </p>
                                        </td>
                                        <td class="bg-danger bg-opacity-10">
                                            <p class="text-xs font-weight-bold mb-0"><?php echo $donnees['femmes_0_12']; ?></p>
                                        </td>
                                        <td class="bg-danger bg-opacity-10">
                                            <p class="text-xs font-weight-bold mb-0"><?php echo $donnees['femmes_13_19']; ?></p>
                                        </td>
                                        <td class="bg-danger bg-opacity-10">
                                            <p class="text-xs font-weight-bold mb-0"><?php echo $donnees['femmes_20_49']; ?></p>
                                        </td>
                                        <td class="bg-danger bg-opacity-10">
                                            <p class="text-xs font-weight-bold mb-0"><?php echo $donnees['femmes_50_plus']; ?></p>
                                        </td>
                                        <td class="bg-danger bg-opacity-10">
                                            <p class="text-xs font-weight-bold mb-0">
                                                <?php echo $donnees['femmes_0_12'] + $donnees['femmes_13_19'] + $donnees['femmes_20_49'] + $donnees['femmes_50_plus']; ?>
                                            </p>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="text-center bg-light">
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">Effectif moyen</p>
                                    </td>
                                    <td class="bg-info bg-opacity-10">
                                        <p class="text-xs font-weight-bold mb-0"><?php echo $moyennes['hommes_0_12']; ?></p>
                                    </td>
                                    <td class="bg-info bg-opacity-10">
                                        <p class="text-xs font-weight-bold mb-0"><?php echo $moyennes['hommes_13_19']; ?></p>
                                    </td>
                                    <td class="bg-info bg-opacity-10">
                                        <p class="text-xs font-weight-bold mb-0"><?php echo $moyennes['hommes_20_49']; ?></p>
                                    </td>
                                    <td class="bg-info bg-opacity-10">
                                        <p class="text-xs font-weight-bold mb-0"><?php echo $moyennes['hommes_50_plus']; ?></p>
                                    </td>
                                    <td class="bg-info bg-opacity-10">
                                        <p class="text-xs font-weight-bold mb-0">
                                            <?php echo $moyennes['hommes_0_12'] + $moyennes['hommes_13_19'] + $moyennes['hommes_20_49'] + $moyennes['hommes_50_plus']; ?>
                                        </p>
                                    </td>
                                    <td class="bg-danger bg-opacity-10">
                                        <p class="text-xs font-weight-bold mb-0"><?php echo $moyennes['femmes_0_12']; ?></p>
                                    </td>
                                    <td class="bg-danger bg-opacity-10">
                                        <p class="text-xs font-weight-bold mb-0"><?php echo $moyennes['femmes_13_19']; ?></p>
                                    </td>
                                    <td class="bg-danger bg-opacity-10">
                                        <p class="text-xs font-weight-bold mb-0"><?php echo $moyennes['femmes_20_49']; ?></p>
                                    </td>
                                    <td class="bg-danger bg-opacity-10">
                                        <p class="text-xs font-weight-bold mb-0"><?php echo $moyennes['femmes_50_plus']; ?></p>
                                    </td>
                                    <td class="bg-danger bg-opacity-10">
                                        <p class="text-xs font-weight-bold mb-0">
                                            <?php echo $moyennes['femmes_0_12'] + $moyennes['femmes_13_19'] + $moyennes['femmes_20_49'] + $moyennes['femmes_50_plus']; ?>
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const antenneSelect = document.getElementById('antenne-select');
    
    function handleAntenneChange() {
        const selectedAntenne = this.value;
        window.location.href = `?annee=<?php echo $annee; ?>&antenne=${encodeURIComponent(selectedAntenne)}`;
    }
    
    // Ajouter l'événement
    antenneSelect.addEventListener('change', handleAntenneChange);
    
    // Réinitialiser l'événement après le changement de thème
    document.addEventListener('themeChanged', function() {
        antenneSelect.removeEventListener('change', handleAntenneChange);
        antenneSelect.addEventListener('change', handleAntenneChange);
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 