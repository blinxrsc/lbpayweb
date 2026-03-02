<x-app-layout>
    <x-slot name="header"><h2>Customer Details</h2></x-slot>
    <div class="p-6 bg-white">
        <div class="grid grid-cols-2 gap-6">
            <div>
                <strong>Name:</strong> {{ $customer->name }}
            </div>
            <div>
                <strong>Email:</strong> {{ $customer->email }}
            </div>
            <div>
                <strong>Phone:</strong> {{ $customer->phone_country_code }} {{ $customer->phone_number }}
            </div>
            <div>
                <strong>Username:</strong> {{ $customer->username }}
            </div>
            <div>
                <strong>Birthday:</strong> {{ $customer->birthday }}
            </div>
            <div>
                <strong>Tags:</strong> {{ $customer->tags }}
            </div>
            <div>
                <strong>Referral Code:</strong> {{ $customer->referral_code }}
            </div>
            <div>
                <strong>Sign In Method:</strong> {{ ucfirst($customer->sign_in) }}
            </div>
            <div>
                <strong>Status:</strong> {{ ucfirst($customer->status) }}
            </div>
            <div>
                <strong>Created At:</strong> {{ $customer->created_at }}
            </div>
            <div>
                <strong>Updated At:</strong> {{ $customer->updated_at }}
            </div>
        </div>

        <div class="mt-6 flex space-x-4">
            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning">Edit</a>
            <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
            <a href="{{ route('customers.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
</x-app-layout>