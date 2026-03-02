<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Role') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Your form content goes here -->
                    <form action="{{ route('roles.store') }}" method="POST">
                        @csrf
                        <div class="form-group mt-4">
                            <x-input-label for="name" :value="__('Role Name')" />
                            <x-text-input id="name" class="form-control" type="text" name="name" required autofocus />
                        </div>

                        <div class="form-group mt-4">
                            <x-input-label for="desc" :value="__('Description')" />
                            <x-text-input id="desc" class="block mt-1 w-full" type="text" name="desc" />
                        </div>

                        <h5 class="mt-4">Module Permissions</h5>
                        <hr>
                        <div class="row">
                            @foreach($groupedPermissions as $module => $permissions)
                                <div class="col-md-12 mb-4">
                                    <div class="card shadow-sm">
                                        <div class="card-header bg-light">
                                            <strong class="text-uppercase">{{ $module }}</strong>
                                        </div>
                                        <div class="card-body d-flex flex-wrap flex items-center">
                                            @foreach($permissions as $permission)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                        name="permissions[]" 
                                                        value="{{ $permission->name }}"
                                                        id="perm-{{ $permission->id }}"
                                                    >
                                                    <label class="form-check-label" for="perm-{{ $permission->id }}">
                                                        <span class="ml-2 text-sm text-gray-600">{{ str_replace($module . '.', '', $permission->name) }}</span>
                                                    </label>
                                                    <span class="ml-2 text-sm text-gray-600" />
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('roles.index') }}" class="text-gray-600 hover:underline">← Back to List</a>
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