<!-- Empty component -->
<!-- resources/views/components/application-logo.blade.php -->
@php
    $logo = \App\Models\Setting::where('key', 'site_logo')->first();
@endphp

<img src="{{ $logo ? asset('storage/'.$logo->value) : asset('images/default-logo.png') }}"
     alt="App Logo"
     class="h-20 w-auto">
