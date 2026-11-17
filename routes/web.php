
<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MidtransNotificationController;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\QrisController;

/*
|--------------------------------------------------------------------------
| HOME
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])
    ->name('home');


/*
|--------------------------------------------------------------------------
| HALAMAN PEMBELI
|--------------------------------------------------------------------------
*/

Route::get('/produk', [ProductController::class, 'index'])
    ->name('products.index');

Route::get('/produk/{product}', [ProductController::class, 'show'])
    ->name('products.show');


/*
|--------------------------------------------------------------------------
| KERANJANG
|--------------------------------------------------------------------------
*/

Route::get('/keranjang', [CartController::class, 'index'])
    ->name('cart.index');

Route::post('/keranjang/tambah/{product}', [CartController::class, 'add'])
    ->name('cart.add');

Route::patch('/keranjang/{product}', [CartController::class, 'update'])
    ->name('cart.update');

Route::delete('/keranjang/{product}', [CartController::class, 'remove'])
    ->name('cart.remove');


/*
|--------------------------------------------------------------------------
| CHECKOUT
|--------------------------------------------------------------------------
*/

Route::get('/checkout', [CheckoutController::class, 'index'])
    ->name('checkout.index');

Route::post('/checkout', [CheckoutController::class, 'store'])
    ->name('checkout.store');


/*
|--------------------------------------------------------------------------
| PEMBAYARAN
|--------------------------------------------------------------------------
*/

Route::middleware('payment.access')->group(function () {

    Route::get(
        '/checkout/bayar/{order}',
        [CheckoutController::class, 'pay']
    )->name('checkout.pay');

    Route::post(
        '/checkout/{order}/bukti-pembayaran',
        [CheckoutController::class, 'uploadProof']
    )->name('checkout.payment-proof');

    Route::post(
        '/checkout/bayar/{order}/konfirmasi',
        [CheckoutController::class, 'confirmPayment']
    )->name('checkout.confirm-payment');

});


/*
|--------------------------------------------------------------------------
| PESANAN BERHASIL
|--------------------------------------------------------------------------
*/

Route::get(
    '/checkout/sukses/{order}',
    [CheckoutController::class, 'success']
)->name('checkout.success');


/*
|--------------------------------------------------------------------------
| PESANAN PELANGGAN
|--------------------------------------------------------------------------
| Hanya pelanggan yang sudah login yang dapat mengakses.
*/

Route::middleware('auth')->group(function () {

    Route::get(
        '/pesanan-saya',
        [OrderController::class, 'index']
    )->name('orders.index');

    Route::get(
        '/pesanan-saya/{order}',
        [OrderController::class, 'show']
    )->name('orders.show');

});


/*
|--------------------------------------------------------------------------
| WEBHOOK MIDTRANS
|--------------------------------------------------------------------------
*/

Route::post(
    '/midtrans/notification',
    MidtransNotificationController::class
)->name('midtrans.notification');


/*
|--------------------------------------------------------------------------
| LOGIN ADMIN
|--------------------------------------------------------------------------
*/

Route::get(
    '/admin/login',
    [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create']
)
    ->middleware('guest')
    ->name('admin.login');


/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->group(function () {

        // PENGATURAN WEBSITE

        Route::get(
            '/settings',
            [SiteSettingController::class, 'edit']
        )->name('admin.settings.edit');

        Route::put(
            '/settings',
            [SiteSettingController::class, 'update']
        )->name('admin.settings.update');


        // QRIS

        Route::get(
            '/qris',
            [QrisController::class, 'edit']
        )->name('admin.qris.edit');

        Route::put(
            '/qris',
            [QrisController::class, 'update']
        )->name('admin.qris.update');


        // DASHBOARD

        Route::get(
            '/dashboard',
            [DashboardController::class, 'index']
        )->name('admin.dashboard');


        // PRODUK

        Route::get(
            '/products',
            [AdminProductController::class, 'index']
        )->name('admin.products.index');

        Route::get(
            '/products/create',
            [AdminProductController::class, 'create']
        )->name('admin.products.create');

        Route::post(
            '/products',
            [AdminProductController::class, 'store']
        )->name('admin.products.store');

        Route::get(
            '/products/{product}/edit',
            [AdminProductController::class, 'edit']
        )->name('admin.products.edit');

        Route::put(
            '/products/{product}',
            [AdminProductController::class, 'update']
        )->name('admin.products.update');

        Route::delete(
            '/products/{product}',
            [AdminProductController::class, 'destroy']
        )->name('admin.products.destroy');


        // PESANAN ADMIN

        Route::get(
            '/orders',
            [AdminOrderController::class, 'index']
        )->name('admin.orders.index');

        Route::get(
            '/orders/{order}',
            [AdminOrderController::class, 'show']
        )->name('admin.orders.show');

        Route::delete(
            '/orders/{order}',
            [AdminOrderController::class, 'destroy']
        )->name('admin.orders.destroy');

        Route::patch(
            '/orders/{order}/status',
            [AdminOrderController::class, 'updateStatus']
        )->name('admin.orders.status');

        Route::patch(
            '/orders/{order}/payment',
            [AdminOrderController::class, 'updatePaymentStatus']
        )->name('admin.orders.payment');

        Route::patch(
            '/orders/{order}/refund',
            [AdminOrderController::class, 'processRefund']
        )->name('admin.orders.refund');

    });


/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';
