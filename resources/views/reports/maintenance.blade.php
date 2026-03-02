<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Maintenance Dashboard: {{ $stats['period'] }}</h2>
        </h2>
    </x-slot>
    <div class="flex justify-end mb-4">
        <a href="{{ route('reports.maintenance.pdf', ['month' => $stats['month'], 'year' => $stats['year']]) }}" 
        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Download PDF Report
        </a>
    </div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <p class="text-sm text-gray-500 uppercase font-bold">Faults Reported</p>
                    <p class="text-3xl font-bold text-red-600">{{ $stats['total_faulty'] }}</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <p class="text-sm text-gray-500 uppercase font-bold">Repairs Finished</p>
                    <p class="text-3xl font-bold text-green-600">{{ $stats['total_repaired'] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-bold mb-4">Hardware Reliability (Faults by Model)</h3>
                    <div class="h-64">
                        <canvas id="modelChart"></canvas>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-bold mb-4">Maintenance Ratio</h3>
                    <div class="h-64 flex justify-center">
                        <canvas id="ratioChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Data from Service for Bar Chart
        const modelData = @json($stats['faults_by_model']);
        const modelLabels = Object.keys(modelData);
        const modelValues = Object.values(modelData);

        // 1. Model Reliability Bar Chart
        new Chart(document.getElementById('modelChart'), {
            type: 'bar',
            data: {
                labels: modelLabels,
                datasets: [{
                    label: 'Number of Failures',
                    data: modelValues,
                    backgroundColor: 'rgba(239, 68, 68, 0.5)',
                    borderColor: 'rgb(239, 68, 68)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });

        // 2. Maintenance Ratio Pie Chart
        new Chart(document.getElementById('ratioChart'), {
            type: 'doughnut',
            data: {
                labels: ['Faulty', 'Repaired'],
                datasets: [{
                    data: [{{ $stats['total_faulty'] }}, {{ $stats['total_repaired'] }}],
                    backgroundColor: ['#EF4444', '#10B981'],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
            }
        });
    </script>
</x-app-layout>