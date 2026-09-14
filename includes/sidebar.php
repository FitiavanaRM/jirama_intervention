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
        
    </nav>
</aside>