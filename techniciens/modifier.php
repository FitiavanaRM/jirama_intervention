<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/affectations.php';
require_once __DIR__ . '/../ includes/techniciens.php';

requireRole(['Administrateur']);

$technicienId = filter_input(
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? INPUT_POST : INPUT_GET,
    'id_technicien',
    FILTER_VALIDATE_INT
);

if (!is_int($technicienId) || $technicienId < 1) {
    http_response_code(404);
    exit('Technicien introuvable.');
}

$errorMessage = '';
$successMessage = (string) ($_SESSION['technicien_success'] ?? '');
unset($_SESSION['technicien_success']);

$equipes = [];
$utilisateursDisponibles = [];

try {
    $connection = getDatabaseConnection();

    $technicien = getTechnicienById($connection, $technicienId);


}