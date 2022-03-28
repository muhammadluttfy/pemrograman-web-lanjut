{{-- <x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    You're logged in!
                </div>
            </div>
        </div>
    </div>
</x-app-layout> --}}


@extends('layouts.app')

@section('content')
    <section class="dashboard my-5">
        <div class="container">
            <div class="row text-left">
                <div class=" col-lg-12 col-12 header-wrap mt-4">
                    <p class="story">
                        DASHBOARD
                    </p>
                    <h2 class="primary-header ">
                        My Bootcamps
                    </h2>
                </div>
            </div>
            <div class="row my-5">
                @include('components.alert')
                <div class="table-responsive">
                    <table class="table">
                        <tbody>
                            @forelse ($checkouts as $checkout)
                                <tr class="align-middle border-bottom">
                                    <td width="20%">
                                        <img src="{{ asset('images/item_bootcamp.png') }}" height="120" alt="">
                                    </td>
                                    <td>
                                        <p class="mb-2">
                                            <strong>{{ $checkout->Camp->title }}</strong>
                                        </p>
                                        <p>
                                            {{ $checkout->created_at->format('M d, Y') }}
                                        </p>
                                    </td>
                                    <td class="text-center">
                                        <strong>{{ $checkout->Camp->price }}K</strong>
                                    </td>
                                    <td class="text-center">
                                        @if ($checkout->payment_status == 'waiting')
                                            <strong class="text-danger">{{ $checkout->payment_status }}</strong>
                                        @else
                                            <strong class="text-success">{{ $checkout->payment_status }}</strong>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($checkout->payment_status == 'waiting')
                                            <a href="{{ $checkout->midtrans_url }}" class="btn btn-primary">Pay Here</a>
                                        @else

                                        @endif
                                    </td>
                                    <td>
                                        <a href="https://wa.me/6282340378657?text=Hi, saya ingin bertanya tentang kelas {{ $checkout->Camp->title }}"
                                            class="btn btn-primary">
                                            Get Contact Support
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-bottom">
                                    <td colspan="5">
                                        You don't have a course. <a class="text-decoration-none text-success fw-bold"
                                            href="{{ route('welcome') }}">Order now</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
