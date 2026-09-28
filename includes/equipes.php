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