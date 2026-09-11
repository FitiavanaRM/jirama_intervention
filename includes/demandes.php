<?php

declare(strict_types=1);

// Formate une référence de demande selon le format DEM-AAAA-NNNN.

function formatDemandeReference(int $year, int $sequence): string
{
    if ($year < 2000 || $year > 9999 || $sequence < 1) {
        throw new InvalidArgumentException('Les données de référence sont invalides.');
    }

    return sprintf('DEM-%04d-%04d', $year, $sequence);
}

// prochaine reference dispo
function generateDemandeReference(PDO $connection, ?DateTimeInterface $date = null): string
{
    $requestDate = $date ?? new DateTimeImmutable();
    $year = (int) $requestDate->format('Y');
    $prefix = sprintf('DEM-%04d-', $year);

    $statement = $connection->prepare(
        'SELECT MAX(CAST(SUBSTRING(reference, 10) AS UNSIGNED))
         FROM demandes
         WHERE reference LIKE :prefix'
    );

    $statement->execute(['prefix' => $prefix . '%']);
    $lastSequence = (int) ($statement->fetchColumn() ?: 0);
    return formatDemandeReference($year, $lastSequence + 1);
}

// Valide et normalise les données nécessaires à la création d'une demande
 
function validateDemandeData(array $data): array
{
    $fields = [
        'nom_client' => ['Nom du client', 150],
        'telephone_client' => ['Téléphone du client', 20],
        'adresse_client' => ['Adresse du client', null],
        'type_demande' => ['Type de demande', 100],
        'description_probleme' => ['Description du problème', null],
    ];
    $validatedData = [];

    foreach ($fields as $field => [$label, $maximumLength]) {
        $value = trim((string) ($data[$field] ?? ''));

        if ($value === '') {
            throw new InvalidArgumentException("{$label} est obligatoire.");
        }

        if ($maximumLength !== null && strlen($value) > $maximumLength) {
            throw new InvalidArgumentException("{$label} dépasse la longueur autorisée.");
        }

        $validatedData[$field] = $value;
    }

    if (!preg_match('/^[0-9+().\s-]+$/', $validatedData['telephone_client'])) {
        throw new InvalidArgumentException('Le téléphone du client est invalide.');
    }

    return $validatedData;
}

// Crée une demande avec son statut initial et sa référence automatique.

function createDemande(PDO $connection, array $data, int $creatorId): array
{
    if ($creatorId < 1) {
        throw new InvalidArgumentException('L’utilisateur créateur est invalide.');
    }

    $validatedData = validateDemandeData($data);
    $maximumAttempts = 3;

    for ($attempt = 1; $attempt <= $maximumAttempts; $attempt++) {
        try {
            $connection->beginTransaction();
            $reference = generateDemandeReference($connection);
            $statement = $connection->prepare(
                'INSERT INTO demandes (
                    reference,
                    nom_client,
                    telephone_client,
                    adresse_client,
                    type_demande,
                    description_probleme,
                    statut,
                    id_utilisateur
                ) VALUES (
                    :reference,
                    :nom_client,
                    :telephone_client,
                    :adresse_client,
                    :type_demande,
                    :description_probleme,
                    :statut,
                    :id_utilisateur
                )'
            );
            $statement->execute([
                'reference' => $reference,
                'nom_client' => $validatedData['nom_client'],
                'telephone_client' => $validatedData['telephone_client'],
                'adresse_client' => $validatedData['adresse_client'],
                'type_demande' => $validatedData['type_demande'],
                'description_probleme' => $validatedData['description_probleme'],
                'statut' => 'EN_ATTENTE',
                'id_utilisateur' => $creatorId,
            ]);

            $demandeId = (int) $connection->lastInsertId();
            $connection->commit();

            return [
                'id_demande' => $demandeId,
                'reference' => $reference,
            ];
        } catch (PDOException $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            $isDuplicateReference = $exception->getCode() === '23000'
                && (int) ($exception->errorInfo[1] ?? 0) === 1062;

            if ($isDuplicateReference && $attempt < $maximumAttempts) {
                continue;
            }

            throw $exception;
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    throw new RuntimeException('Impossible de créer la demande.');
}

// Retourne les demandes avec les informations utiles à leur consultation

function getDemandes(PDO $connection): array
{
    $statement = $connection->prepare(
        'SELECT
            d.id_demande,
            d.reference,
            d.nom_client,
            d.telephone_client,
            d.adresse_client,
            d.type_demande,
            d.description_probleme,
            d.date_demande,
            d.statut,
            u.nom AS createur_nom,
            u.prenom AS createur_prenom
        FROM demandes AS d
        INNER JOIN utilisateurs AS u ON u.id_utilisateur = d.id_utilisateur
        ORDER BY d.date_demande DESC, d.id_demande DESC'
    );
    $statement->execute();

    return $statement->fetchAll();
}

// Retourne une demande précise avec les informations de son créateur.

function getDemandeById(PDO $connection, int $demandeId): ?array
{
    if ($demandeId < 1) {
        return null;
    }

    $statement = $connection->prepare(
        'SELECT
            d.id_demande,
            d.reference,
            d.nom_client,
            d.telephone_client,
            d.adresse_client,
            d.type_demande,
            d.description_probleme,
            d.date_demande,
            d.statut,
            u.nom AS createur_nom,
            u.prenom AS createur_prenom
        FROM demandes AS d
        INNER JOIN utilisateurs AS u ON u.id_utilisateur = d.id_utilisateur
        WHERE d.id_demande = :id_demande
        LIMIT 1'
    );
    $statement->execute(['id_demande' => $demandeId]);

    $demande = $statement->fetch();

    return $demande === false ? null : $demande;
}

// Mise a jour du statut du demande
function updateDemandeStatut(
    PDO $connection,
    int $demandeId,
    string $nouveauStatut
): void
{
    $statutsValides = [
        'EN_ATTENTE',
        'AFFECTEE',
        'EN_COURS',
        'RESOLUE',
        'CLOTUREE',
        'ANNULEE',
    ];

    if (!in_array($nouveauStatut, $statutsValides, true)) {
        throw new InvalidArgumentException('Le statut demandé est invalide');
    }

    $statement = $connection->prepare(
        'UPDATE demandes
         SET statut = :statut
         WHERE id_demande = :id_demande'
    );
    
}