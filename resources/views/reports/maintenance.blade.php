<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Maintenance Insights: {{ $stats['period'] }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('reports.maintenance.pdf', ['month' => request('month', $stats['month']), 'year' => request('year', $stats['year'])]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Export PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white p-4 rounded-lg shadow mb-6">
                <form action="{{ route('reports.maintenance') }}" method="GET" class="flex flex-wrap items-end gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Month</label>
                        <select name="month" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ request('month', date('n')) == $m ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Year</label>
                        <select name="year" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            @foreach(range(date('Y') - 2, date('Y')) as $y)
                                <option value="{{ $y }}" {{ request('year', date('Y')) == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-bold transition">
                            Filter Report
                        </div>
                        <a href="{{ route('reports.maintenance') }}" class="text-gray-500 hover:text-gray-700 text-sm py-2">
                            Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white p-4 rounded-lg shadow mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white p-5 rounded-lg shadow border-l-4 border-red-500">
                        <p class="text-xs text-gray-500 uppercase font-bold">New Faults</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['total_faulty'] }}</p>
                    </div>
                    <div class="bg-white p-5 rounded-lg shadow border-l-4 border-green-500">
                        <p class="text-xs text-gray-500 uppercase font-bold">Repairs Completed</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['total_repaired'] }}</p>
                    </div>
                    <div class="bg-white p-5 rounded-lg shadow border-l-4 border-blue-500">
                        <p class="text-xs text-gray-500 uppercase font-bold">Avg. Repair Time</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $stats['avg_repair_days'] }} 
                            <span class="text-sm font-normal text-gray-500">days</span>
                        </p>
                        <p class="text-xs {{ $stats['avg_repair_days'] > 3 ? 'text-red-500' : 'text-green-500' }}">
                            {{ $stats['avg_repair_days'] > 3 ? '▲ Slow' : '▼ Healthy' }}
                        </p>
                    </div>
                    <div class="bg-white p-5 rounded-lg shadow border-l-4 border-yellow-500">
                        <p class="text-xs text-gray-500 uppercase font-bold">Uptime Rate</p>
                        <p class="text-2xl font-bold text-gray-900">98.2%</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow">
                        <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" /></svg>
                            Fault Frequency by Model
                        </h3>
                        <div class="h-64"><canvas id="modelChart"></canvas></div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-sm font-bold text-gray-700 mb-4">Repair vs Fault Ratio</h3>
                        <div class="h-64"><canvas id="ratioChart"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow mb-6">
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-sm font-bold text-gray-700 italic">"Lemon" Devices (Frequent Failures)</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Device Info</th>
                                <th class="px-6 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">Faults</th>
                                <th class="px-6 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">Repairs</th>
                                <th class="px-6 py-3 text-right font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Latest Fix Note</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($stats['top_faulty_devices'] as $item)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-indigo-600 font-mono">{{ $item->serial_number }}</div>
                                    <div class="text-xs text-gray-400">{{ $item->model }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        {{ $item->faults_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $item->repairs_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    @if($item->status === 'faulty')
                                        <span class="text-red-500 text-xs font-bold uppercase tracking-tighter animate-pulse">● Currently Down</span>
                                    @else
                                        <span class="text-gray-400 text-xs italic">Available</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($item->last_repair_note)
                                        <div class="text-xs text-gray-600 italic line-clamp-2 max-w-xs" title="{{ $item->last_repair_note }}">
                                            "{{ $item->last_repair_note }}"
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 italic">No repair history</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const modelData = @json($stats['faults_by_model']);
        
        // Bar Chart - Failure Analysis
        new Chart(document.getElementById('modelChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(modelData),
                datasets: [{
                    label: 'Failures',
                    data: Object.values(modelData),
                    backgroundColor: '#6366F1', // Indigo
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, grid: { display: false } } }
            }
        });

        // Ratio Chart
        new Chart(document.getElementById('ratioChart'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Repaired'],
                datasets: [{
                    data: [{{ $stats['total_faulty'] }}, {{ $stats['total_repaired'] }}],
                    backgroundColor: ['#FCA5A5', '#34D399'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { position: 'bottom' } }
            }
        });
    </script>
</x-app-layout>