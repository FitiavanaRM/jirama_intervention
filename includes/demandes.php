<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/demandes.php';

// Formate une référence de demande selon le format DEM-AAAA-NNNN
function formatDemandeReference(int $year, int $sequence): string
{
    if ($year < 2000 || $year > 9999 || $sequence < 1) {
        throw new InvalidArgumentException('Les données de référence sont invalides.');
    }

    return sprintf('DEM-%04d-%04d', $year, $sequence);
}

// prochaine reference dispo
function generateDemandeReference(PDO $connection, ?DateTimeInterface $date = null): string
{
    $requestDate = $date ?? new DateTimeImmutable();
    $year = (int) $requestDate->format('Y');
    $prefix = sprintf('DEM-%04d-', $year);

    $statement = $connection->prepare(
        'SELECT MAX(CAST(SUBSTRING(reference, 10) AS UNSIGNED))
         FROM demandes
         WHERE reference LIKE :prefix'
    );

    $statement->execute(['prefix' => $prefix . '%']);
    $lastSequence = (int) ($statement->fetchColumn() ?: 0);
    return formatDemandeReference($year, $lastSequence + 1);
}

// Valide et normalise les données nécessaires à la création d'une demande
function validateDemandeData(array $data): array
{
    $fields = [
        'nom_client' => ['Nom du client', 150],
        'telephone_client' => ['Téléphone du client', 20],
        'adresse_client' => ['Adresse du client', null],
        'type_demande' => ['Type de demande', 100],
        'description_probleme' => ['Description du problème', null],
    ];
    $validatedData = [];

    foreach ($fields as $field => [$label, $maximumLength]) {
        $value = trim((string) ($data[$field] ?? ''));

        if ($value === '') {
            throw new InvalidArgumentException("{$label} est obligatoire.");
        }

        if ($maximumLength !== null && strlen($value) > $maximumLength) {
            throw new InvalidArgumentException("{$label} dépasse la longueur autorisée.");
        }

        $validatedData[$field] = $value;
    }

    if (!preg_match('/^[0-9+().\s-]+$/', $validatedData['telephone_client'])) {
        throw new InvalidArgumentException('Le téléphone du client est invalide.');
    }

    return $validatedData;
}

