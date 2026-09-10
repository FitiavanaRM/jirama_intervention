<?php

declare(strict_types=1);

// Démarre la session avec des paramètres de cookie adaptés à l'application.

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