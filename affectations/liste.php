<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/affectations.php';

requireRole([
    'Administrateur',
    "Agent d'écart",
    "Chef d'équipe",
    'Chef de division',
]);

$affectations = [];
$errorMessage = '';

try {
    $affectations = getAffectations(getDatabaseConnection());
} catch (RuntimeException $exception) {
    $errorMessage = 'Les affectations ne peuvent pas être chargées pour le moment';
}