<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Device Details') }}
            </h2>
            <span class="px-3 py-1 text-xs font-bold uppercase rounded-full {{ $deviceOutlet->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                {{ $deviceOutlet->status }}
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                
                <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-indigo-600 rounded-lg shadow-indigo-200 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wider font-semibold">Serial Number</p>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $deviceOutlet->device_serial_number }}</h3>
                        </div>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <h4 class="text-sm font-bold text-indigo-600 uppercase">Outlet Information</h4>
                        <div class="flex flex-col">
                            <span class="text-gray-500 text-xs">Name</span>
                            <span class="font-medium text-gray-900">{{ optional($deviceOutlet->outlet)->outlet_name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-gray-500 text-xs">Brand & Ownership</span>
                            <span class="font-medium text-gray-900">
                                {{ optional(optional($deviceOutlet->outlet)->brand)->name }} 
                                <span class="text-gray-400 mx-1">|</span> 
                                <span class="text-sm italic text-gray-600">{{ $deviceOutlet->outlet->type['name'] }}</span>
                            </span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h4 class="text-sm font-bold text-indigo-600 uppercase">Hardware Details</h4>
                        <div class="flex flex-col">
                            <span class="text-gray-500 text-xs">Machine Type & No.</span>
                            <span class="font-medium text-gray-900">
                                {{ ucfirst($deviceOutlet->machine_type) }} #{{ $deviceOutlet->machine_num }}
                            </span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-gray-500 text-xs">Availability</span>
                            <div class="flex items-center mt-1">
                                <div class="w-2 h-2 rounded-full mr-2 {{ $deviceOutlet->availability === 'available' ? 'bg-green-500' : 'bg-orange-500' }}"></div>
                                <span class="font-medium text-gray-900">{{ ucfirst($deviceOutlet->availability) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col md:flex-row md:justify-between text-xs text-gray-400">
                    <span>Created: {{ $deviceOutlet->created_at->format('M d, Y H:i') }}</span>
                    <span>Last Updated: {{ $deviceOutlet->updated_at->diffForHumans() }}</span>
                </div>

                <div class="p-6 flex items-center justify-between border-t border-gray-100">
                    <a href="{{ url()->previous() ?? route('outlets.index') }}"
                        class="text-sm font-semibold text-gray-600 hover:text-gray-900 transition flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Back to List
                    </a>

                    <div class="flex space-x-3">
                        @if($deviceOutlet->status !== 'active')
                            <form method="POST" action="{{ route('admin.device-transactions.activate', $deviceOutlet) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition">
                                    Activate Device
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>