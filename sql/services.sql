-- Table des services médicaux
CREATE TABLE services (
    id_service INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    image VARCHAR(255),
    icone VARCHAR(50),
    disponible BOOLEAN DEFAULT TRUE,
    ordre_affichage INT DEFAULT 0,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertion des services par défaut
INSERT INTO services (titre, description, icone, ordre_affichage) VALUES
('Médecine générale', 'Consultations de médecine générale pour tous types de pathologies courantes.', 'fas fa-stethoscope', 1),
('Pédiatrie', 'Soins et suivi médical pour les enfants de 0 à 18 ans.', 'fas fa-baby', 2),
('Gynécologie', 'Suivi gynécologique et obstétrique pour les femmes.', 'fas fa-female', 3),
('Cardiologie', 'Diagnostic et traitement des maladies cardiovasculaires.', 'fas fa-heartbeat', 4),
('Dermatologie', 'Diagnostic et traitement des affections de la peau.', 'fas fa-user-md', 5),
('Ophtalmologie', 'Examens et soins des yeux.', 'fas fa-eye', 6),
('ORL', 'Soins des affections de l''oreille, du nez et de la gorge.', 'fas fa-head-side-mask', 7),
('Téléconsultation', 'Consultations médicales à distance via vidéo.', 'fas fa-laptop-medical', 8),
('Laboratoire d''analyses', 'Analyses médicales et examens biologiques.', 'fas fa-flask', 9),
('Imagerie médicale', 'Radiologie, échographie et autres examens d''imagerie.', 'fas fa-x-ray', 10); 