<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demandes.php';
require_once __DIR__ . '/../includes/affectations.php';

requirePermission('affectation.creer');

$demandes = [];
$equipes = [];
$techniciensParEquipe = [];
$errorMessage = '';

try {
    $connection = getDatabaseConnection();
    $demandes = getDemandes($connection);
    $equipes = getEquipes($connection);

    foreach ($equipes as $equipe) {
    $equipeId = (int) $equipe['id_equipe'];

    $techniciensParEquipe[$equipeId] = getTechniciensByEquipe($connection, $equipeId);
    }
}