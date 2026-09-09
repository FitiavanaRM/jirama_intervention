<?php

declare(strict_types=1);
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

        <form action="login.php" method="post">
            <div>
                <label for="email">Adresse e-mail</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
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