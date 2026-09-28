<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/equipes.php';

requireRole([
    'Administrateur',
    "Agent d'écart",
    "Chef d'équipe",
    'Chef de division',
]);

$equipes = [];
$errorMessage = '';

$pageTitle = 'Équipes | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>