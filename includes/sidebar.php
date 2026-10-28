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

?>
<aside>
    <p class="role-utilisateur">
        <?= htmlspecialchars($currentUser['nom_role'], ENT_QUOTES, 'UTF-8') ?>
    </p>
    <nav aria-label="Navigation principale">
        <?php
            $base = getProjectBaseUrl();
            $actif = ' class="actif" aria-current="page"';
        ?>
        <ul>
            <li><a href="<?= $base ?>/dashboard.php"<?= isCurrentPage('/dashboard.php') ? $actif : '' ?>>Tableau de bord</a></li>

            <?php if (hasPermission('demande.consulter') || hasPermission('demande.creer')): ?>
                <li><a href="<?= $base ?>/demandes/liste.php"<?= isCurrentPage('/demandes/liste.php') ? $actif : '' ?>><?= hasRole("Agent d'accueil") ? 'Réclamations' : 'Demandes' ?></a></li>
            <?php endif; ?>

            <?php if (hasPermission('demande.creer')): ?>
                <?php if (hasRole("Agent d'accueil")): ?>
                    <li><a href="<?= $base ?>/demandes/reclamation.php"<?= isCurrentPage('/demandes/reclamation.php') ? $actif : '' ?>>Nouvelle réclamation</a></li>
                <?php else: ?>
                    <li><a href="<?= $base ?>/demandes/ajouter.php"<?= isCurrentPage('/demandes/ajouter.php') ? $actif : '' ?>>Nouvelle demande</a></li>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (hasPermission('demande.activer')): ?>
                <li><a href="<?= $base ?>/demandes/recues.php"<?= isCurrentPage('/demandes/recues.php') ? $actif : '' ?>>Demandes reçues</a></li>
            <?php endif; ?>
            
            <?php if (hasPermission('affectation.creer')): ?>
                <li><a href="<?= $base ?>/affectations/ajouter.php"<?= isCurrentPage('/affectations/ajouter.php') ? $actif : '' ?>>Nouvelle affectation</a></li>
                <li><a href="<?= $base ?>/affectations/liste.php"<?= isCurrentPage('/affectations/liste.php') ? $actif : '' ?>>Affectations</a></li>
            <?php endif; ?>

            <?php if (
                hasPermission('intervention.organiser')
                ||
                hasPermission('intervention.personnelle.consulter')
            ): ?>
                <li><a href="<?= $base ?>/interventions/liste.php"<?= isCurrentPage('/interventions/liste.php') ? $actif : '' ?>>Interventions</a></li>
            <?php endif; ?>

            <?php if (
                hasPermission('technicien.consulter')
                ||
                hasPermission('technicien.superviser')
                ||
                hasRole('Administrateur')
            ): ?>
                <li><a href="<?= $base ?>/techniciens/liste.php"<?= isCurrentPage('/techniciens/liste.php') ? $actif : '' ?>>Techniciens</a></li>
            <?php endif; ?>

            <?php if (
                hasPermission('equipe.consulter')
                ||
                hasRole('Administrateur')
            ): ?>
                <li><a href="<?= $base ?>/equipes/liste.php"<?= isCurrentPage('/equipes/liste.php') ? $actif : '' ?>>Équipes</a></li>
            <?php endif; ?>

            <?php if (hasRole('Administrateur')): ?>
                <li><a href="<?= $base ?>/utilisateurs/liste.php"<?= isCurrentPage('/utilisateurs/liste.php') ? $actif : '' ?>>Utilisateurs</a></li>
            <?php endif; ?>

            <?php if (hasPermission('historique.consulter')): ?>
                <li><a href="<?= $base ?>/historiques/liste.php"<?= isCurrentPage('/historiques/liste.php') ? $actif : '' ?>>Historique</a></li>
            <?php endif; ?>

            <?php if (hasPermission('statistique.consulter')): ?>
                <li><a href="<?= $base ?>/statistiques.php"<?= isCurrentPage('/statistiques.php') ? $actif : '' ?>>Statistiques</a></li>
            <?php endif; ?>

            <li><a href="<?= $base ?>/parametres.php"<?= isCurrentPage('/parametres.php') ? $actif : '' ?>>Paramètres</a></li>
            <li><a href="<?= $base ?>/logout.php">Déconnexion</a></li>
        </ul>
    </nav>
</aside>