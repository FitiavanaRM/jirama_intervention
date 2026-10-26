<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demandes.php';
require_once __DIR__ . '/../includes/clients.php';

requirePermission('demande.modifier');

$estAccueil = hasRole("Agent d'accueil");
$estPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$demandeId = filter_input(
    $estPost ? INPUT_POST : INPUT_GET,
    'id_demande',
    FILTER_VALIDATE_INT
);

if (!is_int($demandeId) || $demandeId < 1) {
    http_response_code(404);
    exit('Demande introuvable');
}

$errorMessage = '';
$successMessage = (string) ($_SESSION['demande_modif_success'] ?? '');
unset($_SESSION['demande_modif_success']);

try {
    $connection = getDatabaseConnection();
    $demande = getDemandeById($connection, $demandeId);
    if ($demande === null) {
        http_response_code(404);
        exit('Demande introuvable.');
    }

    $estReclamation = $demande['id_client'] !== null;
    $modifiable = $demande['statut'] === 'EN_ATTENTE';

    // Valeurs de départ du formulaire, selon le type de demande
    if ($estReclamation) {
        $client = getClientById($connection, (int) $demande['id_client']);
        $typesProbleme = getTypesProbleme($connection);
        $idTypeActuel = '';
        foreach ($typesProbleme as $typeProbleme) {
            if ((string) $typeProbleme['libelle'] === (string) $demande['type_demande']) {
                $idTypeActuel = (string) $typeProbleme['id_type_probleme'];
                break;
            }
        }

        $formData = [
            'telephone_client' => (string) $demande['telephone_client'],
            'repere_adresse' => (string) ($demande['repere_adresse'] ?? ''),
            'id_type_probleme' => $idTypeActuel,
            'precision_probleme' => extractPrecisionProbleme(
                (string) $demande['type_demande'],
                (string) $demande['description_probleme']
            ),
        ];
    }
    else {
        $formData = [
            'nom_client' => (string) $demande['nom_client'],
            'telephone_client' => (string) $demande['telephone_client'],
            'adresse_client' => (string) $demande['adresse_client'],
            'type_demande' => (string) $demande['type_demande'],
            'description_probleme' => (string) $demande['description_probleme'],
        ];
    }

    if (!$modifiable) {
        $errorMessage = 'Cette demande n’est plus en attente, elle ne peut plus être modifiée';
    }
    elseif ($estReclamation && $client === null) {
        $errorMessage = 'Le client de cette réclamation est introuvable';
    }
    elseif ($estPost) {
        foreach ($formData as $field => $defaultValue) {
            $value = $_POST[$field] ?? '';
            $formData[$field] = is_string($value) ? $value : '';
        }

        try {
            if ($estReclamation) {
                // Le client est celui déjà enregistré
                $donnees = $_POST;
                $donnees['id_client'] = $demande['id_client'];
                $validatedData = validateReclamationData($connection, $donnees);
                updateReclamation($connection, $demandeId, $validatedData);
            }
            else {
                $validatedData = validateDemandeData($_POST);
                updateDemande($connection, $demandeId, $validatedData);
            }

            $_SESSION['demande_modif_success'] = $estAccueil
                ? 'Réclamation modifiée avec succès.'
                : 'Demande modifiée avec succès.';
            header('Location: modifier.php?id_demande=' . $demandeId);
            exit;
        }
        catch (InvalidArgumentException $exception) {
            $errorMessage = $exception->getMessage();
        }
        catch (RuntimeException $exception) {
            $errorMessage = 'La modification n’a pas pu être enregistrée';
        }
    }

} catch (RuntimeException $exception) {
    http_response_code(500);
    exit('La demande ne peut pas être chargée pour le moment');
}

$pageTitle = ($estAccueil ? 'Modifier la réclamation' : 'Modifier la demande') . ' | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>
<main>
    <h1>
        <?= $estAccueil ? 'Modifier la réclamation' : 'Modifier la demande' ?>
        <?= htmlspecialchars((string) $demande['reference'], ENT_QUOTES, 'UTF-8') ?>
    </h1>

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

    <?php if ($modifiable && $estReclamation && $client !== null): ?>
        <h2>Client</h2>
        <dl>
            <dt>Référence de la facture</dt>
            <dd><?= htmlspecialchars((string) $client['reference_client'], ENT_QUOTES, 'UTF-8') ?></dd>

            <dt>Nom du client</dt>
            <dd><?= htmlspecialchars((string) $client['nom_client'], ENT_QUOTES, 'UTF-8') ?></dd>

            <dt>Adresse du client</dt>
            <dd><?= htmlspecialchars((string) $client['adresse_client'], ENT_QUOTES, 'UTF-8') ?></dd>
        </dl>

        <form action="modifier.php" method="post">
            <input type="hidden" name="id_demande" value="<?= (int) $demandeId ?>">
            <div>
                <label for="telephone_client">Téléphone du client</label>
                <input
                    id="telephone_client"
                    name="telephone_client"
                    type="tel"
                    maxlength="20"
                    value="<?= htmlspecialchars($formData['telephone_client'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div>
                <label for="repere_adresse">Repère de l’adresse</label>
                <input
                    id="repere_adresse"
                    name="repere_adresse"
                    type="text"
                    maxlength="255"
                    value="<?= htmlspecialchars($formData['repere_adresse'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div>
                <label for="id_type_probleme">Problème signalé</label>
                <select id="id_type_probleme" name="id_type_probleme" required>
                    <option value="">Sélectionner un problème</option>
                    <?php foreach ($typesProbleme as $typeProbleme): ?>
                        <option
                            value="<?= (int) $typeProbleme['id_type_probleme'] ?>"
                            <?= ((string) $typeProbleme['id_type_probleme'] === $formData['id_type_probleme']) ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars((string) $typeProbleme['libelle'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="precision_probleme">Précision</label>
                <textarea
                    id="precision_probleme"
                    name="precision_probleme"
                ><?= htmlspecialchars($formData['precision_probleme'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <button type="submit">Enregistrer les modifications</button>
        </form>

    <?php elseif ($modifiable && !$estReclamation): ?>
        <form action="modifier.php" method="post">
            <input type="hidden" name="id_demande" value="<?= (int) $demandeId ?>">

            <div>
                <label for="nom_client">Nom du client</label>
                <input
                    id="nom_client"
                    name="nom_client"
                    type="text"
                    maxlength="150"
                    value="<?= htmlspecialchars($formData['nom_client'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div>
                <label for="telephone_client">Téléphone du client</label>
                <input
                    id="telephone_client"
                    name="telephone_client"
                    type="tel"
                    maxlength="20"
                    value="<?= htmlspecialchars($formData['telephone_client'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div>
                <label for="adresse_client">Adresse du client</label>
                <textarea
                    id="adresse_client"
                    name="adresse_client"
                    required
                ><?= htmlspecialchars($formData['adresse_client'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div>
                <label for="type_demande">Type de demande</label>
                <input
                    id="type_demande"
                    name="type_demande"
                    type="text"
                    maxlength="100"
                    value="<?= htmlspecialchars($formData['type_demande'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div>
                <label for="description_probleme">Description du problème</label>
                <textarea
                    id="description_probleme"
                    name="description_probleme"
                    required
                ><?= htmlspecialchars($formData['description_probleme'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <button type="submit">Enregistrer les modifications</button>

        </form>

    <?php endif; ?>

    <p>
        <a href="details.php?id_demande=<?= (int) $demandeId ?>">
            Retour au détail
        </a>
    </p>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>