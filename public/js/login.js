// Affiche ou masque le mot de passe
const champ = document.getElementById('mot_de_passe');
const bouton = document.getElementById('toggle-mot-de-passe');

if (champ && bouton) {
    bouton.addEventListener('click', function () {
        const estCache = champ.type === 'password';
        champ.type = estCache ? 'text' : 'password';

        bouton.classList.toggle('is-visible', estCache);
        bouton.setAttribute('aria-pressed', estCache ? 'true' : 'false');
        bouton.setAttribute(
            'aria-label',
            estCache ? 'Masquer le mot de passe' : 'Afficher le mot de passe'
        );
    });
}