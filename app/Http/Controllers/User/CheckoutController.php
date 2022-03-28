<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use Illuminate\Http\Request;
use App\Models\Camp;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\User\Checkout\Store;
use Illuminate\Support\Facades\Mail;
use App\Mail\Checkout\AfterCheckout;
use Str;
use Exception;
use Midtrans;

class CheckoutController extends Controller
{

  public function __construct()
  {
    Midtrans\Config::$serverKey = env('MIDTRANS_SERVERKEY');
    Midtrans\Config::$isProduction = env('MIDTRANS_IS_PRODUCTION');
    Midtrans\Config::$isSanitized = env('MIDTRANS_IS_SANITIZED');
    Midtrans\Config::$is3ds = env('MIDTRANS_IS_3DS');
  }


  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
    //
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function create(Camp $camp, Request $request)
  {
    if ($camp->isRegistered) {
      $request->session()->flash('error', "You already registered on {$camp->title} camp.");
      return redirect(route('user.dashboard'));
    }

    return view('checkout.create', [
      'camp' => $camp,
    ]);
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(Store $request, Camp $camp)
  {
    // mapping request data
    $data = $request->all();
    $data['user_id'] = Auth::id();
    $data['camp_id'] = $camp->id;

    // update user's data
    $user = Auth::user();
    $user->email = $data['email'];
    $user->name = $data['name'];
    $user->occupation = $data['occupation'];
    $user->phone = $data['phone'];
    $user->address = $data['address'];
    $user->save();

    // create to checkout table
    $checkout = Checkout::create($data);
    $this->getSnapRedirect($checkout);

    // send email
    Mail::to(Auth::user()->email)->send(new AfterCheckout($checkout));

    return redirect(route('checkout.success'));
  }

  /**
   * Display the specified resource.
   *
   * @param  \App\Models\Checkout  $checkout
   * @return \Illuminate\Http\Response
   */
  public function show(Checkout $checkout)
  {
    //
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param  \App\Models\Checkout  $checkout
   * @return \Illuminate\Http\Response
   */
  public function edit(Checkout $checkout)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \App\Models\Checkout  $checkout
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request, Checkout $checkout)
  {
    //
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  \App\Models\Checkout  $checkout
   * @return \Illuminate\Http\Response
   */
  public function destroy(Checkout $checkout)
  {
    //
  }

  public function success()
  {
    return view('checkout.success');
  }


  /*
  Midtrans Handler
  */
  /**
   * Midtrans Handler
   */
  public function getSnapRedirect(Checkout $checkout)
  {
    $checkout->update([
      'midtrans_booking_code' => $checkout->id . '-' . Str::random(5)
    ]);

    // Fill transaction details
    $transaction_details = array(
      'order_id' => $checkout->midtrans_booking_code,
      'gross_amount' => $checkout->Camp->price * 1000
    );

    $item_details[] = [
      "id" => $checkout->midtrans_booking_code,
      "price" => $checkout->Camp->price * 1000,
      "quantity" => 1,
      "name" => "Payment for {$checkout->Camp->title} Camp"
    ];

    // Optional
    $billing_address = array(
      'first_name'    => $checkout->User->name,
      'last_name'     => "",
      'address'       => $checkout->User->address,
      'city'          => "",
      'postal_code'   => "",
      'phone'         => $checkout->User->phone,
      'country_code'  => "IDN"
    );

    // Optional
    $shipping_address = array(
      'first_name'    => $checkout->User->name,
      'last_name'     => "",
      'address'       => $checkout->User->address,
      'city'          => "",
      'phone'         => $checkout->User->phone,
      'postal_code'   => "",
      'country_code'  => 'IDN'
    );

    $customer_details = array(
      'first_name'    => $checkout->User->name,
      'last_name'     => "",
      'email'         => $checkout->User->email,
      'phone'         => $checkout->User->phone,
      'billing_address'  => $billing_address,
      'shipping_address' => $shipping_address
    );

    $params = array(
      'transaction_details' => $transaction_details,
      'customer_details' => $customer_details,
      'item_details' => $item_details,
    );
    try {
      $paymentUrl = \Midtrans\Snap::createTransaction($params)->redirect_url;
      $checkout->midtrans_url = $paymentUrl;
      $checkout->save();

      return $paymentUrl;
    } catch (Exception $e) {
      return false;
    }
  }

  public function midtransCallback(Request $request)
  {
    $notif = $request->method() == 'POST' ? new Midtrans\Notification() : Midtrans\Transaction::status($request->order_id);

    $transaction_status = $notif->transaction_status;
    $fraud = $notif->fraud_status;

    $checkout_id = explode('-', $notif->order_id)[0];
    $checkout = Checkout::find($checkout_id);

    if ($transaction_status == 'capture') {
      if ($fraud == 'challenge') {
        // TODO Set payment status in merchant's database to 'challenge'
        $checkout->payment_status = 'pending';
      } else if ($fraud == 'accept') {
        // TODO Set payment status in merchant's database to 'success'
        $checkout->payment_status = 'paid';
        $checkout->User->update();
      }
    } else if ($transaction_status == 'cancel') {
      if ($fraud == 'challenge') {
        // TODO Set payment status in merchant's database to 'failure'
        $checkout->payment_status = 'failed';
      } else if ($fraud == 'accept') {
        // TODO Set payment status in merchant's database to 'failure'
        $checkout->payment_status = 'failed';
      }
    } else if ($transaction_status == 'deny') {
      // TODO Set payment status in merchant's database to 'failure'
      $checkout->payment_status = 'failed';
    } else if ($transaction_status == 'settlement') {
      // TODO set payment status in merchant's database to 'Settlement'
      $checkout->payment_status = 'paid';
      $checkout->User->update();
    } else if ($transaction_status == 'pending') {
      // TODO set payment status in merchant's database to 'Pending'
      $checkout->payment_status = 'pending';
    } else if ($transaction_status == 'expire') {
      // TODO set payment status in merchant's database to 'expire'
      $checkout->payment_status = 'failed';
    }

    $checkout->save();
    return view('checkout/success');
  }
}
