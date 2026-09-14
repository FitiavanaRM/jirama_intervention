<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/affectations.php';

requireRole([
    'Administrateur',
    "Agent d'écart",
    "Chef d'équipe",
    'Chef de division',
]);

$affectations = [];
$errorMessage = '';

try {
    $affectations = getAffectations(getDatabaseConnection());
} catch (RuntimeException $exception) {
    $errorMessage = 'Les affectations ne peuvent pas être chargées pour le moment';
}

$pageTitle = 'Affectations | JIRAMA Intervention';

define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>
<main>
    <h1>Affectations</h1>
    <?php if ($errorMessage !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>

    <?php elseif ($affectations === []): ?>
        <p>Aucune affectation n’est enregistrée</p>

    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Client</th>
                    <th>Équipe</th>
                    <th>Technicien</th>
                    <th>Date d’affectation</th>
                    <th>Observation</th>
                </tr>
            </thead>
            <tbody>
                 <?php foreach ($affectations as $affectation): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars(
                                (string) $affectation['reference'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                (string) $affectation['nom_client'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars(
                                (string) $affectation['nom_equipe'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>

                        <td>
                            <?php if ($affectation['id_technicien'] !== null): ?>
                                <?= htmlspecialchars(
                                    (string) $affectation['technicien_prenom'] .
                                    ' ' .
                                    (string) $affectation['technicien_nom'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
</main>