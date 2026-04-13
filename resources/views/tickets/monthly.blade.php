@extends('layouts.app')

@section('content')
<style>
    .monthly-page {
        width: 100%;
        margin: 0;
        padding: 0;
    }

    .monthly-shell {
        margin-left: 1rem;
        margin-right: 1rem;
    }

    @media (min-width: 768px) {
        .monthly-shell {
            margin-left: 1.75rem;
            margin-right: 1.75rem;
        }
    }

    @media (min-width: 1280px) {
        .monthly-shell {
            margin-left: 3rem;
            margin-right: 3rem;
        }
    }

    .monthly-shell {
        background: linear-gradient(160deg, #4a2b31 0%, #3a2328 45%, #2d1b1f 100%);
        border: 1px solid rgba(224, 212, 205, 0.22);
        border-radius: 14px;
        color: #f1ece8;
        box-shadow: 0 12px 28px rgba(28, 17, 20, 0.4);
        padding: 1.6rem 1.5rem 1.75rem;
    }

    @media (min-width: 1024px) {
        .monthly-shell {
            padding: 2.1rem 2.25rem 2.2rem;
        }
    }

    .monthly-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1.4rem;
        flex-wrap: wrap;
    }

    .monthly-title-block {
        min-width: 260px;
    }

    .monthly-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .monthly-btn {
        border: 1px solid rgba(224, 212, 205, 0.4);
        border-radius: 999px;
        padding: 0.65rem 1.2rem;
        font-size: 0.82rem;
        font-weight: 700;
        color: #f1ece8;
        background: rgba(58, 35, 40, 0.55);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
    }

    .monthly-btn:hover {
        background: rgba(147, 80, 63, 0.35);
    }

    .monthly-btn-export {
        background: #6f3f2d;
        border-color: #6f3f2d;
        color: #f8f2ee;
    }

    .monthly-btn-export:hover {
        background: #5a3223;
    }

    .monthly-filter-grid {
        margin-top: 1.55rem;
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    @media (min-width: 980px) {
        .monthly-filter-grid {
            grid-template-columns: 2fr 1fr 1fr auto;
            align-items: end;
        }
    }

    .monthly-field-label {
        margin-bottom: 0.3rem;
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(241, 236, 232, 0.66);
    }

    .monthly-field-control {
        width: 100%;
        border-radius: 10px;
        border: 1px solid rgba(224, 212, 205, 0.24);
        background: #f4f1ee;
        color: #2f1d22;
        padding: 0.95rem 1rem;
        font-size: 0.95rem;
    }

    .monthly-field-control::placeholder {
        color: #8f8490;
    }

    .monthly-field-control:focus {
        outline: none;
        border-color: #8f4f3b;
        box-shadow: 0 0 0 2px rgba(143, 79, 59, 0.2);
    }

    .monthly-actions {
        display: flex;
        gap: 0.5rem;
        align-items: end;
    }

    .monthly-apply,
    .monthly-clear {
        border-radius: 10px;
        min-height: 44px;
        padding: 0.7rem 1rem;
        font-size: 1.05rem;
        font-weight: 700;
    }

    .monthly-apply {
        border: 1px solid #8f4f3b;
        background: #8f4f3b;
        color: #f8f2ee;
        min-width: 160px;
    }

    .monthly-apply:hover {
        background: #754131;
    }

    .monthly-clear {
        border: 1px solid rgba(224, 212, 205, 0.55);
        background: transparent;
        color: #f1ece8;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 88px;
    }

    .monthly-clear:hover {
        background: rgba(147, 80, 63, 0.3);
    }

    .monthly-calendar-wrap {
        border: 1px solid rgba(224, 212, 205, 0.18);
        border-radius: 14px;
        background: linear-gradient(180deg, #4a2b31 0%, #2f1d22 100%);
        box-shadow: 0 16px 36px rgba(20, 12, 14, 0.45);
        overflow-x: auto;
    }

    .monthly-weekdays,
    .monthly-grid {
        min-width: 980px;
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
    }

    .monthly-weekdays > div {
        border-right: 1px solid rgba(224, 212, 205, 0.16);
        border-bottom: 1px solid rgba(224, 212, 205, 0.16);
        padding: 0.6rem;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(241, 236, 232, 0.72);
    }

    .monthly-weekdays > div:last-child {
        border-right: 0;
    }

    .monthly-day {
        min-height: 140px;
        padding: 0.55rem;
        border-right: 1px solid rgba(224, 212, 205, 0.12);
        border-bottom: 1px solid rgba(224, 212, 205, 0.12);
        background: rgba(255, 255, 255, 0.01);
    }

    .monthly-day:nth-child(7n) {
        border-right: 0;
    }

    .monthly-day-muted {
        background: rgba(0, 0, 0, 0.18);
        color: rgba(241, 236, 232, 0.45);
    }

    .monthly-day-number {
        margin-bottom: 0.5rem;
        font-size: 0.76rem;
        font-weight: 700;
        color: rgba(241, 236, 232, 0.92);
    }

    .monthly-chip {
        display: block;
        border-radius: 8px;
        border: 1px solid rgba(224, 212, 205, 0.28);
        padding: 0.35rem 0.45rem;
        text-decoration: none;
        color: #f1ece8;
        font-size: 0.68rem;
        line-height: 1.2;
        margin-bottom: 0.35rem;
    }

    .monthly-chip:hover {
        border-color: rgba(241, 236, 232, 0.6);
    }

    .monthly-chip-high { background: rgba(122, 47, 55, 0.42); border-color: rgba(204, 128, 134, 0.55); }
    .monthly-chip-medium { background: rgba(114, 82, 38, 0.38); border-color: rgba(208, 174, 121, 0.55); }
    .monthly-chip-low { background: rgba(75, 90, 74, 0.4); border-color: rgba(157, 183, 151, 0.52); }
</style>

<div class="monthly-page space-y-6">
    <div class="monthly-shell">
        <div class="monthly-header">
            <div class="monthly-title-block">
                <p class="text-xs uppercase tracking-[0.2em] text-soft-dove/60">Admin</p>
                <h1 class="text-2xl font-bold">Month Ticket View</h1>
                <p class="mt-1 text-sm text-soft-dove/70">
                    {{ $monthStart->format('F Y') }}: {{ $ticketCount }} ticket{{ $ticketCount === 1 ? '' : 's' }} in the current calendar grid.
                </p>
            </div>
            <div class="monthly-toolbar">
                <a href="{{ route('tickets.monthly', array_filter(['month' => $prevMonth, 'search' => $search, 'status' => $status, 'priority' => $priority])) }}" class="monthly-btn">Prev</a>
                <a href="{{ route('tickets.monthly') }}" class="monthly-btn">Today</a>
                <a href="{{ route('tickets.monthly', array_filter(['month' => $nextMonth, 'search' => $search, 'status' => $status, 'priority' => $priority])) }}" class="monthly-btn">Next</a>
                <a href="{{ route('tickets.monthly.export.pdf', array_filter(['month' => $month, 'search' => $search, 'status' => $status, 'priority' => $priority])) }}" class="monthly-btn monthly-btn-export">Export PDF</a>
            </div>
        </div>

        <form method="GET" action="{{ route('tickets.monthly') }}" class="monthly-filter-grid">
            <input type="hidden" name="month" value="{{ $month }}">
            <div>
                <label for="search" class="monthly-field-label">Search</label>
                <input id="search" type="text" name="search" value="{{ $search ?? '' }}" placeholder="Ticket ID, title, user..." class="monthly-field-control">
            </div>
            <div>
                <label for="status" class="monthly-field-label">Status</label>
                <select id="status" name="status" class="monthly-field-control">
                    <option value="">All status</option>
                    @foreach($status_options as $value => $label)
                        <option value="{{ $value }}" {{ ($status ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="priority" class="monthly-field-label">Priority</label>
                <select id="priority" name="priority" class="monthly-field-control">
                    <option value="">All priority</option>
                    @foreach($priority_options as $value => $label)
                        <option value="{{ $value }}" {{ ($priority ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="monthly-actions">
                <button type="submit" class="monthly-apply">Apply</button>
                <a href="{{ route('tickets.monthly', ['month' => $month]) }}" class="monthly-clear">Clear</a>
            </div>
        </form>
    </div>

    <div class="monthly-calendar-wrap text-soft-dove">
        <div class="monthly-weekdays">
            @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                <div>{{ $dayName }}</div>
            @endforeach
        </div>

        <div class="monthly-grid">
            @foreach($days as $day)
                <div class="monthly-day {{ $day['in_month'] ? '' : 'monthly-day-muted' }}">
                    <div class="monthly-day-number">{{ $day['date']->day }}</div>
                    <div>
                        @foreach($day['tickets'] as $ticket)
                            <a href="{{ route('tickets.show', $ticket) }}" class="monthly-chip @if($ticket->priority === 'High') monthly-chip-high @elseif($ticket->priority === 'Medium') monthly-chip-medium @else monthly-chip-low @endif">
                                <div class="font-semibold">TKT-{{ str_pad((string) $ticket->id, 3, '0', STR_PAD_LEFT) }}</div>
                                <div class="truncate">{{ $ticket->title }}</div>
                                <div class="mt-1 flex items-center justify-between gap-1 text-[10px] text-soft-dove/75">
                                    <span>{{ $ticket->user?->name ?? 'Unknown' }}</span>
                                    <span class="rounded-full bg-soft-dove/15 px-2 py-[1px]">{{ ucfirst($ticket->status) }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
