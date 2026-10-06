<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demandes.php';

requirePermission('demande.modifier');

$demandeId = filter_input(
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? INPUT_POST : INPUT_GET,
    'id_demande',
    FILTER_VALIDATE_INT
);

if (!is_int($demandeId) || $demandeId < 1) {
    http_response_code(404);
    exit('Demande introuvable');
}

$errorMessage = '';
$successMessage = (string) ($_SESSION['demande_modif_success'] ?? '');
unset($_SESSION['demande_modif_success']);

try {
    $connection = getDatabaseConnection();
    $demande = getDemandeById($connection, $demandeId);
    if ($demande === null) {
        http_response_code(404);
        exit('Demande introuvable.');
    }

    $formData = [
        'nom_client' => (string) $demande['nom_client'],
        'telephone_client' => (string) $demande['telephone_client'],
        'adresse_client' => (string) $demande['adresse_client'],
        'type_demande' => (string) $demande['type_demande'],
        'description_probleme' => (string) $demande['description_probleme'],
    ];
}