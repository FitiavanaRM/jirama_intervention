<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/utilisateurs.php';

requireRole(['Administrateur']);

$formData = [
    'nom' => '',
    'prenom' => '',
    'telephone' => '',
    'email' => '',
    'id_role' => '',
];

$errrorMessage = '';
$successMessage = (string) ($_SESSION['utilisateur_success'] ?? '');
unset($_SESSION['utilisateur_success']);
$roles = [];