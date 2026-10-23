<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/clients.php';

requirePermission('demande.creer');

$reference = '';
$clients = [];
$rechercheFaite = false;
$errorMessage = '';
$valeur = $_GET['reference'] ?? '';

if (is_string($valeur) && trim($valeur) !== '') {
    $reference = trim($valeur);
    $rechercheFaite = true;

    try {
        $clients = searchClientsByReference(getDatabaseConnection(), $reference);
    } catch (InvalidArgumentException $exception) {
        $errorMessage = $exception->getMessage();
    } catch (RuntimeException $exception) {
        $errorMessage = 'La recherche ne peut pas être effectuée pour le moment';
    }
}

$pageTitle = 'Nouvelle réclamation | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main>
    <h1>Nouvelle réclamation</h1>
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

    <?php if ($errorMessage !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>

    <?php elseif ($rechercheFaite && $clients === []): ?>
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
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars((string) $client['reference_client'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) $client['nom_client'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) $client['adresse_client'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <a href="reclamation.php?id_client=<?= (int) $client['id_client'] ?>">
                                Sélectionner
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>