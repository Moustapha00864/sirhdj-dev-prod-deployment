<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Demande de congé – Refusée</title>
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
            text-align: center;
        }

        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="header">
            <h3>Demande de congé – Refusée</h3>
        </div>

        <p>Bonjour {{ $employee->user->name ?? 'Employé' }},</p>

        <p>
            Nous vous informons que votre demande de congé a été refusée au <strong>niveau
                {{ $leave->current_stage }}</strong> par <strong>{{ $approver->name ?? 'le responsable' }}</strong>.
        </p>

        <h4>Récapitulatif de votre demande :</h4>
        <ul>
            <li><strong>Type de congé :</strong> {{ $leave->type ?? 'N/A' }}</li>
            <li><strong>Période :</strong> du {{ $leave->start_date->format('d/m/Y') }} au
                {{ $leave->end_date->format('d/m/Y') }}</li>
            <li><strong>Nombre de jours :</strong> {{ $leave->days ?? '0' }}</li>
            <li><strong>Motif du rejet :</strong> {{ $comments ?? 'Non précisé' }}</li>
        </ul>

        <p>Pour toute question ou complément d’information, veuillez contacter votre responsable hiérarchique.</p>

        <p>Cordialement,</p>
        <p><strong>MEDISTAFF</strong><br>www.sirhdj.com</p>

        <div class="footer">
            <p>Mail automatique – merci de ne pas répondre.</p>
        </div>

    </div>
</body>

</html>