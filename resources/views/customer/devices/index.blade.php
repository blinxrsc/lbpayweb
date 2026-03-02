<x-customer-layout>
    <x-slot name="header">
        <x-breadcrumbs :links="[
            ['url' => route('customer.dashboard'), 'label' => 'Home', 'icon' => 'heroicon-o-home'],
            ['url' => '#', 'label' => 'Outlet Devices'],
        ]" />
    </x-slot>

    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Devices at {{ $outlet->outlet_name }}
    </h2>
    <!-- Flash Messages -->
    @if(session('success'))
        <div class="mb-4 p-4 rounded bg-green-100 border border-green-400 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 rounded bg-red-100 border border-red-400 text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @foreach($deviceOutlets as $deviceOutlet)
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <div class="flex items-center gap-3">
                <!-- Conditional Icon -->
                <div class="bg-blue-100 p-2 rounded-lg flex-shrink-0">
                    @if(strtolower($deviceOutlet->machine_type) === 'washer')
                        <x-mdi-washing-machine class="w-8 h-8 text-blue-600" />
                    @elseif(strtolower($deviceOutlet->machine_type) === 'dryer')
                        <x-mdi-tumble-dryer class="w-8 h-8 text-blue-600" />
                    @else
                        <i class="mdi mdi-help-circle w-8 h-8 text-gray-700"></i>
                    @endif
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-700">
                        <a href="{{ route('customer.payment.confirm', $deviceOutlet->id) }}" class="text-blue-600 hover:underline">
                            {{ ucfirst($deviceOutlet->machine_type) }} {{ ucfirst($deviceOutlet->machine_num) }} ({{ $deviceOutlet->machine_name }})
                        </a>
                    </h3>

                    <!-- Status Lines -->
                    <p class="text-sm mt-1">
                        @if($deviceOutlet->status)
                            <span class="text-green-600 font-medium">● Online</span>
                        @else
                            <span class="text-red-600 font-medium">● Offline</span>
                        @endif
                    </p>
                    <p class="text-sm">
                        @if($deviceOutlet->availability)
                            <span class="text-orange-600 font-medium">Busy</span>
                        @else
                            <span class="text-green-600 font-medium">Available</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endforeach
</x-customer-layout>
