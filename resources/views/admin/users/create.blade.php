<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add User') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Your form content goes here -->
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf
                        <div class="mb-4">
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" class="form-control" type="text" name="name" required autofocus />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" class="form-control" type="email" name="email" required />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="password" :value="__('Password')" />
                            <x-text-input id="password" class="form-control" type="password" name="password" required />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="role" :value="__('Role')" />
                            <select name="role" id="role" class="form-control rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <x-input-label :value="__('Outlet Access')" />
                            <p class="text-sm text-gray-500 mb-2">Ignored for roles/users granted "outlets.view-all" (e.g. admin) — they see every outlet regardless.</p>
                            <div class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto border rounded-md p-3">
                                @forelse($outlets as $outlet)
                                    <label class="flex items-center">
                                        <input type="checkbox" name="outlet_ids[]" value="{{ $outlet->id }}" class="form-control mr-2" />
                                        {{ $outlet->outlet_name }}
                                    </label>
                                @empty
                                    <span class="text-sm text-gray-400">No outlets found.</span>
                                @endforelse
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('users.index') }}" class="text-gray-600 hover:underline">← Back to List</a>
                            <x-primary-button class="ml-4">
                                {{ __('Create') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>