<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Outlet Statistics Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <!-- Filters -->
                <form method="GET" action="{{ route('dashboard') }}" class="mb-6 bg-white rounded-xl border border-gray-100 shadow-sm">
                    <div class="p-4 flex flex-wrap gap-4 items-end relative z-20">
                        <div x-data="{ from: '{{ request('from') }}', to: '{{ request('to') }}', instanceFrom: null, instanceTo: null }" class="flex items-end gap-2">
                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">From</label>
                                <input x-ref="fromPicker" x-init="instanceFrom = flatpickr($refs.fromPicker, { dateFormat: 'Y-m-d', defaultDate: from })" type="text" name="from" x-model="from" class="w-32 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">To</label>
                                <input x-ref="toPicker" x-init="instanceTo = flatpickr($refs.toPicker, { dateFormat: 'Y-m-d', defaultDate: to })" type="text" name="to" x-model="to" class="w-32 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 border-t border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <button type="submit" class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg text-sm shadow-sm transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                Apply Filters
                            </button>

                            <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700 font-medium px-3 py-2 transition">
                                Clear All
                            </a>
                            <div class="h-6 w-px bg-gray-300 mx-2"></div>
                            <button type="button" name="export" onclick="downloadPDF()" class="flex items-center gap-2 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 font-semibold px-4 py-2 rounded-lg text-sm shadow-sm transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Download PDF Report
                            </button>
                        </div>
                    </div>
                </form>
                
                <form id="pdfForm" action="{{ route('dashboard.export') }}" method="POST" style="display:none">
                    @csrf
                    <input type="hidden" name="statusChart" id="statusInput">
                    <input type="hidden" name="typeChart" id="typeInput">
                    <input type="hidden" name="stateChart" id="stateInput">
                    <input type="hidden" name="brandChart" id="brandInput">
                    <input type="hidden" name="from" value="{{ $start }}">
                    <input type="hidden" name="to" value="{{ $end }}">
                </form>
                <script>
                function downloadPDF() {
                    // 1. Convert charts to Base64 Image strings
                    const statusImg = document.getElementById('statusChart').toDataURL('image/png');
                    const typeImg = document.getElementById('typeChart').toDataURL('image/png');
                    const stateImg = document.getElementById('stateChart').toDataURL('image/png');
                    const brandImg = document.getElementById('brandChart').toDataURL('image/png');

                    // 2. Put strings into the hidden form
                    document.getElementById('statusInput').value = statusImg;
                    document.getElementById('typeInput').value = typeImg;
                    document.getElementById('stateInput').value = stateImg;
                    document.getElementById('brandInput').value = brandImg;

                    // 3. Submit the form
                    document.getElementById('pdfForm').submit();
                }
                </script>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6" x-show="reportData.length > 0">
                <!-- Total Transaction / Usage -->
                <div class="bg-white p-6 rounded-lg shadow flex flex-col">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2 gap-3">
                            <x-heroicon-s-server-stack class="w-6 h-6"/>
                            <h4 class="text-sm font-semibold text-gray-600">Total Devices Installed</h4>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-indigo-700 mt-2">{{ $totalDevices }}</p>
                </div>
                <!-- Total Transaction / Usage -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                </div>
            </div>
            <!-- Charts -->
            <div class="grid grid-cols-2 gap-8">
                <div class="bg-white p-4 rounded-lg shadow"><h3 class="font-bold text-gray-700 mb-4">Connectivity (Online vs Offline)</h3><canvas id="connectivityChart"></canvas></div>
                <div class="bg-white p-4 rounded-lg shadow"><h3 class="font-bold text-gray-700 mb-4">Machine Usage (Available vs Busy)</h3><canvas id="availabilityChart"></canvas></div>
                <div class="bg-white p-4 rounded-lg shadow"><h3 class="font-bold text-gray-700 mb-4"><h3>Outlet Status</h3><canvas id="statusChart"></canvas></div>
                <div class="bg-white p-4 rounded-lg shadow"><h3 class="font-bold text-gray-700 mb-4"><h3>Outlet Types</h3><canvas id="typeChart"></canvas></div>
                <div class="bg-white p-4 rounded-lg shadow"><h3 class="font-bold text-gray-700 mb-4"><h3>Outlets by State</h3><canvas id="stateChart"></canvas></div>
                <div class="bg-white p-4 rounded-lg shadow"><h3 class="font-bold text-gray-700 mb-4"><h3>Top Brands</h3><canvas id="brandChart"></canvas></div>
            </div>
            <!-- Table -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <div class="card-header bg-white"><strong>Outlets by City (Top 10)</strong></div>
                    <table class="table-auto w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-4 py-2">City</th>
                                <th class="px-4 py-2">Total Outlets</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($byCity as $city)
                                <tr>
                                    <td>{{ $city->label }}</td>
                                    <td><span class="badge bg-primary">{{ $city->total }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Table -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <div class="card-header bg-white"><strong>Outlets by City (Top 10)</strong></div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="pb-2">Outlet</th>
                                <th class="pb-2">Machine ID</th>
                                <th class="pb-2 text-right">Lifetime Coins</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topPerformingDevices as $item)
                            <tr class="border-b last:border-0">
                                <td class="py-2">{{ $item->outlet->outlet_name }}</td>
                                <td class="py-2">#{{ $item->machine_id }}</td>
                                <td class="py-2 text-right font-bold text-green-600">
                                    {{ number_format($item->lifetime_coins) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<!-- Alpine Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'];
        new Chart(document.getElementById('connectivityChart'), {
            type: 'pie',
            data: {
                labels: @json($deviceConnectivity->pluck('label')),
                datasets: [{
                    data: @json($deviceConnectivity->pluck('total')),
                    backgroundColor: ['#1cc88a', '#e74a3b', '#858796'] // Green for Online, Red for Offline, Gray for others
                }]
            }
        });
        new Chart(document.getElementById('availabilityChart'), {
            type: 'doughnut',
            data: {
                labels: @json($deviceAvailability->pluck('label')),
                datasets: [{
                    data: @json($deviceAvailability->pluck('total')),
                    backgroundColor: ['#4e73df', '#f6c23e', '#36b9cc'] // Blue for Available, Yellow for Busy
                }]
            }
        });
        new Chart(document.getElementById('statusChart'), {
            type: 'pie',
            data: {
                labels: @json($byStatus->pluck('label')),
                datasets: [{
                    data: @json($byStatus->pluck('total')),
                    backgroundColor: ['#1cc88a', '#e74a3b']
                }]
            }
        });
        new Chart(document.getElementById('typeChart'), {
            type: 'doughnut',
            data: {
                labels: @json($byType->pluck('label')),
                datasets: [{
                    data: @json($byType->pluck('total')),
                    backgroundColor: colors
                }]
            }
        });
        new Chart(document.getElementById('stateChart'), {
            type: 'bar',
            data: {
                labels: @json($byState->pluck('label')),
                datasets: [{
                    label: 'Outlets',
                    data: @json($byState->pluck('total')),
                    backgroundColor: '#4e73df'
                }]
            }
        });
        new Chart(document.getElementById('brandChart'), {
            type: 'bar',
            data: {
                labels: @json($byBrand->pluck('label')),
                datasets: [{
                    label: 'Outlets',
                    data: @json($byBrand->pluck('total')),
                    backgroundColor: '#36b9cc'
                }]
            },
            options: {
                indexAxis: 'y',
            }
        });
    </script>
    
</x-app-layout>