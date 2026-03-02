<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit User') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <form method="POST" action="{{ route('users.update', $user->id) }}">
                    @csrf
                    @method('PUT')

                    <!-- Name -->
                    <div class="mb-4">
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                                 value="{{ old('name', $user->name) }}" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <!-- Email -->
                    <div class="mb-4">
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                                 value="{{ old('email', $user->email) }}" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <!-- Roles -->
                    <div class="mb-4">
                        <x-input-label for="role" :value="__('Role')" />
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($roles as $role)
                                <label class="flex items-center">
                                    <input type="checkbox" name="role" value="{{ $role->id }}"
                                           {{ $user->roles->contains($role->id) ? 'checked' : '' }}
                                           class="form-control mr-2" />
                                    {{ $role->name }}
                                </label>
                            @endforeach
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>
                    </div>
                    <!-- Submit -->
                    <div class="flex justify-end">
                        <a href="{{ route('users.index') }}" class="text-gray-600 hover:underline">← Back to List</a>
                        <x-primary-button class="ml-3">
                            {{ __('Update User') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
