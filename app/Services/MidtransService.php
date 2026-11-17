<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class MidtransService
{
    /**
     * Memeriksa konfigurasi Midtrans.
     */
    public function isConfigured(): bool
    {
        return !empty(config('services.midtrans.server_key'))
            && !empty(config('services.midtrans.client_key'));
    }

    /**
     * Mengambil Client Key.
     */
    public function clientKey(): ?string
    {
        return config('services.midtrans.client_key');
    }

    /**
     * URL Snap.js.
     */
    public function snapJsUrl(): string
    {
        return config('services.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    /**
     * URL API Snap.
     */
    private function snapUrl(): string
    {
        return config('services.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    /**
     * URL API Status Transaksi.
     */
    private function statusUrl(string $orderId): string
    {
        $baseUrl = config('services.midtrans.is_production')
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';

        return $baseUrl . '/v2/' . $orderId . '/status';
    }

    /**
     * Membuat Snap Token pembayaran.
     */
    public function createSnapToken(Order $order): string
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException(
                'Midtrans Server Key atau Client Key belum diatur.'
            );
        }

        $order->loadMissing('items.product');

        $itemDetails = [];

        foreach ($order->items as $item) {
            $itemDetails[] = [
                'id' => (string) $item->product_id,
                'price' => (int) round($item->price),
                'quantity' => (int) $item->quantity,
                'name' => mb_substr(
                    $item->product?->name ?? 'Produk',
                    0,
                    50
                ),
            ];
        }

        if (empty($itemDetails)) {
            throw new RuntimeException(
                'Pesanan tidak memiliki detail produk.'
            );
        }

        $midtransOrderId = 'BK-' . $order->id . '-'
            . now()->format('YmdHis') . '-'
            . Str::lower(Str::random(6));

        $payload = [
            'transaction_details' => [
                'order_id' => $midtransOrderId,
                'gross_amount' => (int) round($order->total),
            ],

            'item_details' => $itemDetails,

            'customer_details' => [
                'first_name' => mb_substr(
                    $order->customer_name ?? 'Pelanggan',
                    0,
                    50
                ),
                'phone' => $order->phone ?? '',
            ],

            'callbacks' => [
                'finish' => route('checkout.success', $order),
            ],
        ];

        try {
            $response = Http::withBasicAuth(
                config('services.midtrans.server_key'),
                ''
            )
                ->acceptJson()
                ->timeout(30)
                ->post($this->snapUrl(), $payload);

            if (
                !$response->successful()
                || !$response->json('token')
            ) {
                Log::error('Gagal membuat Snap Token Midtrans', [
                    'order_id' => $order->id,
                    'http_status' => $response->status(),
                    'response' => $response->json() ?? $response->body(),
                ]);

                throw new RuntimeException(
                    'Gagal membuat transaksi pembayaran. Periksa konfigurasi Midtrans.'
                );
            }

            $token = $response->json('token');

            $order->update([
                'midtrans_order_id' => $midtransOrderId,
                'snap_token' => $token,
            ]);

            return $token;

        } catch (\Throwable $e) {
            Log::error('Midtrans createSnapToken error', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Memvalidasi signature notifikasi Midtrans.
     */
    public function validSignature(array $payload): bool
    {
        $serverKey = config('services.midtrans.server_key');

        if (empty($serverKey)) {
            return false;
        }

        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signature = $payload['signature_key'] ?? '';

        if (
            !$orderId ||
            !$statusCode ||
            !$grossAmount ||
            !$signature
        ) {
            return false;
        }

        $expectedSignature = hash(
            'sha512',
            $orderId . $statusCode . $grossAmount . $serverKey
        );

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Memeriksa status transaksi langsung ke Midtrans.
     */
    public function syncStatus(Order $order): void
    {
        if (
            !$this->isConfigured() ||
            empty($order->midtrans_order_id)
        ) {
            return;
        }

        try {
            $response = Http::withBasicAuth(
                config('services.midtrans.server_key'),
                ''
            )
                ->acceptJson()
                ->timeout(15)
                ->get(
                    $this->statusUrl($order->midtrans_order_id)
                );

            if (
                $response->successful() &&
                $response->json('transaction_status')
            ) {
                $this->applyStatus($order, $response->json());
            }

        } catch (\Throwable $e) {
            Log::error('Gagal memeriksa status Midtrans', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Memperbarui status pesanan berdasarkan notifikasi Midtrans.
     */
    public function applyStatus(
        Order $order,
        array $payload
    ): void {
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? 'accept';

        DB::transaction(function () use (
            $order,
            $transactionStatus,
            $fraudStatus,
            $payload
        ) {
            $lockedOrder = Order::whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedOrder) {
                return;
            }

            if (!empty($payload['payment_type'])) {
                $lockedOrder->payment_type = $payload['payment_type'];
            }

            $alreadyPaid =
                $lockedOrder->payment_status === 'Dibayar';

            // Pembayaran berhasil.
            if (
                in_array(
                    $transactionStatus,
                    ['settlement', 'capture'],
                    true
                ) &&
                $fraudStatus === 'accept'
            ) {
                $lockedOrder->payment_status = 'Dibayar';

                if (!$lockedOrder->paid_at) {
                    $lockedOrder->paid_at = now();
                }
            }

            // Pembayaran masih menunggu.
            elseif (
                $transactionStatus === 'pending' &&
                !$alreadyPaid
            ) {
                $lockedOrder->payment_status =
                    'Menunggu Pembayaran';
            }

            // Pembayaran ditolak atau kedaluwarsa.
            elseif (
                in_array(
                    $transactionStatus,
                    ['deny', 'cancel', 'expire', 'failure'],
                    true
                ) &&
                !$alreadyPaid
            ) {
                $lockedOrder->payment_status = 'Belum Dibayar';

                if ($lockedOrder->status !== 'Dibatalkan') {
                    $lockedOrder->status = 'Dibatalkan';

                    $lockedOrder->loadMissing('items.product');

                    foreach ($lockedOrder->items as $item) {
                        if ($item->product) {
                            $item->product->increment(
                                'stock',
                                $item->quantity
                            );
                        }
                    }
                }
            }

            $lockedOrder->save();
        });
    }
}
