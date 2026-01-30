<!DOCTYPE html>
<html>

<head>
    <title>Activity Logs Export</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        h2 {
            margin-bottom: 5px;
        }

        .meta {
            font-size: 9pt;
            color: #666;
            margin-bottom: 20px;
        }

        .badge {
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8pt;
            color: #fff;
        }

        .bg-success {
            background-color: #28a745;
        }

        .bg-primary {
            background-color: #007bff;
        }

        .bg-info {
            background-color: #17a2b8;
        }

        .bg-secondary {
            background-color: #6c757d;
        }

        .bg-danger {
            background-color: #dc3545;
        }

        .bg-warning {
            background-color: #ffc107;
            color: #000;
        }
    </style>
</head>

<body>
    <h2>Activity Logs Report</h2>
    <div class="meta">
        Generated: {{ now()->toDateTimeString() }} | User: {{ auth()->user()->name }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Time</th>
                <th>User</th>
                <th>Module</th>
                <th>Action</th>
                <th>Description</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
                <tr>
                    <td style="white-space: nowrap;">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->user->name ?? 'System' }}</td>
                    <td>{{ $log->module }}</td>
                    <td>
                        @php
                            $color = match ($log->action) {
                                'Login' => 'success',
                                'Create' => 'primary',
                                'Update' => 'info',
                                'Delete', 'Error' => 'danger',
                                'Warning' => 'warning',
                                default => 'secondary'
                            };
                        @endphp
                        <span class="badge bg-{{ $color }}">{{ $log->action }}</span>
                    </td>
                    <td>{{ $log->description }}</td>
                    <td>{{ $log->ip_address }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>