// Crée une demande avec son statut initial et sa référence automatique.
function createDemande(PDO $connection, array $data, int $creatorId): array
{
    if ($creatorId < 1) {
        throw new InvalidArgumentException('L’utilisateur créateur est invalide.');
    }

    $validatedData = validateDemandeData($data);
    $maximumAttempts = 3;

    for ($attempt = 1; $attempt <= $maximumAttempts; $attempt++) {
        try {
            $connection->beginTransaction();
            $reference = generateDemandeReference($connection);
            $statement = $connection->prepare(
                'INSERT INTO demandes (
                    reference,
                    nom_client,
                    telephone_client,
                    adresse_client,
                    type_demande,
                    description_probleme,
                    statut,
                    id_utilisateur
                ) VALUES (
                    :reference,
                    :nom_client,
                    :telephone_client,
                    :adresse_client,
                    :type_demande,
                    :description_probleme,
                    :statut,
                    :id_utilisateur
                )'
            );
            $statement->execute([
                'reference' => $reference,
                'nom_client' => $validatedData['nom_client'],
                'telephone_client' => $validatedData['telephone_client'],
                'adresse_client' => $validatedData['adresse_client'],
                'type_demande' => $validatedData['type_demande'],
                'description_probleme' => $validatedData['description_probleme'],
                'statut' => 'EN_ATTENTE',
                'id_utilisateur' => $creatorId,
            ]);

            $demandeId = (int) $connection->lastInsertId();
            $connection->commit();

            return [
                'id_demande' => $demandeId,
                'reference' => $reference,
            ];
        } catch (PDOException $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            $isDuplicateReference = $exception->getCode() === '23000'
                && (int) ($exception->errorInfo[1] ?? 0) === 1062;

            if ($isDuplicateReference && $attempt < $maximumAttempts) {
                continue;
            }

            throw $exception;
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    throw new RuntimeException('Impossible de créer la demande');
}

// Retourne les demandes avec les informations utiles à leur consultation
function getDemandes(PDO $connection): array
{
    $statement = $connection->prepare(
        'SELECT
            d.id_demande,
            d.reference,
            d.nom_client,
            d.telephone_client,
            d.adresse_client,
            d.type_demande,
            d.description_probleme,
            d.date_demande,
            d.statut,
            d.code_intervention,
            u.nom AS createur_nom,
        FROM demandes AS d
        INNER JOIN utilisateurs AS u
            ON u.id_utilisateur = d.id_utilisateur
        ORDER BY
            d.date_demande DESC,
            d.id_demande DESC'
    );
    
    $statement->execute();
    return $statement->fetchAll();
}

// Retourne une demande précise avec les informations de son créateur.
function getDemandeById(PDO $connection, int $demandeId): ?array
{
    if ($demandeId < 1) {
        return null;
    }

    $statement = $connection->prepare(
        'SELECT
            d.id_demande,
            d.reference,
            d.nom_client,
            d.telephone_client,
            d.adresse_client,
            d.type_demande,
            d.description_probleme,
            d.date_demande,
            d.statut,
            d.id_client,
            d.repere_adresse,
            d.code_intervention,
            u.nom AS createur_nom,
            u.prenom AS createur_prenom
        FROM demandes AS d
        INNER JOIN utilisateurs AS u ON u.id_utilisateur = d.id_utilisateur
        WHERE d.id_demande = :id_demande
        LIMIT 1'
    );

    $statement->execute(['id_demande' => $demandeId]);
    $demande = $statement->fetch();
    return $demande === false ? null : $demande;
}

// Mise à jour du statut du demande
function updateDemandeStatut(
    PDO $connection,
    int $demandeId,
    string $nouveauStatut
): void
{
    $statutsValides = [
        'EN_ATTENTE',
        'ACTIVE',
        'AFFECTEE',
        'EN_COURS',
        'RESOLUE',
        'CLOTUREE',
        'ANNULEE',
    ];

    if (!in_array($nouveauStatut, $statutsValides, true)) {
        throw new InvalidArgumentException('Le statut demandé est invalide');
    }

    $statement = $connection->prepare(
        'UPDATE demandes
         SET statut = :statut
         WHERE id_demande = :id_demande'
    );

    $statement->execute([
        'statut' => $nouveauStatut,
        'id_demande' => $demandeId,
    ]);
}

// retourn uniquement les demandes en attente d'affection
function getDemandesEnAttente(PDO $connection): array
{
    $statement = $connection->prepare(
        'SELECT d.id_demande,
                d.reference,
                d.nom_client,
                d.type_demande,
                d.date_demande
        FROM demandes AS d WHERE d.statut = :statut ORDER BY d.date_demande ASC, d.id_demande ASC'
    );

    $statement->execute([
        'statut' => 'EN_ATTENTE'
    ]);

    return $statement->fetchAll();
}


// Enregistre un changement de statut dans l'historique de la demande
function insertHistorique(
    PDO $connection,
    int $demandeId,
    int $utilisateurId,
    ?string $ancienStatut,
    string $nouveauStatut,
    ?string $commentaire = null
): void
{
    $statement = $connection->prepare(
        'INSERT INTO historiques (
            id_demande,
            id_utilisateur,
            ancien_statut,
            nouveau_statut,
            commentaire
        ) VALUES (
            :id_demande,
            :id_utilisateur,
            :ancien_statut,
            :nouveau_statut,
            :commentaire
        )'
    );

    $statement->execute([
        'id_demande' => $demandeId,
        'id_utilisateur' => $utilisateurId,
        'ancien_statut' => $ancienStatut,
        'nouveau_statut' => $nouveauStatut,
        'commentaire' => $commentaire,
    ]);
}
    
// Retourne les demandes affectées, en attente d'organisation de l'intervention.
function getDemandesAffectees(PDO $connection): array
{
    $statement = $connection->prepare(
        'SELECT
            d.id_demande,
            d.reference,
            d.nom_client,
            d.type_demande,
            d.description_probleme,
            a.date_affectation,
            e.nom_equipe,
            t.nom AS technicien_nom,
            t.prenom AS technicien_prenom
        FROM demandes AS d
        INNER JOIN affectations AS a
            ON a.id_demande = d.id_demande
        INNER JOIN equipes AS e
            ON e.id_equipe = a.id_equipe
        LEFT JOIN techniciens AS t
            ON t.id_technicien = a.id_technicien
        WHERE d.statut = :statut
        ORDER BY a.date_affectation ASC, d.id_demande ASC'
    );

    $statement->execute([
        'statut' => 'AFFECTEE'
    ]);

    return $statement->fetchAll();
}

