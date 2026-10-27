// Gestion du thème clair/sombre
(function () {
    var CLE = 'jirama_theme';

    function lireTheme() {
        try {
            var valeur = localStorage.getItem(CLE);
            return valeur === 'light' || valeur === 'dark' ? valeur : 'dark';
        } catch (erreur) {
            return 'dark';
        }
    }

    function appliquerTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
    }

    function enregistrerTheme(theme) {
        try {
            localStorage.setItem(CLE, theme);
        } catch (erreur) {
            // Stockage indisponible : le choix vaut seulement pour cette page
        }
    }

    // Appliqué dès le chargement, avant l'affichage de la page
    appliquerTheme(lireTheme());

    // Disponible pour la page Paramètres
    window.themeJirama = {
        lire: lireTheme,
        choisir: function (theme) {
            if (theme !== 'light' && theme !== 'dark') {
                return;
            }
            appliquerTheme(theme);
            enregistrerTheme(theme);
        }
    };
})();