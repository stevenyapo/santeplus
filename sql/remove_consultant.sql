-- Suppression des contraintes de clé étrangère
SET FOREIGN_KEY_CHECKS = 0;

-- Suppression des tables directement liées aux consultants
DROP TABLE IF EXISTS consultant;

-- Suppression des données liées aux consultants dans les autres tables
DELETE FROM notifications WHERE type_destinataire = 'consultant';

-- Modification de la table notifications pour supprimer l'option 'consultant' du type_destinataire
ALTER TABLE notifications MODIFY COLUMN type_destinataire ENUM('medecin');

-- Réactivation des contraintes de clé étrangère
SET FOREIGN_KEY_CHECKS = 1; 