// Vérifie qu'une demande existe et qu'elle est bien résolue avant clôture
function checkClotureCoherence(
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

    if ($demande['statut'] !== 'RESOLUE') {
        throw new InvalidArgumentException('Cette demande n’est pas encore résolue');
    }
}

// retourne l'historique complet
function getHistoriques(PDO $connection) : array
{
    $statement = $connection->prepare(
        'SELECT
            h.id_historique,
            h.ancien_statut,
            h.nouveau_statut,
            h.date_action,
            h.commentaire,
            d.id_demande,
            d.reference,
            d.nom_client,
            u.nom AS utilisateur_nom,
            u.prenom AS utilisateur_prenom
        FROM historiques AS h
        INNER JOIN demandes AS d
            ON d.id_demande = h.id_demande
        INNER JOIN utilisateurs AS u
            ON u.id_utilisateur = h.id_utilisateur
        ORDER BY h.date_action DESC, h.id_historique DESC'
    );

    $statement->execute();
    return $statement->fetchAll();
}

// statistique
function getStatistiquesParStatut(PDO $connection): array
{
    $statement = $connection->prepare(
        'SELECT statut, COUNT(*) AS total
         FROM demandes
         GROUP BY statut'
    );

    $statement->execute();
    $resultats = $statement->fetchAll();
    $statutsPossibles = [
        'EN_ATTENTE' => 0,
        'ACTIVE' => 0,
        'AFFECTEE' => 0,
        'EN_COURS' => 0,
        'RESOLUE' => 0,
        'CLOTUREE' => 0,
        'ANNULEE' => 0,
    ];

    foreach ($resultats as $resultat) {
        $statutsPossibles[$resultat['statut']] = (int) $resultat['total'];
    }

    return $statutsPossibles;
}

// Met à jour les champs source d'une demande encore en attente
function updateDemande(
    PDO $connection,
    int $demandeId,
    array $data
): void
{
    $statement = $connection->prepare(
        'UPDATE demandes
         SET
            nom_client = :nom_client,
            telephone_client = :telephone_client,
            adresse_client = :adresse_client,
            type_demande = :type_demande,
            description_probleme = :description_probleme
         WHERE id_demande = :id_demande
         AND statut = :statut_attendu'
    );

    $statement->execute([
        'nom_client' => $data['nom_client'],
        'telephone_client' => $data['telephone_client'],
        'adresse_client' => $data['adresse_client'],
        'type_demande' => $data['type_demande'],
        'description_probleme' => $data['description_probleme'],
        'id_demande' => $demandeId,
        'statut_attendu' => 'EN_ATTENTE',
    ]);

    if ($statement->rowCount() === 0) {
        throw new InvalidArgumentException('Cette demande ne peut plus être modifiée (elle n’est plus en attente)');
    }
}

// Retourne le HTML d'une pastille de statut
function renderStatutBadge(string $statut): string
{
    // classe CSS, libellé, code JIRAMA
    $statuts = [
        'EN_ATTENTE' => ['statut-en-attente', 'En attente', '100'],
        'ACTIVE' => ['statut-active', 'Active', '110'],
        'AFFECTEE' => ['statut-affectee', 'Affectée', null],
        'EN_COURS' => ['statut-en-cours', 'En cours', null],
        'RESOLUE' => ['statut-resolue', 'Résolue', null],
        'CLOTUREE' => ['statut-cloturee', 'Clôturée', '999'],
        'ANNULEE' => ['statut-annulee', 'Annulée', null],
    ];

    [$classe, $libelle, $code] = $statuts[$statut] ?? ['statut-inconnu', $statut, null];
    $html = '<span class="statut ' . $classe . '">';
    if ($code !== null) {
        $html .= '<span class="statut-code">'
            . htmlspecialchars($code, ENT_QUOTES, 'UTF-8')
            . '</span> ';
    }

    $html .= htmlspecialchars($libelle, ENT_QUOTES, 'UTF-8') . '</span>';
    return $html;
}

