<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$currentUser = requireLogin();

$roleMessages = [
    'Administrateur' => 'Vous disposez d’un accès complet aux fonctionnalités de gestion.',
    "Agent d'accueil" => 'Vous pouvez enregistrer, consulter et rechercher les demandes.',
    "Agent d'écart" => 'Vous pouvez suivre les demandes et préparer leurs affectations.',
    "Chef d'équipe" => 'Vous pouvez suivre les interventions confiées à votre équipe.',
    'Technicien' => 'Vous pouvez consulter et renseigner vos interventions.',
    'Chef de division' => 'Vous pouvez superviser l’activité et consulter les informations de suivi.',
];

$roleLinks = [
    'Administrateur' => [
        'demandes/liste.php' => 'Liste des demandes',
        'affectations/liste.php' => 'Liste des affectations',
        'interventions/liste.php' => 'Interventions',
        'historiques/liste.php' => 'Historiques',
        'statistiques.php' => 'Statistiques',
        'techniciens/ajouter.php' => 'Ajouter un technicien',
    ],
    "Agent d'accueil" => [
        'demandes/ajouter.php' => 'Nouvelle demande',
        'demandes/liste.php' => 'Liste des demandes',
    ],
    "Agent d'écart" => [
        'affectations/ajouter.php' => 'Nouvelle affectation',
        'demandes/liste.php' => 'Liste des demandes',
        'affectations/liste.php' => 'Liste des affectations',
    ],
    "Chef d'équipe" => [
        'interventions/liste.php' => 'Demandes affectées à organiser',
        'techniciens/ajouter.php' => 'Ajouter un technicien',
    ],
    'Technicien' => [
        'interventions/liste.php' => 'Mes interventions en cours',
    ],
    'Chef de division' => [
        'historiques/liste.php' => 'Historiques',
        'statistiques.php' => 'Statistiques',
        'demandes/liste.php' => 'Liste de demandes',
    ],
];

$pageTitle = 'Tableau de bord | JIRAMA Intervention';
$roleMessage = $roleMessages[$currentUser['nom_role']] ?? 'Votre rôle ne possède pas encore de présentation dédiée.';
$liens = $roleLinks[$currentUser['nom_role']] ?? [];

define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main>
    <h1>Tableau de bord</h1>
    <p>
        Bienvenue,
        <?= htmlspecialchars($currentUser['prenom'] . ' ' . $currentUser['nom'], ENT_QUOTES, 'UTF-8') ?>.
    </p>
    <p>
        <?= htmlspecialchars($roleMessage, ENT_QUOTES, 'UTF-8') ?>
    </p>
    <p>Les indicateurs chiffrés seront ajoutés au module Statistiques</p>

    <?php if ($liens !== []): ?>
        <h2>Accès rapides</h2>
        <ul>
            <?php foreach ($liens as $url => $libelle): ?>
                <li>
                    <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($libelle, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>