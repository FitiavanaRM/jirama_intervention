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

// valide les données d'un utilisateur
function validateUtilisateurData(array $data, bool $motDePasseObligatoire = true): array
{
    $nom = trim((string) ($data['nom'] ?? ''));
    $prenom = trim((string) ($data['prenom'] ?? ''));
    $telephone = trim((string) ($data['telephone'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $motDePasse = (string) ($data['mot_de_passe'] ?? '');
}