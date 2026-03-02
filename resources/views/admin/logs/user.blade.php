<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('User Logs') }}
        </h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <!-- Transactions Table -->
                <table class="min-w-full border-collapse border">
                    <thead class="bg-gray-100 sticky top-0 z-10 shadow-sm">
                        <tr class="bg-gray-100">
                            <th class="border px-4 py-2 text-sm tracking-wider whitespace-nowrap">Time</th>
                            <th class="border px-4 py-2 text-sm tracking-wider whitespace-nowrap">Admin</th>
                            <th class="border px-4 py-2 text-sm tracking-wider whitespace-nowrap">Action</th>
                            <th class="border px-4 py-2 text-sm tracking-wider whitespace-nowrap">Target</th>
                            <th class="border px-4 py-2 text-sm tracking-wider whitespace-nowrap">Changes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                            <tr>
                                <td class="border px-4 py-2 whitespace-nowrap text-sm text-gray-600">
                                    {{ $activity->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="border px-4 py-2 whitespace-nowrap text-sm text-gray-600">{{ $activity->causer->name ?? 'System' }}</td>
                                <td class="border px-4 py-2 whitespace-nowrap text-sm text-gray-600">{{ $activity->description }}</td>
                                <td class="border px-4 py-2 whitespace-nowrap text-sm text-gray-600">{{ class_basename($activity->subject_type) }} (ID: {{ $activity->subject_id }})</td>
                                <td class="border px-4 py-2 whitespace-nowrap text-sm text-gray-600">
                                    @if($activity->changes())
                                        <code class="small">{{ json_encode($activity->changes()['attributes']) }}</code>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <p class="text-gray-500 text-lg font-medium">
                                            <x-heroicon-m-exclamation-circle />No transactions found
                                        </p>
                                        <p class="text-gray-400 text-sm">Try adjusting your filters or resetting the search.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <!-- Pagination -->
                <div class="mt-4">
                    {{ $activities->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
