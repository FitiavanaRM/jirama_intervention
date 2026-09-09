<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$email = '';
$loginError = '';
$loginValidated = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['mot_de_passe'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $loginError = 'Veuillez renseigner une adresse e-mail et un mot de passe valides.';
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
                $loginError = 'Adresse e-mail ou mot de passe incorrect.';
            } else {
                registerAuthenticatedUser($user);
                $loginValidated = true;
            }
        } catch (RuntimeException $exception) {
            $loginError = 'Le service de connexion est temporairement indisponible.';
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
</head>
<body>
    <main>
        <h1>Connexion</h1>
        <p>Application de gestion des interventions et dépannages électriques.</p>

        <?php if ($loginError !== ''): ?>
            <p role="alert"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($loginValidated): ?>
            <p>Vos identifiants ont été vérifiés avec succès.</p>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div>
                <label for="email">Adresse e-mail</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div>
                <label for="mot_de_passe">Mot de passe</label>
                <input
                    id="mot_de_passe"
                    name="mot_de_passe"
                    type="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit">Se connecter</button>
        </form>
    </main>
</body>
</html>
