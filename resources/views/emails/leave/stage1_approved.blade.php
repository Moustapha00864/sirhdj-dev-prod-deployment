<h1>Leave Request Approved (Stage 1)</h1>
<p>Hello,</p>
<p>The leave request for <strong>{{ $leave->employee->name }}</strong> has been approved by the Department Manager.</p>
<p>It is now pending HR approval (Stage 2).</p>
<p><strong>Reason:</strong> {{ $leave->reason }}</p>
<p><strong>From:</strong> {{ $leave->start_date->format('Y-m-d') }} <strong>To:</strong>
    {{ $leave->end_date->format('Y-m-d') }}</p>