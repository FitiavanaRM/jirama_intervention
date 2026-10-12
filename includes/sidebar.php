<?php

declare(strict_types=1);

if (!defined('JIRAMA_LAYOUT')) {
    http_response_code(403);
    exit('Accès direct interdit.');
}

$currentUser = getCurrentUser();

if ($currentUser === null) {
    denyAccess();
}
// verifie si l utilisateur est connecté et si oui affiche son nom et son role dans la sidebar
?>

<aside>
    <p>
        <?= htmlspecialchars($currentUser['prenom'] . ' ' . $currentUser['nom'], ENT_QUOTES, 'UTF-8') ?>
        —
        <?= htmlspecialchars($currentUser['nom_role'], ENT_QUOTES, 'UTF-8') ?>
    </p>
        <nav aria-label="Navigation principale">

        <?php $base = getProjectBaseUrl(); ?>

        <ul>
            <li><a href="<?= $base ?>/dashboard.php">Tableau de bord</a></li>

            <?php if (hasPermission('demande.consulter') || hasPermission('demande.creer')): ?>
                <li><a href="<?= $base ?>/demandes/liste.php">Demandes</a></li>
            <?php endif; ?>

            <?php if (hasPermission('demande.creer')): ?>
                <li><a href="<?= $base ?>/demandes/ajouter.php">Nouvelle demande</a></li>
            <?php endif; ?>

            <?php if (hasPermission('affectation.creer')): ?>
                <li><a href="<?= $base ?>/affectations/ajouter.php">Nouvelle affectation</a></li>
                <li><a href="<?= $base ?>/affectations/liste.php">Affectations</a></li>
            <?php endif; ?>

            <?php if (
                hasPermission('intervention.organiser')
                ||
                hasPermission('intervention.personnelle.consulter')
            ): ?>
                <li><a href="<?= $base ?>/interventions/liste.php">Interventions</a></li>
            <?php endif; ?>

            <?php if (
                hasPermission('technicien.consulter')
                ||
                hasPermission('technicien.superviser')
                ||
                hasRole('Administrateur')
            ): ?>
                <li><a href="<?= $base ?>/techniciens/liste.php">Techniciens</a></li>
            <?php endif; ?>y    

            <?php if (
                hasPermission('equipe.consulter')
                ||
                hasRole('Administrateur')
            ): ?>
                <li><a href="<?= $base ?>/equipes/liste.php">Équipes</a></li>
            <?php endif; ?>

            <?php if (hasRole('Administrateur')): ?>
                <li><a href="<?= $base ?>/utilisateurs/liste.php">Utilisateurs</a></li>
            <?php endif; ?>

            <?php if (hasPermission('historique.consulter')): ?>
                <li><a href="<?= $base ?>/historiques/liste.php">Historique</a></li>
            <?php endif; ?>

            <?php if (hasPermission('statistique.consulter')): ?>
                <li><a href="<?= $base ?>/statistiques.php">Statistiques</a></li>
            <?php endif; ?>

            <li><a href="<?= $base ?>/logout.php">Déconnexion</a></li>
        </ul>
    </nav>
</aside>