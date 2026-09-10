<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demandes.php';

requirePermission('demande.creer');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    exit;
}

$currentUser = getCurrentUser();

if ($currentUser === null) {
    denyAccess();
}

header('Content-Type: application/json; charset=UTF-8');

try {
    $demande = createDemande(
        getDatabaseConnection(),
        $_POST,
        $currentUser['id_utilisateur']
    );

    http_response_code(201);
    echo json_encode([
        'message' => 'Demande créée avec succès.',
        'id_demande' => $demande['id_demande'],
        'reference' => $demande['reference'],
    ], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode([
        'message' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
} catch (RuntimeException $exception) {
    http_response_code(500);
    echo json_encode([
        'message' => 'La demande n’a pas pu être créée.',
    ], JSON_UNESCAPED_UNICODE);
}