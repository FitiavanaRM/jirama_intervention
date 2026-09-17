<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/affectations.php';
require_once __DIR__ . '/../includes/techniciens.php';

requirePermission('technicien.superviser');

$currentUser = getCurrentUser();
if ($currentUser === null) {
    denyAccess();
}

$formData = [
    'nom' => '',
    'prenom' => '',
    'telephone' => '',
    'fonction' => '',
    'id_equipe' => '',
    'id_utilisateur' => '',
];

$errorMessage = '';
$successMessage = (string) ($_SESSION['technicien_success'] ?? '');
unset($_SESSION['technicien_success']);

$equipes = [];
$utilisateursDisponibles = [];
$connection = getDatabaseConnection();

try {
    $connection = getDatabaseConnection();
    $equipes = getEquipes($connection);
    $utilisateursDisponibles = getUtilisateursTechnicienDisponibles($connection);

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        foreach ($formData as $field => $defaultValue) {
            $value = $_POST[$field] ?? '';
            $formData[$field] = is_string($value) ? $value : '';
        }
    }

    try {
        $validatedData = validateTechnicienData($_POST);
        insertTechnicien($connection, $validatedData);
        $_SESSION['technicien_success'] = 'Technicien créé avec succès';
        header('Location: ajouter.php');
        exit;
    }
    catch (InvalidArgumentException $exception) {
        $errorMessage = $exception->getMessage();
    } 
    catch (RuntimeException $exception) {
        $errorMessage = 'Le technicien n’a pas pu être créé';
    }
}
catch (RuntimeException $exception) {
    $errorMessage = 'Les données ne peuvent pas être chargées pour le moment';
}

$pageTitle = 'Nouveau technicien | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main>
    <h1>Ajouter un technicien</h1>

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

    <form action="ajouter.php" method="post">

        <div>
            <label for="nom">Nom</label>
            <input
                id="nom"
                name="nom"
                type="text"
                maxlength="100"
                value="<?= htmlspecialchars($formData['nom'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div>
            <label for="prenom">Prénom</label>
            <input
                id="prenom"
                name="prenom"
                type="text"
                maxlength="100"
                value="<?= htmlspecialchars($formData['prenom'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div>
            <label for="telephone">Téléphone</label>
            <input
                id="telephone"
                name="telephone"
                type="tel"
                maxlength="20"
                value="<?= htmlspecialchars($formData['telephone'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div>
            <label for="fonction">Fonction</label>
            <input
                id="fonction"
                name="fonction"
                type="text"
                maxlength="100"
                value="<?= htmlspecialchars($formData['fonction'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div>
            <label for="id_equipe">Équipe</label>
            <select id="id_equipe" name="id_equipe" required>
                <option value="">Sélectionner une équipe</option>
                <?php foreach ($equipes as $equipe): ?>
                    <option value="<?= (int) $equipe['id_equipe'] ?>">
                        <?= htmlspecialchars((string) $equipe['nom_equipe'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="id_utilisateur">Compte utilisateur associé (optionnel)</label>
            <select id="id_utilisateur" name="id_utilisateur">
                <option value="">Aucun compte pour l’instant</option>
                <?php foreach ($utilisateursDisponibles as $utilisateur): ?>
                    <option value="<?= (int) $utilisateur['id_utilisateur'] ?>">
                        <?= htmlspecialchars(
                            (string) $utilisateur['prenom'] . ' ' . (string) $utilisateur['nom'],
                            ENT_QUOTES,
                            'UTF-8' 
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit">Enregistrer le technicien</button>
    </form>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>