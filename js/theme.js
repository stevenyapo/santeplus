// Fonction pour changer le thème
function setTheme(theme) {
    const html = document.documentElement;
    
    // Supprimer les classes de thème existantes
    html.classList.remove('theme-transition', 'dark-mode', 'light-mode');
    
    // Ajouter la classe de transition
    html.classList.add('theme-transition');
    
    // Définir le thème
    html.setAttribute('data-bs-theme', theme);
    html.classList.add(theme === 'dark' ? 'dark-mode' : 'light-mode');
    
    // Sauvegarder le thème
    localStorage.setItem('theme', theme);
    
    // Forcer le reflow pour s'assurer que la transition fonctionne
    html.offsetHeight;
    
    // Supprimer la classe de transition après un court délai
    setTimeout(() => {
        html.classList.remove('theme-transition');
    }, 300);
    
    // Recharger les événements de la page
    document.querySelectorAll('button, a, select').forEach(element => {
        const clone = element.cloneNode(true);
        element.parentNode.replaceChild(clone, element);
    });
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    const themeSwitcher = document.getElementById('themeSwitcher');
    
    // Gestionnaire de clic sur le bouton de thème
    if (themeSwitcher) {
        themeSwitcher.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        });
    }
    
    // Appliquer le thème sauvegardé au chargement
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        setTheme(savedTheme);
    } else {
        // Utiliser le thème du système par défaut
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        setTheme(prefersDark ? 'dark' : 'light');
    }
    
    // Écouter les changements de préférence système
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (!localStorage.getItem('theme')) {
            setTheme(e.matches ? 'dark' : 'light');
        }
    });
}); 