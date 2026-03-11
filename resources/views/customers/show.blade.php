<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Customer Profile') }}
            </h2>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $customer->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                    {{ ucfirst($customer->status) }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-xl border border-gray-200 overflow-hidden">
                
                <div class="p-8 bg-gradient-to-r from-slate-50 to-white border-b border-gray-100">
                    <div class="flex flex-col md:flex-row items-center gap-6">
                        <div class="h-24 w-24 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 text-3xl font-bold shadow-inner">
                            {{ strtoupper(substr($customer->name, 0, 1)) }}
                        </div>
                        
                        <div class="flex-1 text-center md:text-left">
                            <h1 class="text-2xl font-bold text-gray-900">{{ $customer->name }}</h1>
                            <p class="text-gray-500 font-medium">@<span>{{ $customer->username }}</span></p>
                            <div class="mt-2 flex flex-wrap justify-center md:justify-start gap-2">
                                @foreach(explode(',', $customer->tags) as $tag)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                        #{{ trim($tag) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-2">
                            <span class="text-xs text-gray-400 uppercase tracking-widest font-bold">Referral Code</span>
                            <code class="px-3 py-1 bg-amber-50 border border-amber-200 text-amber-700 rounded font-mono font-bold">
                                {{ $customer->referral_code }}
                            </code>
                        </div>
                    </div>
                </div>

                <div class="p-8 grid grid-cols-1 md:grid-cols-3 gap-8">
                    
                    <div class="space-y-4">
                        <h3 class="flex items-center text-sm font-bold text-gray-400 uppercase tracking-wider">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Contact Details
                        </h3>
                        <div>
                            <p class="text-xs text-gray-400">Email Address</p>
                            <p class="text-gray-900 font-medium">{{ $customer->email }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Phone Number</p>
                            <p class="text-gray-900 font-medium">+{{ $customer->phone_country_code }} {{ $customer->phone_number }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h3 class="flex items-center text-sm font-bold text-gray-400 uppercase tracking-wider">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Account Info
                        </h3>
                        <div>
                            <p class="text-xs text-gray-400">Birthday</p>
                            <p class="text-gray-900 font-medium">{{ $customer->birthday ?? 'Not set' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Sign-in Method</p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                {{ ucfirst($customer->sign_in) }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h3 class="flex items-center text-sm font-bold text-gray-400 uppercase tracking-wider">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Activity
                        </h3>
                        <div>
                            <p class="text-xs text-gray-400">Member Since</p>
                            <p class="text-gray-700 text-sm">{{ $customer->created_at?->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Last Updated</p>
                            <p class="text-gray-700 text-sm">{{ $customer->updated_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-8 py-6 flex items-center justify-between">
                    <a href="{{ route('customers.index') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-700 transition">
                        &larr; Back to Customers
                    </a>
                    
                    <div class="flex items-center space-x-3">
                        <form action="{{ route('customers.destroy', $customer) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-800 transition">
                                Delete Account
                            </button>
                        </form>
                        <a href="{{ route('customers.edit', $customer) }}" 
                           class="inline-flex items-center px-6 py-2 bg-gray-900 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none transition">
                            Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>