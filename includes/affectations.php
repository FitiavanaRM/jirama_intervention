<?php

declare(strict_types=1);

// Retourne les équipes disponibles pour une affectation
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

// Retourne les techniciens rattachés à une équipe précise
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

// c'est pour inserer une nouvelle affectation en base
function insertAffectation(
    PDO $connection,
    int $demandeId,
    int $equipeId,
    int $technicienId,
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
function validateAffectationData(array $data) : array
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
        throw new InvalidArgumentException('La demande, l’équipe et le technicien sont obligatoires.');
    }

    $observation = trim(
        (string) ($data['observation'] ?? '')
    );

    if (strlen($observation) > 1000) {
        throw new InvalidArgumentException('L’observation dépasse la longueur autorisée.');
    }

    return [
        'id_demande' => $idDemande,
        'id_equipe' => $idEquipe,
        'id_technicien' => $idTechnicien,
        'observation' => $observation,
    ];
}


// Vérifie que la demande, l'équipe et le technicien existent, et que le technicien appartient bien à l'équipe sélectionnée

function checkAffectationCoherence(
    PDO $connection,
    int $demandeId,
    int $equipeId,
    int $technicienId
): void
{
    // demande
    $statement = $connection->prepare('SELECT statut FROM demandes WHERE id_demande = :id_demande LIMIT 1');
    $statement->execute(['id_demande' => $demandeId]);
    $demande = $statement->fetch();

    if ($demande === false) {
        throw new InvalidArgumentException('La demande sélectionnée n’existe pas.');
    }

    if ($demande['statut'] !== 'EN_ATTENTE') {
        throw new InvalidArgumentException('Cette demande n’est plus en attente d’affectation.');
    }

    // equipe
    $statement = $connection->prepare('SELECT id_equipe FROM equipes WHERE id_equipe = :id_equipe LIMIT 1');
    $statement->execute(['id_equipe' => $equipeId]);

    if ($statement->fetch() === false) {
        throw new InvalidArgumentException('L’équipe sélectionnée n’existe pas');
    }

    // technicien
    $statement = $connection->prepare('SELECT id_equipe FROM techniciens WHERE id_technicien = :id_technicien LIMIT 1');
    $statement->execute(['id_technicien' => $technicienId]);
    $technicien = $statement->fetch();

    if ($technicien === false) {
        throw new InvalidArgumentException('Le technicien sélectionné n’existe pas.');
    }

    if ((int) $technicien['id_equipe'] !== $equipeId) {
        throw new InvalidArgumentException('Le technicien sélectionné n’appartient pas à cette équipe.');
    }
}

// Retourne les affectations avec les informations utiles à leur consultation
function getAffectations(PDO $connection): array
{
    $statement = $connection->prepare(
        'SELECT
            a.id_affectation,
            a.date_affectation,
            a.observation,
            d.id_demande,
            d.reference,
            d.nom_client,
            d.type_demande,
            e.id_equipe,
            e.nom_equipe,
            t.id_technicien,
            t.nom AS technicien_nom,
            t.prenom AS technicien_prenom
        FROM affectations AS a
        INNER JOIN demandes AS d
            ON d.id_demande = a.id_demande
        INNER JOIN equipes AS e
            ON e.id_equipe = a.id_equipe
        LEFT JOIN techniciens AS t
            ON t.id_technicien = a.id_technicien
        ORDER BY a.date_affectation DESC, a.id_affectation DESC'
    );
    
    $statement->execute();
    return $statement->fetchAll();
}

// Retourne le nombre d'affectations traitées par équipe
function getStatistiquesParEquipe(PDO $connection): array
{
    $statement = $connection->prepare(
        'SELECT
            e.nom_equipe,
            COUNT(a.id_affectation) AS total
        FROM equipes AS e
}