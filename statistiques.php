<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/demandes.php';
require_once __DIR__ . '/includes/affectations.php';

requirePermission('statistique.consulter');
$statistiques = [];
$statistiquesEquipes = [];
$indicateurs = [];
$errorMessage = '';

try {
    $connection = getDatabaseConnection();
    $statistiques = getStatistiquesParStatut($connection);
    $statistiquesEquipes = getStatistiquesParEquipe($connection);
    $indicateurs = getIndicateursGeneraux($connection);
}
catch (RuntimeException $exception) {
    $errorMessage ='Les statistiques ne peuvent pas être chargées pour le moment';
}

$pageTitle = 'Statistiques | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

?>
<main>
    <h1>Statistiques des demandes</h1>
    <?php if ($errorMessage !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>

    <?php else: ?>
        <div class="cartes">
            <div class="carte carte-en-attente">
                <p class="carte-libelle">Total des demandes</p>
                <p class="carte-valeur"><?= (int) $indicateurs['total'] ?></p>
            </div>

            <div class="carte carte-en-cours">
                <p class="carte-libelle">En cours</p>
                <p class="carte-valeur"><?= (int) $indicateurs['en_cours'] ?></p>
            </div>

            <div class="carte carte-resolue">
                <p class="carte-libelle">Taux de résolution</p>
                <p class="carte-valeur"><?= (int) $indicateurs['taux_resolution'] ?> %</p>
            </div>

            <div class="carte carte-affectee">
                <p class="carte-libelle">Délai moyen d’intervention</p>
                <p class="carte-valeur">
                    <?= htmlspecialchars(
                        formatDuree($indicateurs['delai_moyen_minutes']),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>
            </div>
        </div>

        <h2>Répartition par statut</h2>
        <?php $maxStatut = max(1, ...array_values($statistiques)); ?>
        <div class="barres">
            <?php foreach ($statistiques as $statut => $total): ?>
                <div class="barre-ligne">
                    <span class="barre-libelle">
                        <?= renderStatutBadge($statut) ?>
                    </span>
                    <div class="barre-fond">
                        <div
                            class="barre-remplie barre-<?= htmlspecialchars(strtolower(str_replace('_', '-', $statut)), ENT_QUOTES, 'UTF-8') ?>"
                            style="width: <?= (int) round($total * 100 / $maxStatut) ?>%"
                        ></div>
                    </div>
                    <span class="barre-valeur"><?= (int) $total ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <h2>Afféctation par équipe</h2>
        <table>
            <thead>
                <tr>
                    <th>Equipe</th>
                    <th>Nombre d'affectations</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statistiquesEquipes as $equipe): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars((string) $equipe['nom_equipe'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= (int) $equipe['total'] ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>