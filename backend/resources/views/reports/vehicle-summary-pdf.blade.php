<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Vehicle Count Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .meta { color: #4b5563; margin-bottom: 18px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        .empty { padding: 18px; border: 1px solid #d1d5db; color: #6b7280; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Vehicle Count Report</h1>
    <div class="meta">
        Period:
        {{ $startDate ?: 'Beginning' }}
        to
        {{ $endDate ?: 'Latest' }}
    </div>

    @if ($summaries->isEmpty())
        <div class="empty">No data available</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Location</th>
                    <th class="right">Total Vehicle</th>
                    <th>Generated At</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summaries as $summary)
                    <tr>
                        <td>{{ $summary->summary_date?->toDateString() }}</td>
                        <td>{{ $summary->location?->location_name }}</td>
                        <td class="right">{{ $summary->total_vehicle }}</td>
                        <td>{{ $summary->generated_at?->toDateTimeString() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
