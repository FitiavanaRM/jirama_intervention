<?php

declare(strict_types=1);

if (!defined('JIRAMA_LAYOUT')) {
    http_response_code(403);
    exit('Accès direct interdit.');
}

$pageTitle = $pageTitle ?? 'JIRAMA Intervention';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= getProjectBaseUrl() ?>/public/css/style.css">
    <link rel="stylesheet" href="<?= getProjectBaseUrl() ?>/public/css/styleSuite.css">
</head>
<body>
    <div class="application">
        <header style="display:flex; align-items:center; justify-content:space-between;">
            <p>JIRAMA Toliara — Gestion des interventions</p>
            <?php $utilisateurEntete = getCurrentUser(); ?>

            <?php if ($utilisateurEntete !== null): ?>
                <span style="font-family:'Segoe Script','Brush Script MT','Lucida Handwriting',cursive; font-size:15px; font-style:italic; letter-spacing:0.5px; color:#f5b800; padding-bottom:2px; border-bottom:1px solid rgba(245,184,0,0.45);">
                    <?= htmlspecialchars(
                        $utilisateurEntete['prenom'] . ' ' . $utilisateurEntete['nom'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            <?php endif; ?>
        </header>