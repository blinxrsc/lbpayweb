<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Confirm CSV import') }}
        </h2>
    </x-slot>
    <div class="p-6">
        <table class="min-w-full border">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-4 py-2">Email</th>
                    <th class="border px-4 py-2">Name</th>
                    <th class="border px-4 py-2">Phone ID</th>
                </tr>
            </thead>
            <tbody>
                @foreach($previewData as $row)
                    <tr>
                        <td class="border px-4 py-2">{{ $row['Name'] }}</td>
                        <td class="border px-4 py-2">{{ $row['Email'] }}</td>
                        <td class="border px-4 py-2">{{ $row['Phone Country CodePhone Number'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <form action="{{ route('customers.import.confirm') }}" method="POST" class="mt-4">
            @csrf
            <button type="submit" class="bg-emerald-500 text-white px-4 py-2 rounded">Confirm & Import</button>
            <a href="{{ route('customers.index') }}" class="text-gray-500 ml-4">Cancel</a>
        </form>
    </div>
</x-app-layout>