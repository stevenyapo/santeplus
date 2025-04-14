// Fonction pour définir le thème
function setTheme(theme) {
    const html = document.documentElement;
    const themeSwitcher = document.getElementById('themeSwitcher');
    const themeIcon = themeSwitcher.querySelector('i');
    
    // Supprimer les classes existantes
    html.classList.remove('dark', 'light');
    
    // Ajouter la classe de transition
    html.classList.add('theme-transition');
    
    // Définir le nouveau thème
    html.classList.add(theme);
    
    // Mettre à jour l'icône
    themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    
    // Sauvegarder le thème
    localStorage.setItem('theme', theme);
    
    // Supprimer la classe de transition après l'animation
    setTimeout(() => {
        html.classList.remove('theme-transition');
    }, 300);
}

// Fonction pour initialiser le thème
function initTheme() {
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Utiliser le thème sauvegardé ou la préférence système
    const initialTheme = savedTheme || (prefersDark ? 'dark' : 'light');
    setTheme(initialTheme);
}

// Fonction pour gérer le clic sur le bouton
function handleThemeSwitch() {
    const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    setTheme(newTheme);
}

// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le thème
    initTheme();
    
    // Ajouter l'écouteur d'événements au bouton
    const themeSwitcher = document.getElementById('themeSwitcher');
    if (themeSwitcher) {
        themeSwitcher.addEventListener('click', handleThemeSwitch);
    }
    
    // Écouter les changements de préférence système
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (!localStorage.getItem('theme')) {
            setTheme(e.matches ? 'dark' : 'light');
        }
    });
}); 