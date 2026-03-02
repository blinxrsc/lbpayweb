<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Legal & Terms Management') }}
            </h2>
            <button type="button" 
                class="inline-flex items-center px-2 py-1 text-red-600 hover:text-red-800"
                title="Delete" 
                x-data
                x-on:click="$dispatch('open-modal', 'create-policy')"
            > Add New Policy
                <x-heroicon-o-trash class="w-5 h-5"/> 
            </button>
            <x-primary-button type="button" x-data x-on:click="$dispatch('open-modal', 'create-policy')">
                Add New Policy
            </x-primary-button>
            <x-modal name="create-policy" focusable>
                <form method="post" action="{{ route('admin.terms.store') }}" class="p-6">
                    @csrf
                    <h2 class="text-lg font-bold text-gray-900 mb-4 text-left">Create New Legal Policy</h2>
                    
                    <div class="mb-4 text-left">
                        <label class="block text-sm font-medium text-gray-700">Title (e.g., Scam Notice)</label>
                        <input type="text" name="title" placeholder="Privacy Notice" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <div class="mb-4 text-left">
                        <label class="block text-sm font-medium text-gray-700">Policy Content</label>
                        <textarea name="content" rows="8" placeholder="Enter the full text here..." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required></textarea>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                        <x-primary-button>Publish Policy</x-primary-button>
                    </div>
                </form>
            </x-modal>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-4 font-semibold text-gray-700">Policy Title</th>
                            <th class="p-4 font-semibold text-gray-700">Slug</th>
                            <th class="p-4 font-semibold text-gray-700 text-center">Current Version</th>
                            <th class="p-4 font-semibold text-gray-700 text-center">Status</th>
                            <th class="p-4 font-semibold text-gray-700 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($terms as $term)
                        <tr>
                            <td class="p-4 font-medium">{{ $term->title }}</td>
                            <td class="p-4 text-gray-500 text-sm">{{ $term->slug }}</td>
                            <td class="p-4 text-center">
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-bold">v{{ $term->version }}</span>
                            </td>
                            <td class="p-4 text-center">
                                @if($term->is_active)
                                    <span class="text-green-600 flex items-center justify-center italic text-sm">Active</span>
                                @else
                                    <span class="text-gray-400 flex items-center justify-center italic text-sm">Inactive</span>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'edit-term-{{ $term->id }}')" class="text-indigo-600 hover:text-indigo-900 font-semibold text-sm">
                                    Edit Content
                                </button>
                            </td>
                        </tr>

                        <x-modal name="edit-term-{{ $term->id }}" focusable>
                            <form method="post" action="{{ route('admin.terms.update', $term->id) }}" class="p-6 text-left">
                                @csrf @method('PATCH')
                                <h2 class="text-lg font-bold text-gray-900 mb-4">Edit {{ $term->title }}</h2>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700">Content</label>
                                    <textarea name="content" rows="10" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>{{ $term->content }}</textarea>
                                </div>

                                <div class="flex items-center mb-4 p-3 bg-red-50 rounded border border-red-100">
                                    <input type="checkbox" name="major_update" id="major{{ $term->id }}" value="1" class="rounded border-gray-300 text-red-600 shadow-sm">
                                    <label for="major{{ $term->id }}" class="ml-2 text-sm text-red-700 font-semibold">
                                        This is a Major Version Update
                                    </label>
                                    <p class="ml-2 text-xs text-red-500 italic">(Increments version and forces new signature logic)</p>
                                </div>

                                <div class="flex justify-end space-x-3">
                                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                    <x-primary-button>Save Changes</x-primary-button>
                                </div>
                            </form>
                        </x-modal>
                        @empty
                        <tr>
                            <td colspan="5" class="p-10 text-center text-gray-500 italic">No policies found. Click "Add New Policy" to start.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    


    





</x-app-layout>