<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demandes.php';
require_once __DIR__ . '/../includes/affectations.php';

requirePermission('affectation.creer');

$demandes = [];
$equipes = [];
$techniciensParEquipe = [];
$errorMessage = '';

try {
    $connection = getDatabaseConnection();
    $demandes = getDemandes($connection);
    $equipes = getEquipes($connection);

    foreach ($equipes as $equipe) {
    $equipeId = (int) $equipe['id_equipe'];
    $techniciensParEquipe[$equipeId] = getTechniciensByEquipe($connection, $equipeId);
    }
}
catch (RuntimeException $exception) {
    $errorMessage =
        'Les données d’affectation ne peuvent pas être chargées pour le moment';
}

// traitement de POST
$successMessage = (string) ($_SESSION['affectation_success'] ?? '');
unset($_SESSION['affectation_success']);

if ($errorMessage === '' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        $validatedData = validateAffectationData($_POST);

        checkAffectationCoherence(
            $connection,
            $validatedData['id_demande'],
            $validatedData['id_equipe'],
            $validatedData['id_technicien']
        );

        insertAffectation(
            $connection,
            $validatedData['id_demande'],
            $validatedData['id_equipe'],
            $validatedData['id_technicien'],
            $validatedData['observation']
        );

        $_SESSION['affectation_success'] = 'Affectation enregistrée avec succès';
        header('Location: ajouter.php');
        exit;
    } 
    catch (InvalidArgumentException $exception) {
        $errorMessage = $exception->getMessage();
    } 
    catch (RuntimeException $exception) {
        $errorMessage = 'L’affectation n’a pas pu être enregistrée';
    }
}

$pageTitle = 'Nouvelle affectation | JIRAMA Intervention';

define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main>
    <h1>Affecter une demande</h1>

    <?php if ($successMessage !== ''): ?>
        <p role="status">
            <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>
    
    <?php if ($errorMessage !== ''): ?>

    <p role="alert">
        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
    </p>
    <?php else: ?>

    <form action="ajouter.php" method="post">

        <div>
            <label for="id_demande">Demande</label>

            <select id="id_demande" name="id_demande" required>
                <option value="">Sélectionner une demande</option>

                <?php foreach ($demandes as $demande): ?>
                    <option value="<?= (int) $demande['id_demande'] ?>">
                        <?= htmlspecialchars(
                            (string) $demande['reference'] .
                            ' — ' .
                            (string) $demande['nom_client'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </option>
                <?php endforeach; ?>

            </select>
        </div>
        <div>
            <label for="id_equipe">Équipe</label>

            <select id="id_equipe" name="id_equipe" required>
                <option value="">Sélectionner une équipe</option>

                <?php foreach ($equipes as $equipe): ?>
                    <option value="<?= (int) $equipe['id_equipe'] ?>">
                        <?= htmlspecialchars(
                            (string) $equipe['nom_equipe'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="id_technicien">Technicien</label>
            <select id="id_technicien" name="id_technicien" required>
                <option value="">Sélectionner un technicien</option>

                <?php foreach ($equipes as $equipe): ?>
                <?php $equipeId = (int) $equipe['id_equipe']; ?>
                <optgroup
                    label="<?= htmlspecialchars(
                        (string) $equipe['nom_equipe'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
             >

                    <?php foreach ($techniciensParEquipe[$equipeId] ?? [] as $technicien): ?>
                        <option value="<?= (int) $technicien['id_technicien'] ?>">
                            <?= htmlspecialchars(
                                (string) $technicien['prenom'] .
                                ' ' .
                                (string) $technicien['nom'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
        </div>
        <div>
            <label for="observation">Observation</label>

            <textarea
                id="observation"
                name="observation"
            ></textarea>
        </div>

        <button type="submit">Enregistrer l’affectation</button>
	</form>
	<?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>