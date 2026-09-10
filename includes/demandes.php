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