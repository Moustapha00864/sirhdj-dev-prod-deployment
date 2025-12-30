<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4F46E5;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }

        .footer {
            margin-top: 30px;
            font-size: 0.8em;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Bienvenue sur {{ config('app.name') }}</h2>

        <p>Bonjour {{ $user->name }},</p>

        <p>Un compte utilisateur a été créé pour vous sur la plateforme Medistaff.</p>

        <p><strong>Détails du compte :</strong></p>
        <ul>
            <li><strong>Nom :</strong> {{ $user->name }}</li>
            <li><strong>Email :</strong> {{ $user->email }}</li>
            <li><strong>Rôle :</strong> {{ ucfirst($user->type) }}</li>
        </ul>

        <p>Veuillez cliquer sur le bouton ci-dessous pour configurer votre mot de passe et accéder à votre compte :</p>

        <a href="{{ $resetLink }}" class="button">Configurer mon mot de passe</a>

        <p>Ou copiez et collez le lien suivant dans votre navigateur :<br>
            <small>{{ $resetLink }}</small>
        </p>

        <p>Ce lien expirera dans {{ config('auth.passwords.users.expire') }} minutes.</p>

        <p>Cordialement,<br>
            L'équipe {{ config('app.name') }}</p>

        <div class="footer">
            <p>Si vous n'êtes pas le destinataire de cet e-mail, veuillez l'ignorer.</p>
        </div>
    </div>
</body>

</html>