
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-10">

    <div class="max-w-xl mx-auto bg-white rounded-xl shadow-lg p-6 text-center">

        <h2 class="text-2xl font-bold mb-3 text-gray-800">
            Pembayaran BengkuluKita
        </h2>

        <p class="text-gray-600 mb-6">
            Selesaikan pembayaran pesanan kamu melalui Midtrans.
            Pilih transfer bank, e-wallet, kartu, atau QRIS
            sesuai metode yang tersedia.
        </p>

        {{-- Informasi Pesanan --}}
        <div class="bg-gray-100 rounded-lg p-4 mb-6">

            <p class="text-gray-600 text-sm">
                Nomor Pesanan
            </p>

            <p class="font-semibold text-gray-800">
                {{ $order->id }}
            </p>

            <p class="text-gray-600 text-sm mt-4">
                Total Pembayaran
            </p>

            <p class="text-2xl font-bold text-amber-700">
                Rp {{ number_format($order->total, 0, ',', '.') }}
            </p>

            <p class="text-gray-500 text-xs mt-2">
                Pembayaran diproses secara otomatis melalui Midtrans.
            </p>

        </div>

        {{-- Pesan Error --}}
        @if(!empty($paymentError))
            <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4">
                {{ $paymentError }}
            </div>
        @endif

        {{-- Data Pembayaran --}}
        <div
            id="midtrans-data"
            data-snap-token="{{ $order->snap_token }}"
            data-finish-url="{{ route('checkout.success', $order) }}"
        ></div>

        {{-- Tombol Bayar --}}
        <button
            type="button"
            id="pay-button"
            @disabled(empty($order->snap_token) || !empty($paymentError))
            class="w-full bg-amber-700 hover:bg-amber-800 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold py-3 px-5 rounded-lg transition"
        >
            Bayar Sekarang
        </button>

        <p
            id="payment-message"
            class="text-sm text-gray-600 mt-4"
            role="status"
            aria-live="polite"
        >
            @if(empty($order->snap_token))
                Token pembayaran belum tersedia.
            @elseif(!empty($paymentError))
                Silakan periksa konfigurasi pembayaran.
            @endif
        </p>

        <a
            href="{{ route('home') }}"
            class="inline-block mt-5 text-sm text-amber-700 hover:underline"
        >
            Kembali ke Beranda
        </a>

    </div>

</div>

{{-- Midtrans Snap --}}
@if(!empty($order->snap_token) && !empty($clientKey) && !empty($snapJsUrl))

    <script
        src="{{ $snapJsUrl }}?client-key={{ urlencode($clientKey) }}"
        type="text/javascript">
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const paymentData = document.getElementById('midtrans-data');
            const payButton = document.getElementById('pay-button');
            const message = document.getElementById('payment-message');

            const snapToken = paymentData.dataset.snapToken;
            const finishUrl = paymentData.dataset.finishUrl;

            function resetButton() {
                payButton.disabled = false;
                payButton.textContent = 'Bayar Sekarang';
            }

            payButton.addEventListener('click', function () {

                if (!snapToken) {
                    message.textContent =
                        'Token pembayaran tidak tersedia. Silakan hubungi admin.';
                    return;
                }

                if (typeof window.snap === 'undefined') {
                    message.textContent =
                        'Midtrans gagal dimuat. Periksa koneksi internet dan muat ulang halaman.';
                    resetButton();
                    return;
                }

                payButton.disabled = true;
                payButton.textContent = 'Memproses...';

                message.textContent =
                    'Membuka halaman pembayaran Midtrans...';

                window.snap.pay(snapToken, {

                    onSuccess: function () {
                        message.textContent =
                            'Pembayaran berhasil. Memeriksa status pesanan...';

                        window.location.href = finishUrl;
                    },

                    onPending: function () {
                        message.textContent =
                            'Pembayaran sedang menunggu penyelesaian.';

                        window.location.href = finishUrl;
                    },

                    onError: function () {
                        message.textContent =
                            'Pembayaran gagal. Silakan coba kembali.';

                        resetButton();
                    },

                    onClose: function () {
                        message.textContent =
                            'Halaman pembayaran ditutup. Kamu masih bisa melanjutkan pembayaran.';

                        resetButton();
                    }

                });

            });

        });
    </script>

@endif

@endsection
