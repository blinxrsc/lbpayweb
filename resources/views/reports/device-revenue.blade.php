<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Device Revenue Summary') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('reports.device-revenue') }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Outlet</label>
                        <select name="outlet_id" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                            <option value="">-- All Outlets --</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ $outletId == $outlet->id ? 'selected' : '' }}>{{ $outlet->outlet_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                        <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                        <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                        Filter
                    </button>
                    <a href="{{ route('reports.device-revenue') }}" class="inline-flex items-center px-4 py-2 text-sm text-gray-500 hover:text-gray-700">
                        Reset to This Month
                    </a>
                </form>
                <p class="text-xs text-gray-400 mt-3">
                    Showing {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}
                    @if($outletId)
                        · {{ $outlets->firstWhere('id', $outletId)?->outlet_name }}
                    @else
                        · All outlets
                    @endif
                </p>
            </div>

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-lg border border-gray-200">
                    <div class="flex items-center gap-2 text-gray-500">
                        <x-heroicon-s-device-phone-mobile class="w-5 h-5"/>
                        <h4 class="text-xs font-semibold uppercase tracking-wide">Mobile Revenue</h4>
                    </div>
                    <p class="text-3xl font-semibold text-gray-900 mt-2">RM {{ number_format($summary['mobile_revenue'], 2) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $summary['mobile_count'] }} transaction{{ $summary['mobile_count'] == 1 ? '' : 's' }} · direct payment gateway</p>
                </div>
                <div class="bg-white p-5 rounded-lg border border-gray-200">
                    <div class="flex items-center gap-2 text-gray-500">
                        <x-heroicon-s-wallet class="w-5 h-5"/>
                        <h4 class="text-xs font-semibold uppercase tracking-wide">Member Revenue</h4>
                    </div>
                    <p class="text-3xl font-semibold text-gray-900 mt-2">RM {{ number_format($summary['member_revenue'], 2) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $summary['member_count'] }} transaction{{ $summary['member_count'] == 1 ? '' : 's' }} · paid from balance</p>
                </div>
                <div class="bg-white p-5 rounded-lg border border-gray-200">
                    <div class="flex items-center gap-2 text-gray-500">
                        <x-heroicon-s-banknotes class="w-5 h-5"/>
                        <h4 class="text-xs font-semibold uppercase tracking-wide">Total Revenue</h4>
                    </div>
                    <p class="text-3xl font-semibold text-gray-900 mt-2">RM {{ number_format($summary['total_revenue'], 2) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $summary['total_count'] }} transaction{{ $summary['total_count'] == 1 ? '' : 's' }} total</p>
                </div>
            </div>

            <!-- Per-outlet breakdown -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">By Outlet</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-2">Outlet</th>
                                <th class="px-4 py-2 text-right">Mobile Revenue</th>
                                <th class="px-4 py-2 text-right">Member Revenue</th>
                                <th class="px-4 py-2 text-right">Total Revenue</th>
                                <th class="px-4 py-2 text-right">Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byOutlet as $row)
                                <tr class="text-sm border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                    <td class="px-4 py-2">{{ $row['outlet']->outlet_name }}</td>
                                    <td class="px-4 py-2 text-right">RM {{ number_format($row['mobile_revenue'], 2) }}</td>
                                    <td class="px-4 py-2 text-right">RM {{ number_format($row['member_revenue'], 2) }}</td>
                                    <td class="px-4 py-2 text-right font-medium text-gray-900">RM {{ number_format($row['total_revenue'], 2) }}</td>
                                    <td class="px-4 py-2 text-right text-gray-500">{{ $row['total_count'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-400">No revenue recorded for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
