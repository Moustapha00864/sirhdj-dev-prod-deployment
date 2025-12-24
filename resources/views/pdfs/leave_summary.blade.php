<!DOCTYPE html>
<html>

<head>
    <title>Leave Summary</title>
    <style>
        body {
            font-family: sans-serif;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .content {
            margin-bottom: 20px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Leave Request Summary</h1>
        <p>Reference: #{{ $leave->id }}</p>
    </div>

    <div class="content">
        <h3>Employee Details</h3>
        <p>Name: {{ $leave->employee->name }}</p>
        <p>Department: {{ $leave->employee->employee->department->name ?? 'N/A' }}</p>

        <h3>Leave Details</h3>
        <p>Type: {{ $leave->leaveType->name }}</p>
        <p>Period: {{ $leave->start_date->format('Y-m-d') }} to {{ $leave->end_date->format('Y-m-d') }}</p>
        <p>Total Days: {{ $leave->total_days }}</p>
        <p>Status: {{ strtoupper($leave->status) }}</p>

        <h3>Approval History</h3>
        <table>
            <thead>
                <tr>
                    <th>Stage</th>
                    <th>Approver</th>
                    <th>Status</th>
                    <th>Comments</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leave->approvals as $approval)
                    <tr>
                        <td>Stage {{ $approval->stage }}</td>
                        <td>{{ $approval->approver->name }}</td>
                        <td>{{ ucfirst($approval->status) }}</td>
                        <td>{{ $approval->comments }}</td>
                        <td>{{ $approval->actioned_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Generated on {{ now()->format('Y-m-d H:i') }} | Medistaff HR System
    </div>
</body>

</html>