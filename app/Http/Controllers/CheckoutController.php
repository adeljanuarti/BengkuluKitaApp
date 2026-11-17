<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\MidtransService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Halaman checkout.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $cart = $request->session()->get('cart', []);

        if (empty($cart)) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Keranjang kamu masih kosong.');
        }

        $products = Product::whereIn('id', array_keys($cart))->get();

        $cartItems = $products->map(function ($product) use ($cart) {
            $quantity = (int) ($cart[$product->id]['quantity'] ?? 0);

            return [
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => $product->price * $quantity,
            ];
        });

        $total = $cartItems->sum('subtotal');

        return view('checkout.index', compact('cartItems', 'total'));
    }

    /**
     * Membuat pesanan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'payment_method' => ['required', 'in:Midtrans'],
        ], [
            'customer_name.required' => 'Nama wajib diisi.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'address.required' => 'Alamat wajib diisi.',
            'payment_method.required' => 'Metode pembayaran tidak valid.',
            'payment_method.in' => 'Pembayaran harus melalui Midtrans.',
        ]);

        $cart = $request->session()->get('cart', []);

        if (empty($cart)) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Keranjang kamu masih kosong.');
        }

        try {
            $order = DB::transaction(function () use (
                $cart,
                $validated,
                $request
            ) {
                // Ambil produk dan kunci data selama transaksi.
                $products = Product::whereIn('id', array_keys($cart))
                    ->lockForUpdate()
                    ->get();

                $total = 0;
                $items = [];

                // Periksa stok dan hitung total.
                foreach ($products as $product) {
                    $quantity = (int) (
                        $cart[$product->id]['quantity'] ?? 0
                    );

                    if ($quantity <= 0) {
                        continue;
                    }

                    if ($product->stock < $quantity) {
                        throw new \Exception(
                            "Stok produk {$product->name} tidak mencukupi."
                        );
                    }

                    $subtotal = $product->price * $quantity;
                    $total += $subtotal;

                    $items[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'subtotal' => $subtotal,
                    ];
                }

                if (empty($items)) {
                    throw new \Exception(
                        'Tidak ada produk yang dapat diproses.'
                    );
                }

                // Buat pesanan dengan metode pembayaran Midtrans.
                $order = Order::create([
                    'user_id' => $request->user()?->id,
                    'customer_name' => $validated['customer_name'],
                    'phone' => $validated['phone'],
                    'address' => $validated['address'],
                    'total' => $total,
                    'status' => 'Pesanan Baru',
                    'payment_method' => 'Midtrans',
                    'payment_status' => 'Menunggu Pembayaran',
                    'paid_at' => null,
                ]);

                // Simpan detail pesanan dan kurangi stok.
                foreach ($items as $item) {
                    $product = $item['product'];

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'price' => $product->price,
                    ]);

                    $product->decrement(
                        'stock',
                        $item['quantity']
                    );
                }

                return $order;
            });

            // Kosongkan keranjang setelah pesanan berhasil dibuat.
            $request->session()->forget('cart');

            // Simpan ID pesanan untuk akses pelanggan guest.
            $guestOrders = $request->session()->get(
                'guest_order_ids',
                []
            );

            $guestOrders[] = $order->id;

            $request->session()->put(
                'guest_order_ids',
                array_values(array_unique($guestOrders))
            );

            // Arahkan pelanggan ke halaman pembayaran.
            return redirect()->route('checkout.pay', $order);

        } catch (\Exception $e) {
            return redirect()
                ->route('cart.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mengecek apakah user boleh mengakses pesanan.
     */
    private function authorizeOrder(
        Request $request,
        Order $order
    ): void {
        // Admin boleh melihat semua pesanan.
        if ($request->user()?->role === 'admin') {
            return;
        }

        $guestOrders = $request->session()->get(
            'guest_order_ids',
            []
        );

        abort_unless(
            (
                $order->user_id
                && $request->user()?->id === $order->user_id
            )
            || in_array($order->id, $guestOrders, true),
            403
        );
    }

    /**
     * Halaman pembayaran Midtrans.
     */
    public function pay(
        Request $request,
        Order $order,
        MidtransService $midtrans
    ): View|RedirectResponse {

        $this->authorizeOrder($request, $order);

        // Periksa status pembayaran terbaru.
        if (
            $midtrans->isConfigured()
            && $order->midtrans_order_id
            && $order->payment_status !== 'Dibayar'
        ) {
            $midtrans->syncStatus($order);
            $order->refresh();
        }

        // Jika pesanan sudah dibayar atau dibatalkan.
        if (
            !in_array(
                $order->payment_method,
                ['QRIS', 'Midtrans'],
                true
            )
            || $order->payment_status === 'Dibayar'
            || $order->status === 'Dibatalkan'
        ) {
            return redirect()->route(
                'checkout.success',
                $order
            );
        }

        $order->load('items.product');

        // Jika Midtrans sudah dikonfigurasi.
        if ($midtrans->isConfigured()) {

            $paymentError = null;

            try {
                // Gunakan token yang sudah ada.
                // Buat token baru jika belum tersedia.
                if (empty($order->snap_token)) {
                    $midtrans->createSnapToken($order);
                    $order->refresh();
                }

                if (empty($order->snap_token)) {
                    throw new \RuntimeException(
                        'Snap Token pembayaran belum berhasil dibuat.'
                    );
                }

            } catch (\Throwable $e) {
                report($e);

                $paymentError = $e instanceof \RuntimeException
                    ? $e->getMessage()
                    : 'Tidak dapat terhubung ke layanan pembayaran. Silakan coba lagi.';
            }

            return view('checkout.midtrans', [
                'order' => $order,
                'clientKey' => $midtrans->clientKey(),
                'snapJsUrl' => $midtrans->snapJsUrl(),
                'paymentError' => $paymentError,
            ]);
        }

        // Fallback QRIS manual jika Midtrans belum dikonfigurasi.
        return view('checkout.pay', [
            'order' => $order,
            'settings' => SiteSetting::first(),
        ]);
    }

    /**
     * Upload bukti pembayaran manual.
     */
    public function uploadProof(
        Request $request,
        Order $order
    ): RedirectResponse {

        $this->authorizeOrder($request, $order);

        if (
            !in_array(
                $order->payment_method,
                ['QRIS', 'Midtrans'],
                true
            )
        ) {
            return back()->with(
                'error',
                'Metode pembayaran pesanan ini tidak sesuai.'
            );
        }

        if ($order->payment_status === 'Dibayar') {
            return back()->with(
                'error',
                'Pembayaran pesanan ini sudah dikonfirmasi.'
            );
        }

        $request->validate([
            'payment_proof' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ], [
            'payment_proof.required' =>
                'Silakan upload bukti pembayaran.',

            'payment_proof.image' =>
                'File harus berupa gambar.',

            'payment_proof.mimes' =>
                'Bukti pembayaran harus berupa JPG, JPEG, PNG, atau WEBP.',

            'payment_proof.max' =>
                'Ukuran bukti pembayaran maksimal 5 MB.',
        ]);

        if ($request->hasFile('payment_proof')) {
            $path = $request->file('payment_proof')
                ->store('payment-proofs', 'public');

            $order->update([
                'payment_proof' => $path,
                'payment_status' => 'Menunggu Verifikasi',
            ]);
        }

        return redirect()
            ->route('checkout.success', $order)
            ->with(
                'success',
                'Bukti pembayaran berhasil dikirim. Admin akan memeriksa pembayaran kamu.'
            );
    }

    /**
     * Konfirmasi pembayaran manual.
     */
    public function confirmPayment(
        Request $request,
        Order $order
    ): RedirectResponse {

        $this->authorizeOrder($request, $order);

        if ($order->payment_status !== 'Dibayar') {
            $order->update([
                'payment_status' => 'Menunggu Verifikasi',
            ]);
        }

        return redirect()
            ->route('checkout.success', $order)
            ->with(
                'success',
                'Konfirmasi pembayaran sudah dikirim. Admin akan memeriksa pembayaran kamu.'
            );
    }

    /**
     * Halaman sukses dan status pesanan.
     */
    public function success(
        Request $request,
        Order $order,
        MidtransService $midtrans
    ): View {

        $this->authorizeOrder($request, $order);

        // Periksa status langsung ke Midtrans.
        if (
            $midtrans->isConfigured()
            && $order->midtrans_order_id
            && $order->payment_status !== 'Dibayar'
            && $order->status !== 'Dibatalkan'
        ) {
            $midtrans->syncStatus($order);
            $order->refresh();
        }

        $order->load('items.product');

        return view('checkout.success', compact('order'));
    }
}
