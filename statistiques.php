<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/demandes.php';
require_once __DIR__ . '/includes/affectations.php';

requirePermission('statistique.consulter');
$statistiques = [];
$errorMessage = '';

try {
    $connection = getDatabaseConnection();
    $statistiques = getStatistiquesParStatut($connection);
    $statistiquesEquipes = getStatistiquesParEquipe($connection);
}
catch (RuntimeException $exception) {
    $errorMessage ='Les statistiques ne peuvent pas être chargées pour le moment';
}

$pageTitle = 'Statistiques | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

?>
<main>
    <h1>Statistiques des demandes</h1>
    <?php if ($errorMessage !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Statut</th>
                    <th>Nombre de demandes</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($statistiques as $statut => $total): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($statut, ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= (int) $total ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

            </tbody>
        </table>

        <h2>Afféctation par équipe</h2>
        <table>
            <thead>
                <tr>
                    <th>Equipe</th>
                    <th>Nombre d'affectations</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statistiquesEquipes as $equipe): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars((string) $equipe['nom_equipe'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= (int) $equipe['total'] ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>