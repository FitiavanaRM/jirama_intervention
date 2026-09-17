<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/affectations.php';
require_once __DIR__ . '/../includes/techniciens.php';

requirePermission('technicien.superviser');

$currentUser = getCurrentUser();
if ($currentUser === null) {
    denyAccess();
}

$formData = [
    'nom' => '',
    'prenom' => '',
    'telephone' => '',
    'fonction' => '',
    'id_equipe' => '',
    'id_utilisateur' => '',
];

$errorMessage = '';
$successMessage = (string) ($_SESSION['technicien_success'] ?? '');
unset($_SESSION['technicien_success']);