<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Demande de congé – Approbation requise (Niveau 2)</title>
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
        <h3>Alerte – Demande de congé en attente d’approbation (Niveau 2)</h3>
    </div>

    <p>Bonjour {{ $manager->name ?? 'Manager' }},</p>

    <p>
        Une demande de congé validée au premier niveau est en attente
        de votre approbation (<strong>Niveau 2</strong>).
    </p>

    <p>
        Merci de bien vouloir confirmer votre décision :
    </p>

    <ul>
        <li>Approbation</li>
        <li>Rejet</li>
    </ul>

    <p><strong>Récapitulatif de la demande :</strong></p>

    <ul>
        <li><strong>Employé :</strong> {{ $employee->user->name ?? 'N/A' }}</li>
        <li><strong>Matricule :</strong> {{ $employee->matricule ?? 'N/A' }}</li>
        <li><strong>Poste / Service :</strong>
            {{ $employee->designation->name ?? 'N/A' }} –
            {{ $employee->department->name ?? 'N/A' }}
        </li>
        <li><strong>Type de congé :</strong> {{ $leave->type ?? 'N/A' }}</li>
        <li><strong>Période :</strong>
            du {{ $leave->start_date->format('d/m/Y') }}
            au {{ $leave->end_date->format('d/m/Y') }}
        </li>
        <li><strong>Nombre de jours :</strong> {{ $leave->days ?? '0' }}</li>
    </ul>

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
