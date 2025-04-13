// Fonction pour changer le thème
function setTheme(theme) {
    const html = document.documentElement;
    const body = document.body;
    
    if (!html || !body) {
        console.error('DOM elements not found');
        return;
    }
    
    // Supprimer les classes de thème existantes
    html.classList.remove('theme-transition', 'dark-mode', 'light-mode');
    
    // Ajouter la classe de transition
    html.classList.add('theme-transition');
    
    // Définir le thème
    html.setAttribute('data-bs-theme', theme);
    body.setAttribute('data-bs-theme', theme);
    html.classList.add(theme === 'dark' ? 'dark-mode' : 'light-mode');
    
    // Sauvegarder le thème
    localStorage.setItem('theme', theme);
    
    // Mettre à jour l'icône
    const themeToggle = document.querySelector('.theme-toggle');
    if (themeToggle) {
        themeToggle.innerHTML = theme === 'dark' ? 
            '<i class="fas fa-sun"></i>' : 
            '<i class="fas fa-moon"></i>';
    }
    
    // Forcer le reflow pour s'assurer que la transition fonctionne
    html.offsetHeight;
    
    // Supprimer la classe de transition après un court délai
    setTimeout(() => {
        html.classList.remove('theme-transition');
    }, 300);
}

// Attendre que le DOM soit prêt
document.addEventListener('DOMContentLoaded', function() {
    // Appliquer le thème initial
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        setTheme(savedTheme);
    } else {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        setTheme(prefersDark ? 'dark' : 'light');
    }
    
    // Gestionnaire de clic sur le bouton de thème
    document.addEventListener('click', function(e) {
        if (e.target.closest('.theme-toggle')) {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        }
    });
    
    // Écouter les changements de préférence système
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (!localStorage.getItem('theme')) {
            setTheme(e.matches ? 'dark' : 'light');
        }
    });
}); 