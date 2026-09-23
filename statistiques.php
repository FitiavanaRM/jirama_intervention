<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/demandes.php';

requirePermission('statistique.consulter');
$statistiques = [];
$errorMessage = '';

try {
    $statistiques = getStatistiquesParStatut(
        getDatabaseConnection()
    );
}
catch (RuntimeException $exception) {
    $errorMessage ='Les statistiques ne peuvent pas être chargées pour le moment';
}

$pageTitle = 'Statistiques | JIRAMA Intervention';