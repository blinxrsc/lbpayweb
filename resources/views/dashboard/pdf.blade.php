<html>
<head>
    <style>
        body { font-family: sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        .chart-box { width: 100%; text-align: center; margin-bottom: 20px; }
        img { width: 400px; height: auto; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Outlet Statistics Report</h1>
        <p>Period: {{ $start ?? 'All Time' }} to {{ $end ?? 'Present' }}</p>
    </div>

    <div class="chart-box">
        <h3>Outlet Status Distribution</h3>
        <img src="{{ $statusChart }}">
    </div>

    <div class="chart-box">
        <h3>Outlet Type Distribution</h3>
        <img src="{{ $typeChart }}">
    </div>
</body>
</html>