// Page Paramètres sombre et clair
(function () {
    var CLE = 'jirama_theme';
    var radios = document.querySelectorAll('input[name="theme"]');

    if (radios.length === 0) {
        return;
    }

    function themeActuel() {
        return document.documentElement.getAttribute('data-theme') === 'light'
            ? 'light'
            : 'dark';
    }

    function marquerSelection() {
        var actuel = themeActuel();
        radios.forEach(function (radio) {
            radio.checked = radio.value === actuel;

            var carte = radio.closest('.choix-theme');
            if (carte) {
                carte.classList.toggle('is-selected', radio.checked);
            }
        });
    }

    radios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (radio.value !== 'light' && radio.value !== 'dark') {
                return;
            }

            document.documentElement.setAttribute('data-theme', radio.value);

            try {
                localStorage.setItem(CLE, radio.value);
            } catch (erreur) {
                // Stockage indisponible : le choix vaut seulement pour cette page
            }

            marquerSelection();
        });
    });

    marquerSelection();
})();