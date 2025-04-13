-- Suppression des contraintes de clé étrangère
SET FOREIGN_KEY_CHECKS = 0;

-- Suppression des tables directement liées aux secrétaires
DROP TABLE IF EXISTS secretaire;

-- Suppression des données liées aux secrétaires dans les autres tables
DELETE FROM notifications WHERE type_destinataire = 'secretaire';

-- Modification de la table notifications pour supprimer l'option 'secretaire' du type_destinataire
ALTER TABLE notifications MODIFY COLUMN type_destinataire ENUM('medecin');

-- Réactivation des contraintes de clé étrangère
SET FOREIGN_KEY_CHECKS = 1; 