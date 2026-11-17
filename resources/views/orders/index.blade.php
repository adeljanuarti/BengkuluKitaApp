
@extends('layouts.app')

@section('content')

<div class="min-h-screen bg-gradient-to-b from-[#fffaf2] to-[#f7f0e4] py-12">

    <div class="max-w-6xl mx-auto px-5">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-[#29251f]">
                Pesanan Saya
            </h1>

            <p class="text-gray-600 mt-2">
                Lihat riwayat dan perkembangan pesanan kamu di BengkuluKita.
            </p>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-xl">
                {{ session('success') }}
            </div>
        @endif

        @if($orders->isEmpty())

            <!-- Empty State -->
            <div class="bg-white rounded-3xl border border-[#e2d5c3] p-12 text-center">

                <div class="text-5xl mb-4">
                    🛍️
                </div>

                <h2 class="text-xl font-bold text-[#29251f]">
                    Belum Ada Pesanan
                </h2>

                <p class="text-gray-500 mt-2">
                    Kamu belum memiliki riwayat pesanan.
                </p>

                <a href="{{ route('products.index') }}"
                   class="inline-block mt-6 px-7 py-3 rounded-xl bg-[#29251f] text-white font-bold hover:bg-[#8b6b45] transition">
                    Mulai Belanja
                </a>

            </div>

        @else

            <div class="space-y-5">

                @foreach($orders as $order)

                    @php
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

                        $orderStatus = $order->status ?? 'Pesanan Baru';
                        $paymentStatus = $order->payment_status ?? 'Belum Dibayar';
                    @endphp

                    <!-- Order Card -->
                    <div class="bg-white rounded-3xl border border-[#e2d5c3] shadow-sm overflow-hidden">

                        <!-- Order Header -->
                        <div class="flex flex-wrap justify-between items-center gap-3 p-5 border-b border-[#eee4d7]">

                            <div>

                                <p class="text-xs text-gray-500 font-semibold">
                                    NOMOR PESANAN
                                </p>

                                <p class="font-extrabold text-[#29251f]">
                                    #{{ $order->id }}
                                </p>

                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $order->created_at->format('d M Y, H:i') }}
                                </p>

                            </div>

                            <div class="flex flex-wrap gap-2">

                                <!-- Status Pesanan -->
                                <span class="px-3 py-2 rounded-full text-xs font-bold {{ $statusColors[$orderStatus] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $orderStatus }}
                                </span>

                                <!-- Status Pembayaran -->
                                <span class="px-3 py-2 rounded-full text-xs font-bold {{ $paymentColors[$paymentStatus] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $paymentStatus }}
                                </span>

                            </div>

                        </div>

                        <!-- Order Items -->
                        <div class="p-5">

                            @forelse($order->items as $item)

                                <div class="flex justify-between items-center gap-4 py-2">

                                    <div>

                                        <p class="font-bold text-[#29251f]">
                                            {{ $item->product?->name ?? 'Produk tidak ditemukan' }}
                                        </p>

                                        <p class="text-sm text-gray-500">
                                            {{ $item->quantity }} ×
                                            Rp {{ number_format((float) $item->price, 0, ',', '.') }}
                                        </p>

                                    </div>

                                    <p class="font-bold text-[#8b6b45] whitespace-nowrap">
                                        Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                    </p>

                                </div>

                            @empty

                                <p class="text-sm text-gray-500">
                                    Tidak ada detail produk pada pesanan ini.
                                </p>

                            @endforelse

                            <!-- Order Total -->
                            <div class="flex flex-wrap justify-between items-center gap-4 mt-4 pt-4 border-t border-[#eee4d7]">

                                <div>

                                    <p class="text-sm text-gray-500">
                                        Total Pesanan
                                    </p>

                                    <p class="text-xl font-extrabold text-[#8b6b45]">
                                        {{ $order->formatted_total }}
                                    </p>

                                    @if((float) $order->refund_amount > 0)

                                        <p class="text-sm text-red-600 mt-2">
                                            Pengembalian Dana:
                                            {{ $order->formatted_refund }}
                                        </p>

                                        <p class="text-sm font-bold text-[#29251f] mt-1">
                                            Total Bersih:
                                            {{ $order->formatted_net_total }}
                                        </p>

                                    @endif

                                    @if($order->refund_status)
                                        <p class="text-xs text-gray-500 mt-1">
                                            Status Refund: {{ $order->refund_status }}
                                        </p>
                                    @endif

                                </div>

                                <!-- Detail Button -->
                                <a href="{{ route('orders.show', $order) }}"
                                   class="px-6 py-3 rounded-xl bg-[#29251f] text-white font-bold hover:bg-[#8b6b45] transition">

                                    Lihat Detail

                                </a>

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $orders->links() }}
            </div>

        @endif

    </div>

</div>

@endsection
