<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/affectations.php';
require_once __DIR__ . '/../ includes/techniciens.php';

requireRole(['Administrateur']);

$technicienId = filter_input(
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? INPUT_POST : INPUT_GET,
    'id_technicien',
    FILTER_VALIDATE_INT
);

if (!is_int($technicienId) || $technicienId < 1) {
    http_response_code(404);
    exit('Technicien introuvable.');
}

$errorMessage = '';
$successMessage = (string) ($_SESSION['technicien_success'] ?? '');
unset($_SESSION['technicien_success']);

$equipes = [];
$utilisateursDisponibles = [];

try {
    $connection = getDatabaseConnection();
    $technicien = getTechnicienById($connection, $technicienId);
    if ($technicien === null) {
        http_response_code(404);
        exit('Technicien introuvable');
    }

    $formData = [
        'nom' => (string) $technicien['nom'],
        'prenom' => (string) $technicien['prenom'],
        'telephone' => (string) ($technicien['telephone'] ?? ''),
        'fonction' => (string) ($technicien['fonction'] ?? ''),
        'id_equipe' => (string) $technicien['id_equipe'],
        'id_utilisateur' => $technicien['id_utilisateur'] !== null
            ? (string) $technicien['id_utilisateur']
            : '',
    ];

    $equipes = getEquipes($connection);
    $utilisateursDisponibles = getUtilisateursTechnicienDisponiblesPourModification(
        $connection,
        $technicien['id_utilisateur'] !== null
            ? (int) $technicien['id_utilisateur']
            : null
    );

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        foreach ($formData as $field => $defaultValue) {
            $value = $_POST[$field] ?? '';
            $formData[$field] = is_string($value) ? $value : '';
        }

        try {
            $validatedData = validateTechnicienData($_POST);
            updateTechnicien($connection, $technicienId, $validatedData);
            $_SESSION['technicien_success'] = 'Technicien modifié avec succès.';
            header('Location: modifier.php?id_technicien=' . $technicienId);
            exit;
        }
        catch (InvalidArgumentException $exception) {
            $errorMessage = $exception->getMessage();
        }
        catch (RuntimeException $exception) {
            $errorMessage = 'Le technicien n’a pas pu être modifié';
        }
    }
}
catch (RuntimeException $exception) {
    http_response_code(500);
    exit('Le technicien ne peut pas être chargé pour le moment');
}

$pageTitle = 'Modifier un technicien | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>
<main>
    <h1>Modifier le technicien</h1>

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
        <input type="hidden" name="id_technicien" value="<?= (int) $technicienId ?>">
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
                    <option
                        value="<?= (int) $equipe['id_equipe'] ?>"
                        <?= ((string) $equipe['id_equipe'] === $formData['id_equipe']) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars((string) $equipe['nom_equipe'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="id_utilisateur">Compte utilisateur associé (optionnel)</label>
            <select id="id_utilisateur" name="id_utilisateur">
                <option value="">Aucun compte</option>
                <?php foreach ($utilisateursDisponibles as $utilisateur): ?>
                    <option
                        value="<?= (int) $utilisateur['id_utilisateur'] ?>"
                        <?= ((string) $utilisateur['id_utilisateur'] === $formData['id_utilisateur']) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars(
                            (string) $utilisateur['prenom'] . ' ' . (string) $utilisateur['nom'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit">Enregistrer les modifications</button>
    </form>
    <p>
        <a href="liste.php">Retour à la liste des techniciens</a>
    </p>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>