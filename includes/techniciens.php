<?php

declare(strict_types=1);

// retourne les utilisateurs ayant le role technicien et non encore rattaché au fichier technicien
function getUtilisateursTechnicienDisponibles(PDO $connection): array
{
    $statement = $connection->prepare(
        "SELECT u.id_utilisateur, u.nom, u.prenom
         FROM utilisateurs AS u
         INNER JOIN roles AS r
            ON r.id_role = u.id_role
         WHERE r.nom_role = 'Technicien'
         AND u.id_utilisateur NOT IN (
            SELECT id_utilisateur
            FROM techniciens
            WHERE id_utilisateur IS NOT NULL
         )
         ORDER BY u.nom ASC, u.prenom ASC"
    );

    $statement->execute();
    return $statement->fetchAll();
}