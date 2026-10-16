<?php 
 
declare(strict_types=1); 
 
require_once __DIR__ . '/../includes/auth.php'; 
require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../includes/techniciens.php'; 
 
requireRole([ 
    'Administrateur', 
    "Agent d'écart", 
    "Chef d'équipe", 
]); 
 
$techniciens = []; 
$errorMessage = ''; 
 
try { 
    $techniciens = getTechniciens(getDatabaseConnection()); 
} catch (RuntimeException $exception) { 
    $errorMessage = 'Les techniciens ne peuvent pas être chargés pour le moment'; 
} 
 
$pageTitle = 'Techniciens | JIRAMA Intervention'; 
define('JIRAMA_LAYOUT', true); 
 
require_once __DIR__ . '/../includes/header.php'; 
require_once __DIR__ . '/../includes/sidebar.php'; 
 
?> 
<main> 
    <h1>Techniciens</h1> 
    <?php if ($errorMessage !== ''): ?> 
        <p role="alert"> 
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?> 
        </p> 
 
    <?php elseif ($techniciens === []): ?> 
        <p>Aucun technicien n’est enregistré.</p> 
 
    <?php else: ?> 
        <div class="tableau-defilant">
            <table> 
                <thead> 
                    <tr> 
                        <th>Nom</th> 
                        <th>Téléphone</th> 
                        <th>Fonction</th> 
                        <th>Équipe</th> 
                        <th>Compte utilisateur</th> 
                        <?php if (hasRole('Administrateur')): ?> 
                            <th>Action</th> 
                        <?php endif; ?> 
                    </tr> 
                </thead> 
 
                <tbody> 
                    <?php foreach ($techniciens as $technicien): ?> 
                        <tr> 
                            <td> 
                                <?= htmlspecialchars( 
                                    (string) $technicien['prenom'] . ' ' . (string) $technicien['nom'], 
                                    ENT_QUOTES, 
                                    'UTF-8' 
                                ) ?> 
                            </td> 
                            <td> 
                                <?= htmlspecialchars((string) ($technicien['telephone'] ?? ''), ENT_QUOTES, 'UTF-8') ?> 
                            </td> 
                            <td> 
                                <?= htmlspecialchars((string) ($technicien['fonction'] ?? ''), ENT_QUOTES, 'UTF-8') ?> 
                            </td> 
                            <td> 
                                <?= htmlspecialchars((string) $technicien['nom_equipe'], ENT_QUOTES, 'UTF-8') ?> 
                            </td> 
                            <td> 
                                <?php if ($technicien['utilisateur_email'] !== null): ?> 
                                <?= htmlspecialchars((string) $technicien['utilisateur_email'], ENT_QUOTES, 'UTF-8') ?> 
                                <?php else: ?> 
                                — 
                                <?php endif; ?> 
                            </td> 
                            <?php if (hasRole('Administrateur')): ?> 
                                <td> 
                                    <a href="modifier.php?id_technicien=<?= (int) $technicien['id_technicien'] ?>"> 
                                        Modifier 
                                    </a> 
                                </td> 
                            <?php endif; ?> 
                        </tr> 

                    <?php endforeach; ?> 
                </tbody> 
            </table> 
        </div>
    <?php endif; ?> 
</main> 
 
<?php require_once __DIR__ . '/../includes/footer.php'; ?>