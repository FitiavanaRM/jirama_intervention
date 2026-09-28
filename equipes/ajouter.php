<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/equipes.php';

requireRole(['Administrateur']);

$formData = [
    'nom_equipe' => '',
    'type_equipe' => '',
    'description' => '',
];

$errorMessage = '';
$successMessage = (string) ($_SESSION['equipe_success'] ?? '');
unset($_SESSION['equipe_success']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach ($formData as $field => $defaultValue) {
        $value = $_POST[$field] ?? '';
        $formData[$field] = is_string($value) ? $value : '';
    }
}