// Retourne les indicateurs généraux affichés en haut de la page de statistiques
function getIndicateursGeneraux(PDO $connection): array
{
    // On réutilise le comptage par statut déjà existant
    $parStatut = getStatistiquesParStatut($connection);
    $total = array_sum($parStatut);
    $traitees = $parStatut['RESOLUE'] + $parStatut['CLOTUREE'];

    // Les demandes annulées ne comptent pas dans le taux de résolution
    $baseTaux = $total - $parStatut['ANNULEE'];

    $taux = $baseTaux > 0
        ? (int) round($traitees * 100 / $baseTaux)
        : 0;

    $statement = $connection->prepare(
        'SELECT AVG(TIMESTAMPDIFF(MINUTE, date_debut, date_fin))
         FROM interventions
         WHERE date_debut IS NOT NULL
         AND date_fin IS NOT NULL'
    );

    $statement->execute();
    $moyenne = $statement->fetchColumn();
    return [
        'total' => $total,
        'en_cours' => $parStatut['EN_COURS'],
        'taux_resolution' => $taux,
        'delai_moyen_minutes' => $moyenne !== null && $moyenne !== false
            ? (int) round((float) $moyenne)
            : null,
    ];
}

// Affiche une durée en minutes
function formatDuree(?int $minutes): string
{
    if ($minutes === null) {
        return '—';
    }

    $heures = intdiv($minutes, 60);
    $reste = $minutes % 60;
    if ($heures === 0) {
        return $reste . ' min';
    }
    
    return $heures . ' h ' . str_pad((string) $reste, 2, '0', STR_PAD_LEFT);
}


// Retourne la liste des problèmes proposés pour une réclamation
function getTypesProbleme(PDO $connection): array
{
    $statement = $connection->prepare(
        'SELECT id_type_probleme, libelle
         FROM types_probleme
         ORDER BY
            (libelle = :autre) ASC,
            libelle ASC'
    );

    $statement->execute([
        'autre' => 'Autre'
    ]);

    return $statement->fetchAll();
}


// Retourne un problème précis, ou null s'il n'existe pas
function getTypeProblemeById(PDO $connection, int $typeProblemeId): ?array
{
    if ($typeProblemeId < 1) {
        return null;
    }

    $statement = $connection->prepare(
        'SELECT id_type_probleme, libelle
         FROM types_probleme
         WHERE id_type_probleme = :id_type_probleme
         LIMIT 1'
    );

    $statement->execute([
        'id_type_probleme' => $typeProblemeId
    ]);

    $typeProbleme = $statement->fetch();
    return $typeProbleme === false ? null : $typeProbleme;
}

// Retourne le prochain numéro de dossier
function generateNumeroDossier(PDO $connection): string
{
    $statement = $connection->prepare(
        'SELECT MAX(CAST(reference AS UNSIGNED))
         FROM demandes
         WHERE reference REGEXP :modele'
    );

    $statement->execute([
        'modele' => '^[0-9]{6}$'
    ]);

    $dernierNumero = (int) ($statement->fetchColumn() ?: 0);

    if ($dernierNumero >= 999999) {
        throw new RuntimeException('Le numéro de dossier maximum est atteint');
    }

    return sprintf('%06d', $dernierNumero + 1);
}

