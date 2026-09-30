<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/equipes.php';

requireRole([
    'Administrateur',
    "Agent d'écart",
    "Chef d'équipe",
    'Chef de division',
]);

$equipes = [];
$errorMessage = '';

try {
    $equipes = getEquipesAvecEffectif(getDatabaseConnection());
} 
catch (RuntimeException $exception) {
    $errorMessage ='Les équipes ne peuvent pas être chargées pour le moment';
}

$pageTitle = 'Équipes | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>
<main>
    <h1>Équipes</h1>
    <?php if ($errorMessage !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>

    <?php elseif ($equipes === []): ?>
        <p>Aucune équipe n’est enregistrée</p>

    <?php else: ?>

        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Techniciens</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($equipes as $equipe): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars((string) $equipe['nom_equipe'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) ($equipe['type_equipe'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= nl2br(htmlspecialchars((string) ($equipe['description'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                        </td>
                        <td>
                            <?= (int) $equipe['nombre_techniciens'] ?>
                        </td>
                    </tr>

                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>