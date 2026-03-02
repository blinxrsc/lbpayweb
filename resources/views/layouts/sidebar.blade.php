<div class="p-4">
    <h2 class="text-lg font-semibold mb-4 text-gray-800">Menu</h2>
    <ul class="space-y-1">
        
        <li>
            <x-nav-link :href="route('dashboard')" 
                        :active="request()->routeIs('dashboard')" 
                        icon="heroicon-s-home"
                        class="{{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-gray-400' }}"
            >
                Dashboard
            </x-nav-link>
        </li>
        <!-- system users -->
        @can('users.manage')
        <li x-data="{ open: {{ request()->routeIs('users.*','roles.*','permissions.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" 
                class="flex items-center w-full px-3 py-2 text-sm font-medium text-left rounded-md hover:bg-gray-100 transition-colors
                {{ request()->routeIs('users.*','roles.*','permissions.*') ? 'text-blue-700' : 'text-gray-600' }}">
                
                <x-heroicon-m-user-circle class="w-5 h-5 mr-3 {{ request()->routeIs('users.*','roles.*','permissions.*') ? 'text-blue-600' : 'text-gray-400' }}" />
                <span class="flex-1">System User</span>
                
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>              
            <ul x-show="open" x-cloak class="mt-1 space-y-1 pl-11">
                <li>
                    <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.index')" class="{{ request()->routeIs('users.*','roles.*','permissions.*') ? 'text-blue-600' : 'text-gray-400' }}">
                        Manage Users
                    </x-nav-link>
                </li>
                @can('users.roles')
                <li>
                    <x-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.*')" class="{{ request()->routeIs('users.*','roles.*','permissions.*') ? 'text-blue-600' : 'text-gray-400' }}">
                        Manage Roles
                    </x-nav-link>
                </li>
                @endcan
                @can('users.permission')
                <li>
                    <x-nav-link :href="route('permissions.index')" :active="request()->routeIs('permissions.*')" class="{{ request()->routeIs('users.*','roles.*','permissions.*') ? 'text-blue-600' : 'text-gray-400' }}">
                        Manage Permissions
                    </x-nav-link>
                </li> 
                @endcan
            </ul>
        </li>
        @endcan
        <!-- outlet -->
        @can('outlets.manage')
        <li x-data="{ open: {{ request()->routeIs('outlets.*','brands.*','managers.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" 
                class="flex items-center w-full px-3 py-2 text-sm font-medium text-left rounded-md hover:bg-gray-100 transition-colors
                {{ request()->routeIs('outlets.*','brands.*','managers.*') ? 'text-blue-700' : 'text-gray-600' }}">
                
                <x-heroicon-m-building-storefront class="w-5 h-5 mr-3 {{ request()->routeIs('outlets.*','brands.*','managers.*') ? 'text-blue-600' : 'text-gray-400' }}" />
                <span class="flex-1">Outlet</span>
                
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>              
            <ul x-show="open" x-cloak class="mt-1 space-y-1 pl-11">
                <li>
                    <x-nav-link :href="route('outlets.index')" :active="request()->routeIs('outlets.*')" class="{{ request()->routeIs('outlets.*') ? 'text-blue-600' : 'text-gray-400' }}" >
                        Manage Outlets
                    </x-nav-link>
                </li>
                @can('outlets.brand')
                <li>
                    <x-nav-link :href="route('brands.index')" :active="request()->routeIs('brands.*')" class="{{ request()->routeIs('brands.*') ? 'text-blue-600' : 'text-gray-400' }}" >
                        Manage Brands
                    </x-nav-link>
                </li>
                @endcan
                @can('outlets.manager')
                <li>
                    <x-nav-link :href="route('managers.index')" :active="request()->routeIs('managers.*')" class="{{ request()->routeIs('managers.*') ? 'text-blue-600' : 'text-gray-400' }}" >
                        Manage Managers
                    </x-nav-link>
                </li>
                @endcan
            </ul>
        </li>
        @endcan
        <!-- Device -->
        @can('devices.manage')
        <li x-data="{ open: {{ request()->routeIs('devices.*','device_outlets.*','suppliers.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" 
                class="flex items-center w-full px-3 py-2 text-sm font-medium text-left rounded-md hover:bg-gray-100 transition-colors
                {{ request()->routeIs('devices.*','device_outlets.*','suppliers.*') ? 'text-blue-700' : 'text-gray-600' }}">
                
                <x-heroicon-m-server class="w-5 h-5 mr-3 {{ request()->routeIs('devices.*','device_outlets.*','suppliers.*') ? 'text-blue-600' : 'text-gray-400' }}" />
                <span class="flex-1">Device</span>
                
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>              
            <ul x-show="open" x-cloak class="mt-1 space-y-1 pl-11">
                @can('devices_outlet.manage')
                <li>
                    <x-nav-link :href="route('device_outlets.index')" :active="request()->routeIs('device_outlets.*')" class="{{ request()->routeIs('device_outlets.*') ? 'text-blue-600' : 'text-gray-400' }}">
                        Manage Device ↔ Outlet
                    </x-nav-link>
                </li>
                @endcan
                <li>
                    <x-nav-link :href="route('devices.index')" :active="request()->routeIs('devices.*')" class="{{ request()->routeIs('devices.*') ? 'text-blue-600' : 'text-gray-400' }}">
                        Manage Devices
                    </x-nav-link>
                </li>
                @can('devices.supplier')
                <li>
                    <x-nav-link :href="route('suppliers.index')" :active="request()->routeIs('suppliers.*')" class="{{ request()->routeIs('suppliers.*') ? 'text-blue-600' : 'text-gray-400' }}">
                        Manage Supplier
                    </x-nav-link>
                </li>
                @endcan
            </ul>
        </li>
        @endcan
        <!-- Customer -->
        @can('customers.manage')
        <li x-data="{ open: {{ request()->routeIs('customers.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" 
                class="flex items-center w-full px-3 py-2 text-sm font-medium text-left rounded-md hover:bg-gray-100 transition-colors
                {{ request()->routeIs('customers.*') ? 'text-blue-700' : 'text-gray-600' }}">
                
                <x-heroicon-m-users class="w-5 h-5 mr-3 {{ request()->routeIs('customers.*') ? 'text-blue-600' : 'text-gray-400' }}" />
                <span class="flex-1">Customer</span>
                
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>              
            <ul x-show="open" x-cloak class="mt-1 space-y-1 pl-11">
                <li>
                    <x-nav-link :href="route('customers.index')" :active="request()->routeIs('customers.*')" class="{{ request()->routeIs('customers.*') ? 'text-blue-600' : 'text-gray-400' }}">
                        User Account
                    </x-nav-link>
                </li>
            </ul>
        </li>
        @endcan
        <!-- Ewallet -->
        @can('ewallet.manage')
        <li x-data="{ open: {{ request()->routeIs('admin.ewallet.index','admin.packages.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" 
                class="flex items-center w-full px-3 py-2 text-sm font-medium text-left rounded-md hover:bg-gray-100 transition-colors
                {{ request()->routeIs('admin.ewallet.index','admin.packages.*') ? 'text-blue-700' : 'text-gray-600' }}">
                
                <x-heroicon-m-credit-card class="w-5 h-5 mr-3 {{ request()->routeIs('admin.ewallet.index','admin.packages.*') ? 'text-blue-600' : 'text-gray-400' }}" />
                <span class="flex-1">Ewallet</span>
                
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>              
            <ul x-show="open" x-cloak class="mt-1 space-y-1 pl-11">
                @can('ewallet.adjust')
                <li>
                    <x-nav-link href="{{ route('admin.ewallet.index') }}" :active="request()->routeIs('admin.ewallet.index')" class="{{ request()->routeIs('admin.ewallet.index') ? 'text-blue-600' : 'text-gray-400' }}">
                        Adjust Balances
                    </x-nav-link>
                </li>
                @endcan
                @can('ewallet.package')
                <li>
                    <x-nav-link href="{{ route('admin.packages.index') }}" :active="request()->routeIs('admin.packages.*')" class="{{ request()->routeIs('admin.packages.*') ? 'text-blue-600' : 'text-gray-400' }}">
                        Top-Up Packages
                    </x-nav-link>
                </li>
                @endcan
            </ul>
        </li>
        @endcan
        <!-- Transaction -->
        @can('transactions.manage')
        <li x-data="{ open: {{ request()->routeIs('admin.device-transactions.*','admin.ewallet.transaction','admin.paymentgateway.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" 
                class="flex items-center w-full px-3 py-2 text-sm font-medium text-left rounded-md hover:bg-gray-100 transition-colors
                {{ request()->routeIs('admin.device-transactions.*') ? 'text-blue-700' : 'text-gray-600' }}">
                
                <x-heroicon-m-clipboard-document-list class="w-5 h-5 mr-3 {{ request()->routeIs('admin.device-transactions.*','admin.ewallet.transaction','admin.paymentgateway.*') ? 'text-blue-600' : 'text-gray-400' }}" />
                <span class="flex-1">Transaction</span>
                
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>              
            <ul x-show="open" x-cloak class="mt-1 space-y-1 pl-11">
                @can('transactions.device')
                <li>
                    <x-nav-link href="{{ route('admin.device-transactions.index') }}" :active="request()->routeIs('admin.device-transactions.*')" class="{{ request()->routeIs('admin.device-transactions.*') ? 'text-blue-600' : 'text-gray-400' }}">
                        Device
                    </x-nav-link>
                </li>
                @endcan
                @can('transactions.member')
                <li>
                    <x-nav-link href="{{ route('admin.ewallet.transaction') }}" :active="request()->routeIs('admin.ewallet.transaction')" class="{{ request()->routeIs('admin.ewallet.transaction') ? 'text-blue-600' : 'text-gray-400' }}">
                        Member
                    </x-nav-link>
                </li>
                @endcan
                @can('transactions.topup')
                <li>
                    <x-nav-link href="{{ route('admin.paymentgateway.index') }}" :active="request()->routeIs('admin.paymentgateway.*')" class="{{ request()->routeIs('admin.paymentgateway.*') ? 'text-blue-600' : 'text-gray-400' }}">
                        Topup
                    </x-nav-link>
                </li>
                @endcan
            </ul>
        </li>
        @endcan
        <!-- Report -->
        @can('reports.manage')
        <li x-data="{ open: {{ request()->routeIs('reports.members.*','reports.maintenance') ? 'true' : 'false' }} }">
            <button @click="open = !open" 
                class="flex items-center w-full px-3 py-2 text-sm font-medium text-left rounded-md hover:bg-gray-100 transition-colors
                {{ request()->routeIs('reports.members.*') ? 'text-blue-700' : 'text-gray-600' }}">
                
                <x-heroicon-m-presentation-chart-line class="w-5 h-5 mr-3 {{ request()->routeIs('reports.members.*','reports.maintenance')? 'text-blue-600' : 'text-gray-400' }}" />
                <span class="flex-1">Report</span>
                
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>              
            <ul x-show="open" x-cloak class="mt-1 space-y-1 pl-11">
                @can('reports.member')
                <li>
                    <x-nav-link href="{{ route('reports.members.index') }}" :active="request()->routeIs('reports.members.*')" class="{{ request()->routeIs('reports.members.*')? 'text-blue-600' : 'text-gray-400' }}">
                        Member
                    </x-nav-link>
                </li>
                @endcan
            </ul>
            <ul x-show="open" x-cloak class="mt-1 space-y-1 pl-11">
                @can('reports.maintenance')
                <li>
                    <x-nav-link href="{{ route('reports.maintenance') }}" :active="request()->routeIs('reports.maintenance')" class="{{ request()->routeIs('reports.maintenance')? 'text-blue-600' : 'text-gray-400' }}">
                        Maintenance
                    </x-nav-link>
                </li>
                @endcan
            </ul>
        </li>
        @endcan
        <!-- Setting -->
        @can('setting.manage')
        <li x-data="{ open: {{ request()->routeIs('payment_gateway.*','mailserver.*','admin.logo.*','backup.*', 'admin.terms.*', 'admin.merchant.setting') ? 'true' : 'false' }} }">
            <button @click="open = !open" 
                class="flex items-center w-full px-3 py-2 text-sm font-medium text-left rounded-md hover:bg-gray-100 transition-colors
                {{ request()->routeIs('payment_gateway.*') ? 'text-blue-700' : 'text-gray-600' }}">
                
                <x-heroicon-m-wrench-screwdriver class="w-5 h-5 mr-3 {{ request()->routeIs('payment_gateway.*','mailserver.*','admin.logo.*','backup.*', 'admin.terms.*', 'admin.merchant.setting') ? 'text-blue-600' : 'text-gray-400' }}" />
                <span class="flex-1">Setting</span>
                
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>              
            <ul x-show="open" x-cloak class="mt-1 space-y-1 pl-11">
                @can('setting.payment_gateway')
                <li>
                    <x-nav-link :href="route('payment_gateway.index')" :active="request()->routeIs('payment_gateway.*')" class="{{ request()->routeIs('payment_gateway.*') ? 'text-blue-600' : 'text-gray-400' }}">
                        Payment Gateway
                    </x-nav-link>
                </li>
                @endcan
                @can('setting.mailserver')
                <li>
                    <x-nav-link :href="route('mailserver.index')" :active="request()->routeIs('mailserver.*')" class="{{ request()->routeIs('mailserver.*') ? 'text-blue-600' : 'text-gray-400' }}">
                        Mail Server
                    </x-nav-link>
                </li>
                @endcan
                @can('setting.policy')
                <li>
                    <x-nav-link :href="route('admin.terms.index')" :active="request()->routeIs('admin.terms.*')" class="{{ request()->routeIs('admin.terms.*') ? 'text-blue-600' : 'text-gray-400' }}">
                        Policy
                    </x-nav-link>
                </li>
                @endcan
                @can('setting.applogo')
                <li>
                    <x-nav-link :href="route('admin.logo.edit')" :active="request()->routeIs('admin.logo.*')" class="{{ request()->routeIs('admin.logo.*') ? 'text-blue-600' : 'text-gray-400' }}">
                        Site Logo
                    </x-nav-link>
                </li>
                @endcan
                @can('setting.backup')
                <li>
                    <x-nav-link :href="route('backup.index')" :active="request()->routeIs('backup.*')" class="{{ request()->routeIs('backup.*') ? 'text-blue-600' : 'text-gray-400' }}">
                        Backup System
                    </x-nav-link>
                </li>
                @endcan
                @can('setting.merchant')
                <li>
                    <x-nav-link :href="route('admin.merchant.setting')" :active="request()->routeIs('admin.merchant.setting')" class="{{ request()->routeIs('admin.merchant.setting') ? 'text-blue-600' : 'text-gray-400' }}">
                        Merchant Profile
                    </x-nav-link>
                </li>
                @endcan
            </ul>
        </li>
        @endcan
        <!-- Logs -->
        <li x-data="{ open: {{ request()->routeIs('mailserver.logs', 'admin.health','admin.user.logs') ? 'true' : 'false' }} }">
            <button @click="open = !open" 
                class="flex items-center w-full px-3 py-2 text-sm font-medium text-left rounded-md hover:bg-gray-100 transition-colors
                {{ request()->routeIs('mailserver.logs','admin.health','admin.user.logs') ? 'text-blue-700' : 'text-gray-600' }}">
                
                <x-heroicon-m-adjustments-horizontal class="w-5 h-5 mr-3 {{ request()->routeIs('mailserver.logs','admin.health','admin.user.logs')? 'text-blue-600' : 'text-gray-400' }}" />
                <span class="flex-1">Log</span>
                
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>              
            <ul x-show="open" x-cloak class="mt-1 space-y-1 pl-11">
                <li>
                    <x-nav-link :href="route('admin.health')" :active="request()->routeIs('admin.health')" class="{{ request()->routeIs('admin.health')? 'text-blue-600' : 'text-gray-400' }}">System Health</x-nav-link>
                </li>
                <li>
                    <x-nav-link :href="route('admin.user.logs')" :active="request()->routeIs('admin.user.logs')" class="{{ request()->routeIs('admin.user.logs')? 'text-blue-600' : 'text-gray-400' }}">User Logs</x-nav-link>
                </li>
                <li>
                    <x-nav-link :href="route('mailserver.logs')" :active="request()->routeIs('mailserver.logs')" class="{{ request()->routeIs('mailserver.logs')? 'text-blue-600' : 'text-gray-400' }}">Mail Server Logs</x-nav-link>
                </li>
            </ul>
        </li>
    </ul>
</div>