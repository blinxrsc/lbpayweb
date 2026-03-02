<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Device #{{ $deviceOutlet->device_serial_number }}
        </h2>
    </x-slot>

    <div class="bg-white shadow-md rounded-lg p-6 max-w-lg mx-auto">
        <!-- Transaction Details -->
        <table class="w-full text-sm border">
            <tr>
                <td class="border px-3 py-2">Outlet</td>
                <td class="border px-3 py-2">{{ optional($deviceOutlet->outlet)->outlet_name }}</td>
            </tr>
            <tr>
                <td class="border px-3 py-2">Brand</td>
                <td class="border px-3 py-2">{{ optional(optional($deviceOutlet->outlet)->brand)->name }}</td>
            </tr>
            <tr>
                <td class="border px-3 py-2">Ownership</td>
                <td class="border px-3 py-2">{{ optional($deviceOutlet->outlet)->type }}</td>
            </tr>
            <tr>
                <td class="border px-3 py-2">Machine #</td>
                <td class="border px-3 py-2">{{ ucfirst($deviceOutlet->machine_type) }} {{ ucfirst($deviceOutlet->machine_num) }} ({{ ucfirst($deviceOutlet->machine_name) }})</td>
            </tr>
            <tr>
                <td class="border px-3 py-2">Status</td>
                <td class="border px-3 py-2">{{ ucfirst($deviceOutlet->status) }}</td>
            </tr>
            <tr>
                <td class="border px-3 py-2">Availability</td>
                <td class="border px-3 py-2">{{ ucfirst($deviceOutlet->availability) }}</td>
            </tr>
            <tr>
                <td class="border px-3 py-2">Created At</td>
                <td class="border px-3 py-2">{{ $deviceOutlet->created_at }}</td>
            </tr>
            <tr>
                <td class="border px-3 py-2">Updated At</td>
                <td class="border px-3 py-2">{{ $deviceOutlet->updated_at }}</td>
            </tr>
        </table>

        <!-- Actions -->
        <div class="mt-6 flex space-x-3">
            <form method="POST" action="{{ route('admin.device-transactions.activate', $deviceOutlet) }}">
                @csrf
                <x-primary-button>Activate</x-primary-button>
            </form>
        </div>
        <div class="mt-6 flex justify-end">
            <a href="{{ url()->previous() ?? route('outlets.index') }}"
            class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md">
                ← Back
            </a>
        </div>

    </div>
</x-app-layout>