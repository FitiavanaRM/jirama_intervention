<?php

declare(strict_types=1);

/**
 * Démarre la session avec des paramètres de cookie adaptés à l'application.
 */
function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');
    session_name('jirama_intervention_session');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
    ]);
    if (!@session_start()) {
        throw new RuntimeException('Impossible de démarrer la session utilisateur.');
    }
}

/**
 * Enregistre les informations non sensibles de l'utilisateur authentifié.
 *
 * @param array<string, mixed> $user
 */
function registerAuthenticatedUser(array $user): void
{
    startSecureSession();
    session_regenerate_id(true);

    $_SESSION['id_utilisateur'] = (int) $user['id_utilisateur'];
    $_SESSION['nom'] = (string) $user['nom'];
    $_SESSION['prenom'] = (string) $user['prenom'];
    $_SESSION['id_role'] = (int) $user['id_role'];
    $_SESSION['nom_role'] = (string) $user['nom_role'];
}

/**
 * Retourne les informations non sensibles de l'utilisateur connecté.
 *
 * @return array<string, int|string>|null
 */
function getCurrentUser(): ?array
{
    startSecureSession();

    $requiredKeys = ['id_utilisateur', 'nom', 'prenom', 'id_role', 'nom_role'];

    foreach ($requiredKeys as $key) {
        if (!isset($_SESSION[$key])) {
            return null;
        }
    }

    return [
        'id_utilisateur' => (int) $_SESSION['id_utilisateur'],
        'nom' => (string) $_SESSION['nom'],
        'prenom' => (string) $_SESSION['prenom'],
        'id_role' => (int) $_SESSION['id_role'],
        'nom_role' => (string) $_SESSION['nom_role'],
    ];
}

/**
 * Indique si l'utilisateur connecté possède l'un des rôles demandés.
 *
 * @param string|array<int, string> $roles
 */
function hasRole(string|array $roles): bool
{
    $user = getCurrentUser();

    if ($user === null) {
        return false;
    }

    $allowedRoles = is_array($roles) ? $roles : [$roles];

    return in_array($user['nom_role'], $allowedRoles, true);
}

/**
 * Indique si l'utilisateur connecté possède une permission donnée.
 */
function hasPermission(string $permission): bool
{
    $user = getCurrentUser();

    if ($user === null) {
        return false;
    }

    $permissionsByRole = [
        'Administrateur' => ['*'],
        "Agent d'accueil" => [
            'demande.creer',
            'demande.consulter',
            'demande.rechercher',
            'demande.modifier',
        ],
        "Agent d'écart" => [
            'demande.consulter',
            'demande.suivre',
            'affectation.creer',
            'equipe.consulter',
            'technicien.consulter',
        ],
        "Chef d'équipe" => [
            'demande.affectee.consulter',
            'intervention.consulter',
            'intervention.organiser',
            'technicien.superviser',
            'intervention.suivre',
        ],
        'Technicien' => [
            'intervention.personnelle.consulter',
            'intervention.demarrer',
            'intervention.terminer',
            'intervention.renseigner',
        ],
        'Chef de division' => [
            'demande.consulter',
            'intervention.consulter',
            'historique.consulter',
            'statistique.consulter',
            'activite.superviser',
        ],
    ];

    $permissions = $permissionsByRole[$user['nom_role']] ?? [];

    return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
}
