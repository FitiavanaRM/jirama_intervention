<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/utilisateurs.php';

requireRole(['Administrateur']);

$utilisateurId = filter_input(
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? INPUT_POST : INPUT_GET,
    'id_utilisateur',
    FILTER_VALIDATE_INT
);

if (!is_int($utilisateurId) || $utilisateurId < 1) {
    http_response_code(404);
    exit('Utilisateur introuvable');
}

$errorMessage = '';
$successMessage = (string) ($_SESSION['utilisateur_success'] ?? '');
unset($_SESSION['utilisateur_success']);
$roles = [];

try {
    $connection = getDatabaseConnection();
    $utilisateur = getUtilisateurById($connection, $utilisateurId);

    if ($utilisateur === null) {
        http_response_code(404);
        exit('Utilisateur introuvable.');
    }

    $formData = [
        'nom' => (string) $utilisateur['nom'],
        'prenom' => (string) $utilisateur['prenom'],
        'telephone' => (string) ($utilisateur['telephone'] ?? ''),
        'email' => (string) $utilisateur['email'],
        'id_role' => (string) $utilisateur['id_role'],
    ];

    $roles = getRoles($connection);
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        foreach ($formData as $field => $defaultValue) {
            $value = $_POST[$field] ?? '';
            $formData[$field] = is_string($value) ? $value : '';
        }

        try {
            $validatedData = validateUtilisateurData($_POST, false);
            updateUtilisateur($connection, $utilisateurId, $validatedData);
            $_SESSION['utilisateur_success'] = 'Utilisateur modifié avec succès';
            header('Location: modifier.php?id_utilisateur=' . $utilisateurId);
            exit;
        }
        catch (InvalidArgumentException $exception) {
            $errorMessage = $exception->getMessage();
        }
        catch (RuntimeException $exception) {
            $errorMessage = 'L’utilisateur n’a pas pu être modifié';
        }
    }
}
catch (RuntimeException $exception) {
    http_response_code(500);
    exit('L’utilisateur ne peut pas être chargé pour le moment');
}

$pageTitle = 'Modifier un utilisateur | JIRAMA Intervention';

define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>
<main>
    <h1>Modifier l’utilisateur</h1>

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

    <form action="modifier.php" method="post">
        <input type="hidden" name="id_utilisateur" value="<?= (int) $utilisateurId ?>">
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
            <label for="mot_de_passe">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
            <input
                id="mot_de_passe"
                name="mot_de_passe"
                type="password"
                minlength="8"
            >
        </div>

        <div>
            <label for="id_role">Rôle</label>
            <select id="id_role" name="id_role" required>
                <option value="">Sélectionner un rôle</option>
                <?php foreach ($roles as $role): ?>
                    <option
                        value="<?= (int) $role['id_role'] ?>"
                        <?= ((string) $role['id_role'] === $formData['id_role']) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars((string) $role['nom_role'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit">Enregistrer les modifications</button>

    </form>
    <p>
        <a href="liste.php">Retour à la liste des utilisateurs</a>
    </p>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>