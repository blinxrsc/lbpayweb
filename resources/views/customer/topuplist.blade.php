<x-customer-layout>
    <x-slot name="header">
        <x-breadcrumbs :links="[
            ['url' => route('customer.dashboard'), 'label' => 'Home', 'icon' => 'heroicon-o-home'],
            ['url' => '#', 'label' => 'Add Topup'],
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
        </div>
        <!-- Top-up Form -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-700">Add Top‑up</h3>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($packages as $p)
                    <button type="button"
                            onclick="initiateTopup({{ $p->id }}, {{ $p->topup_amount }})"
                            class="w-full text-left bg-yellow-50 px-4 py-3 rounded-lg border border-gray-200
                                hover:border-indigo-500 hover:bg-indigo-50
                                text-sm text-gray-700 shadow-sm cursor-pointer">
                        <p class="font-semibold">Top‑up RM {{ number_format($p->topup_amount,2) }}</p>
                        <p>Bonus RM {{ number_format($p->bonus_amount,2) }}</p>
                    </button>
                @endforeach
            </div>
        </div>

        <script>
            function initiateTopup(packageId, amount) {
                // Example: redirect to your Laravel route with package info
                // You can also use AJAX if you prefer
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = "{{ route('ewallet.topup.initiate') }}";

                // Add CSRF token
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = "{{ csrf_token() }}";
                form.appendChild(csrf);

                // Add package ID
                const pkg = document.createElement('input');
                pkg.type = 'hidden';
                pkg.name = 'package_id';
                pkg.value = packageId;
                form.appendChild(pkg);

                // Add amount
                const amt = document.createElement('input');
                amt.type = 'hidden';
                amt.name = 'amount';
                amt.value = amount;
                form.appendChild(amt);

                document.body.appendChild(form);
                form.submit();
            }
        </script>
    </div>
</x-customer-layout>

