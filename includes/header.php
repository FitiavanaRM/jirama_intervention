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
    <script>
        // Applique le thème choisi par l'utilisateur avant l'affichage de la page
        try {
            var themeEnregistre = localStorage.getItem('jirama_theme');
            if (themeEnregistre === 'light' || themeEnregistre === 'dark') {
                document.documentElement.setAttribute('data-theme', themeEnregistre);
            }
        } catch (erreur) {
            // Stockage indisponible : on garde le thème sombre par défaut
        }
    </script>
    <link rel="stylesheet" href="<?= getProjectBaseUrl() ?>/public/css/style.css?v=<?= (int) @filemtime(__DIR__ . '/../public/css/style.css') ?>">
</head>
<body>
    <div class="application">
        <header>
            <p>JIRAMA Toliara — Gestion des interventions</p>
            <?php $utilisateurEntete = getCurrentUser(); ?>

            <?php if ($utilisateurEntete !== null): ?>
                <span class="entete-utilisateur">
                    <?= htmlspecialchars(
                        $utilisateurEntete['prenom'] . ' ' . $utilisateurEntete['nom'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            <?php endif; ?>
        </header>