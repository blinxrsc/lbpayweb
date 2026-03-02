<x-app-layout>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Report of Member Transaction (Weekly/Monthly)') }}
        </h2>
    </x-slot>

    <div class="py-6" x-data="memberReport()" x-init="loadMonthly()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <x-primary-button @click="loadWeekly" class="bg-indigo-600 text-white">Weekly</x-primary-button>
                <x-primary-button @click="loadMonthly" class="bg-green-600 text-white">Monthly</x-primary-button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6" x-show="reportData.length > 0">
                <!-- Total Transaction / Usage -->
                <div class="bg-white p-6 rounded-lg shadow flex flex-col">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2 gap-3">
                            <!-- Credit Card Icon -->
                            <x-heroicon-s-document-currency-dollar class="w-6 h-6"/>
                            <h4 class="text-sm font-semibold text-gray-600">Total Transaction Amount</h4>
                        </div>
                        <span :class="trendClass(reportData, 'total_transaction_amount')" 
                            x-text="trend(reportData, 'total_transaction_amount')"></span>
                    </div>
                    <p class="text-3xl font-bold text-indigo-700 mt-2" 
                    x-text="formatCurrency(sum(reportData.map(r => r.total_transaction_amount)))"></p>
                    <p class="text-xs text-gray-500 mt-1" x-text="dateRange"></p>

                    <div class="flex items-center justify-between mt-4">
                        <div class="flex items-center space-x-2 gap-3">
                            <!-- Cash Icon -->
                            <x-heroicon-s-folder-open class="w-6 h-6"/>
                            <h4 class="text-sm font-semibold text-gray-600">Total Usage</h4>
                        </div>
                        <span :class="trendClass(reportData, 'total_usage')" 
                            x-text="trend(reportData, 'total_usage')"></span>
                    </div>
                    <p class="text-3xl font-bold text-indigo-700 mt-2" 
                    x-text="formatCurrency(sum(reportData.map(r => r.total_usage)))"></p>
                </div>

                <!-- Bonus Credited / Bonus Usage -->
                <div class="bg-white p-6 rounded-lg shadow flex flex-col">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2 gap-3">
                            <!-- Gift Icon -->
                            <x-heroicon-s-gift class="w-6 h-6"/>
                            <h4 class="text-sm font-semibold text-gray-600">Bonus Credited</h4>
                        </div>
                        <span :class="trendClass(reportData, 'bonus_credited')" 
                            x-text="trend(reportData, 'bonus_credited')"></span>
                    </div>
                    <p class="text-3xl font-bold text-emerald-500 mt-2" 
                    x-text="formatCurrency(sum(reportData.map(r => r.bonus_credited)))"></p>
                    <p class="text-xs text-gray-500 mt-1" x-text="dateRange"></p>

                    <div class="flex items-center justify-between mt-4">
                        <div class="flex items-center space-x-2 gap-3">
                            <!-- Minus Icon -->
                            <x-heroicon-s-currency-dollar class="w-6 h-6"/>
                            <h4 class="text-sm font-semibold text-gray-600">Bonus Usage</h4>
                        </div>
                        <span :class="trendClass(reportData, 'bonus_usage')" 
                            x-text="trend(reportData, 'bonus_usage')"></span>
                    </div>
                    <p class="text-3xl font-bold text-emerald-500 mt-2" 
                    x-text="formatCurrency(sum(reportData.map(r => r.bonus_usage)))"></p>
                </div>

                <!-- Registrations / Active Members -->
                <div class="bg-white p-6 rounded-lg shadow flex flex-col">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2 gap-3">
                            <!-- User Add Icon -->
                            <x-heroicon-s-user-plus class="w-6 h-6"/>
                            <h4 class="text-sm font-semibold text-gray-600">New Registrations</h4>
                        </div>
                        <span :class="trendClass(reportData, 'new_registrations')" 
                            x-text="trend(reportData, 'new_registrations')"></span>
                    </div>
                    <p class="text-3xl font-bold text-blue-500 mt-2" 
                    x-text="sum(reportData.map(r => r.new_registrations))"></p>
                    <p class="text-xs text-gray-500 mt-1" x-text="dateRange"></p>

                    <div class="flex items-center justify-between mt-4">
                        <div class="flex items-center space-x-2 gap-3">
                            <!-- Users Icon -->
                            <x-heroicon-s-identification class="w-6 h-6"/>
                            <h4 class="text-sm font-semibold text-gray-600">Active Members</h4>
                        </div>
                        <span :class="trendClass(reportData, 'active_members')" 
                            x-text="trend(reportData, 'active_members')"></span>
                    </div>
                    <p class="text-3xl font-bold text-blue-500 mt-2" 
                    x-text="sum(reportData.map(r => r.active_members))"></p>
                </div>
            </div>
            <!-- Charts -->
            <div class="grid grid-cols-2 gap-8">
                <div><h3>Total Transaction Amount</h3><canvas id="chartTransaction"></canvas></div>
                <div><h3>Total Usage</h3><canvas id="chartUsage"></canvas></div>
                <div><h3>Total Bonus Credited</h3><canvas id="chartBonusCredited"></canvas></div>
                <div><h3>Total Bonus Usage</h3><canvas id="chartBonusUsage"></canvas></div>
                <div><h3>New Registrations</h3><canvas id="chartRegistrations"></canvas></div>
                <div><h3>Active Members</h3><canvas id="chartActive"></canvas></div>
            </div>
            <!-- Table -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <table class="table-auto w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-4 py-2">Period</th>
                                <th class="px-4 py-2">Total Tx Amount</th>
                                <th class="px-4 py-2">Usage</th>
                                <th class="px-4 py-2">Bonus Credited</th>
                                <th class="px-4 py-2">Bonus Usage</th>
                                <th class="px-4 py-2">Active Members</th>
                                <th class="px-4 py-2">Active %</th>
                                <th class="px-4 py-2">New Registrations</th>
                                <th class="px-4 py-2">Total Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in reportData" :key="row.period">
                                <tr>
                                    <td class="px-4 py-2 text-center" x-text="row.period"></td>
                                    <td class="px-4 py-2 text-center" x-text="row.total_transaction_amount"></td>
                                    <td class="px-4 py-2 text-center" x-text="row.total_usage"></td>
                                    <td class="px-4 py-2 text-center" x-text="row.bonus_credited"></td>
                                    <td class="px-4 py-2 text-center" x-text="row.bonus_usage"></td>
                                    <td class="px-4 py-2 text-center" x-text="row.active_members"></td>
                                    <td class="px-4 py-2 text-center" x-text="row.active_member_percentage + '%'"></td>
                                    <td class="px-4 py-2 text-center" x-text="row.new_registrations"></td>
                                    <td class="px-4 py-2 text-center" x-text="row.total_registered"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Alpine Script -->
    <script>
    function memberReport() 
    {
        return {
            reportData: [],
            reportPeriod: '',
            dateRange: '',
            charts: {},

            loadWeekly() {
                fetch('{{ route('reports.members.weekly') }}')
                    .then(res => res.json())
                    .then(data => {
                        this.reportData = data;
                        this.reportPeriod = 'Weekly';
                        this.dateRange = this.formatRange(data, 'week');
                        this.updateCharts(data);
                    });
            },

            loadMonthly() {
                fetch('{{ route('reports.members.monthly') }}')
                    .then(res => res.json())
                    .then(data => {
                        this.reportData = data;
                        this.reportPeriod = 'Monthly';
                        this.dateRange = this.formatRange(data, 'month');
                        this.updateCharts(data);
                    });
            },

            sum(values) {
                return values.reduce((a, b) => a + (parseFloat(b) || 0), 0).toFixed(2);
            },

            formatCurrency(value) {
                return new Intl.NumberFormat('en-MY', { 
                    style: 'currency', 
                    currency: 'MYR' 
                }).format(value);
            },

            trend(data, field) {
                if (data.length < 2) return 'NA%';
                const latest = parseFloat(data[data.length - 1][field]) || 0;
                const prev = parseFloat(data[data.length - 2][field]) || 0;
                if (prev === 0) return 'NA%';
                const change = ((latest - prev) / prev * 100).toFixed(1);
                return (change >= 0 ? '↑ ' : '↓ ') + change + '%';
            },

            trendClass(data, field) {
                if (data.length < 2) return 'text-gray-400';
                const latest = parseFloat(data[data.length - 1][field]) || 0;
                const prev = parseFloat(data[data.length - 2][field]) || 0;
                if (prev === 0) return 'text-gray-400';
                return latest >= prev ? 'text-green-600 font-bold' : 'text-red-600 font-bold';
            },

            formatRange(data, type) {
                if (!data.length) return '';
                const first = data[0].period;
                const last = data[data.length - 1].period;
                return type === 'week'
                    ? `Week Range: ${first} – ${last}`
                    : `Month Range: ${first} – ${last}`;
            },

            updateCharts(data) {
                const labels = data.map(r => r.period);
                this.renderChart('chartTransaction', labels, data.map(r => r.total_transaction_amount), 'Total Transaction Amount', '#4F46E5');
                this.renderChart('chartUsage', labels, data.map(r => r.total_usage), 'Total Usage', '#6366F1');
                this.renderChart('chartBonusCredited', labels, data.map(r => r.bonus_credited), 'Bonus Credited', '#10B981');
                this.renderChart('chartBonusUsage', labels, data.map(r => r.bonus_usage), 'Bonus Usage', '#F59E0B');
                this.renderChart('chartRegistrations', labels, data.map(r => r.new_registrations), 'New Registrations', '#3B82F6');
                this.renderChart('chartActive', labels, data.map(r => r.active_members), 'Active Members', '#EF4444');
            },

            renderChart(canvasId, labels, values, label, color) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return;
                const ctx = canvas.getContext('2d');

                if (this.charts[canvasId]) {
                    this.charts[canvasId].destroy();
                }

                this.charts[canvasId] = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: label,
                            data: values,
                            backgroundColor: color
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: true }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }

        }
    }
    </script>
</x-app-layout>