<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Demande de congé – Validation RH requise</title>
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
        <h3>Alerte RH – Demande de congé en attente de validation</h3>
    </div>

    <p>Bonjour Équipe RH,</p>

    <p>
        Une demande de congé a été validée par les responsables hiérarchiques
        et nécessite désormais votre validation.
    </p>

    <p>
        Merci de bien vouloir confirmer la décision à prendre :
    </p>

    <ul>
        <li>Approbation</li>
        <li>Rejet</li>
        <li>Demande de complément d’information (si nécessaire)</li>
    </ul>

    <p><strong>Récapitulatif :</strong></p>

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
