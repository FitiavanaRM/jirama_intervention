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