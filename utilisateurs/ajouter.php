<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/utilisateurs.php';

requireRole(['Administrateur']);

$formData = [
    'nom' => '',
    'prenom' => '',
    'telephone' => '',
    'email' => '',
    'id_role' => '',
];

$errrorMessage = '';
$successMessage = (string) ($_SESSION['utilisateur_success'] ?? '');
unset($_SESSION['utilisateur_success']);
$roles = [];

try {
    $connection = getDatabaseConnection();
    $roles = getRoles($connection);

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        foreach ($formData as $field => $defaultValue) {
            $value = $_POST[$field] ?? '';
            $formData[$field] = is_string($value) ? $value : '';
        }
        
        try {
            $validatedData = validateUtilisateurData($_POST, true);
            insertUtilisateur($connection, $ValidatedData);

            $_SESSION['utilisateur_success'] = 'Utilisateur crée avec succès';
            header('Location : ajouter.php');
            exit;
        }
        catch (InvalidArgumentException $exception) {
            $errorMessage = $exception->getMessage();
        }
        catch (RuntimeException $exception) {
            $errorMessage = 'L utilisateur n a pas pu être créer';
        }
    }
}
catch (RuntimeException $exception) {
    $erreurMessage = 'Les données ne peuvent pas être chargées pour le moment';
}

$pageTitle = 'Nouvel utilisateur | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>
<main>
    <h1>Ajouter un utilisateur</h1>

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
            <label for="telephone">Téléphone (optionnel)</label>
            <input
                id="telephone"
                name="telephone"
                type="tel"
                maxlength="20"
                value="<?= htmlspecialchars($formData['telephone'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div>
            <label for="email">Adresse e-mail</label>
            <input
                id="email"
                name="email"
                type="email"
                maxlength="150"
                value="<?= htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div>
            <label for="mot_de_passe">Mot de passe</label>
            <input
                id="mot_de_passe"
                name="mot_de_passe"
                type="password"
                minlength="8"
                required
            >
        </div>

        <div>
            <label for="id_role">Rôle</label>
            <select id="id_role" name="id_role" required>
                <option value="">Sélectionner un rôle</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= (int) $role['id_role'] ?>">
                        <?= htmlspecialchars((string) $role['nom_role'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit">Enregistrer l’utilisateur</button>
    </form>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>