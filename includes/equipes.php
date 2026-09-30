<?php

declare(strict_types=1);

// Valide et normalise les données du formulaire d'une équipe
function validateEquipeData(array $data): array
{
    $nomEquipe = trim((string) ($data['nom_equipe'] ?? ''));
    $typeEquipe = trim((string) ($data['type_equipe'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));

    if ($nomEquipe === '') {
        throw new InvalidArgumentException('Le nom de l’équipe est obligatoire');
    }
    
    if (strlen($nomEquipe) > 150) {
        throw new InvalidArgumentException('Le nom de l’équipe dépasse la longueur autorisée');
    }

    return [
        'nom_equipe' => $nomEquipe,
        'type_equipe' => $typeEquipe !== '' ? $typeEquipe : null,
        'description' => $description !== '' ? $description : null,
    ];
}

// Insère une nouvelle équipe en base et retourne son identifiant
function insertEquipe(PDO $connection, array $data): int
{
    $statement = $connection->prepare(
        'INSERT INTO equipes (
            nom_equipe,
            type_equipe,
            description
        ) VALUES (
            :nom_equipe,
            :type_equipe,
            :description
        )'
    );

    try {
        $statement->execute([
            'nom_equipe' => $data['nom_equipe'],
            'type_equipe' => $data['type_equipe'],
            'description' => $data['description'],
        ]);
    } catch (PDOException $exception) {
        $isDuplicateName =
            $exception->getCode() === '23000'
            &&
            (int) ($exception->errorInfo[1] ?? 0) === 1062;

        if ($isDuplicateName) {
            throw new InvalidArgumentException('Une équipe portant ce nom existe déjà');
        }

        throw $exception;
    }
    return (int) $connection->lastInsertId();
}

// Retourne les équipes avec le nombre de techniciens de chacune
function getEquipesAvecEffectif(PDO $connection): array
{
    $statement = $connection->prepare(
        'SELECT
            e.id_equipe,
            e.nom_equipe,
            e.type_equipe,
            e.description,
            COUNT(t.id_technicien) AS nombre_techniciens
        FROM equipes AS e
        LEFT JOIN techniciens AS t
            ON t.id_equipe = e.id_equipe
        GROUP BY
            e.id_equipe,
            e.nom_equipe,
            e.type_equipe,
            e.description
        ORDER BY e.nom_equipe ASC'
    );

    $statement->execute();
    return $statement->fetchAll();
}

// Retourne une équipe précise
function getEquipeById(PDO $connection, int $equipeId): ?array
{
    if ($equipeId < 1) {
        return null;
    }

    $statement = $connection->prepare(
        'SELECT id_equipe, nom_equipe, type_equipe, description
         FROM equipes
         WHERE id_equipe = :id_equipe
         LIMIT 1'
    );

    $statement->execute([
        'id_equipe' => $equipeId
    ]);

    $equipe = $statement->fetch();
    return $equipe === false ? null : $equipe;
}

// Met à jour une équipe existante
function updateEquipe(
    PDO $connection,
    int $equipeId,
    array $data
): void
{
    $statement = $connection->prepare(
        'UPDATE equipes
         SET
            nom_equipe = :nom_equipe,
            type_equipe = :type_equipe,
            description = :description
         WHERE id_equipe = :id_equipe'
    );

    try {
        $statement->execute([
            'nom_equipe' => $data['nom_equipe'],
            'type_equipe' => $data['type_equipe'],
            'description' => $data['description'],
            'id_equipe' => $equipeId,
        ]);
    } 
    catch (PDOException $exception) {
        $isDuplicateName =
            $exception->getCode() === '23000'
            &&
            (int) ($exception->errorInfo[1] ?? 0) === 1062;

        if ($isDuplicateName) {
            throw new InvalidArgumentException('Une équipe portant ce nom existe déjà');
        }

        throw $exception;
    }
}