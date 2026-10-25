<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/clients.php';
require_once __DIR__ . '/../includes/demandes.php';

requirePermission('demande.creer');
$currentUser = getCurrentUser();
if ($currentUser === null) {
    denyAccess();
}

$estPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$reference = '';
$clients = [];
$rechercheFaite = false;
$client = null;
$typesProbleme = [];
$errorMessage = '';
$successMessage = (string) ($_SESSION['reclamation_success'] ?? '');
unset($_SESSION['reclamation_success']);

$formData = [
    'telephone_client' => '',
    'repere_adresse' => '',
    'id_type_probleme' => '',
    'precision_probleme' => '',
];

$idClient = filter_input(
    $estPost ? INPUT_POST : INPUT_GET,
    'id_client',
    FILTER_VALIDATE_INT
);

try {
    $connection = getDatabaseConnection();
    if (is_int($idClient) && $idClient > 0) {
        // Un client a été sélectionné on charge ses informations
        $client = getClientById($connection, $idClient);

        if ($client === null) {
            $errorMessage = 'Ce client est introuvable';
        } else {
            $typesProbleme = getTypesProbleme($connection);
            $formData['telephone_client'] = (string) ($client['telephone_client'] ?? '');

            // Envoi du formulaire on valide puis on enregistre
            if ($estPost) {
                foreach ($formData as $field => $defaultValue) {
                    $value = $_POST[$field] ?? '';
                    $formData[$field] = is_string($value) ? $value : '';
                }

                try {
                    $validatedData = validateReclamationData($connection, $_POST);
                    $reclamation = createReclamation(
                        $connection,
                        $validatedData,
                        $currentUser['id_utilisateur']
                    );

                    $_SESSION['reclamation_success'] = 'Réclamation ' . $reclamation['reference'] . ' enregistrée avec succès';
                    header('Location: reclamation.php');
                    exit;

                } catch (InvalidArgumentException $exception) {
                    $errorMessage = $exception->getMessage();
                } catch (RuntimeException $exception) {
                    $errorMessage = 'La réclamation n’a pas pu être enregistrée';
                }
            }
        }

    } else {
        // Aucun client sélectionné : on traite la recherche par référence de facture
        $valeur = $_GET['reference'] ?? '';

        if (is_string($valeur) && trim($valeur) !== '') {
            $reference = trim($valeur);
            $rechercheFaite = true;
            $clients = searchClientsByReference($connection, $reference);
        }
    }
} catch (InvalidArgumentException $exception) {
    $errorMessage = $exception->getMessage();
} catch (RuntimeException $exception) {
    $errorMessage = 'La page ne peut pas être chargée pour le moment';
}

$pageTitle = 'Nouvelle réclamation | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main>
    <h1>Nouvelle réclamation</h1>
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

    <?php if ($client !== null): ?>
        <h2>Client</h2>
        <dl>
            <dt>Référence de la facture</dt>
            <dd><?= htmlspecialchars((string) $client['reference_client'], ENT_QUOTES, 'UTF-8') ?></dd>

            <dt>Nom du client</dt>
            <dd><?= htmlspecialchars((string) $client['nom_client'], ENT_QUOTES, 'UTF-8') ?></dd>

            <dt>Adresse du client</dt>
            <dd><?= htmlspecialchars((string) $client['adresse_client'], ENT_QUOTES, 'UTF-8') ?></dd>
        </dl>

        <h2>Réclamation</h2>
        <form action="reclamation.php" method="post">
            <input type="hidden" name="id_client" value="<?= (int) $client['id_client'] ?>">

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
                        <option value="<?= (int) $typeProbleme['id_type_probleme'] ?>"
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

            <button type="submit">Enregistrer la réclamation</button>
        </form>

        <p>
            <a href="reclamation.php">Chercher un autre client</a>
        </p>

    <?php else: ?>
        <p>Saisissez la référence indiquée sur la facture apportée par le client</p>
        <form action="reclamation.php" method="get">
            <div>
                <label for="reference">Référence de la facture</label>
                <input
                    id="reference"
                    name="reference"
                    type="text"
                    maxlength="50"
                    placeholder="Ex : 123456789e"
                    value="<?= htmlspecialchars($reference, ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="off"
                    autofocus
                    required
                >
            </div>

            <button type="submit">Rechercher</button>
        </form>

        <?php if ($rechercheFaite && $clients === [] && $errorMessage === ''): ?>
            <p role="alert">Aucun client ne correspond à cette référence</p>

        <?php elseif ($clients !== []): ?>
            <h2>Clients trouvés</h2>
            <table>
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Client</th>
                        <th>Adresse</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $resultat): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars((string) $resultat['reference_client'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <?= htmlspecialchars((string) $resultat['nom_client'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <?= htmlspecialchars((string) $resultat['adresse_client'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <a href="reclamation.php?id_client=<?= (int) $resultat['id_client'] ?>">
                                    Sélectionner
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>