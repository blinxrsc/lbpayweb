<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Backup System') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="backupManager()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h3 class="text-lg font-bold mb-4">{{ __('Backup Information') }}</h3>
                    <table class="w-full text-sm border">
                        <tr class="bg-gray-50">
                            <td class="border px-3 py-2 font-semibold">Container Path</td>
                            <td class="border px-3 py-2">/storage/app/backups</td>
                        </tr>
                        <tr>
                            <td class="border px-3 py-2">Log file</td>
                            <td class="border px-3 py-2">/storage/logs/backup.log</td>
                        </tr>
                        <tr>
                            <td class="border px-3 py-2 font-semibold">Status</td>
                            <td class="border px-3 py-2">
                                <span class="flex items-center">
                                    <div :class="status === 'running' ? 'animate-ping' : ''" class="h-2 w-2 rounded-full bg-indigo-500 mr-2"></div>
                                    <span x-text="statusText">Ready</span>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h3 class="text-lg font-bold mb-4">{{ __('Choose Backup Format') }}</h3>
                    <div class="flex items-center gap-3">
                        <select x-model="format" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="zip">ZIP (Fastest)</option>
                            <option value="tar.gz">TAR.GZ (Compressed)</option>
                        </select>
                        <x-primary-button x-on:click="startBackup" ::disabled="status === 'running'">
                            <span x-show="status !== 'running'">Create & Download</span>
                            <span x-show="status === 'running'">Processing...</span>
                        </x-primary-button>
                    </div>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl flex justify-between items-center">
                    <h3 class="text-lg font-bold">{{ __('View Backup History') }}</h3>
                    <a href="{{ route('backup.history') }}">
                        <x-secondary-button>View Logs</x-secondary-button>
                    </a>
                </div>
            </div>
        </div>

        <x-modal name="backup-process" :show="false" focusable>
            <div class="p-6 text-center">
                <h2 class="text-lg font-medium text-gray-900" x-text="modalTitle"></h2>
                
                <div class="mt-6 flex flex-col items-center justify-center">
                    <template x-if="status === 'running'">
                        <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent align-[-0.125em] motion-reduce:animate-[spin_1.5s_linear_infinite]"></div>
                    </template>

                    <template x-if="status === 'done'">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </template>

                    <p class="mt-4 text-sm text-gray-600" x-text="message"></p>
                </div>

                <div class="mt-6" x-show="status !== 'running'">
                    <x-secondary-button x-on:click="$dispatch('close-modal', 'backup-process')">
                        Close
                    </x-secondary-button>
                </div>
            </div>
        </x-modal>
    </div>

    <script>
        function backupManager() {
            return {
                status: 'idle',
                statusText: 'Ready',
                format: 'zip',
                message: '',
                modalTitle: 'Preparing Backup',
                
                async startBackup() {
                    this.status = 'running';
                    this.statusText = 'Working...';
                    this.message = 'Initializing background task...';
                    this.modalTitle = 'Processing Backup';
                    this.$dispatch('open-modal', 'backup-process');

                    try {
                        // 1. Dispatch the Job
                        const response = await fetch("{{ route('backup.create') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ format: this.format })
                        });

                        const data = await response.json();
                        if (!response.ok) throw new Error(data.message || 'Failed to start');

                        const backupId = data.backup_id;
                        this.message = 'Zipping files in background (Horizon)...';

                        // 2. Poll for completion
                        this.pollStatus(backupId);

                    } catch (e) {
                        this.handleError(e.message);
                    }
                },

                pollStatus(backupId) {
                    const interval = setInterval(async () => {
                        try {
                            const res = await fetch(`/admin/backup/status/${backupId}`);
                            const data = await res.json();

                            if (data.status === 'completed') {
                                clearInterval(interval);
                                this.finalizeDownload(data.url);
                            } else if (data.status === 'failed') {
                                clearInterval(interval);
                                throw new Error('Background process failed.');
                            }
                        } catch (e) {
                            clearInterval(interval);
                            this.handleError(e.message);
                        }
                    }, 2000); // Poll every 2 seconds
                },

                finalizeDownload(url) {
                    this.status = 'done';
                    this.statusText = 'Completed';
                    this.message = 'Download starting automatically...';

                    // Force the URL to HTTPS if it comes back as HTTP
                    const secureUrl = url.replace("http://", "https://");
                    
                    // Trigger the download
                    const link = document.createElement('a');
                    link.href = secureUrl;
                    link.setAttribute('download', '');
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    setTimeout(() => {
                        this.$dispatch('close-modal', 'backup-process');
                        this.status = 'idle';
                        this.statusText = 'Ready';
                    }, 3000);
                },

                handleError(err) {
                    this.status = 'error';
                    this.statusText = 'Failed';
                    this.modalTitle = 'Error';
                    this.message = err;
                }
            }
        }
    </script>
</x-app-layout>