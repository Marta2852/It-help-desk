<!-- Navbar -->
<nav class="bg-dark-sienna text-soft-dove px-6 py-4 flex items-center shadow">

    <!-- Logo -->
    <div class="font-bold text-xl whitespace-nowrap">
        IT Help Desk
    </div>

    <!-- Links -->
    <div class="flex items-center gap-6 ml-10">
        <a href="/dashboard" class="hover:text-moon-rock">Dashboard</a>
        <a href="/tickets?view=my" class="hover:text-moon-rock">My Tickets</a>
        <a href="{{ route('notifications.index') }}" class="hover:text-moon-rock">
            Notifications
            @if(auth()->user()->unreadNotifications()->count() > 0)
                <span class="ml-1 inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-green-700 text-soft-dove text-xs font-bold">{{ auth()->user()->unreadNotifications()->count() }}</span>
            @endif
        </a>
        @if(auth()->user()->role === 'it')
            <a href="/tickets?view=all" class="hover:text-moon-rock">All Tickets</a>
            <a href="{{ route('tickets.monthly') }}" class="hover:text-moon-rock">Monthly View</a>
        @else
            <a href="/tickets/create" class="hover:text-moon-rock">Create Ticket</a>
        @endif
    </div>

    <!-- Logout pushed to the far right -->
    <div class="ml-auto">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="hover:text-moon-rock">
                Logout
            </button>
        </form>
    </div>

</nav>
