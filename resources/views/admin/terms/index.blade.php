<x-app-layout>
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Manage Legal Agreements') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-end items-right mb-6">
                    <button type="button" 
                        class="bg-blue-600 text-white px-4 py-2 rounded"
                        title="Create Policy" 
                        x-data
                        x-on:click="$dispatch('open-modal', 'create-term')"
                    >Add New Policy
                    </button>
                </div>
                <x-modal name="create-term" focusable>
                    <form action="{{ route('admin.terms.store') }}" method="POST" class="p-6">
                        @csrf
                        <h3 class="text-lg font-bold mb-4">Create New Policy</h3>
                        <div class="mb-4">
                            <label>Policy Title</label>
                            <input type="text" name="title" class="w-full border-gray-300 rounded" placeholder="e.g., Refund Policy, Terms and Condition Notice" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Policy Content</label>
                            <div class="mt-1 block w-full h-48 rounded-md shadow-sm border-gray-300">
                                <textarea name="content" id="editor-create" required></textarea>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                            <x-primary-button>Publish Policy</x-primary-button>
                        </div>
                    </form>
                </x-modal>

                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b bg-gray-50">
                            <th class="p-3">Title</th>
                            <th class="p-3">Version</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($terms as $term)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3 font-semibold">{{ $term->title }}</td>
                            <td class="p-3">v{{ $term->version }}</td>
                            <td class="p-3">
                                <span class="{{ $term->is_active ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $term->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </td>
                            <td class="p-3">
                                <button type="button" 
                                    class="text-blue-600 hover:underline"
                                    title="Edit Policy" 
                                    x-data
                                    x-on:click="$dispatch('open-modal', 'edit-term-{{ $term->id }}')"
                                >Edit Policy
                                </button>
                            </td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <p class="text-gray-500 text-lg font-medium">
                                            <x-heroicon-m-exclamation-circle />No transactions found
                                        </p>
                                        <p class="text-gray-400 text-sm">Try adjusting your filters or resetting the search.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <x-modal name="edit-term-{{ $term->id }}" maxWidth="2xl" focusable>
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 text-left">Edit {{ $term->title }}</h3>
                        <div class="mt-6 flex justify-end gap-3">
                            <form action="{{ route('admin.terms.update', $term->id) }}" method="POST" class="p-6" style="display:inline;">
                                @csrf @method('PATCH')
                                <div class="mb-4 text-left">
                                    <label class="block text-sm font-medium text-gray-700">Policy Content</label>
                                    <textarea 
                                        name="content" 
                                        id="editor-edit-{{ $term->id }}"
                                        class="legal-content prose w-full h-64 border-gray-300 rounded mb-4"
                                    >{{ $term->content }}
                                    </textarea>
                                    <input type="checkbox" name="major_update" id="major{{ $term->id }}" class="mr-2">
                                    <label for="major{{ $term->id }}" class="text-sm text-red-600 font-bold">This is a major update (Forces customers to re-agree)</label>
                                </div>
                                <div class="flex justify-end space-x-2">
                                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                    <x-primary-button>Update Policy</x-primary-button>
                                </div>
                            </form>
                        </div>
                    </div>
                </x-modal>
            </div>
        </div>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize the Create Editor
        ClassicEditor
            .create(document.querySelector('#editor-create'), {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo']
            })
            .then(editor => {
                // SYNC DATA: This line ensures the textarea stays updated
                editor.model.document.on('change:data', () => {
                    document.querySelector('#editor-create').value = editor.getData();
                });
            })
            .catch(error => {
                console.error(error);
            });

        // Initialize Edit Editors for existing terms
        @foreach($terms as $term)
            ClassicEditor
                .create(document.querySelector('#editor-edit-{{ $term->id }}'), {
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo']
                })
                .then(editor => {
                    // Set height via JS
                    editor.editing.view.change(writer => {
                        writer.setStyle('height', '450px', editor.editing.view.document.getRoot());
                    });

                    // Sync data
                    editor.model.document.on('change:data', () => {
                        document.querySelector('#editor-edit-{{ $term->id }}').value = editor.getData();
                    });
                })
                .catch(error => { console.error(error); });
        @endforeach
    });
</script>
    
</x-app-layout>