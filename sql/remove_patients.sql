-- Suppression des contraintes de clé étrangère
SET FOREIGN_KEY_CHECKS = 0;

-- Suppression des tables directement liées aux patients
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS patient_medecin;
DROP TABLE IF EXISTS rendez_vous;
DROP TABLE IF EXISTS rendezvous;
DROP TABLE IF EXISTS suivis_traitement;
DROP TABLE IF EXISTS documents_medicaux;
DROP TABLE IF EXISTS demandes_documents;
DROP TABLE IF EXISTS teleconsultations;
DROP TABLE IF EXISTS urgences;

-- Suppression des données liées aux patients dans les autres tables
DELETE FROM notifications WHERE type_destinataire = 'patient';

-- Modification de la table notifications pour supprimer l'option 'patient' du type_destinataire
ALTER TABLE notifications MODIFY COLUMN type_destinataire ENUM('medecin','secretaire');

-- Suppression des colonnes liées aux patients dans les tables restantes
-- Vérification et suppression des colonnes dans rapports_hemodialyse
SELECT COUNT(*) INTO @exists FROM information_schema.columns 
WHERE table_name = 'rapports_hemodialyse' AND column_name = 'id_patient';
SET @query = IF(@exists > 0, 'ALTER TABLE rapports_hemodialyse DROP COLUMN id_patient', 'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérification et suppression des colonnes dans messages
SELECT COUNT(*) INTO @exists FROM information_schema.columns 
WHERE table_name = 'messages' AND column_name = 'id_patient';
SET @query = IF(@exists > 0, 'ALTER TABLE messages DROP COLUMN id_patient', 'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérification et suppression des colonnes dans notifications
SELECT COUNT(*) INTO @exists FROM information_schema.columns 
WHERE table_name = 'notifications' AND column_name = 'id_patient';
SET @query = IF(@exists > 0, 'ALTER TABLE notifications DROP COLUMN id_patient', 'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Réactivation des contraintes de clé étrangère
SET FOREIGN_KEY_CHECKS = 1; 