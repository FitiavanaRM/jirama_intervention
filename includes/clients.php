<?php

declare(strict_types=1);

// Cherche les clients dont la référence de facture commence par la saisie
function searchClientsByReference(PDO $connection, string $reference): array
{
    $reference = trim($reference);

    if (!preg_match('/^[A-Za-z0-9]{3,50}$/', $reference)) {
        throw new InvalidArgumentException('Référence de client invalide');
    }

    $statement = $connection->prepare(
        'SELECT
            id_client,
            reference_client,
            nom_client,
            adresse_client,
            telephone_client
        FROM clients
        WHERE reference_client LIKE :recherche
        ORDER BY reference_client ASC
        LIMIT 10'
    );

    $statement->execute([
        'recherche' => $reference . '%'
    ]);

    return $statement->fetchAll();
}


// Retourne un client précis, ou null s'il n'existe pas
function getClientById(PDO $connection, int $clientId): ?array
{
    if ($clientId < 1) {
        return null;
    }

    $statement = $connection->prepare(
        'SELECT
            id_client,
            reference_client,
            nom_client,
            adresse_client,
            telephone_client
        FROM clients
        WHERE id_client = :id_client
        LIMIT 1'
    );

    $statement->execute([
        'id_client' => $clientId
    ]);

    $client = $statement->fetch();
    return $client === false ? null : $client;
}