@extends('layouts.app')

@section('content')
@php $currentView = $view ?? request()->query('view', auth()->user()->role === 'it' ? 'submitted' : 'my'); @endphp
<div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold">{{ $heading ?? (auth()->user()->role === 'it' ? 'All Tickets' : 'My Tickets') }}</h2>
        <p class="text-sm text-soft-dove/70 mt-1">
            @if($search ?? false)
                Found {{ $tickets->total() }} ticket{{ $tickets->total() === 1 ? '' : 's' }} with name matching "{{ $search }}".
            @else
                Showing {{ $tickets->count() }} of {{ $tickets->total() }} ticket{{ $tickets->total() === 1 ? '' : 's' }}.
            @endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if(auth()->user()->role === 'it')
            <a href="/tickets?view=all" class="px-4 py-2 rounded font-semibold {{ $currentView === 'all' ? 'bg-dark-sienna text-soft-dove' : 'bg-moon-rock text-soft-dove/80' }}">All Tickets</a>
            <a href="/tickets?view=submitted" class="px-4 py-2 rounded font-semibold {{ $currentView === 'submitted' ? 'bg-dark-sienna text-soft-dove' : 'bg-moon-rock text-soft-dove/80' }}">My Submitted</a>
            <a href="/tickets?view=assigned" class="px-4 py-2 rounded font-semibold {{ $currentView === 'assigned' ? 'bg-dark-sienna text-soft-dove' : 'bg-moon-rock text-soft-dove/80' }}">Assigned to Me</a>
        @else
            <a href="/tickets?view=my" class="px-4 py-2 rounded bg-moon-rock text-soft-dove font-semibold">My Tickets</a>
        @endif
        <a href="/tickets/create" class="px-4 py-2 rounded bg-spiced-hot-chocolate text-soft-dove font-semibold hover:bg-moon-rock">New Ticket</a>
    </div>
</div>

