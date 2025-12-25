<h1>Leave Request Approved (Final)</h1>
<p>Hello {{ $leave->employee->name }},</p>
<p>Your leave request has been fully approved.</p>
<p><strong>Reason:</strong> {{ $leave->reason }}</p>
<p><strong>From:</strong> {{ $leave->start_date->format('Y-m-d') }} <strong>To:</strong>
    {{ $leave->end_date->format('Y-m-d') }}</p>
<p>Enjoy your time off!</p>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Demande de congé – Validation requise</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: auto; border: 1px solid #ddd; padding: 20px; border-radius: 6px; }
        .header { background: #f5f5f5; padding: 10px; margin-bottom: 20px; border-radius: 4px; }
        .footer { margin-top: 30px; font-size: 12px; color: #777; }
    </style>
</head>
<body>
<div class="container">

    <div class="header">
        <h3>Alerte – Demande de congé en attente (Niveau 1)</h3>
    </div>

    <p>Bonjour {{ $manager->name ?? 'Manager' }},</p>

    <p>
        Une nouvelle demande de congé a été soumise et nécessite votre validation.
    </p>

    <ul>
        <li><strong>Employé :</strong> {{ $employee->user->name }}</li>
        <li><strong>Matricule :</strong> {{ $employee->matricule }}</li>
        <li><strong>Poste / Service :</strong> {{ $employee->designation->name }} – {{ $employee->department->name }}</li>
        <li><strong>Type de congé :</strong> {{ $leave->type }}</li>
        <li><strong>Période :</strong> du {{ $leave->start_date->format('d/m/Y') }} au {{ $leave->end_date->format('d/m/Y') }}</li>
        <li><strong>Nombre de jours :</strong> {{ $leave->days }}</li>
    </ul>

    <p>Merci de confirmer votre décision.</p>

    <p><strong>{{ config('app.name') }}</strong></p>

    <div class="footer">
        <p>Email automatique – merci de ne pas répondre.</p>
    </div>

</div>
</body>
</html>