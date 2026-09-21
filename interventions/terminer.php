<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demandes.php';
require_once __DIR__ . '/../includes/interventions.php';

requirePermission('intervention.terminer');

$currentUser = getCurrentUser();
if ($currentUser === null) {
    denyAccess();
}

$demandeId = filter_input(
    INPUT_GET,
    'id_demande',
    FILTER_VALIDATE_INT
);