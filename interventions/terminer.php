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
if ($demandeId === false && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $demandeId = filter_input(
        INPUT_POST,
        'id_demande',
        FILTER_VALIDATE_INT
    );
}

if (!is_int($demandeId) || $demandeId < 1) {
    http_response_code(404);
    exit('Demande introuvable');
}

$formData = [
    'probleme_constate' => '',
    'travail_effectue' => '',
    'resultat' => '',
    'observation' => '',
];

$errorMessage = '';

try {
    $connection = getDatabaseConnection();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {