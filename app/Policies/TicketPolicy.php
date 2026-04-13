<?php

namespace App\Policies;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->role === 'it' || $ticket->user_id === $user->id;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->role === 'it' || $ticket->user_id === $user->id;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->role === 'it' || $ticket->user_id === $user->id;
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $user->role === 'it' || $ticket->user_id === $user->id;
    }

    public function claim(User $user, Ticket $ticket): bool
    {
        return $user->role === 'it' && !$ticket->assigned_to;
    }

    public function complete(User $user, Ticket $ticket): bool
    {
        return $user->role === 'it' && $ticket->assigned_to === $user->id;
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        return $user->role === 'it' && $ticket->status === TicketStatus::CLOSED->value;
    }

    public function unassign(User $user, Ticket $ticket): bool
    {
        return $user->role === 'it'
            && $ticket->assigned_to === $user->id
            && $ticket->status !== TicketStatus::CLOSED->value;
    }

    public function transfer(User $user, Ticket $ticket): bool
    {
        return $user->role === 'it'
            && $ticket->assigned_to === $user->id
            && $ticket->status !== TicketStatus::CLOSED->value;
    }

    public function viewAttachment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function deleteAttachment(User $user, Ticket $ticket): bool
    {
        return $this->update($user, $ticket);
    }
}
