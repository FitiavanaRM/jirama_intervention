<?php

declare(strict_types=1);

// Crée une intervention pour une demande et enregistre la date de début
function insertIntervention(
    PDO $connection,
    int $demandeId
): int
{
    $statement = $connection->prepare(
        'INSERT INTO interventions (id_demande, date_debut) VALUES (:id_demande,NOW())'
    );

    $statement->execute([
        'id_demande' => $demandeId,
    ]);
    
    return (int) $connection->lastInsertId();
}

function checkInterventionCoherence(
    PDO $connection,
    int $demandeId
): void
{
    $statement = $connection->prepare(
        'SELECT statut FROM demandes WHERE id_demande = :id_demande LIMIT 1'
    );

    $statement->execute([
        'id_demande' => $demandeId
    ]);

    $demande = $statement->fetch();

    if ($demande === false) {
        throw new InvalidArgumentException('La demande sélectionnée n’existe pas');
    }

    if ($demande['statut'] !== 'AFFECTEE') {
        throw new InvalidArgumentException('Cette demande n’est pas en attente d’intervention');
    }
}


// Retourne les interventions en cours du technicien lié au compte utilisateur donné
function getInterventionsDuTechnicien(
    PDO $connection,
    int $utilisateurId
): array
{
    $statement = $connection->prepare(
        'SELECT
            i.id_intervention,
            i.date_debut,
            d.id_demande,
            d.reference,
            d.nom_client,
            d.adresse_client,
            d.type_demande,
            d.description_probleme
        FROM interventions AS i
        INNER JOIN demandes AS d
            ON d.id_demande = i.id_demande
        INNER JOIN affectations AS a
            ON a.id_demande = d.id_demande
        INNER JOIN techniciens AS t
            ON t.id_technicien = a.id_technicien
        WHERE t.id_utilisateur = :id_utilisateur
        AND d.statut = :statut
        AND i.date_fin IS NULL
        ORDER BY i.date_debut ASC, i.id_intervention ASC'
    );

    $statement->execute([
        'id_utilisateur' => $utilisateurId,
        'statut' => 'EN_COURS',
    ]);

    return $statement->fetchAll();
}

// Valide les données de fin d'intervention saisies par le technicien
function validateInterventionFinData(array $data): array
{
    $problemeConstate = trim((string) ($data['probleme_constate'] ?? ''));
    $travailEffectue = trim((string) ($data['travail_effectue'] ?? ''));
    $resultat = trim((string) ($data['resultat'] ?? ''));
    $observation = trim((string) ($data['observation'] ?? ''));

    if ($problemeConstate === '' || $travailEffectue === '' || $resultat === '') {
        throw new InvalidArgumentException('Le problème constaté, le travail effectué et le résultat sont obligatoires');
    }

    return [
        'probleme_constate' => $problemeConstate,
        'travail_effectue' => $travailEffectue,
        'resultat' => $resultat,
        'observation' => $observation !== '' ? $observation : null,
    ];
}

// Enregistre les renseignements finaux d'une intervention et sa date de fin
function terminerIntervention(
    PDO $connection,
    int $demandeId,
    array $data
): void
{
    $statement = $connection->prepare(
        'UPDATE interventions
        SET
            date_fin = NOW(),
            probleme_constate = :probleme_constate,
            travail_effectue = :travail_effectue,
            resultat = :resultat,
            observation = :observation
        WHERE id_demande = :id_demande
        AND date_fin IS NULL'
    );
}