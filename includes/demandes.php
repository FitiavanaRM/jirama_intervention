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