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

// Valide et normalise les données du formulaire de création d'un technicien
function validateTechnicienData(array $data): array
{
    $nom = trim((string) ($data['nom'] ?? ''));
    $prenom = trim((string) ($data['prenom'] ?? ''));
    $telephone = trim((string) ($data['telephone'] ?? ''));
    $fonction = trim((string) ($data['fonction'] ?? ''));

    if ($nom === '' || $prenom === '') {
        throw new InvalidArgumentException('Le nom et le prénom du technicien sont obligatoires');
    }

    if (strlen($nom) > 100 || strlen($prenom) > 100) {
        throw new InvalidArgumentException('Le nom ou le prénom dépasse la longueur autorisée');
    }

    if (
        $telephone !== '' && !preg_match('/^[0-9+().\s-]+$/', $telephone)
    ) {
        throw new InvalidArgumentException('Le téléphone du technicien est invalide');
    }

    $idEquipe = filter_var(
        $data['id_equipe'] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($idEquipe === false || $idEquipe < 1) {
        throw new InvalidArgumentException('L’équipe est obligatoire');
    }

    $idUtilisateurRaw = trim((string) ($data['id_utilisateur'] ?? ''));
    $idUtilisateur = null;
}