<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Demande de congé validée – Niveau 1</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: auto;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 6px;
        }
        .header {
            background: #f5f5f5;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
<div class="container">

    <div class="header">
        <h3>Notification – Demande de congé validée (Niveau 1)</h3>
    </div>

    <p>Bonjour {{ $employee->user->name ?? 'Employé' }},</p>

    <p>
        Nous vous informons que votre demande de congé a été
        <strong>validée au premier niveau d’approbation</strong>
        par votre responsable hiérarchique.
    </p>

    <p>
        La demande est désormais transmise au
        <strong>second niveau de validation</strong>.
    </p>

    <p><strong>Récapitulatif de votre demande :</strong></p>

    <ul>
        <li><strong>Type de congé :</strong> {{ $leave->type ?? 'N/A' }}</li>
        <li><strong>Période :</strong>
            du {{ $leave->start_date->format('d/m/Y') }}
            au {{ $leave->end_date->format('d/m/Y') }}
        </li>
        <li><strong>Nombre de jours :</strong> {{ $leave->days ?? '0' }}</li>
    </ul>

    <p>
        Vous serez notifié(e) dès qu’une nouvelle décision sera prise.
    </p>

    <p>
        <strong>MEDISTAFF</strong><br>
        www.sirhdj.com
    </p>

    <div class="footer">
        <p>Mail automatique, merci de ne pas répondre.</p>
    </div>

</div>
</body>
</html>
