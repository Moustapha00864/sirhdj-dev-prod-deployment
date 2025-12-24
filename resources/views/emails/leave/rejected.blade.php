<h1>Leave Request Rejected</h1>
<p>Hello {{ $leave->employee->name }},</p>
<p>Your leave request has been rejected.</p>
@if($rejectionComments)
    <p><strong>Reason for rejection:</strong> {{ $rejectionComments }}</p>
@endif
<p><strong>Original Request Reason:</strong> {{ $leave->reason }}</p>
<p><strong>Dates:</strong> {{ $leave->start_date->format('Y-m-d') }} - {{ $leave->end_date->format('Y-m-d') }}</p>