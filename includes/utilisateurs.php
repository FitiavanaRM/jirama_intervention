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

    if ($nom === '' || $prenom === '') {
        throw new InvalidArgumentException('Le nom et le prénom sont obligatoires');
    }

    if (strlen($nom) > 100 || strlen($prenom) > 100) {
        throw new InvalidArgumentException('Le nom ou le prénom dépasse la longueur autorisée');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('L’adresse e-mail est invalide');
    }

    if ($telephone !== '' && !preg_match('/^[0-9+().\s-]+$/', $telephone)) {
        throw new InvalidArgumentException('Le téléphone est invalide');
    }

    if ($motDePasseObligatoire && strlen($motDePasse) < 8) {
        throw new InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères');
    }

    if (!$motDePasseObligatoire && $motDePasse !== '' && strlen($motDePasse) < 8) {
        throw new InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères');
    }

    $idRole = filter_var(
        $data['id_role'] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($idRole === false || $idRole < 1) {
        throw new InvalidArgumentException('Le rôle est obligatoire');
    }

    return [
        'nom' => $nom,
        'prenom' => $prenom,
        'telephone' => $telephone !== '' ? $telephone : null,
        'email' => $email,
        'mot_de_passe' => $motDePasse !== '' ? $motDePasse : null,
        'id_role' => $idRole,
    ];
}

// Insère un nouvel utilisateur
function insertUtilisateur(PDO $connection, array $data): int
{
    $statement = $connection->prepare(
        'INSERT INTO utilisateurs (
            nom,
            prenom,
            telephone,
            email,
            mot_de_passe,
            id_role
        ) VALUES (
            :nom,
            :prenom,
            :telephone,
            :email,
            :mot_de_passe,
            :id_role
        )'
    );
    
    try {
        $statement->execute([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'telephone' => $data['telephone'],
            'email' => $data['email'],
            'mot_de_passe' => password_hash((string) $data['mot_de_passe'], PASSWORD_DEFAULT),
            'id_role' => $data['id_role'],
        ]);
    }
}