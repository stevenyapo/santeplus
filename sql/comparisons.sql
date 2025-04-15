-- Table des consultations
CREATE TABLE IF NOT EXISTS consultations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    date_consultation DATETIME NOT NULL,
    duree INT NOT NULL COMMENT 'Durée en minutes',
    motif TEXT,
    observations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des diagnostics
CREATE TABLE IF NOT EXISTS diagnostics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des médicaments
CREATE TABLE IF NOT EXISTS medicaments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des prescriptions
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    medicament_id INT NOT NULL,
    posologie TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE,
    FOREIGN KEY (medicament_id) REFERENCES medicaments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des logs
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    level VARCHAR(20) NOT NULL,
    context TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index pour optimiser les requêtes de comparaison
CREATE INDEX idx_consultations_date ON consultations(date_consultation);
CREATE INDEX idx_diagnostics_consultation ON diagnostics(consultation_id);
CREATE INDEX idx_prescriptions_consultation ON prescriptions(consultation_id);
CREATE INDEX idx_prescriptions_medicament ON prescriptions(medicament_id);

-- Données de test pour les médicaments
INSERT INTO medicaments (nom, description) VALUES
('Paracétamol', 'Antidouleur et antipyrétique'),
('Ibuprofène', 'Anti-inflammatoire non stéroïdien'),
('Amoxicilline', 'Antibiotique de la famille des pénicillines'),
('Oméprazole', 'Inhibiteur de la pompe à protons'),
('Atorvastatine', 'Hypolipémiant de la famille des statines'); 