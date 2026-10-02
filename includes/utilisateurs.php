<?php

declare(strict_types=1);

// Retourne les rôles disponibles
function getRoles(PDO $connection): array
{
    $statement = $connection->prepare(
        'SELECT id_role, nom_role FROM roles ORDER BY nom_role ASC'
    );

    $statement->execute();
    return $statement->fetchAll();
}