@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold">Notifications</h2>
        <p class="text-soft-dove/70 text-sm">Recent ticket activity for your account.</p>
    </div>
    <form method="POST" action="{{ route('notifications.markAllRead') }}">
        @csrf
        <button type="submit" class="px-4 py-2 rounded bg-dark-sienna hover:bg-black-raspberry text-soft-dove text-sm font-semibold">Mark all as read</button>
    </form>
</div>

@if($notifications->isEmpty())
    <div class="bg-moon-rock text-soft-dove p-6 rounded shadow border border-soft-dove/20">
        No notifications yet.
    </div>
@else
    <div class="space-y-3">
        @foreach($notifications as $notification)
            @php
                $data = $notification->data;
                $isUnread = is_null($notification->read_at);
            @endphp
            <article class="bg-moon-rock text-soft-dove p-4 rounded shadow border {{ $isUnread ? 'border-spiced-hot-chocolate' : 'border-soft-dove/20' }}">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="font-semibold">{{ $data['message'] ?? 'Ticket activity update' }}</p>
                        <p class="text-sm text-soft-dove/70 mt-1">Ticket #{{ $data['ticket_id'] ?? '?' }}: {{ $data['ticket_title'] ?? 'Untitled ticket' }}</p>
                        <p class="text-xs text-soft-dove/60 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if(!empty($data['url']))
                            <a href="{{ $data['url'] }}" class="px-3 py-1 rounded bg-green-700 hover:bg-green-800 text-soft-dove text-xs">Open Ticket</a>
                        @endif
                        @if($isUnread)
                            <form method="POST" action="{{ route('notifications.markRead', $notification->id) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1 rounded bg-dark-sienna hover:bg-black-raspberry text-soft-dove text-xs">Mark Read</button>
                            </form>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
@endif
@endsection
