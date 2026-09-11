<?php

declare(strict_types=1);

// Retourne les équipes disponibles pour une affectation.

function getEquipes(PDO $connection): array
{
    $statement = $connection->prepare(
        'SELECT id_equipe, nom_equipe, type_equipe, description
         FROM equipes
         ORDER BY nom_equipe ASC, id_equipe ASC'
    );
    $statement->execute();

    return $statement->fetchAll();
}

// Retourne les techniciens rattachés à une équipe précise.

function getTechniciensByEquipe(PDO $connection, int $equipeId): array
{
    if ($equipeId < 1) {
        return [];
    }

    $statement = $connection->prepare(
        'SELECT id_technicien, nom, prenom, telephone, fonction, id_equipe
         FROM techniciens
         WHERE id_equipe = :id_equipe
         ORDER BY nom ASC, prenom ASC, id_technicien ASC'
    );
    $statement->execute(['id_equipe' => $equipeId]);

    return $statement->fetchAll();
}

// c est pour inserer une nouvelle affectation en base
function insertAffectation(
    PDO $connection,
    int $demandeId,
    int $equipeId,
    int $technicienID,
    string $observation
): int
{
    $statement = $connection->prepare(
        'INSERT INTO affectations (
            id_demande,
            id_equipe,
            id_technicien,
            observation
        ) VALUES (
            :id_demande,
            :id_equipe,
            :id_technicien,
            :observation
        )'
    );
    $statement->execute([
        'id_demande' => $demandeId,
        'id_equipe' => $equipeId,
        'id_technicien' => $technicienId,
        'observation' => $observation !== '' ? $observation : null,
    ]);

    return (int) $connection->lastInsertId();
}

// pour valider l affectation
function validateAffectation(array $data) : array
{
    $idDemande = filter_var(
        $data['id_demande'] ?? null,
        FILTER_VALIDATE_INT
    );

    $idEquipe = filter_var(
        $data['id_equipe'] ?? null,
        FILTER_VALIDATE_INT
    );

    $idTechnicien = filter_var(
        $data['id_technicien'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        $idDemande === false || $idDemande < 1 ||
        $idEquipe === false || $idEquipe < 1 ||
        $idTechnicien === false || $idTechnicien < 1
    ) {
        throw new InvalidArgumentException(
            'La demande, l’équipe et le technicien sont obligatoires.'
        );
    }

    $observation = trim(
        (string) ($data['observation'] ?? '')
    );

    if (strlen($observation) > 1000) {
        throw new InvalidArgumentException(
            'L’observation dépasse la longueur autorisée.'
        );
    }

    return [
        'id_demande' => $idDemande,
        'id_equipe' => $idEquipe,
        'id_technicien' => $idTechnicien,
        'observation' => $observation,
    ];
}


// Vérifie que la demande, l'équipe et le technicien existent,
// et que le technicien appartient bien à l'équipe sélectionnée.

function checkAffectationCoherence(
    PDO $connection,
    int $demandeId,
    int $equipeId,
    int $technicienId
): void
{
    $statement = $connection->prepare(
        'SELECT statut FROM demandes WHERE id_demande = :id_demande LIMIT 1'
    );

    $statement->execute(['id_demande' => $demandeId]);

    $demande = $statement->fetch();

    if ($demande === false) {
        throw new InvalidArgumentException('La demande sélectionnée n’existe pas.');
    }

    if ($demande['statut'] !== 'EN_ATTENTE') {
        throw new InvalidArgumentException('Cette demande n’est plus en attente d’affectation.');
    }
}