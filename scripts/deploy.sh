#!/bin/bash

# Configuration
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
CONFIG_FILE="$PROJECT_ROOT/config/deploy.php"
LOG_FILE="$PROJECT_ROOT/logs/deploy.log"

# Fonctions utilitaires
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

error() {
    log "ERREUR: $1"
    exit 1
}

# Vérification des prérequis
check_prerequisites() {
    log "Vérification des prérequis..."
    
    # Vérifier PHP
    if ! command -v php &> /dev/null; then
        error "PHP n'est pas installé"
    fi
    
    # Vérifier Composer
    if ! command -v composer &> /dev/null; then
        error "Composer n'est pas installé"
    fi
    
    # Vérifier l'espace disque
    FREE_SPACE=$(df -P "$PROJECT_ROOT" | awk 'NR==2 {print $4}')
    if [ "$FREE_SPACE" -lt 104857600 ]; then
        error "Espace disque insuffisant (minimum 100 Mo requis)"
    fi
    
    log "Prérequis vérifiés avec succès"
}

# Créer une sauvegarde
create_backup() {
    log "Création de la sauvegarde..."
    BACKUP_DIR="$PROJECT_ROOT/backups/$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    
    # Sauvegarder les fichiers
    rsync -a --exclude-from="$PROJECT_ROOT/.gitignore" "$PROJECT_ROOT/" "$BACKUP_DIR/files/"
    
    # Sauvegarder la base de données
    if [ -f "$PROJECT_ROOT/config/database.php" ]; then
        source "$PROJECT_ROOT/config/database.php"
        mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/database.sql"
    fi
    
    log "Sauvegarde créée dans $BACKUP_DIR"
}

# Mettre à jour les fichiers
update_files() {
    log "Mise à jour des fichiers..."
    
    # Mettre à jour depuis le dépôt
    cd "$PROJECT_ROOT" || error "Impossible de se déplacer dans le répertoire du projet"
    git pull origin main || error "Échec de la mise à jour depuis le dépôt"
    
    # Installer les dépendances
    composer install --no-dev --optimize-autoloader || error "Échec de l'installation des dépendances"
    
    log "Fichiers mis à jour avec succès"
}

# Mettre à jour la base de données
update_database() {
    log "Mise à jour de la base de données..."
    
    if [ -f "$PROJECT_ROOT/sql/migrations" ]; then
        cd "$PROJECT_ROOT" || error "Impossible de se déplacer dans le répertoire du projet"
        php scripts/migrate.php || error "Échec de la mise à jour de la base de données"
    fi
    
    log "Base de données mise à jour avec succès"
}

# Vérifier l'intégrité
check_integrity() {
    log "Vérification de l'intégrité..."
    
    # Vérifier les fichiers essentiels
    ESSENTIAL_FILES=(
        "index.php"
        "config/database.php"
        "includes/header.php"
        "includes/footer.php"
    )
    
    for file in "${ESSENTIAL_FILES[@]}"; do
        if [ ! -f "$PROJECT_ROOT/$file" ]; then
            error "Fichier essentiel manquant: $file"
        fi
    done
    
    # Vérifier les permissions
    DIRS=("cache" "logs" "uploads" "backups")
    for dir in "${DIRS[@]}"; do
        if [ -d "$PROJECT_ROOT/$dir" ]; then
            chmod 775 "$PROJECT_ROOT/$dir"
        fi
    done
    
    log "Intégrité vérifiée avec succès"
}

# Nettoyer
cleanup() {
    log "Nettoyage..."
    
    # Nettoyer le cache
    if [ -d "$PROJECT_ROOT/cache" ]; then
        rm -rf "$PROJECT_ROOT/cache/*"
    fi
    
    # Optimiser l'autoloader
    cd "$PROJECT_ROOT" || error "Impossible de se déplacer dans le répertoire du projet"
    composer dump-autoload --optimize
    
    log "Nettoyage terminé"
}

# Fonction principale
main() {
    log "Démarrage du déploiement..."
    
    check_prerequisites
    create_backup
    update_files
    update_database
    check_integrity
    cleanup
    
    log "Déploiement terminé avec succès"
}

# Exécuter le script
main 