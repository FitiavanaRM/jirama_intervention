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


