<?php

namespace App\Http\Controllers;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\User;
use App\Notifications\TicketActivityNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Validation\Rule;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use Symfony\Component\Process\Process;
use Throwable;


class TicketController extends Controller
{
    public function create()
    {
        return view('tickets.create', [
            'priority_options' => TicketPriority::labels(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required',
            'class_department' => 'required',
            'category' => 'required',
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'title' => 'required',
            'description' => 'required',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'file|mimes:jpeg,png,jpg,gif,pdf,doc,docx,xls,xlsx,txt|max:10240',
        ]);

        $ticket = Ticket::create([
            'user_id' => auth()->id(),
            'full_name' => $request->full_name,
            'class_department' => $request->class_department,
            'category' => $request->category,
            'priority' => $request->priority,
            'title' => $request->title,
            'description' => $request->description,
        ]);

        if($request->hasFile('attachments')){
            foreach($request->file('attachments') as $attachment){
                $path = $this->storeAttachmentWithOriginalName($attachment);

                Attachment::create([
                    'ticket_id' => $ticket->id,
                    'file_path' => $path
                ]);
            }
        }

        $itRecipients = User::query()
            ->where('role', 'it')
            ->where('id', '!=', auth()->id())
            ->get();

        $this->notifyUsers(
            $itRecipients,
            $ticket,
            'created',
            auth()->user()->name.' created a new ticket.'
        );

        return redirect('/dashboard')->with('success','Ticket created!');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $view = $request->query('view');

        $tickets = Ticket::query();

        if ($user->role === 'it') {
            if ($view === 'assigned') {
                $tickets->where('assigned_to', $user->id);
                $heading = 'Assigned to Me';
            } elseif ($view === 'all') {
                $heading = 'All Tickets';
            } else {
                $tickets->where('user_id', $user->id);
                $heading = 'My Submitted Tickets';
                $view = 'submitted';
            }
        } else {
            $tickets->where('user_id', $user->id);
            $heading = 'My Tickets';
            $view = 'my';
        }

        $maxTicketId = (clone $tickets)->max('id');

        if ($request->search) {
            $tickets->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $ticketId = $request->filled('ticket_id') ? (int) $request->ticket_id : null;

        if ($ticketId !== null && $ticketId <= 0) {
            $ticketId = null;
        }

        if ($ticketId !== null && $maxTicketId !== null) {
            $ticketId = min($ticketId, (int) $maxTicketId);
            $tickets->where('id', $ticketId);
        }

        $validatedFilters = $request->validate([
            'status' => ['nullable', Rule::in(array_merge(TicketStatus::values(), ['in_progress']))],
            'priority' => ['nullable', Rule::enum(TicketPriority::class)],
        ]);

        $statusFilter = $validatedFilters['status'] ?? null;
        if ($statusFilter === 'in_progress') {
            $statusFilter = TicketStatus::ASSIGNED->value;
        }

        if ($statusFilter) {
            $tickets->where('status', $statusFilter);
        }

        $priorityFilter = $validatedFilters['priority'] ?? null;
        if ($priorityFilter) {
            $tickets->where('priority', $priorityFilter);
        }

        $tickets = $tickets->latest()->paginate(10);

        return view('tickets.index', [
            'tickets' => $tickets,
            'heading' => $heading,
            'view' => $view,
            'search' => $request->search,
            'ticket_id' => $ticketId,
            'max_ticket_id' => $maxTicketId,
            'status' => $statusFilter,
            'priority' => $priorityFilter,
            'currentView' => $request->view,
            'status_options' => TicketStatus::labels(),
            'priority_options' => TicketPriority::labels(),
        ]);
    }

    public function monthly(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'it') {
            abort(403);
        }

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'priority' => ['nullable', Rule::enum(TicketPriority::class)],
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');
        $search = $validated['search'] ?? null;
        $status = $validated['status'] ?? null;
        $priority = $validated['priority'] ?? null;

        $calendar = $this->buildMonthlyCalendarData($month, $search, $status, $priority);

        return view('tickets.monthly', [
            'month' => $month,
            'search' => $search,
            'status' => $status,
            'priority' => $priority,
            'status_options' => TicketStatus::labels(),
            'priority_options' => TicketPriority::labels(),
            ...$calendar,
        ]);
    }

    public function exportMonthlyPdf(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'it') {
            abort(403);
        }

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'priority' => ['nullable', Rule::enum(TicketPriority::class)],
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');
        $search = $validated['search'] ?? null;
        $status = $validated['status'] ?? null;
        $priority = $validated['priority'] ?? null;

        $calendar = $this->buildMonthlyCalendarData($month, $search, $status, $priority);

        $pdf = Pdf::loadView('tickets.monthly_pdf', [
            'month' => $month,
            'search' => $search,
            'status' => $status,
            'priority' => $priority,
            ...$calendar,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('tickets-monthly-'.$month.'.pdf');
    }

    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $itUsers = User::query()
            ->where('role', 'it')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tickets.show', compact('ticket', 'itUsers'));
    }

    public function claim(Ticket $ticket)
    {
        $this->authorize('claim', $ticket);

        if ($ticket->assigned_to) {
            return redirect('/tickets/'.$ticket->id)->with('error', 'Ticket is already claimed.');
        }

        $ticket->update([
            'assigned_to' => auth()->id(),
            'status' => TicketStatus::ASSIGNED->value,
        ]);

        $this->notifyUsers(
            User::query()->where('id', $ticket->user_id)->get(),
            $ticket,
            'claimed',
            auth()->user()->name.' claimed your ticket.'
        );

        return redirect('/tickets/'.$ticket->id)->with('success', 'Ticket claimed successfully.');
    }

    public function complete(Ticket $ticket)
    {
        $this->authorize('complete', $ticket);

        $ticket->update([
            'status' => TicketStatus::CLOSED->value,
        ]);

        $this->purgeTicketNotifications($ticket);

        return redirect('/dashboard')->with('completion_success', 'Ticket completed successfully.');
    }

    public function reopen(Ticket $ticket)
    {
        $this->authorize('reopen', $ticket);

        $ticket->update([
            'status' => TicketStatus::ASSIGNED->value,
        ]);

        $this->notifyUsers(
            User::query()->where('id', $ticket->user_id)->get(),
            $ticket,
            'reopened',
            auth()->user()->name.' reopened your ticket.'
        );

        return redirect('/tickets/'.$ticket->id)->with('success', 'Ticket reopened successfully.');
    }

    public function unassign(Ticket $ticket)
    {
        $this->authorize('unassign', $ticket);

        $ticket->update([
            'assigned_to' => null,
            'status' => TicketStatus::OPEN->value,
        ]);

        return redirect('/tickets/'.$ticket->id)->with('success', 'Ticket unassigned and returned to open queue.');
    }

    public function transfer(Request $request, Ticket $ticket)
    {
        $this->authorize('transfer', $ticket);

        $validated = $request->validate([
            'transfer_to' => 'required|integer|exists:users,id',
        ]);

        $targetUser = User::query()
            ->where('id', $validated['transfer_to'])
            ->where('role', 'it')
            ->first();

        if (!$targetUser) {
            return redirect('/tickets/'.$ticket->id)->with('error', 'Transfer target must be an IT user.');
        }

        if ((int) $targetUser->id === (int) auth()->id()) {
            return redirect('/tickets/'.$ticket->id)->with('error', 'Ticket is already assigned to you.');
        }

        $ticket->update([
            'assigned_to' => $targetUser->id,
            'status' => TicketStatus::ASSIGNED->value,
        ]);

        $transferRecipients = User::query()
            ->whereIn('id', [$ticket->user_id, $targetUser->id])
            ->where('id', '!=', auth()->id())
            ->get();

        $this->notifyUsers(
            $transferRecipients,
            $ticket,
            'transferred',
            auth()->user()->name.' transferred this ticket to '.$targetUser->name.'.'
        );

        return redirect('/tickets/'.$ticket->id)->with('success', 'Ticket transferred to '.$targetUser->name.'.');
    }

    public function edit(Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        return view('tickets.edit', [
            'ticket' => $ticket,
            'priority_options' => TicketPriority::labels(),
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'full_name' => 'required',
            'class_department' => 'required',
            'category' => 'required',
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'title' => 'required',
            'description' => 'required',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'file|mimes:jpeg,png,jpg,gif,pdf,doc,docx,xls,xlsx,txt|max:10240',
        ]);

        $ticket->update($request->only([
            'full_name',
            'class_department',
            'category',
            'priority',
            'title',
            'description'
        ]));

        if($request->hasFile('attachments')){
            foreach($request->file('attachments') as $attachment){
                $path = $this->storeAttachmentWithOriginalName($attachment);

                Attachment::create([
                    'ticket_id' => $ticket->id,
                    'file_path' => $path
                ]);
            }
        }

        return redirect('/tickets/'.$ticket->id)
                ->with('success','Ticket updated!');
    }

    public function addComment(Request $request, Ticket $ticket)
    {
        $this->authorize('comment', $ticket);

        $request->validate([
            'comment' => 'required'
        ]);

        Comment::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'comment' => $request->comment
        ]);

