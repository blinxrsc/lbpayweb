<nav class="w-full sm:max-w-md mt-6 px-6 py-4 fixed bottom-0 left-1/2 transform -translate-x-1/2 
            bg-white shadow-md border rounded-xl w-11/12 sm:w-96 z-50">
    <div class="flex justify-center space-x-10 py-2">
        <a href="{{ route('customer.dashboard') }}" 
           class="flex flex-col items-center flex justify between mx-4 px-6 text-gray-600 hover:text-blue-600">
            <x-heroicon-o-home class="w-6 h-6 mb-1" />
            <span class="text-xs">Home</span>
        </a>
        <a href="{{ route('customer.transaction') }}" 
           class="flex flex-col items-center mx-4 px-6 text-gray-600 hover:text-blue-600">
            <x-heroicon-o-clipboard class="w-6 h-6 mb-1" />
            <span class="text-xs">Usage</span>
        </a>
        <a href="{{ route('customer.profile.edit') }}" 
           class="flex flex-col items-center mx-4 px-6 text-gray-600 hover:text-blue-600">
            <x-heroicon-o-user class="w-6 h-6 mb-1" />
            <span class="text-xs">Profile</span>
        </a>
    </div>
</nav>