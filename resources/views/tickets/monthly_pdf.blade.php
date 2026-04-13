<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Ticket View {{ $monthStart->format('F Y') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 18px; font-family: DejaVu Sans, Arial, sans-serif; color: #172033; }
        h1 { margin: 0 0 4px 0; font-size: 20px; }
        .meta { margin: 0 0 14px 0; font-size: 11px; color: #3f4d65; }
        .filters { margin-bottom: 14px; padding: 8px 10px; border: 1px solid #c8d2e6; background: #f6f9ff; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #c8d2e6; vertical-align: top; }
        th { height: 24px; background: #13233f; color: #ffffff; font-size: 10px; letter-spacing: 0.12em; text-transform: uppercase; }
        td { height: 110px; padding: 5px; font-size: 10px; }
        .muted-day { background: #f2f5fb; color: #8b96aa; }
        .day { font-weight: 700; margin-bottom: 4px; }
        .chip { margin-bottom: 4px; padding: 3px 4px; border-radius: 4px; border: 1px solid #a8b5cf; background: #eef3ff; }
        .chip-high { border-color: #c95c5c; background: #ffecec; }
        .chip-medium { border-color: #d39e37; background: #fff8e7; }
        .chip-low { border-color: #4ca175; background: #e9f8ef; }
        .chip-id { font-weight: 700; }
        .chip-title { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chip-meta { color: #49556e; font-size: 9px; margin-top: 2px; }
    </style>
</head>
<body>
    <h1>IT Monthly Ticket Calendar</h1>
    <p class="meta">{{ $monthStart->format('F Y') }} | Tickets in grid: {{ $ticketCount }} | Generated: {{ now()->format('Y-m-d H:i') }}</p>

    <div class="filters">
        Search: {{ $search ?: 'All' }}
        | Status: {{ $status ?: 'All' }}
        | Priority: {{ $priority ?: 'All' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Mon</th>
                <th>Tue</th>
                <th>Wed</th>
                <th>Thu</th>
                <th>Fri</th>
                <th>Sat</th>
                <th>Sun</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_chunk($days, 7) as $week)
                <tr>
                    @foreach($week as $day)
                        <td class="{{ $day['in_month'] ? '' : 'muted-day' }}">
                            <div class="day">{{ $day['date']->day }}</div>
                            @foreach($day['tickets'] as $ticket)
                                <div class="chip {{ strtolower($ticket->priority) === 'high' ? 'chip-high' : (strtolower($ticket->priority) === 'medium' ? 'chip-medium' : 'chip-low') }}">
                                    <div class="chip-id">TKT-{{ str_pad((string) $ticket->id, 3, '0', STR_PAD_LEFT) }}</div>
                                    <div class="chip-title">{{ $ticket->title }}</div>
                                    <div class="chip-meta">{{ $ticket->status }} | {{ $ticket->user?->name ?? 'Unknown' }}</div>
                                </div>
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