// Valide les données d'une réclamation et retourne les valeurs à enregistrer
function validateReclamationData(PDO $connection, array $data): array
{
    $idClient = filter_var($data['id_client'] ?? null, FILTER_VALIDATE_INT);

    if ($idClient === false || $idClient < 1) {
        throw new InvalidArgumentException('Le client est invalide');
    }

    $client = getClientById($connection, $idClient);

    if ($client === null) {
        throw new InvalidArgumentException('Ce client est introuvable');
    }

    $telephone = trim((string) ($data['telephone_client'] ?? ''));

    if ($telephone === '') {
        throw new InvalidArgumentException('Le téléphone du client est obligatoire');
    }

    if (strlen($telephone) > 20) {
        throw new InvalidArgumentException('Le téléphone du client dépasse la longueur autorisée');
    }

    if (!preg_match('/^[0-9+().\s-]+$/', $telephone)) {
        throw new InvalidArgumentException('Le téléphone du client est invalide');
    }
    $repere = trim((string) ($data['repere_adresse'] ?? ''));

    if ($repere === '') {
        throw new InvalidArgumentException('Le repère de l’adresse est obligatoire');
    }

    if (strlen($repere) > 255) {
        throw new InvalidArgumentException('Le repère de l’adresse dépasse la longueur autorisée');
    }

    $idTypeProbleme = filter_var($data['id_type_probleme'] ?? null, FILTER_VALIDATE_INT);

    if ($idTypeProbleme === false || $idTypeProbleme < 1) {
        throw new InvalidArgumentException('Le problème signalé est obligatoire');
    }

    $typeProbleme = getTypeProblemeById($connection, $idTypeProbleme);

    if ($typeProbleme === null) {
        throw new InvalidArgumentException('Le problème sélectionné n’existe pas');
    }

    $libelle = (string) $typeProbleme['libelle'];
    $precision = trim((string) ($data['precision_probleme'] ?? ''));

    if (strlen($precision) > 1000) {
        throw new InvalidArgumentException('La précision dépasse la longueur autorisée');
    }

    if ($libelle === 'Autre' && $precision === '') {
        throw new InvalidArgumentException('Précisez le problème lorsque vous choisissez « Autre »');
    }

    if ($libelle === 'Autre') {
        $description = $precision;
    } elseif ($precision !== '') {
        $description = $libelle . ' : ' . $precision;
    } else {
        $description = $libelle;
    }

    return [
        'id_client' => (int) $client['id_client'],
        'nom_client' => (string) $client['nom_client'],
        'adresse_client' => (string) $client['adresse_client'],
        'telephone_client' => $telephone,
        'repere_adresse' => $repere,
        'type_demande' => $libelle,
        'description_probleme' => $description,
    ];
}

// Enregistre une réclamation
function createReclamation(
    PDO $connection,
    array $data,
    int $creatorId
): array
{
    if ($creatorId < 1) {
        throw new InvalidArgumentException('L’utilisateur créateur est invalide');
    }

    $maximumAttempts = 3;
    for ($attempt = 1; $attempt <= $maximumAttempts; $attempt++) {
        try {
            $connection->beginTransaction();
            $numeroDossier = generateNumeroDossier($connection);
            $statement = $connection->prepare(
                'INSERT INTO demandes (
                    reference,
                    nom_client,
                    telephone_client,
                    adresse_client,
                    repere_adresse,
                    type_demande,
                    description_probleme,
                    statut,
                    id_utilisateur,
                    id_client
                ) VALUES (
                    :reference,
                    :nom_client,
                    :telephone_client,
                    :adresse_client,
                    :repere_adresse,
                    :type_demande,
                    :description_probleme,
                    :statut,
                    :id_utilisateur,
                    :id_client
                )'
            );

            $statement->execute([
                'reference' => $numeroDossier,
                'nom_client' => $data['nom_client'],
                'telephone_client' => $data['telephone_client'],
                'adresse_client' => $data['adresse_client'],
                'repere_adresse' => $data['repere_adresse'],
                'type_demande' => $data['type_demande'],
                'description_probleme' => $data['description_probleme'],
                'statut' => 'EN_ATTENTE',
                'id_utilisateur' => $creatorId,
                'id_client' => $data['id_client'],
            ]);

            $demandeId = (int) $connection->lastInsertId();
            // Le téléphone vérifié avec le client devient celui de sa fiche
            $statement = $connection->prepare(
                'UPDATE clients
                 SET telephone_client = :telephone_client
                 WHERE id_client = :id_client'
            );

            $statement->execute([
                'telephone_client' => $data['telephone_client'],
                'id_client' => $data['id_client'],
            ]);

            insertHistorique(
                $connection,
                $demandeId,
                $creatorId,
                null,
                'EN_ATTENTE',
                'Réclamation enregistrée'
            );

            $connection->commit();
            return [
                'id_demande' => $demandeId,
                'reference' => $numeroDossier,
            ];

        } catch (PDOException $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            $isDuplicateReference =
                $exception->getCode() === '23000'
                &&
                (int) ($exception->errorInfo[1] ?? 0) === 1062;

            if ($isDuplicateReference && $attempt < $maximumAttempts) {
                continue;
            }
            throw $exception;

        }
        catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    throw new RuntimeException('Impossible d’enregistrer la réclamation');
}


// Retrouve la précision écrite si c'est autre
function extractPrecisionProbleme(string $libelle, string $description): string
{
    if ($libelle === 'Autre') {
        return $description;
    }

    $prefixe = $libelle . ' : ';
    if (str_starts_with($description, $prefixe)) {
        return substr($description, strlen($prefixe));
    }

    return '';
}


