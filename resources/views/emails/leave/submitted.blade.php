<h1>New Leave Request</h1>
<p>Hello,</p>
<p>A new leave request has been submitted by <strong>{{ $leave->employee->name }}</strong>.</p>
<p><strong>Reason:</strong> {{ $leave->reason }}</p>
<p><strong>From:</strong> {{ $leave->start_date->format('Y-m-d') }} <strong>To:</strong>
    {{ $leave->end_date->format('Y-m-d') }}</p>
<p>Please review and take action.</p>