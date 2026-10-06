<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/utilisateurs.php';

requireRole(['Administrateur']);

$utilisateurs = [];
$errorMessage = '';

try {
    $utilisateurs = getUtilisateurs(getDatabaseConnection());
}
catch (RuntimeException $exception) {
    $errorMessage ='Les utilisateurs ne peuvent pas être chargés pour le moment';
}

$pageTitle = 'Utilisateurs | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>
<main>

    <h1>Utilisateurs</h1>

    <?php if ($errorMessage !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>

    <?php elseif ($utilisateurs === []): ?>
        <p>Aucun utilisateur n’est enregistré.</p>

    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Téléphone</th>
                    <th>E-mail</th>
                    <th>Rôle</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($utilisateurs as $utilisateur): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars(
                                (string) $utilisateur['prenom'] . ' ' . (string) $utilisateur['nom'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) ($utilisateur['telephone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) $utilisateur['email'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) $utilisateur['nom_role'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <a href="modifier.php?id_utilisateur=<?= (int) $utilisateur['id_utilisateur'] ?>">
                                Modifier
                            </a>
                        </td>
                    </tr>

                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>