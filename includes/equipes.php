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
}