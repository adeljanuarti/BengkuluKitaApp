@extends('layouts.app')

@section('content')

@php
    $snapError = $snapError ?? null;
    $snapToken = $snapToken ?? null;
@endphp

<div class="min-h-screen bg-gradient-to-b from-[#fffaf2] to-[#f7f0e4] py-10 md:py-16">
<div class="max-w-2xl mx-auto px-5">

    <div class="bg-white rounded-[2rem] border border-[#e2d5c3] shadow-[0_20px_60px_rgba(70,50,30,0.12)] overflow-hidden">

        {{-- HEADER --}}
        <div class="bg-gradient-to-br from-[#29251f] to-[#5a4327] text-white text-center px-8 py-9">
            <p class="text-xs uppercase tracking-[0.2em] text-[#f5e7c7]/80 font-bold">Pembayaran QRIS</p>
            <p class="text-4xl font-extrabold mt-3 text-[#f5e7c7]">Rp {{ number_format($order->total, 0, ',', '.') }}</p>
            <p class="text-sm text-white/70 mt-3">Pesanan #{{ $order->id }}</p>
        </div>

        <div class="p-6 md:p-8">

            {{-- RINGKASAN ITEM --}}
            <div class="border border-[#e2d5c3] rounded-2xl overflow-hidden">
                @foreach($order->items as $item)
                    <div class="flex items-center justify-between gap-4 px-5 py-4 {{ ! $loop->last ? 'border-b border-[#eee4d7]' : '' }}">
                        <div class="min-w-0">
                            <p class="font-bold text-[#29251f] truncate">{{ $item->product?->name ?? 'Produk' }}</p>
                            <p class="text-sm text-gray-500 mt-1">{{ $item->quantity }} × Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                        </div>
                        <p class="font-bold text-[#8b6b45] whitespace-nowrap">
                            Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}
                        </p>
                    </div>
                @endforeach
            </div>

            @if($snapError)
                {{-- ERROR --}}
                <div class="mt-6 rounded-2xl bg-red-50 border border-red-200 p-5 text-red-700 text-sm">
                    {{ $snapError }}
                </div>

                <a href="{{ route('checkout.pay', $order) }}"
                    class="block text-center mt-5 bg-gradient-to-r from-[#29251f] to-[#5a4327] text-white py-4 rounded-xl font-bold hover:-translate-y-0.5 hover:shadow-xl transition">
                    Coba Lagi
                </a>
            @else
                <div class="mt-6 rounded-2xl bg-[#fffaf2] border border-[#e2d5c3] p-5 text-sm text-gray-600">
                    <p class="font-bold text-[#29251f]">Cara pembayaran:</p>
                    <ol class="mt-2 space-y-1 list-decimal list-inside">
                        <li>Tekan tombol <strong>Bayar Sekarang</strong>.</li>
                        <li>Pilih QRIS lalu scan dengan mobile banking atau e-wallet.</li>
                        <li>Selesaikan pembayaran. Status pesanan akan terkonfirmasi otomatis.</li>
                    </ol>
                </div>

                <p id="snap-message" class="hidden mt-4 text-sm text-red-600 text-center"></p>

                <button id="pay-button" type="button"
                    data-token="{{ $snapToken }}"
                    data-finish-url="{{ route('checkout.success', $order) }}"
                    class="w-full mt-6 bg-gradient-to-r from-[#29251f] to-[#5a4327] text-white py-4 rounded-xl font-bold hover:-translate-y-0.5 hover:shadow-xl transition">
                    Bayar Sekarang
                </button>
            @endif

            {{-- STATUS --}}
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500">Status pembayaran:</p>
                <span class="inline-block mt-2 px-4 py-2 rounded-full bg-yellow-100 text-yellow-700 text-sm font-bold">
                    {{ $order->payment_status }}
                </span>
            </div>

            <a href="{{ route('checkout.success', $order) }}"
                class="block text-center text-sm text-gray-500 hover:text-[#8b6b45] underline mt-6">
                Lihat status pesanan
            </a>

            <p class="text-xs text-gray-400 text-center mt-6">
                Pembayaran diproses dengan aman oleh Midtrans.
            </p>
        </div>
    </div>

</div>
</div>

@if(! $snapError && $snapToken)
    <script src="{{ $snapJsUrl }}" data-client-key="{{ $clientKey }}"></script>
    <script>
        (function () {
            const button = document.getElementById('pay-button');
            const message = document.getElementById('snap-message');
            const token = button.dataset.token;
            const finishUrl = button.dataset.finishUrl;

            function showMessage(text) {
                message.textContent = text;
                message.classList.remove('hidden');
            }

            function openSnap() {
                if (typeof window.snap === 'undefined') {
                    showMessage('Layanan pembayaran belum termuat. Muat ulang halaman ini.');
                    return;
                }

                message.classList.add('hidden');

                window.snap.pay(token, {
                    onSuccess: function () { window.location.href = finishUrl; },
                    onPending: function () { window.location.href = finishUrl; },
                    onError: function () {
                        showMessage('Pembayaran gagal diproses. Silakan coba lagi.');
                    },
                    onClose: function () {
                        showMessage('Pembayaran belum selesai. Tekan "Bayar Sekarang" untuk melanjutkan.');
                    }
                });
            }

            button.addEventListener('click', openSnap);
            window.addEventListener('load', openSnap);
        })();
    </script>
@endif

@endsection