<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Remote Start Log') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                <form method="GET" action="{{ route('admin.remote-start-logs.index') }}" class="mb-4 flex flex-wrap gap-2">
                    <input type="text" name="device_serial_number" value="{{ request('device_serial_number') }}"
                        placeholder="Device serial" class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-40 outline-none focus:ring-2 focus:ring-blue-500">
                    <input type="date" name="from" value="{{ request('from') }}" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <input type="date" name="to" value="{{ request('to') }}" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                        Filter
                    </button>
                    @if(request()->anyFilled(['device_serial_number','from','to']))
                        <a href="{{ route('admin.remote-start-logs.index') }}" class="inline-flex items-center px-4 py-2 text-sm text-gray-500 hover:text-gray-700">
                            Clear
                        </a>
                    @endif
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-2">Date</th>
                                <th class="px-4 py-2">Performed By</th>
                                <th class="px-4 py-2">Device Serial</th>
                                <th class="px-4 py-2">Cycle</th>
                                <th class="px-4 py-2 text-right">Value</th>
                                <th class="px-4 py-2">Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr class="text-sm border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-600">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td class="px-4 py-2">{{ $log->user->name ?? 'Unknown (deleted account)' }}</td>
                                    <td class="px-4 py-2 font-mono text-xs">{{ $log->device_serial_number }}</td>
                                    <td class="px-4 py-2">{{ $log->cycle_type }}</td>
                                    <td class="px-4 py-2 text-right font-medium text-gray-900">RM {{ number_format($log->equivalent_price, 2) }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $log->reason ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">No remote starts recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
