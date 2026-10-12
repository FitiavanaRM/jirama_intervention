<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$email = '';
$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['mot_de_passe'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $loginError = 'Veuillez entrer une adresse e-mail et un mot de passe valides';
    } else {
        try {
            $connection = getDatabaseConnection();
            $statement = $connection->prepare(
                'SELECT u.id_utilisateur, u.nom, u.prenom, u.mot_de_passe,
                        r.id_role, r.nom_role
                 FROM utilisateurs AS u
                 INNER JOIN roles AS r ON r.id_role = u.id_role
                 WHERE u.email = :email
                 LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();

            if ($user === false || !password_verify($password, $user['mot_de_passe'])) {
                $loginError = 'Adresse e-mail ou mot de passe incorrect';
            } else {
                registerAuthenticatedUser($user);    
                header('Location: dashboard.php');
                exit;
            }
        } catch (RuntimeException $exception) {
            $loginError = 'Le service de connexion est temporairement indisponible';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | JIRAMA Intervention</title>
    <link rel="stylesheet" href="public/css/login.css">
</head>
<body class="login-page">
    <div class="login-layout">
        <section class="login-brand">
            <div class="login-brand-content">
                <p class="login-brand-name">JIRAMA</p>
                <p class="login-brand-subtitle">Intervention</p>
                <p class="login-brand-slogan">Ensemble pour une énergie fiable et durable</p>
            </div>
        </section>
    
        <main class="login-panel">

            <div class="login-box">

                <h1>Connexion</h1>
                <p class="login-intro">
                    Accédez à votre espace JIRAMA Intervention
                </p>

                <?php if ($loginError !== ''): ?>
                    <p class="login-error" role="alert">
                        <?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>

                <form action="login.php" method="post">

                    <div class="login-field">
                        <label for="email">Adresse e-mail</label>
                        <div class="login-input">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
                                 stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                <path d="M3 7l9 6 9-6"></path>
                            </svg>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                placeholder="nom@jirama.mg"
                                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="login-field">
                        <label for="mot_de_passe">Mot de passe</label>
                        <div class="login-input">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
                                 stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <rect x="5" y="11" width="14" height="9" rx="2"></rect>
                                <path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
                            </svg>
                            <input
                                id="mot_de_passe"
                                name="mot_de_passe"
                                type="password"
                                autocomplete="current-password"
                                placeholder="Votre mot de passe"
                                required
                            >
                        </div>
                    </div>

                    <button type="submit" class="login-button">Se connecter</button>

                </form>

            </div>

            <p class="login-footer">JIRAMA – Toliara</p>

        </main>
    </div>
</body>
</html>