@extends('layouts.app')

@section('content')
<h2 class="text-2xl font-bold mb-4">{{ $ticket->title }}</h2>

<div class="bg-moon-rock text-soft-dove p-6 rounded shadow space-y-2">
    <p><b>Name:</b> {{ $ticket->full_name }}</p>
    <p><b>Class / Department:</b> {{ $ticket->class_department }}</p>
    <p><b>Category:</b> {{ $ticket->category }}</p>
    <p><b>Priority:</b> 
        <span class="px-2 py-1 rounded
        @if($ticket->priority=='Low') bg-green-700
        @elseif($ticket->priority=='Medium') bg-yellow-700
        @else bg-red-700 @endif text-soft-dove font-bold">
            {{ $ticket->priority }}
        </span>
    </p>
    <p><b>Status:</b> 
        <span class="font-bold
        @if($ticket->status=='open') text-green-300
        @elseif($ticket->status=='closed') text-red-300
        @else text-yellow-300 @endif">
            {{ ucfirst($ticket->status) }}
        </span>
    </p>

    @if($ticket->assigned_to)
        <p><b>Assigned to:</b> {{ $ticket->assignedTo?->name ?? 'Unknown' }}</p>

        @if(auth()->user()->role === 'it' && $ticket->status !== 'closed' && $ticket->assigned_to === auth()->id())
            <div class="mt-3 flex flex-wrap gap-2 items-end">
                <form method="POST" action="{{ route('tickets.unassign', $ticket) }}" class="inline">
                    @csrf
                    <button type="submit" class="bg-moon-rock px-4 py-2 rounded hover:bg-spiced-hot-chocolate text-soft-dove font-bold">
                        Unassign Ticket
                    </button>
                </form>

                <form method="POST" action="{{ route('tickets.transfer', $ticket) }}" class="inline-flex gap-2 items-center">
                    @csrf
                    <select name="transfer_to" class="p-2 rounded text-black">
                        @foreach(($itUsers ?? collect()) as $itUser)
                            @if($itUser->id !== auth()->id())
                                <option value="{{ $itUser->id }}">Transfer to {{ $itUser->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    <button type="submit" class="bg-dark-sienna px-4 py-2 rounded hover:bg-black-raspberry text-soft-dove font-bold">
                        Transfer
                    </button>
                </form>
            </div>
        @endif
    @elseif(auth()->user()->role === 'it')
        <form method="POST" action="/tickets/{{ $ticket->id }}/claim" class="mt-2">
            @csrf
            <button type="submit" class="bg-green-700 px-4 py-2 rounded hover:bg-green-800 text-soft-dove font-bold">
                Claim Ticket
            </button>
        </form>
    @endif

    <hr class="my-2 border-soft-dove">

    <p>{{ $ticket->description }}</p>

    <h3 class="mt-4 font-semibold">Attachments</h3>
    <div class="grid gap-3 mt-2 md:grid-cols-2">
        @foreach($ticket->attachments as $attachment)
            @php
                $extension = pathinfo($attachment->file_path, PATHINFO_EXTENSION);
                $ext = strtolower($extension);
                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']);
                $isDocx = $ext === 'docx';
                $isPreviewable = $isImage || in_array($ext, ['pdf', 'txt']) || $isDocx;
                $isOffice = in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);
                $previewUrl = route('tickets.attachments.show', ['ticket' => $ticket, 'attachment' => $attachment, 'preview' => 1]);
                $docxHtmlPreviewUrl = route('tickets.attachments.show', ['ticket' => $ticket, 'attachment' => $attachment, 'preview_html' => 1, 'force_html' => 1, 'v' => now()->timestamp]);
                $downloadUrl = route('tickets.attachments.show', ['ticket' => $ticket, 'attachment' => $attachment, 'download' => 1]);
                $officeViewerUrl = 'https://view.officeapps.live.com/op/view.aspx?src=' . urlencode($previewUrl);
            @endphp
            <div class="rounded border border-soft-dove/20 p-3 bg-dark-sienna/40">
                <p class="text-sm font-semibold mb-2">{{ basename($attachment->file_path) }}</p>

                @if($isImage)
                    <img src="{{ $previewUrl }}" class="w-28 h-28 object-cover rounded mb-3">
                @endif

                <div class="flex flex-wrap gap-2">
                    @if($isPreviewable)
                        <button type="button" class="px-3 py-1 rounded bg-green-700 hover:bg-green-800 text-soft-dove text-sm" data-preview-url="{{ $isDocx ? $docxHtmlPreviewUrl : $previewUrl }}" data-preview-type="{{ $isImage ? 'image' : 'frame' }}">
                            Preview
                        </button>
                    @endif

                    @if($isOffice && !$isDocx)
                        <a href="{{ $officeViewerUrl }}" target="_blank" class="px-3 py-1 rounded bg-moon-rock hover:bg-spiced-hot-chocolate text-soft-dove text-sm">
                            Open in Office Viewer
                        </a>
                    @endif

                    <a href="{{ $downloadUrl }}" class="px-3 py-1 rounded bg-dark-sienna hover:bg-black-raspberry text-soft-dove text-sm">
                        Download
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    <div id="attachment-preview-overlay" class="fixed inset-0 bg-black/60 hidden z-[90]"></div>
    <div id="attachment-preview-modal" class="fixed inset-0 hidden z-[100] items-center justify-center p-4">
        <div class="attachment-preview-card relative w-full max-w-4xl bg-moon-rock rounded-xl border border-soft-dove/20 shadow-2xl p-4">
            <button type="button" id="attachment-preview-close" class="absolute top-2 right-2 px-2 py-1 rounded bg-dark-sienna text-soft-dove hover:bg-black-raspberry">X</button>
            <div id="attachment-preview-content" class="attachment-preview-content mt-8"></div>
        </div>
    </div>

    <script>
        (function () {
            const overlay = document.getElementById('attachment-preview-overlay');
            const modal = document.getElementById('attachment-preview-modal');
            const content = document.getElementById('attachment-preview-content');
            const closeBtn = document.getElementById('attachment-preview-close');
            const previewButtons = document.querySelectorAll('[data-preview-url]');

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                overlay.classList.add('hidden');
                content.innerHTML = '';
            };

            previewButtons.forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const url = btn.getAttribute('data-preview-url');
                    const type = btn.getAttribute('data-preview-type');

                    if (type === 'image') {
                        content.innerHTML = '<img src="' + url + '" class="max-h-full max-w-full mx-auto rounded" />';
                    } else {
                        content.innerHTML = '<iframe src="' + url + '" class="w-full h-full rounded border border-soft-dove/20"></iframe>';
                    }

                    overlay.classList.remove('hidden');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });
            });

            closeBtn.addEventListener('click', closeModal);
            overlay.addEventListener('click', closeModal);
        })();
    </script>

    <h3 class="mt-4 font-semibold">Comments</h3>
    <div class="space-y-2">
        @foreach($ticket->comments as $c)
            <p><b>{{ $c->user->name }}:</b> {{ $c->comment }}</p>
        @endforeach
    </div>

    <form method="POST" action="/tickets/{{ $ticket->id }}/comment" class="mt-4">
        @csrf
        <textarea name="comment" placeholder="Write a comment" class="w-full p-2 rounded text-black mb-2"></textarea>
        <button class="bg-dark-sienna px-4 py-2 rounded hover:bg-black-raspberry text-soft-dove font-bold">Send</button>
    </form>

    <div class="mt-4 space-x-2">
        @if(auth()->user()->role === 'it' && $ticket->assigned_to === auth()->id() && $ticket->status !== 'closed')
            <form method="POST" action="/tickets/{{ $ticket->id }}/complete" class="inline">
                @csrf
                <button class="bg-green-600 px-4 py-2 rounded hover:bg-green-700 text-white font-bold">Complete Ticket</button>
            </form>
        @endif

        @if(auth()->user()->role === 'it' && $ticket->status === 'closed')
            <form method="POST" action="{{ route('tickets.reopen', $ticket) }}" class="inline">
                @csrf
                <button class="bg-yellow-600 px-4 py-2 rounded hover:bg-yellow-700 text-white font-bold">Reopen Ticket</button>
            </form>
        @endif

        <a href="/tickets/{{ $ticket->id }}/edit" class="bg-moon-rock px-4 py-2 rounded hover:bg-spiced-hot-chocolate text-soft-dove">Edit Ticket</a>

        <form method="POST" action="/tickets/{{ $ticket->id }}" class="inline">
            @csrf
            @method('DELETE')
            <button onclick="return confirm('Delete ticket?')" class="bg-dark-sienna px-4 py-2 rounded hover:bg-black-raspberry text-soft-dove">Delete Ticket</button>
        </form>
    </div>
</div>
@endsection
