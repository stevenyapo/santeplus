<?php
require_once 'includes/header.php';

// Données statiques des services
$services = [
    [
        'id' => 1,
        'titre' => 'Médecine générale',
        'description' => 'Consultations de médecine générale pour tous types de pathologies courantes.',
        'icone' => 'fas fa-stethoscope',
        'disponible' => true
    ],
    [
        'id' => 2,
        'titre' => 'Pédiatrie',
        'description' => 'Soins et suivi médical pour les enfants de 0 à 18 ans.',
        'icone' => 'fas fa-baby',
        'disponible' => true
    ],
    [
        'id' => 3,
        'titre' => 'Gynécologie',
        'description' => 'Suivi gynécologique et obstétrique pour les femmes.',
        'icone' => 'fas fa-female',
        'disponible' => true
    ],
    [
        'id' => 4,
        'titre' => 'Cardiologie',
        'description' => 'Diagnostic et traitement des maladies cardiovasculaires.',
        'icone' => 'fas fa-heartbeat',
        'disponible' => true
    ],
    [
        'id' => 5,
        'titre' => 'Dermatologie',
        'description' => 'Diagnostic et traitement des affections de la peau.',
        'icone' => 'fas fa-user-md',
        'disponible' => true
    ],
    [
        'id' => 6,
        'titre' => 'Ophtalmologie',
        'description' => 'Examens et soins des yeux.',
        'icone' => 'fas fa-eye',
        'disponible' => true
    ],
    [
        'id' => 7,
        'titre' => 'ORL',
        'description' => 'Soins des affections de l\'oreille, du nez et de la gorge.',
        'icone' => 'fas fa-head-side-mask',
        'disponible' => true
    ],
    [
        'id' => 8,
        'titre' => 'Téléconsultation',
        'description' => 'Consultations médicales à distance via vidéo.',
        'icone' => 'fas fa-laptop-medical',
        'disponible' => true
    ],
    [
        'id' => 9,
        'titre' => 'Laboratoire d\'analyses',
        'description' => 'Analyses médicales et examens biologiques.',
        'icone' => 'fas fa-flask',
        'disponible' => true
    ],
    [
        'id' => 10,
        'titre' => 'Imagerie médicale',
        'description' => 'Radiologie, échographie et autres examens d\'imagerie.',
        'icone' => 'fas fa-x-ray',
        'disponible' => true
    ]
];
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="text-center mb-5">Nos Services Médicaux</h1>
            <p class="text-center text-muted mb-5">
                Découvrez notre gamme complète de services médicaux, 
                assurés par une équipe de professionnels qualifiés et expérimentés.
            </p>
        </div>
    </div>

    <div class="row">
        <?php foreach ($services as $service): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="<?php echo htmlspecialchars($service['icone']); ?> me-2"></i>
                        <?php echo htmlspecialchars($service['titre']); ?>
                    </h5>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
                    <?php if ($service['disponible']): ?>
                    <?php if (isLoggedIn()): ?>
                    <a href="/santeplus/patient/rendez-vous.php?service=<?php echo $service['id']; ?>" 
                       class="btn btn-primary">
                        <i class="fas fa-calendar-plus me-2"></i>Prendre rendez-vous
                    </a>
                    <?php else: ?>
                    <a href="/santeplus/login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Connectez-vous pour prendre rendez-vous
                    </a>
                    <?php endif; ?>
                    <?php else: ?>
                    <button class="btn btn-secondary" disabled>
                        <i class="fas fa-clock me-2"></i>Service temporairement indisponible
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row mt-5">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="mb-0">Besoin d'une consultation urgente ?</h3>
                            <p class="mb-0">Notre service d'urgences est disponible 24h/24 et 7j/7</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <?php if (isLoggedIn()): ?>
                            <a href="/santeplus/patient/urgences.php" class="btn btn-light">
                                <i class="fas fa-ambulance me-2"></i>Service d'urgences
                            </a>
                            <?php else: ?>
                            <a href="/santeplus/login.php" class="btn btn-light">
                                <i class="fas fa-sign-in-alt me-2"></i>Connectez-vous pour accéder aux urgences
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-12">
            <h2 class="text-center mb-4">Pourquoi nous choisir ?</h2>
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <div class="icon icon-shape bg-gradient-primary shadow-primary rounded-circle mb-3">
                        <i class="fas fa-user-md text-lg opacity-10"></i>
                    </div>
                    <h5>Équipe médicale qualifiée</h5>
                    <p class="text-muted">Des professionnels expérimentés à votre service</p>
                </div>
                <div class="col-md-3 text-center mb-4">
                    <div class="icon icon-shape bg-gradient-success shadow-success rounded-circle mb-3">
                        <i class="fas fa-clock text-lg opacity-10"></i>
                    </div>
                    <h5>Rendez-vous rapides</h5>
                    <p class="text-muted">Accès rapide aux consultations</p>
                </div>
                <div class="col-md-3 text-center mb-4">
                    <div class="icon icon-shape bg-gradient-warning shadow-warning rounded-circle mb-3">
                        <i class="fas fa-laptop-medical text-lg opacity-10"></i>
                    </div>
                    <h5>Téléconsultation</h5>
                    <p class="text-muted">Consultations à distance disponibles</p>
                </div>
                <div class="col-md-3 text-center mb-4">
                    <div class="icon icon-shape bg-gradient-info shadow-info rounded-circle mb-3">
                        <i class="fas fa-file-medical text-lg opacity-10"></i>
                    </div>
                    <h5>Suivi médical complet</h5>
                    <p class="text-muted">Accès à votre dossier médical en ligne</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="text-center mb-4">Horaires d'ouverture</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Consultations</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-clock me-2"></i>
                                    Lundi - Vendredi : 8h00 - 20h00
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-clock me-2"></i>
                                    Samedi : 9h00 - 17h00
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-clock me-2"></i>
                                    Dimanche : Fermé
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Urgences</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-ambulance me-2"></i>
                                    24h/24 et 7j/7
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-phone me-2"></i>
                                    Tél : 01 23 45 67 89
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 