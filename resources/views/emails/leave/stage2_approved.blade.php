<h1>Leave Request Approved (Final)</h1>
<p>Hello {{ $leave->employee->name }},</p>
<p>Your leave request has been fully approved.</p>
<p><strong>Reason:</strong> {{ $leave->reason }}</p>
<p><strong>From:</strong> {{ $leave->start_date->format('Y-m-d') }} <strong>To:</strong>
    {{ $leave->end_date->format('Y-m-d') }}</p>
<p>Enjoy your time off!</p>