        $commentRecipients = User::query()
            ->whereIn('id', array_values(array_filter([
                (int) $ticket->user_id,
                (int) ($ticket->assigned_to ?? 0),
            ])))
            ->where('id', '!=', auth()->id())
            ->get();

        $this->notifyUsers(
            $commentRecipients,
            $ticket,
            'commented',
            auth()->user()->name.' added a comment on this ticket.'
        );

        return redirect('/tickets/'.$ticket->id);
    }

    public function destroy(Ticket $ticket)
    {
    $this->authorize('delete', $ticket);

    $this->purgeTicketNotifications($ticket);

    $ticket->delete();

    return redirect('/tickets?view=all')
            ->with('success','Ticket deleted!');
}

    public function showAttachment(Request $request, Ticket $ticket, Attachment $attachment)
    {
        if ($attachment->ticket_id !== $ticket->id) {
            abort(404);
        }

        $this->authorize('viewAttachment', $ticket);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'Attachment file not found on server.');
        }

        $fullPath = Storage::disk('public')->path($attachment->file_path);
        $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
        $downloadName = basename($attachment->file_path);
        $extension = strtolower(pathinfo($downloadName, PATHINFO_EXTENSION));

        if ($request->boolean('download')) {
            return response()->download($fullPath, $downloadName, ['Content-Type' => $mime]);
        }

        if ($request->boolean('preview_html') && $extension === 'docx') {
            try {
                $directory = pathinfo($attachment->file_path, PATHINFO_DIRNAME);
                $baseName = pathinfo($attachment->file_path, PATHINFO_FILENAME);
                $pdfPath = ($directory === '.' ? '' : $directory.'/').$baseName.'.pdf';

                // If a matching PDF exists, use it for the most accurate visual preview.
                if (!$request->boolean('force_html') && Storage::disk('public')->exists($pdfPath)) {
                    $pdfFullPath = Storage::disk('public')->path($pdfPath);

                    return response()->file($pdfFullPath, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="'.$baseName.'.pdf"',
                    ]);
                }

                $tempDir = storage_path('app/tmp/phpword');
                if (!is_dir($tempDir)) {
                    mkdir($tempDir, 0775, true);
                }

                Settings::setTempDir($tempDir);

                $phpWord = IOFactory::load($fullPath, 'Word2007');
                $writer = IOFactory::createWriter($phpWord, 'HTML');

                ob_start();
                $writer->save('php://output');
                $htmlContent = ob_get_clean() ?: '';

                $html = '<!doctype html><html><head><meta charset="utf-8"><title>'.e($downloadName).'</title><style>body{margin:0;padding:16px;background:#f7f7f7;} .docx-wrap{max-width:960px;margin:0 auto;background:#fff;padding:24px;box-shadow:0 8px 24px rgba(0,0,0,.12);} .docx-wrap, .docx-wrap *{color:#faa396 !important;} img{max-width:100%;height:auto;} table{max-width:100%;}</style></head><body><div class="docx-wrap">'.$htmlContent.'</div></body></html>';

                return response($html, 200, [
                    'Content-Type' => 'text/html; charset=UTF-8',
                    'Content-Disposition' => 'inline; filename="'.pathinfo($downloadName, PATHINFO_FILENAME).'.html"',
                ]);
            } catch (Throwable $e) {
                $errorHtml = '<!doctype html><html><head><meta charset="utf-8"><title>DOCX Preview Error</title><style>body{margin:0;padding:16px;background:#f7f7f7;font-family:Arial,sans-serif;} .docx-wrap{max-width:720px;margin:0 auto;background:#fff;padding:24px;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);} .title{font-size:20px;font-weight:700;margin:0 0 8px;} .muted{color:#555;margin:0 0 16px;} .details{margin-top:8px;padding:10px;background:#f3f3f3;border-radius:6px;font-size:13px;color:#333;word-break:break-word;}</style></head><body><div class="docx-wrap"><p class="title">DOCX preview failed</p><p class="muted">This file could not be rendered in-browser on the server.</p><p><a href="'.e(route('tickets.attachments.show', ['ticket' => $ticket, 'attachment' => $attachment, 'download' => 1])).'">Download this file</a></p><div class="details">'.e($e->getMessage()).'</div></div></body></html>';

                return response($errorHtml, 200, [
                    'Content-Type' => 'text/html; charset=UTF-8',
                    'Content-Disposition' => 'inline; filename="docx-preview-error.html"',
                ]);
            }
        }

        // Force inline for preview routes (including online office viewers).
        if ($request->boolean('preview')) {
            return response()->file($fullPath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
            ]);
        }

        // Preview common browser-viewable files; download the rest.
        if (str_starts_with($mime, 'image/') || in_array($mime, ['application/pdf', 'text/plain'])) {
            return response()->file($fullPath, ['Content-Type' => $mime]);
        }

        return response()->download($fullPath, $downloadName, ['Content-Type' => $mime]);
    }

    public function destroyAttachment(Ticket $ticket, Attachment $attachment)
    {
        if ($attachment->ticket_id !== $ticket->id) {
            abort(404);
        }

        $this->authorize('deleteAttachment', $ticket);

        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return redirect('/tickets/'.$ticket->id.'/edit')->with('success', 'Attachment removed successfully.');
    }

    private function notifyUsers($users, Ticket $ticket, string $event, string $message): void
    {
        foreach ($users->unique('id') as $user) {
            $user->notify(new TicketActivityNotification($ticket, $event, $message));
        }
    }

    private function buildMonthlyCalendarData(string $month, ?string $search, ?string $status, ?string $priority): array
    {
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = (clone $monthStart)->endOfMonth();
        $gridStart = (clone $monthStart)->startOfWeek(Carbon::MONDAY);
        $gridEnd = (clone $monthEnd)->endOfWeek(Carbon::SUNDAY);

        $query = Ticket::query()
            ->with(['user:id,name', 'assignedTo:id,name'])
            ->whereBetween('created_at', [
                (clone $gridStart)->startOfDay(),
                (clone $gridEnd)->endOfDay(),
            ]);

        if ($search) {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%'.$search.'%');
                    });

                if (ctype_digit($search)) {
                    $builder->orWhere('id', (int) $search);
                }
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        $tickets = $query
            ->orderBy('created_at')
            ->get();

        $ticketsByDay = $tickets->groupBy(fn (Ticket $ticket) => $ticket->created_at->toDateString());

        $days = [];
        $cursor = (clone $gridStart);
        while ($cursor->lte($gridEnd)) {
            $days[] = [
                'date' => (clone $cursor),
                'in_month' => $cursor->month === $monthStart->month,
                'tickets' => $ticketsByDay->get($cursor->toDateString(), collect()),
            ];
            $cursor->addDay();
        }

        return [
            'monthStart' => $monthStart,
            'prevMonth' => $monthStart->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $monthStart->copy()->addMonth()->format('Y-m'),
            'days' => $days,
            'ticketCount' => $tickets->count(),
        ];
    }

    private function purgeTicketNotifications(Ticket $ticket): void
    {
        DatabaseNotification::query()
            ->where('data->ticket_id', $ticket->id)
            ->delete();
    }

    private function storeAttachmentWithOriginalName(UploadedFile $file): string
    {
        $directory = 'tickets';
        $originalName = $file->getClientOriginalName();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

        $candidate = $originalName;
        $counter = 1;

        while (Storage::disk('public')->exists($directory.'/'.$candidate)) {
            $suffix = ' ('.$counter.')';
            $candidate = $baseName.$suffix.($extension ? '.'.$extension : '');
            $counter++;
        }

        $storedPath = $file->storeAs($directory, $candidate, 'public');

        if (strtolower(pathinfo($storedPath, PATHINFO_EXTENSION)) === 'docx') {
            $this->generatePdfSidecarForDocx($storedPath);
        }

        return $storedPath;
    }

    private function generatePdfSidecarForDocx(string $publicRelativeDocxPath): void
    {
        $sourceAbsolutePath = Storage::disk('public')->path($publicRelativeDocxPath);
        $outputDirectory = dirname($sourceAbsolutePath);
        $baseName = pathinfo($sourceAbsolutePath, PATHINFO_FILENAME);
        $expectedPdfPath = $outputDirectory.DIRECTORY_SEPARATOR.$baseName.'.pdf';

        // No need to reconvert if sidecar PDF already exists.
        if (is_file($expectedPdfPath)) {
            return;
        }

        $soffice = $this->resolveLibreOfficeBinary();
        if ($soffice === null) {
            return;
        }

        try {
            $process = new Process([
                $soffice,
                '--headless',
                '--convert-to',
                'pdf',
                '--outdir',
                $outputDirectory,
                $sourceAbsolutePath,
            ]);

            $process->setTimeout(60);
            $process->run();

            if (!$process->isSuccessful() || !is_file($expectedPdfPath)) {
                Log::warning('DOCX sidecar PDF generation failed.', [
                    'docx' => $publicRelativeDocxPath,
                    'output' => $process->getErrorOutput() ?: $process->getOutput(),
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('DOCX sidecar PDF generation threw an exception.', [
                'docx' => $publicRelativeDocxPath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveLibreOfficeBinary(): ?string
    {
        $candidates = array_filter([
            env('LIBREOFFICE_PATH'),
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            'soffice',
        ]);

        foreach ($candidates as $candidate) {
            if ($candidate === 'soffice') {
                try {
                    $probe = new Process(['where', 'soffice']);
                    $probe->setTimeout(5);
                    $probe->run();

                    if ($probe->isSuccessful()) {
                        return 'soffice';
                    }
                } catch (Throwable) {
                    continue;
                }

                continue;
            }

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

}
