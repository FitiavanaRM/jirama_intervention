<?php 
 
declare(strict_types=1); 
 
require_once __DIR__ . '/../includes/auth.php'; 
require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../includes/demandes.php'; 
 
requireRole([ 
    'Administrateur', 
    "Agent d'accueil", 
    "Agent d'écart", 
    'Chef de division', 
]); 

$estAccueil = hasRole("Agent d'accueil");
$demandes = []; 
$errorMessage = ''; 
 
try { 
    $demandes = getDemandes(getDatabaseConnection()); 
} catch (RuntimeException $exception) { 
    $errorMessage = 'Les demandes ne peuvent pas être chargées pour le moment'; 
} 
 
$pageTitle = ($estAccueil ? 'Réclamations' : 'Demandes') . ' | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true); 
 
require_once __DIR__ . '/../includes/header.php'; 
require_once __DIR__ . '/../includes/sidebar.php'; 
?> 
<main> 
    <h1><?= $estAccueil ? 'Réclamations' : 'Demandes d’intervention' ?></h1> 
    <?php if ($errorMessage !== ''): ?> 
        <p role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p> 
    <?php elseif ($demandes === []): ?> 
        <p><?= $estAccueil ? 'Aucune réclamation n’est enregistrée.' : 'Aucune demande n’est enregistrée.' ?></p> 
    <?php else: ?>
        <div class="tableau-defilant">
        <table>
            <thead> 
                <tr> 
                    <th>Référence</th> 
                    <th>Client</th> 
                    <th>Type</th> 
                    <th>Date</th> 
                    <th>Statut</th>
                    <th>Intervention</th>
                    <th>Enregistrée par</th>
                    <th>Action</th>
                </tr> 
            </thead> 
            <tbody> 
                <?php foreach ($demandes as $demande): ?> 
                    <tr> 
                        <td><?= htmlspecialchars((string) $demande['reference'], ENT_QUOTES, 'UTF-8') ?></td> 
                        <td><?= htmlspecialchars((string) $demande['nom_client'], ENT_QUOTES, 'UTF-8') ?></td> 
                        <td><?= htmlspecialchars((string) $demande['type_demande'], ENT_QUOTES, 'UTF-8') ?></td> 
                        <td><?= htmlspecialchars((string) $demande['date_demande'], ENT_QUOTES, 'UTF-8') ?></td> 
                        <td><?= renderStatutBadge((string) $demande['statut']) ?></td>
                        <td>
                            <?php if (($demande['code_intervention'] ?? null) !== null): ?>
                                <?= renderCodeInterventionBadge((string) $demande['code_intervention']) ?>
                            <?php else: ?>
                            —
                            <?php endif; ?>
                        </td>
                        <td> 
                            <?= htmlspecialchars( 
                                (string) $demande['createur_prenom'] . ' ' . (string) $demande['createur_nom'], 
                                ENT_QUOTES, 
                                'UTF-8' 
                            ) ?> 
                        </td> 
                        <td>
                            <a href="details.php?id_demande=<?= (int) $demande['id_demande'] ?>">Détails</a>
                        </td>
                    </tr> 
                <?php endforeach; ?> 
            </tbody> 
        </table>
        </div>
    <?php endif; ?>
</main> 
<?php require_once __DIR__ . '/../includes/footer.php'; ?>