<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demandes.php';
require_once __DIR__ . '/../includes/interventions.php';

requireRole([
    'Administrateur',
    "Chef d'équipe",
    'chef de division',
]); 

$currentUser = getCurrentUser();

if ($currentUser === null) {
    denyAccess();
}

$demandes = [];
$errorMessage = '';
$successMessage = (string) ($_SESSION['intervention_success'] ?? '');
unset($_SESSION['intervention_success']);

try {
    $connection = getDatabaseConnection();
    $demandes = getDemandesAffectees($connection);

    if (
        ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && hasPermission('intervention.organiser')
    ) {
        $demandeId = filter_var(
            $_POST['id_demande'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($demandeId === false || $demandeId < 1) {
            $errorMessage = 'La demande sélectionnée est invalide';
        }
        else {
            try {
                checkInterventionCoherence($connection, $demandeId);

                $connection->beginTransaction();
                try {
                    insertIntervention($connection, $demandeId);
                    updateDemandeStatut(
                        $connection,
                        $demandeId,
                        'EN_COURS'
                    );

                    insertHistorique(
                        $connection,
                        $demandeId,
                        $currentUser['id_utilisateur'],
                        'AFFECTEE',
                        'EN_COURS'
                    );

                    $connection->commit();
                }
                catch (Throwable $exception) {
                    // si un erreur arrive on peut annuler l'opération avec ce fonction 
                    $connection->rollBack();
                    throw $exception;
                }

                $_SESSION['intervention_success'] = 'Intervention démarrée avec succès';
                header('Location: liste.php');
                exit;
            }
            catch (InvalidArgumentException $exception) {
                $errorMessage = $exception->getMessage();
            }
        }
    }
}
catch (RuntimeException $exception){
    $errorMessage = 'Les demandes affectées ne peuvent pas être chargées pour le moment';
}

$pageTitle = 'Interventions à organiser | JIRAMA Intervention';

define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>
<main>
    <h1>Demandes affectées à organiser</h1>

    <?php if ($errorMessage !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>

    <?php elseif ($demandes === []): ?>
        <p>Aucune demande affectée en attente d’organisation</p>

    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Équipe</th>
                    <th>Technicien</th>
                    <th>Date d’affectation</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                 <?php foreach ($demandes as $demande): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars(
                                (string) $demande['reference'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars(
                                (string) $demande['nom_client'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars(
                                (string) $demande['type_demande'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars(
                                (string) $demande['nom_equipe'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                        <td>
                            <?php if ($demande['technicien_nom'] !== null): ?>
                                <?= htmlspecialchars(
                                    (string) $demande['technicien_prenom'] .
                                    ' ' .
                                    (string) $demande['technicien_nom'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars(
                                (string) $demande['date_affectation'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>
                        <td>
                            <?php if (hasPermission('intervention.organiser')): ?>
                            <form action="liste.php" method="post">
                                <input
                                    type="hidden"
                                    name="id_demande"
                                    value="<?= (int) $demande['id_demande'] ?>"
                                >

                                <button type="submit">Démarrer</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>