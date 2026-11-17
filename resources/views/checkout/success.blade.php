
@extends('layouts.app')

@section('content')

@php
    $paid = $order->payment_status === 'Dibayar';
    $cancelled = $order->status === 'Dibatalkan';
    $waitingVerification = $order->payment_status === 'Menunggu Verifikasi';

    $pendingPayment = in_array($order->payment_method, ['QRIS', 'Midtrans'], true)
        && ! $paid
        && ! $cancelled
        && ! $waitingVerification;

    if ($paid) {
        [$ring, $dot, $eyebrow, $title, $text] = [
            'bg-[#e8f3e5]',
            'bg-[#6f8f5f]',
            'Pembayaran Berhasil',
            'Pembayaran Berhasil Dikonfirmasi!',
            'Pembayaran kamu sudah dikonfirmasi dan pesanan akan segera diproses.'
        ];
    } elseif ($cancelled) {
        [$ring, $dot, $eyebrow, $title, $text] = [
            'bg-red-50',
            'bg-red-500',
            'Pesanan Dibatalkan',
            'Pembayaran Tidak Selesai',
            'Transaksi dibatalkan atau batas waktu pembayaran telah habis.'
        ];
    } elseif ($waitingVerification) {
        [$ring, $dot, $eyebrow, $title, $text] = [
            'bg-[#fbf0d2]',
            'bg-[#d4a017]',
            'Menunggu Verifikasi',
            'Bukti Pembayaran Diterima!',
            'Bukti pembayaran sudah dikirim. Admin akan memeriksa pembayaran kamu.'
        ];
    } else {
        [$ring, $dot, $eyebrow, $title, $text] = [
            'bg-[#fbf0d2]',
            'bg-[#d4a017]',
            'Pesanan Berhasil',
            'Pesanan Kamu Diterima!',
            'Silakan selesaikan pembayaran dan unggah bukti pembayaran untuk diperiksa oleh admin.'
        ];
    }
@endphp

