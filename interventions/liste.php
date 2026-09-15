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
</main>