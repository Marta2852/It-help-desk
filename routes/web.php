<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Enums\TicketStatus;
use App\Models\Ticket;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user->role === 'it') {
        $totalTickets = Ticket::count();
        $openTickets = Ticket::where('status', TicketStatus::OPEN->value)->count();
        $assignedToMe = Ticket::where('assigned_to', $user->id)->count();
        $closedTickets = Ticket::where('status', TicketStatus::CLOSED->value)->count();
        $recentTickets = Ticket::latest()->take(3)->get();
    } else {
        $totalTickets = Ticket::where('user_id', $user->id)->count();
        $openTickets = Ticket::where('user_id', $user->id)->where('status', TicketStatus::OPEN->value)->count();
        $assignedToMe = 0;
        $closedTickets = Ticket::where('user_id', $user->id)->where('status', TicketStatus::CLOSED->value)->count();
        $recentTickets = Ticket::where('user_id', $user->id)->latest()->take(3)->get();
    }

    return view('dashboard', compact('totalTickets', 'openTickets', 'assignedToMe', 'closedTickets', 'recentTickets'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markRead'])->name('notifications.markRead');

    Route::get('/tickets', [TicketController::class,'index'])->name('tickets.index');
    Route::get('/tickets/monthly', [TicketController::class, 'monthly'])->name('tickets.monthly');
    Route::get('/tickets/monthly/export/pdf', [TicketController::class, 'exportMonthlyPdf'])->name('tickets.monthly.export.pdf');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class,'show'])->name('tickets.show');
    Route::get('/tickets/{ticket}/edit', [TicketController::class,'edit'])->name('tickets.edit');
    Route::put('/tickets/{ticket}', [TicketController::class,'update'])->name('tickets.update');
    Route::delete('/tickets/{ticket}', [TicketController::class,'destroy'])->name('tickets.destroy');
    Route::post('/tickets/{ticket}/comment', [TicketController::class,'addComment'])->name('tickets.addComment');
    Route::post('/tickets/{ticket}/claim', [TicketController::class,'claim'])->name('tickets.claim');
    Route::post('/tickets/{ticket}/complete', [TicketController::class,'complete'])->name('tickets.complete');
    Route::post('/tickets/{ticket}/reopen', [TicketController::class, 'reopen'])->name('tickets.reopen');
    Route::post('/tickets/{ticket}/unassign', [TicketController::class, 'unassign'])->name('tickets.unassign');
    Route::post('/tickets/{ticket}/transfer', [TicketController::class, 'transfer'])->name('tickets.transfer');
    Route::get('/tickets/{ticket}/attachments/{attachment}', [TicketController::class, 'showAttachment'])->name('tickets.attachments.show');
    Route::delete('/tickets/{ticket}/attachments/{attachment}', [TicketController::class, 'destroyAttachment'])->name('tickets.attachments.destroy');

});

require __DIR__.'/auth.php';
