<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demandes.php';

requireRole([
    'Administrateur',
    "Chef d'équipe",
    'chef de division',
]); 

$demandes = [];
$errorMessage = '';

try {
    $demandes = getDemandesAffectees(getDatabaseConnection());
}
catch (RuntimeException $exception){
    $errorMessage = 'Les demandes affectées ne peuvent pas être chargées pour le moment';
}

$pageTitle = 'Interventions à organiser | JIRAMA Intervention';

define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>
<main>
    <h1>Demandes affectées à organiser</h1>

    <?php if ($errorMessage !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>

    <?php elseif ($demandes === []): ?>
        <p>Aucune demande affectée en attente d’organisation</p>

    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Équipe</th>
                    <th>Technicien</th>
                    <th>Date d’affectation</th>
                </tr>
            </thead>

            <tbody>
                 <?php foreach ($demandes as $demande): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars(
                                (string) $demande['reference'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars(
                                (string) $demande['nom_client'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars(
                                (string) $demande['type_demande'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars(
                                (string) $demande['nom_equipe'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                        <td>
                            <?php if ($demande['technicien_nom'] !== null): ?>
                                <?= htmlspecialchars(
                                    (string) $demande['technicien_prenom'] .
                                    ' ' .
                                    (string) $demande['technicien_nom'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars(
                                (string) $demande['date_affectation'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>