@extends('layouts.app')

@section('content')
<h2 class="text-2xl font-bold mb-6">{{ isset($ticket) ? 'Edit Ticket' : 'Create Ticket' }}</h2>

<form method="POST" action="{{ isset($ticket) ? '/tickets/'.$ticket->id : '/tickets' }}" enctype="multipart/form-data" class="bg-moon-rock text-soft-dove p-6 rounded shadow space-y-4">
    @csrf
    @if(isset($ticket)) @method('PUT') @endif

    @if($errors->any())
        <div class="rounded border border-red-300/50 bg-red-900/30 p-3">
            <p class="font-semibold text-red-200">Please fix the highlighted fields below.</p>
        </div>
    @endif

    <div>
        <label for="full_name" class="block mb-2 font-semibold">Full Name <span class="text-red-300">*</span></label>
        <input id="full_name" type="text" name="full_name" value="{{ old('full_name', $ticket->full_name ?? '') }}" placeholder="Full Name" class="w-full p-2 rounded text-black border @error('full_name') border-red-500 @else border-transparent @enderror">
        @error('full_name')
            <p class="mt-1 text-sm text-red-300">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="class_department" class="block mb-2 font-semibold">Class / Department <span class="text-red-300">*</span></label>
        <input id="class_department" type="text" name="class_department" value="{{ old('class_department', $ticket->class_department ?? '') }}" placeholder="Class / Department" class="w-full p-2 rounded text-black border @error('class_department') border-red-500 @else border-transparent @enderror">
        @error('class_department')
            <p class="mt-1 text-sm text-red-300">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="category" class="block mb-2 font-semibold">Category <span class="text-red-300">*</span></label>
        <select id="category" name="category" class="w-full p-2 rounded text-black border @error('category') border-red-500 @else border-transparent @enderror">
            <option value="">Select Category</option>
            <option value="Hardware" {{ old('category', $ticket->category ?? '') === 'Hardware' ? 'selected' : '' }}>Hardware</option>
            <option value="Software" {{ old('category', $ticket->category ?? '') === 'Software' ? 'selected' : '' }}>Software</option>
            <option value="Network" {{ old('category', $ticket->category ?? '') === 'Network' ? 'selected' : '' }}>Network</option>
            <option value="Account / Login" {{ old('category', $ticket->category ?? '') === 'Account / Login' ? 'selected' : '' }}>Account / Login</option>
            <option value="Printer" {{ old('category', $ticket->category ?? '') === 'Printer' ? 'selected' : '' }}>Printer</option>
            <option value="Other" {{ old('category', $ticket->category ?? '') === 'Other' ? 'selected' : '' }}>Other</option>
        </select>
        @error('category')
            <p class="mt-1 text-sm text-red-300">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="priority" class="block mb-2 font-semibold">Priority <span class="text-red-300">*</span></label>
        <select id="priority" name="priority" class="w-full p-2 rounded text-black border @error('priority') border-red-500 @else border-transparent @enderror">
            <option value="">Select Priority</option>
            @foreach(($priority_options ?? \App\Enums\TicketPriority::labels()) as $value => $label)
                <option value="{{ $value }}" {{ old('priority', $ticket->priority ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('priority')
            <p class="mt-1 text-sm text-red-300">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="title" class="block mb-2 font-semibold">Title <span class="text-red-300">*</span></label>
        <input id="title" type="text" name="title" value="{{ old('title', $ticket->title ?? '') }}" placeholder="Title" class="w-full p-2 rounded text-black border @error('title') border-red-500 @else border-transparent @enderror">
        @error('title')
            <p class="mt-1 text-sm text-red-300">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="description" class="block mb-2 font-semibold">Description <span class="text-red-300">*</span></label>
        <textarea id="description" name="description" placeholder="Describe problem" class="w-full p-2 rounded text-black border @error('description') border-red-500 @else border-transparent @enderror">{{ old('description', $ticket->description ?? '') }}</textarea>
        @error('description')
            <p class="mt-1 text-sm text-red-300">{{ $message }}</p>
        @enderror
    </div>

    @if(isset($ticket) && $ticket->attachments->isNotEmpty())
        <div>
            <p class="block mb-2 font-semibold">Existing Attachments</p>
            <div class="space-y-2">
                @foreach($ticket->attachments as $attachment)
                    @php
                        $previewUrl = route('tickets.attachments.show', ['ticket' => $ticket, 'attachment' => $attachment, 'preview' => 1]);
                        $downloadUrl = route('tickets.attachments.show', ['ticket' => $ticket, 'attachment' => $attachment, 'download' => 1]);
                    @endphp
                    <div class="flex flex-col gap-2 rounded border border-soft-dove/20 bg-black/10 p-3 md:flex-row md:items-center md:justify-between">
                        <span class="text-sm break-all">{{ basename($attachment->file_path) }}</span>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ $previewUrl }}" target="_blank" class="px-3 py-1 rounded bg-green-700 hover:bg-green-800 text-soft-dove text-xs">Preview</a>
                            <a href="{{ $downloadUrl }}" class="px-3 py-1 rounded bg-moon-rock hover:bg-spiced-hot-chocolate text-soft-dove text-xs">Download</a>
                            <button
                                type="button"
                                class="attachment-delete-btn px-3 py-1 rounded bg-dark-sienna hover:bg-black-raspberry text-soft-dove text-xs"
                                data-delete-url="{{ route('tickets.attachments.destroy', ['ticket' => $ticket, 'attachment' => $attachment]) }}"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div>
        <label for="attachments-input" class="block mb-2 font-semibold">Attachments</label>
        <input id="attachments-input" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt" class="block w-full rounded border @if($errors->has('attachments') || $errors->has('attachments.*')) border-red-500 @else border-transparent @endif">
        <p class="mt-1 text-xs text-soft-dove/80">You can add files in multiple rounds. New picks are added to the current list.</p>
        <div id="attachments-selected-list" class="mt-3 space-y-2"></div>
        @error('attachments')
            <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
        @enderror
        @error('attachments.*')
            <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
        @enderror
    </div>

    <button type="submit" class="bg-dark-sienna px-6 py-2 rounded hover:bg-black-raspberry text-soft-dove font-bold">
        {{ isset($ticket) ? 'Update Ticket' : 'Create Ticket' }}
    </button>
</form>

<script>
    (function () {
        const input = document.getElementById('attachments-input');
        const list = document.getElementById('attachments-selected-list');
        const deleteButtons = document.querySelectorAll('.attachment-delete-btn');

        if (!input || !list) {
            return;
        }

        const selectedFiles = new Map();

        const fileKey = (file) => [file.name, file.size, file.lastModified].join('::');

        const syncInputFiles = () => {
            const dt = new DataTransfer();
            selectedFiles.forEach((file) => dt.items.add(file));
            input.files = dt.files;
        };

        const renderList = () => {
            list.innerHTML = '';

            if (selectedFiles.size === 0) {
                list.innerHTML = '<p class="text-xs text-soft-dove/70">No files selected yet.</p>';
                return;
            }

            selectedFiles.forEach((file, key) => {
                const row = document.createElement('div');
                row.className = 'flex items-center justify-between gap-2 rounded border border-soft-dove/20 px-3 py-2 bg-black/10';
                row.innerHTML = '<span class="text-sm break-all">' + file.name + ' (' + Math.ceil(file.size / 1024) + ' KB)</span>';

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'px-2 py-1 rounded bg-dark-sienna hover:bg-black-raspberry text-soft-dove text-xs';
                removeBtn.textContent = 'Remove';
                removeBtn.addEventListener('click', () => {
                    selectedFiles.delete(key);
                    syncInputFiles();
                    renderList();
                });

                row.appendChild(removeBtn);
                list.appendChild(row);
            });
        };

        input.addEventListener('change', () => {
            Array.from(input.files).forEach((file) => {
                selectedFiles.set(fileKey(file), file);
            });

            syncInputFiles();
            renderList();
        });

        deleteButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const deleteUrl = btn.getAttribute('data-delete-url');

                if (!deleteUrl) {
                    return;
                }

                if (!confirm('Remove this attachment?')) {
                    return;
                }

                const deleteForm = document.createElement('form');
                deleteForm.method = 'POST';
                deleteForm.action = deleteUrl;

                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';

                const method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';

                deleteForm.appendChild(csrf);
                deleteForm.appendChild(method);
                document.body.appendChild(deleteForm);
                deleteForm.submit();
            });
        });

        renderList();
    })();
</script>
@endsection
