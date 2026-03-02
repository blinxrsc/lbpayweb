<html>
<head>
    <style>
        body { font-family: sans-serif; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .stats-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .stats-table th, .stats-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .stats-table th { background-color: #f2f2f2; }
        .summary-box { background: #f9f9f9; padding: 15px; margin-top: 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Maintenance Audit Report</h1>
        <p>Period: {{ $stats['period'] }}</p>
    </div>

    <div class="summary-box">
        <p><strong>Total Faults Reported:</strong> {{ $stats['total_faulty'] }}</p>
        <p><strong>Total Repairs Completed:</strong> {{ $stats['total_repaired'] }}</p>
    </div>

    <h3>Faulty Incidents by Model</h3>
    <table class="stats-table">
        <thead>
            <tr>
                <th>Device Model</th>
                <th>Failure Count</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats['faults_by_model'] as $model => $count)
            <tr>
                <td>{{ $model }}</td>
                <td>{{ $count }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>