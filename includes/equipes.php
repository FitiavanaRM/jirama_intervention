<?php

declare(strict_types=1);

// Valide et normalise les données du formulaire d'une équipe
function validateEquipeData(array $data): array
{
    $nomEquipe = trim((string) ($data['nom_equipe'] ?? ''));
    $typeEquipe = trim((string) ($data['type_equipe'] ?? ''));
}