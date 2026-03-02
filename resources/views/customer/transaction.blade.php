<x-customer-layout>
    <x-slot name="header">
        <x-breadcrumbs :links="[
            ['url' => route('customer.dashboard'), 'label' => 'Home', 'icon' => 'heroicon-o-home'],
            ['url' => '#', 'label' => 'My Transaction'],
        ]" />
    </x-slot>


    <div class="space-y-6 pb-20"> <!-- padding bottom for nav bar -->

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="bg-green-100 text-green-700 px-4 py-2 rounded">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded">
                {{ session('error') }}
            </div>
        @endif

        <!-- Transaction History -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Recent Transactions</h3>
            <ul class="divide-y divide-gray-200">
                @foreach(auth('customer')->user()->ewalletTransactions()->latest()->take(10)->get() as $txn)
                    <li class="py-3 flex justify-between items-center">
                        <div>
                            <p class="text-sm font-semibold">{{ ucfirst(str_replace('_',' ', $txn->type)) }}</p>
                            <p class="text-xs text-gray-500">{{ $txn->created_at->format('d M Y H:i') }}</p>
                        </div>
                        <p class="text-sm font-bold {{ $txn->isDebit() ? 'text-red-600' : 'text-blue-600' }}">
                            RM {{ number_format($txn->amount, 2) }}
                            @if($txn->isDebit()) (-) @endif
                        </p>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</x-customer-layout>

