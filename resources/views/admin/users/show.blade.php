<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('User Profile') }}
            </h2>
            <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700 transition">
                &larr; Back to Directory
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">    
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden">   
                <div class="h-32 bg-gradient-to-r from-indigo-500 to-purple-600"></div>
                <div class="px-8 pb-8">
                    <div class="relative flex justify-between items-end -mt-12 mb-6">
                        <div class="p-1 bg-white rounded-full">
                            <div class="h-24 w-24 rounded-full bg-gray-200 flex items-center justify-center border-4 border-white shadow-sm">
                                <span class="text-3xl font-bold text-gray-400">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </span>
                            </div>
                        </div>
                        @can('users.update')
                        <div class="flex space-x-3">
                            <a href="{{ route('users.edit', $user->id) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                Edit Profile
                            </a>
                        </div>
                        @endcan
                    </div>

                    <div class="mb-8">
                        <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
                        <p class="text-gray-500">{{ $user->email }}</p>
                    </div>

                    <hr class="border-gray-100 mb-8">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-white shadow sm:rounded-lg p-6">
                            <h3 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-4">Account Details</h3>
                            <dl class="space-y-4">
                                <div>
                                    <dt class="text-sm text-gray-500">Member Since</dt>
                                    <dd class="text-sm font-semibold text-gray-800">{{ $user->created_at->format('M d, Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Account Status</dt>
                                    <dd>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-4">Assigned Roles</h3>
                            <div class="flex flex-wrap gap-2">
                                @forelse($user->roles as $role)
                                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        <svg class="w-2 h-2 mr-2 fill-indigo-400" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"></circle></svg>
                                        {{ $role->name }}
                                    </span>
                                @empty
                                    <span class="text-sm text-gray-400 italic">No roles assigned</span>
                                @endforelse
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            @can('users.delete')
            <div class="mt-8 bg-white shadow-sm border border-red-100 rounded-2xl p-6 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Delete Account</h3>
                    <p class="text-sm text-gray-500">Once deleted, all data will be permanently removed.</p>
                </div>
                <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                    @csrf
                    @method('DELETE')
                    <button class="text-red-600 font-semibold hover:text-red-800 transition">Remove User</button>
                </form>
            </div>
            @endcan
        </div>
    </div>
</x-app-layout>