<div class="min-h-screen bg-gradient-to-b from-[#fffaf2] to-[#f7f0e4] py-10 md:py-16">
    <div class="max-w-3xl mx-auto px-5 sm:px-6">

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 rounded-2xl p-5">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-2xl p-5">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-2xl p-5">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Status Pesanan --}}
        <div class="text-center mb-8">
            <div class="mx-auto w-24 h-24 rounded-full {{ $ring }} flex items-center justify-center mb-5">
                <div class="w-14 h-14 rounded-full {{ $dot }} flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-white" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">

                        @if($cancelled)
                            <path d="M18 6 6 18M6 6l12 12"/>
                        @elseif($pendingPayment || $waitingVerification)
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 7v5l3 2"/>
                        @else
                            <path d="M20 6 9 17l-5-5"/>
                        @endif
                    </svg>
                </div>
            </div>

            <p class="text-sm font-bold uppercase tracking-[0.18em] text-[#8b6b45]">
                {{ $eyebrow }}
            </p>

            <h1 class="text-3xl md:text-4xl font-extrabold text-[#29251f] mt-2">
                {{ $title }}
            </h1>

            <p class="text-gray-600 max-w-lg mx-auto mt-3 leading-relaxed">
                {{ $text }}
            </p>
        </div>

        <div class="bg-white rounded-[2rem] border border-[#e2d5c3] shadow-[0_15px_45px_rgba(70,50,30,0.08)] overflow-hidden">

            {{-- Ringkasan Pesanan --}}
            <div class="px-6 md:px-8 py-6 bg-[#fffaf3] border-b border-[#eee4d7] grid grid-cols-2 sm:grid-cols-4 gap-4">

                <div>
                    <p class="text-xs uppercase tracking-wider font-bold text-gray-500">No. Pesanan</p>
                    <p class="text-xl font-extrabold text-[#29251f] mt-1">
                        #{{ $order->id }}
                    </p>
                </div>

                <div>
                    <p class="text-xs uppercase tracking-wider font-bold text-gray-500">Metode</p>
                    <p class="font-bold text-[#29251f] mt-1">
                        {{ $order->payment_method }}
                        @if($order->payment_type)
                            <span class="block text-xs font-medium text-gray-500">
                                {{ str_replace('_', ' ', $order->payment_type) }}
                            </span>
                        @endif
                    </p>
                </div>

                <div>
                    <p class="text-xs uppercase tracking-wider font-bold text-gray-500">Pembayaran</p>
                    <p class="font-bold mt-1 {{ $paid ? 'text-[#4f7a3f]' : ($cancelled ? 'text-red-600' : 'text-[#b8860b]') }}">
                        {{ $order->payment_status }}
                    </p>
                </div>

                <div>
                    <p class="text-xs uppercase tracking-wider font-bold text-gray-500">Total</p>
                    <p class="text-xl font-extrabold text-[#8b6b45] mt-1">
                        Rp {{ number_format($order->total, 0, ',', '.') }}
                    </p>
                </div>

            </div>

            <div class="p-6 md:p-8">

                {{-- Tombol Bayar --}}
                @if($pendingPayment)
                    <a href="{{ route('checkout.pay', $order) }}"
                        class="block text-center mb-6 bg-gradient-to-r from-[#8b6b45] to-[#6f5437] text-white py-4 rounded-xl font-bold hover:shadow-xl transition">
                        Bayar Sekarang
                    </a>
                @endif

                {{-- Upload Bukti Pembayaran --}}
                @if(!$paid && !$cancelled && !$waitingVerification)

                    <div class="mb-8 rounded-2xl border border-[#e2d5c3] bg-[#fffaf3] p-5 md:p-6">

                        <h2 class="text-lg font-extrabold text-[#29251f] mb-2">
                            Upload Bukti Pembayaran
                        </h2>

                        <p class="text-sm text-gray-600 mb-5">
                            Setelah melakukan pembayaran, silakan unggah bukti transfer atau screenshot pembayaran agar admin dapat memeriksanya.
                        </p>

                        <form action="{{ route('checkout.payment-proof', $order) }}"
                            method="POST"
                            enctype="multipart/form-data">

                            @csrf

                            <label for="payment_proof"
                                class="block text-sm font-semibold text-[#29251f] mb-2">
                                Pilih Foto Bukti Pembayaran
                            </label>

                            <input
                                type="file"
                                name="payment_proof"
                                id="payment_proof"
                                accept="image/jpeg,image/png,image/webp"
                                required
                                class="w-full text-sm border border-[#d8cbbb] rounded-xl bg-white p-3
                                file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                                file:bg-[#8b6b45] file:text-white file:font-semibold">

                            <p class="text-xs text-gray-500 mt-2">
                                Format JPG, JPEG, PNG, atau WEBP. Maksimal 5 MB.
                            </p>

                            <button type="submit"
                                class="w-full mt-5 bg-[#29251f] text-white py-3.5 rounded-xl font-bold hover:bg-[#8b6b45] transition">
                                Kirim Bukti Pembayaran
                            </button>

                        </form>
                    </div>

                @elseif($waitingVerification)

                    <div class="mb-8 rounded-2xl border border-yellow-200 bg-yellow-50 p-5">

                        <h2 class="font-extrabold text-yellow-800 mb-2">
                            Menunggu Pemeriksaan Admin
                        </h2>

                        <p class="text-sm text-yellow-700">
                            Bukti pembayaran sudah dikirim. Silakan tunggu admin memeriksa dan mengonfirmasi pembayaran kamu.
                        </p>

                        @if($order->payment_proof)
                            <div class="mt-4">
                                <p class="text-sm font-semibold text-gray-700 mb-2">
                                    Bukti Pembayaran yang Dikirim:
                                </p>

                                <a href="{{ asset('storage/' . $order->payment_proof) }}"
                                    target="_blank">
                                    <img
                                        src="{{ asset('storage/' . $order->payment_proof) }}"
                                        alt="Bukti Pembayaran"
                                        class="max-w-xs w-full rounded-xl border border-[#e2d5c3]">
                                </a>
                            </div>
                        @endif

                    </div>

                @elseif($paid)

                    <div class="mb-8 rounded-2xl border border-green-200 bg-green-50 p-5">
                        <h2 class="font-bold text-green-800 mb-1">
                            Pembayaran Telah Dikonfirmasi
                        </h2>
                        <p class="text-sm text-green-700">
                            Terima kasih. Pesanan kamu akan segera diproses.
                        </p>
                    </div>

                @endif

                {{-- Detail Produk --}}
                <h2 class="text-lg font-bold text-[#29251f] mb-4">
                    Detail Pesanan
                </h2>

                <div class="border border-[#e2d5c3] rounded-2xl overflow-hidden">

                    @foreach($order->items as $item)
                        <div class="flex items-center justify-between gap-4 px-5 py-4 {{ !$loop->last ? 'border-b border-[#eee4d7]' : '' }}">

                            <div class="min-w-0">
                                <p class="font-bold text-[#29251f] truncate">
                                    {{ $item->product?->name ?? 'Produk' }}
                                </p>

                                <p class="text-sm text-gray-500 mt-1">
                                    {{ $item->quantity }} × Rp {{ number_format($item->price, 0, ',', '.') }}
                                </p>
                            </div>

                            <p class="font-bold text-[#8b6b45] whitespace-nowrap">
                                Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}
                            </p>

                        </div>
                    @endforeach

                </div>

                {{-- Informasi Pengiriman --}}
                <div class="mt-6 rounded-2xl bg-[#faf6ef] border border-[#eee4d7] p-5">

                    <h3 class="font-bold text-[#29251f] mb-3">
                        Informasi Pengiriman
                    </h3>

                    <p class="font-semibold text-[#29251f]">
                        {{ $order->customer_name }} · {{ $order->phone }}
                    </p>

                    <p class="text-gray-600 mt-1 leading-relaxed">
                        {{ $order->address }}
                    </p>

                </div>

                {{-- Tombol Navigasi --}}
                <div class="mt-8 flex flex-col sm:flex-row gap-3">

                    <a href="{{ route('products.index') }}"
                        class="flex-1 text-center px-6 py-3.5 rounded-xl bg-[#29251f] text-white font-bold hover:bg-[#8b6b45] transition">
                        Lanjut Belanja
                    </a>

                    <a href="{{ route('home') }}"
                        class="flex-1 text-center px-6 py-3.5 rounded-xl border-2 border-[#d8cbbb] bg-white text-[#29251f] font-bold hover:border-[#8b6b45] hover:text-[#8b6b45] transition">
                        Kembali ke Beranda
                    </a>

                </div>

            </div>
        </div>

        <p class="text-center text-sm text-gray-500 mt-8">
            Terima kasih telah memilih
            <span class="font-bold text-[#8b6b45]">BengkuluKita</span>.
        </p>

    </div>
</div>

@endsection
