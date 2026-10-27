<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/utilisateurs.php';

$currentUser = requireLogin();

$compte = null;
$errorMessage = '';

try {
    $compte = getUtilisateurById(
        getDatabaseConnection(),
        $currentUser['id_utilisateur']
    );

    if ($compte === null) {
        $errorMessage = 'Votre compte est introuvable';
    }
} catch (RuntimeException $exception) {
    $errorMessage = 'Les informations du compte ne peuvent pas être chargées pour le moment';
}

$pageTitle = 'Paramètres | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

?>
<main>
    <h1>Paramètres</h1>
    <p class="sous-titre">Consultez vos informations et choisissez l’apparence de l’application</p>

    <?php if ($errorMessage !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <div class="parametres-grille">
        <?php if ($compte !== null): ?>
            <section class="carte-parametre">
                <h2>Informations du profil</h2>
                <div class="profil-entete">
                    <span class="profil-avatar" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="34" height="34" fill="currentColor">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7z"></path>
                        </svg>
                    </span>
                    <div>
                        <p class="profil-titre">Profil utilisateur</p>
                        <p class="profil-sous-titre">Vos informations personnelles</p>
                    </div>
                </div>

                <div class="champ-lecture">
                    <span class="champ-libelle">Nom complet</span>
                    <div class="champ-valeur">
                        <?= htmlspecialchars(
                            (string) $compte['prenom'] . ' ' . (string) $compte['nom'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>
                </div>

                <div class="champ-lecture">
                    <span class="champ-libelle">Adresse e-mail</span>
                    <div class="champ-valeur">
                        <?= htmlspecialchars((string) $compte['email'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>

                <div class="champ-lecture">
                    <span class="champ-libelle">Téléphone</span>
                    <div class="champ-valeur">
                        <?= htmlspecialchars(
                            (string) (($compte['telephone'] ?? '') !== '' ? $compte['telephone'] : '—'),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>
                </div>

                <div class="champ-lecture">
                    <span class="champ-libelle">Rôle</span>
                    <div class="champ-valeur champ-valeur-fige">
                        <?= htmlspecialchars($currentUser['nom_role'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </section>

        <?php endif; ?>

        <section class="carte-parametre">
            <h2>Mode d’affichage</h2>
            <p class="carte-description">Choisissez l’apparence de l’application.</p>
            <div class="choix-themes" role="radiogroup" aria-label="Mode d’affichage">
                <label class="choix-theme">
                    <span class="apercu apercu-clair" aria-hidden="true">
                        <span class="apercu-barre"></span>
                        <span class="apercu-contenu">
                            <span class="apercu-ligne"></span>
                            <span class="apercu-ligne"></span>
                            <span class="apercu-ligne"></span>
                        </span>
                    </span>
                    <span class="choix-ligne">
                        <input type="radio" name="theme" value="light">
                        <span class="choix-titre">Mode clair</span>
                    </span>
                    <span class="choix-texte">Interface plus lumineuse et classique</span>
                </label>

                <label class="choix-theme">
                    <span class="apercu apercu-sombre" aria-hidden="true">
                        <span class="apercu-barre"></span>
                        <span class="apercu-contenu">
                            <span class="apercu-ligne"></span>
                            <span class="apercu-ligne"></span>
                            <span class="apercu-ligne"></span>
                        </span>
                    </span>
                    <span class="choix-ligne">
                        <input type="radio" name="theme" value="dark">
                        <span class="choix-titre">Mode sombre</span>
                    </span>
                    <span class="choix-texte">Idéal pour un meilleur confort visuel la nuit</span>
                </label>

            </div>

            <p class="info-theme">Le mode d’affichage est appliqué immédiatement et mémorisé sur cet ordinateur</p>
        </section>
    </div>
    <script src="<?= getProjectBaseUrl() ?>/public/js/parametres.js?v=<?= (int) @filemtime(__DIR__ . '/public/js/parametres.js') ?>"></script>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>