<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_creation_with_multiple_attachments_stores_all_files(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'user']);

        $response = $this
            ->actingAs($user)
            ->post('/tickets', [
                'full_name' => 'Alex Example',
                'class_department' => 'ICT',
                'category' => 'Software',
                'priority' => 'Medium',
                'title' => 'Cannot open app',
                'description' => 'The app fails to start.',
                'attachments' => [
                    UploadedFile::fake()->image('screenshot.png'),
                    UploadedFile::fake()->create('error-log.pdf', 100, 'application/pdf'),
                ],
            ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseCount('tickets', 1);

        $ticket = Ticket::query()->firstOrFail();
        $this->assertCount(2, $ticket->attachments);

        foreach ($ticket->attachments as $attachment) {
            Storage::disk('public')->assertExists($attachment->file_path);
        }
    }

    public function test_it_lifecycle_actions_update_ticket_and_send_notifications(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $itOne = User::factory()->create(['role' => 'it']);
        $itTwo = User::factory()->create(['role' => 'it']);
        $ticket = $this->makeTicket($owner);

        $this->actingAs($itOne)->post('/tickets/'.$ticket->id.'/claim')->assertRedirect('/tickets/'.$ticket->id);
        $ticket->refresh();
        $this->assertSame($itOne->id, $ticket->assigned_to);
        $this->assertSame('assigned', $ticket->status);

        $owner->refresh();
        $this->assertTrue($owner->notifications->contains(fn ($n) => ($n->data['event'] ?? null) === 'claimed'));

        $this->actingAs($itOne)->post('/tickets/'.$ticket->id.'/complete')->assertRedirect('/dashboard');
        $ticket->refresh();
        $this->assertSame('closed', $ticket->status);

        $owner->refresh();
        $this->assertFalse($owner->notifications->contains(fn ($n) => ($n->data['ticket_id'] ?? null) === $ticket->id));

        $this->actingAs($itOne)->post('/tickets/'.$ticket->id.'/reopen')->assertRedirect('/tickets/'.$ticket->id);
        $ticket->refresh();
        $this->assertSame('assigned', $ticket->status);

        $owner->refresh();
        $this->assertTrue($owner->notifications->contains(fn ($n) => ($n->data['event'] ?? null) === 'reopened'));

        $this->actingAs($itOne)->post('/tickets/'.$ticket->id.'/transfer', [
            'transfer_to' => $itTwo->id,
        ])->assertRedirect('/tickets/'.$ticket->id);

        $ticket->refresh();
        $this->assertSame($itTwo->id, $ticket->assigned_to);
        $this->assertSame('assigned', $ticket->status);

        $owner->refresh();
        $itTwo->refresh();
        $this->assertTrue($owner->notifications->contains(fn ($n) => ($n->data['event'] ?? null) === 'transferred'));
        $this->assertTrue($itTwo->notifications->contains(fn ($n) => ($n->data['event'] ?? null) === 'transferred'));

        $this->actingAs($itTwo)->post('/tickets/'.$ticket->id.'/unassign')->assertRedirect('/tickets/'.$ticket->id);
        $ticket->refresh();
        $this->assertNull($ticket->assigned_to);
        $this->assertSame('open', $ticket->status);
    }

    public function test_deleting_ticket_removes_related_notifications(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $itUser = User::factory()->create(['role' => 'it']);
        $ticket = $this->makeTicket($owner);

        $this->actingAs($itUser)->post('/tickets/'.$ticket->id.'/claim')->assertRedirect('/tickets/'.$ticket->id);

        $owner->refresh();
        $this->assertTrue($owner->notifications->contains(fn ($n) => ($n->data['ticket_id'] ?? null) === $ticket->id));

        $this->actingAs($itUser)->delete('/tickets/'.$ticket->id)->assertRedirect('/tickets?view=all');

        $owner->refresh();
        $this->assertFalse($owner->notifications->contains(fn ($n) => ($n->data['ticket_id'] ?? null) === $ticket->id));
    }

    public function test_comment_sends_notification_to_ticket_owner(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $itUser = User::factory()->create(['role' => 'it']);
        $ticket = $this->makeTicket($owner, [
            'assigned_to' => $itUser->id,
            'status' => 'assigned',
        ]);

        $response = $this
            ->actingAs($itUser)
            ->post('/tickets/'.$ticket->id.'/comment', [
                'comment' => 'Working on this now.',
            ]);

        $response->assertRedirect('/tickets/'.$ticket->id);
        $this->assertDatabaseCount('comments', 1);

        $owner->refresh();
        $this->assertTrue($owner->notifications->contains(fn ($n) => ($n->data['event'] ?? null) === 'commented'));

        $comment = Comment::query()->firstOrFail();
        $this->assertSame('Working on this now.', $comment->comment);
    }

    public function test_non_owner_non_it_cannot_view_or_delete_ticket(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);
        $ticket = $this->makeTicket($owner);

        $this->actingAs($otherUser)->get('/tickets/'.$ticket->id)->assertForbidden();
        $this->actingAs($otherUser)->delete('/tickets/'.$ticket->id)->assertForbidden();
    }

    public function test_attachment_preview_access_is_limited_to_owner_or_it(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create(['role' => 'user']);
        $itUser = User::factory()->create(['role' => 'it']);
        $otherUser = User::factory()->create(['role' => 'user']);

        $ticket = $this->makeTicket($owner);
        Storage::disk('public')->put('tickets/sample.pdf', 'pdf bytes');

        $attachment = Attachment::query()->create([
            'ticket_id' => $ticket->id,
            'file_path' => 'tickets/sample.pdf',
        ]);

        $this->actingAs($owner)
            ->get('/tickets/'.$ticket->id.'/attachments/'.$attachment->id.'?preview=1')
            ->assertOk();

        $this->actingAs($itUser)
            ->get('/tickets/'.$ticket->id.'/attachments/'.$attachment->id.'?preview=1')
            ->assertOk();

        $this->actingAs($otherUser)
            ->get('/tickets/'.$ticket->id.'/attachments/'.$attachment->id.'?preview=1')
            ->assertForbidden();
    }

    public function test_ticket_creation_rejects_invalid_priority_value(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this
            ->actingAs($user)
            ->from('/tickets/create')
            ->post('/tickets', [
                'full_name' => 'Invalid Priority User',
                'class_department' => 'QA',
                'category' => 'Software',
                'priority' => 'Urgent',
                'title' => 'Invalid priority test',
                'description' => 'This should fail.',
            ]);

        $response->assertRedirect('/tickets/create');
        $response->assertSessionHasErrors('priority');
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_ticket_index_rejects_unknown_status_filter(): void
    {
        $user = User::factory()->create(['role' => 'it']);

        $response = $this
            ->actingAs($user)
            ->get('/tickets?status=paused');

        $response->assertSessionHasErrors('status');
    }

    public function test_it_can_access_monthly_ticket_view_and_export_pdf(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $itUser = User::factory()->create(['role' => 'it']);
        $ticket = $this->makeTicket($owner, [
            'title' => 'Printer outage',
            'created_at' => now()->startOfMonth()->addDays(2),
            'updated_at' => now()->startOfMonth()->addDays(2),
        ]);

        $this->actingAs($itUser)
            ->get('/tickets/monthly?month='.now()->format('Y-m'))
            ->assertOk()
            ->assertSee('Month Ticket View')
            ->assertSee('Printer outage')
            ->assertSee((string) $ticket->id);

        $this->actingAs($itUser)
            ->get('/tickets/monthly/export/pdf?month='.now()->format('Y-m'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_non_it_cannot_access_monthly_ticket_view_or_export(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get('/tickets/monthly')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/tickets/monthly/export/pdf')
            ->assertForbidden();
    }

    private function makeTicket(User $owner, array $overrides = []): Ticket
    {
        return Ticket::query()->create(array_merge([
            'user_id' => $owner->id,
            'assigned_to' => null,
            'full_name' => 'Jordan Owner',
            'class_department' => 'IT Department',
            'category' => 'Software',
            'priority' => 'Medium',
            'title' => 'Ticket title',
            'description' => 'Ticket description',
            'status' => 'open',
        ], $overrides));
    }
}