// Modifie une réclamation encore en attente
function updateReclamation(
    PDO $connection,
    int $demandeId,
    array $data
): void
{
    try {
        $connection->beginTransaction();
        // On verrouille la ligne pour relire son état au moment exact de la modification
        $statement = $connection->prepare(
            'SELECT statut, id_client
             FROM demandes
             WHERE id_demande = :id_demande
             FOR UPDATE'
        );

        $statement->execute([
            'id_demande' => $demandeId
        ]);

        $demande = $statement->fetch();
        if ($demande === false) {
            throw new InvalidArgumentException('La réclamation sélectionnée n’existe pas');
        }

        if ($demande['statut'] !== 'EN_ATTENTE') {
            throw new InvalidArgumentException('Cette réclamation ne peut plus être modifiée (elle n’est plus en attente)');
        }

        if ($demande['id_client'] === null) {
            throw new InvalidArgumentException('Cette demande n’est pas liée à un client et ne peut pas être modifiée ici');
        }

        $statement = $connection->prepare(
            'UPDATE demandes
             SET
                telephone_client = :telephone_client,
                repere_adresse = :repere_adresse,
                type_demande = :type_demande,
                description_probleme = :description_probleme
             WHERE id_demande = :id_demande'
        );

        $statement->execute([
            'telephone_client' => $data['telephone_client'],
            'repere_adresse' => $data['repere_adresse'],
            'type_demande' => $data['type_demande'],
            'description_probleme' => $data['description_probleme'],
            'id_demande' => $demandeId,
        ]);

        // Le téléphone corrigé devient celui de la fiche du client enregistré dans la demande
        $statement = $connection->prepare(
            'UPDATE clients
             SET telephone_client = :telephone_client
             WHERE id_client = :id_client'
        );

        $statement->execute([
            'telephone_client' => $data['telephone_client'],
            'id_client' => (int) $demande['id_client'],
        ]);

        $connection->commit();
    }
    catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }

        throw $exception;
    }
}

// Retourne le HTML d'une pastille de code d'intervention
function renderCodeInterventionBadge(?string $code): string
{
    $codes = [
        'C1' => ['code-c1', 'Transmise'],
        'C2' => ['code-c2', 'Planifiée'],
        'C5' => ['code-c5', 'Terminée'],
    ];

    if ($code === null || !isset($codes[$code])) {
        return '';
    }

    [$classe, $libelle] = $codes[$code];
    return '<span class="statut ' . $classe . '">'
        . '<span class="statut-code">' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</span> '
        . htmlspecialchars($libelle, ENT_QUOTES, 'UTF-8')
        . '</span>';
}

// Vérifie qu'une demande existe et qu'elle est reçue (100) avant de l'activer (110)
function checkActivationCoherence(
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

    if ($demande['statut'] !== 'EN_ATTENTE') {
        throw new InvalidArgumentException('Cette demande n’est pas à l’état reçu (100) : elle ne peut pas être activée');
    }
}

// Active une demande reçue (100 vers 110) : statut, code C1 et historique, en une transaction
function activerDemande(
    PDO $connection,
    int $demandeId,
    int $utilisateurId
): void
{
    if ($utilisateurId < 1) {
        throw new InvalidArgumentException('L’utilisateur est invalide');
    }

    // Contrôle lisible pour l'utilisateur (existence et statut)
    checkActivationCoherence($connection, $demandeId);
    try {
        $connection->beginTransaction();
        // Le WHERE sur le statut garantit qu'une demande déjà activée.
        $statement = $connection->prepare(
            'UPDATE demandes
             SET statut = :nouveau_statut,
                 code_intervention = :code
             WHERE id_demande = :id_demande
             AND statut = :ancien_statut'
        );

        $statement->execute([
            'nouveau_statut' => 'ACTIVE',
            'code' => 'C1',
            'id_demande' => $demandeId,
            'ancien_statut' => 'EN_ATTENTE',
        ]);

        if ($statement->rowCount() !== 1) {
            throw new InvalidArgumentException('Cette demande a déjà été traitée par un autre utilisateur');
        }

        insertHistorique(
            $connection,
            $demandeId,
            $utilisateurId,
            'EN_ATTENTE',
            'ACTIVE',
            'Demande activée (C1)'
        );

        $connection->commit();

    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }

        throw $exception;
    }
}