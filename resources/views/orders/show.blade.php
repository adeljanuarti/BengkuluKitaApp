
@extends('layouts.app')

@section('content')

<div class="min-h-screen bg-gradient-to-b from-[#fffaf2] to-[#f7f0e4] py-12">

    <div class="max-w-4xl mx-auto px-5">

        <!-- Kembali -->
        <a href="{{ route('orders.index') }}"
           class="text-[#8b6b45] font-semibold hover:underline">
            ← Kembali ke Pesanan Saya
        </a>

        @if(session('success'))
            <div class="mt-5 p-4 bg-green-100 text-green-700 rounded-xl">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mt-5 p-4 bg-red-100 text-red-700 rounded-xl">
                {{ session('error') }}
            </div>
        @endif

        <!-- Detail Pesanan -->
        <div class="mt-6 bg-white rounded-3xl border border-[#e2d5c3] overflow-hidden shadow-sm">

            <!-- Header -->
            <div class="p-6 bg-[#fffaf3] border-b border-[#eee4d7]">

                <h1 class="text-2xl font-extrabold text-[#29251f]">
                    Detail Pesanan #{{ $order->id }}
                </h1>

                <p class="text-sm text-gray-500 mt-2">
                    {{ $order->created_at->format('d M Y, H:i') }}
                </p>

            </div>

            <div class="p-6 space-y-7">

                <!-- Status Pesanan -->
                @php
                    $orderStatus = $order->status ?? 'Pesanan Baru';
                    $paymentStatus = $order->payment_status ?? 'Belum Dibayar';

                    $statusColors = [
                        'Pesanan Baru' => 'bg-yellow-100 text-yellow-700',
                        'Diproses' => 'bg-blue-100 text-blue-700',
                        'Dikirim' => 'bg-purple-100 text-purple-700',
                        'Selesai' => 'bg-green-100 text-green-700',
                        'Dibatalkan' => 'bg-red-100 text-red-700',
                    ];

                    $paymentColors = [
                        'Dibayar' => 'bg-green-100 text-green-700',
                        'Menunggu Pembayaran' => 'bg-yellow-100 text-yellow-700',
                        'Menunggu Verifikasi' => 'bg-orange-100 text-orange-700',
                        'Belum Dibayar' => 'bg-yellow-100 text-yellow-700',
                        'Gagal' => 'bg-red-100 text-red-700',
                        'Dibatalkan' => 'bg-red-100 text-red-700',
                    ];
                @endphp

                <div>

                    <h2 class="font-bold text-[#29251f] mb-3">
                        Status Pesanan
                    </h2>

                    <div class="rounded-xl bg-[#faf6ef] p-5">

                        <div class="flex flex-wrap gap-2">

                            <span class="px-4 py-2 rounded-full text-sm font-bold {{ $statusColors[$orderStatus] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $orderStatus }}
                            </span>

                            <span class="px-4 py-2 rounded-full text-sm font-bold {{ $paymentColors[$paymentStatus] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $paymentStatus }}
                            </span>

                        </div>

                        <p class="text-sm text-gray-500 mt-3">
                            Terakhir diperbarui:
                            {{ $order->updated_at->format('d M Y, H:i') }}
                        </p>

                        @if($order->paid_at)
                            <p class="text-sm text-green-700 mt-2">
                                Pembayaran berhasil pada:
                                {{ $order->paid_at->format('d M Y, H:i') }}
                            </p>
                        @endif

                    </div>

                </div>

                <!-- Produk yang Dibeli -->
                <div>

                    <h2 class="font-bold text-[#29251f] mb-3">
                        Produk yang Dibeli
                    </h2>

                    <div class="divide-y divide-[#eee4d7]">

                        @forelse($order->items as $item)

                            <div class="flex justify-between gap-4 py-4">

                                <div>

                                    <p class="font-bold text-[#29251f]">
                                        {{ $item->product?->name ?? 'Produk tidak ditemukan' }}
                                    </p>

                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ $item->quantity }} ×
                                        Rp {{ number_format((float) $item->price, 0, ',', '.') }}
                                    </p>

                                </div>

                                <p class="font-bold text-[#8b6b45] whitespace-nowrap">
                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                </p>

                            </div>

                        @empty

                            <p class="py-4 text-gray-500">
                                Tidak ada detail produk pada pesanan ini.
                            </p>

                        @endforelse

                    </div>

                    <!-- Total -->
                    <div class="border-t border-[#eee4d7] pt-5 mt-3 space-y-3">

                        <div class="flex justify-between items-center">

                            <span class="font-bold text-[#29251f]">
                                Total Pembayaran
                            </span>

                            <span class="font-extrabold text-xl text-[#8b6b45]">
                                {{ $order->formatted_total }}
                            </span>

                        </div>

                        @if((float) $order->refund_amount > 0)

                            <div class="flex justify-between text-red-600">

                                <span>
                                    Pengembalian Dana
                                </span>

                                <span class="font-semibold">
                                    {{ $order->formatted_refund }}
                                </span>

                            </div>

                            <div class="flex justify-between border-t pt-3">

                                <span class="font-bold text-[#29251f]">
                                    Total Bersih
                                </span>

                                <span class="font-extrabold text-xl text-green-700">
                                    {{ $order->formatted_net_total }}
                                </span>

                            </div>

                        @endif

                        @if($order->refund_status)

                            <p class="text-sm text-gray-500">
                                Status Refund:
                                <span class="font-semibold text-[#8b6b45]">
                                    {{ $order->refund_status }}
                                </span>
                            </p>

                        @endif

                    </div>

                </div>

                <!-- Informasi Pengiriman -->
                <div class="rounded-xl bg-[#faf6ef] p-5">

                    <h2 class="font-bold text-[#29251f] mb-3">
                        Informasi Pengiriman
                    </h2>

                    <div class="space-y-2">

                        <p class="font-semibold">
                            {{ $order->customer_name }}
                        </p>

                        <p class="text-gray-600">
                            {{ $order->phone }}
                        </p>

                        <p class="text-gray-600 mt-2">
                            {{ $order->address }}
                        </p>

                    </div>

                </div>

                <!-- Informasi Pembayaran -->
                <div>

                    <h2 class="font-bold text-[#29251f] mb-3">
                        Informasi Pembayaran
                    </h2>

                    <div class="rounded-xl bg-[#faf6ef] p-5 space-y-2">

                        <p>
                            <span class="text-gray-500">
                                Metode Pembayaran:
                            </span>

                            <span class="font-semibold">
                                {{ $order->payment_method ?? '-' }}
                            </span>
                        </p>

                        <p>
                            <span class="text-gray-500">
                                Tipe Pembayaran:
                            </span>

                            <span class="font-semibold">
                                {{ $order->payment_type ?? '-' }}
                            </span>
                        </p>

                        <p>
                            <span class="text-gray-500">
                                Status Pembayaran:
                            </span>

                            <span class="font-semibold">
                                {{ $paymentStatus }}
                            </span>
                        </p>

                    </div>

                </div>

                <!-- Bukti Pembayaran -->
                @if($order->payment_proof)

                    <div>

                        <h2 class="font-bold text-[#29251f] mb-3">
                            Bukti Pembayaran
                        </h2>

                        <a href="{{ asset('storage/' . $order->payment_proof) }}"
                           target="_blank"
                           rel="noopener noreferrer">

                            <img
                                src="{{ asset('storage/' . $order->payment_proof) }}"
                                alt="Bukti Pembayaran"
                                class="max-w-xs w-full rounded-xl border border-[#e2d5c3] shadow-sm"
                            >

                        </a>

                        <p class="text-xs text-gray-500 mt-2">
                            Klik gambar untuk melihat bukti pembayaran.
                        </p>

                    </div>

                @endif

                <!-- Upload Bukti Pembayaran -->
                @if(
                    in_array($paymentStatus, ['Menunggu Pembayaran', 'Menunggu Verifikasi', 'Belum Dibayar'])
                    && $orderStatus !== 'Dibatalkan'
                )

                    <div class="border-t border-[#eee4d7] pt-6">

                        <h2 class="font-bold text-[#29251f] mb-3">
                            Upload Bukti Pembayaran
                        </h2>

                        @if($order->payment_proof)

                            <p class="text-sm text-orange-600 mb-4">
                                Bukti pembayaran sudah dikirim. Silakan tunggu pemeriksaan admin.
                                Jika perlu, kamu dapat mengunggah ulang bukti pembayaran.
                            </p>

                        @else

                            <p class="text-sm text-gray-500 mb-4">
                                Silakan unggah bukti pembayaran agar admin dapat melakukan verifikasi.
                            </p>

                        @endif

                        <form
                            action="{{ route('checkout.payment-proof', $order) }}"
                            method="POST"
                            enctype="multipart/form-data"
                        >

                            @csrf

                            <input
                                type="file"
                                name="payment_proof"
                                accept="image/jpeg,image/png,image/webp"
                                required
                                class="w-full border border-[#e2d5c3] rounded-xl p-3"
                            >

                            @error('payment_proof')
                                <p class="text-red-600 text-sm mt-2">
                                    {{ $message }}
                                </p>
                            @enderror

                            <button
                                type="submit"
                                class="mt-4 w-full bg-[#8b6b45] hover:bg-[#29251f] text-white py-3 rounded-xl font-bold transition"
                            >
                                Kirim Bukti Pembayaran
                            </button>

                        </form>

                    </div>

                @endif

            </div>

        </div>

    </div>

</div>

@endsection
