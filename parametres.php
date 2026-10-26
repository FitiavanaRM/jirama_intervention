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
    <h2>Mon compte</h2>
    <?php if ($errorMessage !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>

    <?php else: ?>
        <dl>
            <dt>Nom</dt>
            <dd><?= htmlspecialchars((string) $compte['nom'], ENT_QUOTES, 'UTF-8') ?></dd>

            <dt>Prénom</dt>
            <dd><?= htmlspecialchars((string) $compte['prenom'], ENT_QUOTES, 'UTF-8') ?></dd>

            <dt>Adresse e-mail</dt>
            <dd><?= htmlspecialchars((string) $compte['email'], ENT_QUOTES, 'UTF-8') ?></dd>

            <dt>Téléphone</dt>
            <dd>
                <?= htmlspecialchars(
                    (string) (($compte['telephone'] ?? '') !== '' ? $compte['telephone'] : '—'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </dd>

            <dt>Rôle</dt>
            <dd><?= htmlspecialchars($currentUser['nom_role'], ENT_QUOTES, 'UTF-8') ?></dd>
        </dl>

    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>