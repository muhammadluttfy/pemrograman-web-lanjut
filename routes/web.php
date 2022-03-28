<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\User\CheckoutController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\User\DashboardController as UserDashboard;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\CheckoutController as AdminCheckout;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
  return view('welcome');
})->name('welcome');

Route::get('/login', function () {
  return view('login');
})->name('login');


// socialite routes
Route::get('sign-in-google', [UserController::class, 'google'])->name('sign-in-google');
Route::get('auth/google/callback', [UserController::class, 'handleProviderCallback'])->name('auth.google.callback');

// Midtrans routes
Route::get('payment/success', [CheckoutController::class, 'midtransCallback']);
Route::post('payment/success', [CheckoutController::class, 'midtransCallback']);


Route::middleware(['auth'])->group(function () {

  // checkout routes
  Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success')->middleware('ensureUserRole:user');
  Route::get('/checkout/{camp:slug}', [CheckoutController::class, 'create'])->name('checkout.create')->middleware('ensureUserRole:user');
  Route::post('/checkout/{camp}', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('ensureUserRole:user');

  // Dashboard
  Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

  // user dashboard
  route::prefix('user/dashboard')->namespace('User')->name('user.')->middleware('ensureUserRole:user')->group(function () {
    Route::get('/', [UserDashboard::class, 'index'])->name('dashboard');
  });

  // admin dashboard
  route::prefix('admin/dashboard')->namespace('Admin')->name('admin.')->middleware('ensureUserRole:admin')->group(function () {
    Route::get('/', [AdminDashboard::class, 'index'])->name('dashboard');

    // admin checkout | set to paid
    Route::post('checkout/{checkout}', [AdminCheckout::class, 'update'])->name('checkout.update');
  });
});



require __DIR__ . '/auth.php';
