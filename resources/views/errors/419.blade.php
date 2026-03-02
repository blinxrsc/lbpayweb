@extends('errors::minimal')

@section('title', __('Page Expired'))
@section('code', '419')
@section('message', __('The page has expired due to inactivity. Please try again.'))


<x-guest-layout>
    <div class="flex items-center justify-center">
        <p>The page has expired due to inactivity. Please login back.</p>
        <!-- You can put your login form here or a button to show it -->
        <a href="{{ route('customer.login') }}" class="ml-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <x-primary-button>Login</x-primary-button>
        </a>
    </div>
</x-guest-layout>
