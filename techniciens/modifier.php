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
    if ($technicien === null) {
        http_response_code(404);
        exit('Technicien introuvable');
    }

    $formData = [
        'nom' => (string) $technicien['nom'],
        'prenom' => (string) $technicien['prenom'],
        'telephone' => (string) ($technicien['telephone'] ?? ''),
        'fonction' => (string) ($technicien['fonction'] ?? ''),
        'id_equipe' => (string) $technicien['id_equipe'],
        'id_utilisateur' => $technicien['id_utilisateur'] !== null
            ? (string) $technicien['id_utilisateur']
            : '',
    ];

    $equipes = getEquipes($connection);
    $utilisateursDisponibles = getUtilisateursTechnicienDisponiblesPourModification(
        $connection,
        $technicien['id_utilisateur'] !== null
            ? (int) $technicien['id_utilisateur']
            : null
    );

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        foreach ($formData as $field => $defaultValue) {
            $value = $_POST[$field] ?? '';
            $formData[$field] = is_string($value) ? $value : '';
        }
    }
}