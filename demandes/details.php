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

$demandeId = filter_input(INPUT_GET, 'id_demande', FILTER_VALIDATE_INT);

if (!is_int($demandeId) || $demandeId < 1) {
	http_response_code(404);
	exit('Demande introuvable.');
}

try {
	$demande = getDemandeById(getDatabaseConnection(), $demandeId);
} catch (RuntimeException $exception) {
	http_response_code(500);
	exit('La demande ne peut pas être chargée pour le moment.');
}

if ($demande === null) {
	http_response_code(404);
	exit('Demande introuvable.');
}

$pageTitle = 'Détail de la demande | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main>
	<h1>Détail de la demande <?= htmlspecialchars((string) $demande['reference'], ENT_QUOTES, 'UTF-8') ?></h1>

	<dl>
		<dt>Référence</dt>
		<dd><?= htmlspecialchars((string) $demande['reference'], ENT_QUOTES, 'UTF-8') ?></dd>

		<dt>Nom du client</dt>
		<dd><?= htmlspecialchars((string) $demande['nom_client'], ENT_QUOTES, 'UTF-8') ?></dd>

		<dt>Téléphone du client</dt>
		<dd><?= htmlspecialchars((string) $demande['telephone_client'], ENT_QUOTES, 'UTF-8') ?></dd>

		<dt>Adresse du client</dt>
		<dd><?= nl2br(htmlspecialchars((string) $demande['adresse_client'], ENT_QUOTES, 'UTF-8')) ?></dd>

		<dt>Type de demande</dt>
		<dd><?= htmlspecialchars((string) $demande['type_demande'], ENT_QUOTES, 'UTF-8') ?></dd>

		<dt>Description du problème</dt>
		<dd><?= nl2br(htmlspecialchars((string) $demande['description_probleme'], ENT_QUOTES, 'UTF-8')) ?></dd>

		<dt>Date de demande</dt>
		<dd><?= htmlspecialchars((string) $demande['date_demande'], ENT_QUOTES, 'UTF-8') ?></dd>

		<dt>Statut</dt>
		<dd><?= htmlspecialchars((string) $demande['statut'], ENT_QUOTES, 'UTF-8') ?></dd>

		<dt>Enregistrée par</dt>
		<dd>
			<?= htmlspecialchars(
				(string) $demande['createur_prenom'] . ' ' . (string) $demande['createur_nom'],
				ENT_QUOTES,
				'UTF-8'
			) ?>
		</dd>
	</dl>

	<p><a href="liste.php">Retour à la liste des demandes</a></p>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
