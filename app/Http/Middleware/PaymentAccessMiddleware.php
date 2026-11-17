<?php

namespace App\Http\Middleware;

use App\Models\Order;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentAccessMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        // Ambil data pesanan dari route
        $order = $request->route('order');

        // Pastikan pesanan ditemukan
        if (!$order instanceof Order) {
            abort(404, 'Pesanan tidak ditemukan.');
        }

        // Admin boleh mengakses semua pesanan
        if ($request->user()?->role === 'admin') {
            return $next($request);
        }

        // Periksa apakah pesanan milik user yang login
        $userOwnsOrder =
            $order->user_id !== null &&
            (int) $order->user_id ===
            (int) $request->user()?->id;

        // Ambil daftar pesanan milik guest dari session
        $guestOrders = $request->session()->get(
            'guest_order_ids',
            []
        );

        // Periksa kepemilikan pesanan guest
        $guestOwnsOrder = in_array(
            (int) $order->id,
            array_map('intval', $guestOrders),
            true
        );

        // Tolak jika bukan pemilik pesanan
        abort_unless(
            $userOwnsOrder || $guestOwnsOrder,
            403,
            'Kamu tidak memiliki akses ke pesanan ini.'
        );

        return $next($request);
    }
}