<!-- Search and Filter Form -->
<form method="GET" action="/tickets" class="bg-moon-rock p-4 rounded-xl mb-6 border border-soft-dove/20">
    <div class="flex flex-col md:flex-row gap-4 items-end">
        <!-- Search by Name -->
        <div class="flex-1">
            <label for="search" class="block text-sm font-semibold text-soft-dove mb-2">Search by Name</label>
            <input type="text" name="search" id="search" value="{{ $search ?? '' }}" placeholder="Enter submitter name..." class="w-full px-3 py-2 rounded bg-soft-dove text-black border border-soft-dove/20 focus:border-spiced-hot-chocolate focus:outline-none">
        </div>

        <!-- Filter by Ticket ID -->
        <div class="md:w-40">
            <label for="ticket_id" class="block text-sm font-semibold text-soft-dove mb-2">Ticket ID</label>
            <input type="number" min="0" max="{{ $max_ticket_id ?? 1 }}" name="ticket_id" id="ticket_id" value="{{ $ticket_id ?? '' }}" placeholder="1-{{ $max_ticket_id ?? 1 }}" oninput="if (this.value !== '' && Number(this.value) <= 0) this.value = ''; if (this.max && this.value !== '' && Number(this.value) > Number(this.max)) this.value = this.max;" class="w-full px-3 py-2 rounded bg-soft-dove text-black border border-soft-dove/20 focus:border-spiced-hot-chocolate focus:outline-none">
        </div>

        <!-- Filter by Status -->
        <div class="md:w-48">
            <label for="status" class="block text-sm font-semibold text-soft-dove mb-2">Filter by Status</label>
            <select name="status" id="status" class="w-full px-3 py-2 rounded bg-soft-dove text-black border border-soft-dove/20 focus:border-spiced-hot-chocolate focus:outline-none">
                <option value="">All Statuses</option>
                <option value="open" {{ ($status ?? '') === 'open' ? 'selected' : '' }}>Open</option>
                <option value="in_progress" {{ ($status ?? '') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="closed" {{ ($status ?? '') === 'closed' ? 'selected' : '' }}>Closed</option>
            </select>
        </div>

        <!-- Filter by Priority -->
        <div class="md:w-48">
            <label for="priority" class="block text-sm font-semibold text-soft-dove mb-2">Filter by Priority</label>
            <select name="priority" id="priority" class="w-full px-3 py-2 rounded bg-soft-dove text-black border border-soft-dove/20 focus:border-spiced-hot-chocolate focus:outline-none">
                <option value="">All Priorities</option>
                <option value="Low" {{ ($priority ?? '') === 'Low' ? 'selected' : '' }}>Low</option>
                <option value="Medium" {{ ($priority ?? '') === 'Medium' ? 'selected' : '' }}>Medium</option>
                <option value="High" {{ ($priority ?? '') === 'High' ? 'selected' : '' }}>High</option>
            </select>
        </div>

        <!-- Hidden input for current view -->
        <input type="hidden" name="view" value="{{ $currentView }}">

        <!-- Buttons -->
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-dark-sienna text-soft-dove rounded hover:bg-black-raspberry font-semibold">Apply</button>
            <a href="/tickets?view={{ $currentView }}" class="px-4 py-2 bg-moon-rock text-soft-dove rounded hover:bg-spiced-hot-chocolate font-semibold">Clear</a>
        </div>
    </div>
</form>

@if($tickets->isEmpty())
    <div class="bg-moon-rock border border-soft-dove/20 rounded p-8 text-center">
        @if($search ?? false)
            <p class="text-xl font-semibold mb-2">No tickets found.</p>
            <p class="text-soft-dove/70 mb-4">Try adjusting your search terms or filters.</p>
            <a href="/tickets?view={{ $currentView }}" class="inline-block bg-dark-sienna px-5 py-3 rounded hover:bg-black-raspberry text-soft-dove font-semibold">Clear Search</a>
        @else
            <p class="text-xl font-semibold mb-2">No tickets yet.</p>
            <p class="text-soft-dove/70 mb-4">Create your first ticket or check back later for updates.</p>
            <a href="/tickets/create" class="inline-block bg-dark-sienna px-5 py-3 rounded hover:bg-black-raspberry text-soft-dove font-semibold">Create Ticket</a>
        @endif
    </div>
@else
    <div class="grid gap-4 xl:grid-cols-2">
        @foreach($tickets as $ticket)
            <article class="bg-moon-rock p-6 rounded-xl shadow-lg border border-soft-dove/20 hover:border-spiced-hot-chocolate transition">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <a href="/tickets/{{ $ticket->id }}" class="text-xl font-semibold hover:text-spiced-hot-chocolate">{{ $ticket->title }}</a>
                        <p class="mt-2 text-sm text-soft-dove/70">{{ Illuminate\Support\Str::limit($ticket->description, 100) }}</p>
                    </div>
                    <div class="flex flex-col gap-2 text-right">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold 
                            @if($ticket->status === 'open') bg-green-700
                            @elseif($ticket->status === 'closed') bg-red-700
                            @else bg-yellow-700 @endif text-soft-dove">
                            {{ ucfirst($ticket->status) }}
                        </span>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold 
                            @if($ticket->priority === 'Low') bg-green-700
                            @elseif($ticket->priority === 'Medium') bg-yellow-700
                            @else bg-red-700 @endif text-soft-dove">
                            {{ ucfirst($ticket->priority) }}
                        </span>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <p class="text-sm text-soft-dove/70"><span class="font-semibold text-soft-dove">Category:</span> {{ $ticket->category }}</p>
                    <p class="text-sm text-soft-dove/70"><span class="font-semibold text-soft-dove">Department:</span> {{ $ticket->class_department }}</p>
                    <p class="text-sm text-soft-dove/70"><span class="font-semibold text-soft-dove">Created:</span> {{ $ticket->created_at->format('M j, Y') }}</p>
                    @if(auth()->user()->role === 'it')
                        <p class="text-sm text-soft-dove/70"><span class="font-semibold text-soft-dove">Submitted by:</span> {{ $ticket->user?->name ?? 'Unknown' }}</p>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@endif

<!-- Pagination -->
@if($tickets->hasPages())
    <div class="mt-6 flex justify-center">
        {{ $tickets->appends(request()->query())->links() }}
    </div>
@endif
@endsection
