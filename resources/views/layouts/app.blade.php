<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IT Help Desk</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-soft-dove font-sans">

    @include('layouts.navigation')

    @if(session('completion_success'))
        <style>
            .completion-modal-overlay {
                background-color: rgba(0, 0, 0, 0.5);
            }

            .completion-modal-card {
                background-color: #faa396; /* SQUARE COLOUR */
                border-color: #a15145; /* OUTLINE SQUARE */
            }
        </style>

        <div id="completion-modal-overlay" class="completion-modal-overlay fixed inset-0 z-[70]"></div>
        <div id="completion-modal" class="fixed inset-0 z-[80] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="completion-modal-title">
            <div class="completion-modal-card relative w-80 h-80 border text-black rounded-xl shadow-2xl flex items-center justify-center p-8 text-center">
                <button type="button" id="completion-modal-close" class="absolute z-10 w-8 h-8 rounded-full bg-green-200/80 text-green-900 hover:bg-green-300 text-2xl leading-none flex items-center justify-center" style="top: 0.5rem; right: 0.5rem;" aria-label="Close notification">&times;</button>
                <div>
                    <h2 id="completion-modal-title" class="text-xl font-bold mb-3">Success</h2>
                    <p class="font-medium">{{ session('completion_success') }}</p>
                </div>
            </div>
        </div>
        <script>
            (function () {
                const modal = document.getElementById('completion-modal');
                const overlay = document.getElementById('completion-modal-overlay');
                const closeBtn = document.getElementById('completion-modal-close');
                if (!modal) return;

                const removeModal = () => {
                    if (modal && modal.parentNode) {
                        modal.parentNode.removeChild(modal);
                    }
                    if (overlay && overlay.parentNode) {
                        overlay.parentNode.removeChild(overlay);
                    }
                };

                const timer = setTimeout(removeModal, 10000);

                if (closeBtn) {
                    closeBtn.addEventListener('click', function () {
                        clearTimeout(timer);
                        removeModal();
                    });
                }
            })();
        </script>
    @endif

    @if(session('success'))
        <div id="flash-success" class="fixed top-4 right-4 z-50 max-w-sm w-full bg-green-100 border border-green-300 text-green-900 rounded-lg shadow-lg p-4" role="status" aria-live="polite">
            <button type="button" id="flash-success-close" class="absolute top-2 right-2 text-green-800 hover:text-green-950" aria-label="Close notification">&times;</button>
            <p class="pr-6 font-medium">{{ session('success') }}</p>
        </div>
        <script>
            (function () {
                const toast = document.getElementById('flash-success');
                const closeBtn = document.getElementById('flash-success-close');
                if (!toast) return;

                const removeToast = () => {
                    if (toast && toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                };

                const timer = setTimeout(removeToast, 10000);

                if (closeBtn) {
                    closeBtn.addEventListener('click', function () {
                        clearTimeout(timer);
                        removeToast();
                    });
                }
            })();
        </script>
    @endif

    <div class="container mx-auto mt-6 px-4">
        @yield('content')
    </div>

</body>
</html>
