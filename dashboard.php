<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/demandes.php';

$currentUser = requireLogin();

$roleMessages = [
    'Administrateur' => 'Vous disposez d’un accès complet aux fonctionnalités de gestion',
    "Agent d'accueil" => 'Vous pouvez enregistrer, consulter et rechercher les réclamations',
    "Agent d'écart" => 'Vous pouvez suivre les demandes et préparer leurs affectations',
    "Chef d'équipe" => 'Vous pouvez suivre les interventions confiées à votre équipe',
    'Dépanneur' => 'Vous pouvez consulter les demandes activées qui vous sont transmises.',
    'Chef de division' => 'Vous pouvez superviser l’activité et consulter les informations de suivi',
];

$roleLinks = [
    'Administrateur' => [
        'demandes/liste.php' => 'Liste des demandes',
        'demandes/recues.php' => 'Demandes reçues',
        'affectations/liste.php' => 'Liste des affectations',
        'interventions/liste.php' => 'Interventions',
        'historiques/liste.php' => 'Historiques',
        'statistiques.php' => 'Statistiques',
        'techniciens/ajouter.php' => 'Ajouter un technicien',
    ],
    "Agent d'accueil" => [
        
        'demandes/reclamation.php' => 'Nouvelle réclamation',
        'demandes/liste.php' => 'Liste des réclamations',
    ],
    "Agent d'écart" => [
        'demandes/recues.php' => 'Demandes reçues',
        'affectations/ajouter.php' => 'Nouvelle affectation',
        'demandes/liste.php' => 'Liste des demandes',
        'affectations/liste.php' => 'Liste des affectations',
    ],
    "Chef d'équipe" => [
        'interventions/liste.php' => 'Demandes affectées à organiser',
        'techniciens/ajouter.php' => 'Ajouter un technicien',
    ],
    'Dépanneur' => [
        'demandes/actives.php' => 'Demandes actives',
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


$statistiques = [];

if (hasPermission('statistique.consulter')) {
    try {
        $statistiques = getStatistiquesParStatut(getDatabaseConnection());
    }
    catch (RuntimeException $exception) {
        $statistiques = [];
    }
}

define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main>
    <h1>Tableau de bord</h1>
    <p class="accueil-titre">
        Bienvenue,
        <?= htmlspecialchars($currentUser['prenom'] . ' ' . $currentUser['nom'], ENT_QUOTES, 'UTF-8') ?>.
    </p>
    
    <p class="accueil-role"><?= htmlspecialchars($roleMessage, ENT_QUOTES, 'UTF-8') ?></p>
    <?php if ($statistiques !== []): ?>
        <?php
        $cartes = [
            'EN_ATTENTE' => ['En attente', 'carte-en-attente'],
            'AFFECTEE' => ['Affectées', 'carte-affectee'],
            'EN_COURS' => ['En cours', 'carte-en-cours'],
            'RESOLUE' => ['Résolues', 'carte-resolue'],
        ];
        ?>

        <div class="cartes">
            <?php foreach ($cartes as $statut => [$libelle, $classe]): ?>
                <div class="carte <?= $classe ?>">
                    <p class="carte-libelle">
                        <?= htmlspecialchars($libelle, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <p class="carte-valeur">
                        <?= (int) ($statistiques[$statut] ?? 0) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($liens !== []): ?>
        <h2>Accès rapides</h2>
        <ul class="acces-rapides">
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