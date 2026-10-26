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
$currentUser = getCurrentUser();
if ($currentUser == null) {
	denyAccess();
}

$demandeId = filter_input(INPUT_GET, 'id_demande', FILTER_VALIDATE_INT);
if (!is_int($demandeId) || $demandeId < 1) {
	http_response_code(404);
	exit('Demande introuvable.');
}

$errorMessage = '';
$successMessage = (string) ($_SESSION['cloture_success'] ?? '');
unset($_SESSION['cloture_success']);

try {
	$connection = getDatabaseConnection();

    if (
        ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && hasPermission('demande.cloturer')
    ){
        try {
            checkClotureCoherence($connection, $demandeId);
            $connection->beginTransaction();

            try {
                updateDemandeStatut($connection, $demandeId, 'CLOTUREE');
                insertHistorique(
                    $connection,
                    $demandeId,
                    $currentUser['id_utilisateur'],
                    'RESOLUE',
                    'CLOTUREE'
                );

                $connection->commit();
            }
			catch (Throwable $exception) {
                $connection->rollBack();
                throw $exception;
            }

            $_SESSION['cloture_success'] = 'Demande clôturée avec succès';
            header('Location: details.php?id_demande=' . $demandeId);
            exit;

        } catch (InvalidArgumentException $exception) {
            $errorMessage = $exception->getMessage();
        } catch (RuntimeException $exception) {
            $errorMessage = 'La demande n’a pas pu être clôturée';
        }
    }
    $demande = getDemandeById($connection, $demandeId);
} catch (RuntimeException $exception) {
	http_response_code(500);
	exit('La demande ne peut pas être chargée pour le moment');
}

if ($demande === null) {
	http_response_code(404);
	exit('Demande introuvable');
}

$pageTitle = ($estAccueil ? 'Détail de la réclamation' : 'Détail de la demande') . ' | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main>
	<?php if ($successMessage !== ''): ?>
        <p role="status">
            <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <h1>
        <?= $estAccueil ? 'Détail de la réclamation' : 'Détail de la demande' ?>
        <?= htmlspecialchars(
            (string) $demande['reference'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </h1>

	<dl>
		<dt>Référence</dt>
		<dd>
			<?= htmlspecialchars((string) $demande['reference'], ENT_QUOTES, 'UTF-8') ?>
		</dd>

		<dt>Nom du client</dt>
		<dd>
			<?= htmlspecialchars((string) $demande['nom_client'], ENT_QUOTES, 'UTF-8') ?>
		</dd>

		<dt>Téléphone du client</dt>
		<dd>
			<?= htmlspecialchars((string) $demande['telephone_client'], ENT_QUOTES, 'UTF-8') ?>
		</dd>

		<dt>Adresse du client</dt>
		<dd>
			<?= nl2br(htmlspecialchars((string) $demande['adresse_client'], ENT_QUOTES, 'UTF-8')) ?>
		</dd>

		<dt>Type de demande</dt>
		<dd>
			<?= htmlspecialchars((string) $demande['type_demande'], ENT_QUOTES, 'UTF-8') ?>
		</dd>

		<dt>Description du problème</dt>
		<dd>
			<?= nl2br(htmlspecialchars((string) $demande['description_probleme'], ENT_QUOTES, 'UTF-8')) ?>
		</dd>

		<dt>Date de demande</dt>
		<dd>
			<?= htmlspecialchars((string) $demande['date_demande'], ENT_QUOTES, 'UTF-8') ?>
		</dd>

		<dt>Statut</dt>
        <dd>
            <?= renderStatutBadge((string) $demande['statut']) ?>
        </dd>

		<dt>Enregistrée par</dt>
		<dd>
			<?= htmlspecialchars(
				(string) $demande['createur_prenom'] . ' ' . (string) $demande['createur_nom'],
				ENT_QUOTES,
				'UTF-8'
			) ?>
		</dd>
	</dl>

	<?php if ($demande['statut'] === 'EN_ATTENTE' && hasPermission('demande.modifier')): ?>
        <p>
            <a href="modifier.php?id_demande=<?= (int) $demande['id_demande'] ?>">
                <?= $estAccueil ? 'Modifier la réclamation' : 'Modifier la demande' ?>
            </a>
        </p>
    <?php endif; ?>

	<?php if ($demande['statut'] === 'RESOLUE' && hasPermission('demande.cloturer')): ?>
        <form action="details.php" method="post">
            <input type="hidden" name="id_demande" value="<?= (int) $demande['id_demande'] ?>">
            <button type="submit">Clôturer la demande</button>
        </form>
    <?php endif; ?>

	<p><a href="liste.php">
        <?= $estAccueil ? 'Retour à la liste des réclamations' : 'Retour à la liste des demandes' ?>
    </a></p>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>