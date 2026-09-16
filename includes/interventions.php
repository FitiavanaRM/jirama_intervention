<?php

declare(strict_types=1);

// Crée une intervention pour une demande et enregistre la date de début
function insertIntervention(
    PDO $connection,
    int $demandeId
): int
{
    $statement = $connection->prepare(
        'INSERT INTO interventions (id_demande, date_debut) VALUES (:id_demande,NOW())'
    );

    $statement->execute([
        'id_demande' => $demandeId,
    ]);
    
    return (int) $connection->lastInsertId();
}

function checkInterventionCoherence(
    PDO $connection,
    int $demandeId
): void
{
    
}