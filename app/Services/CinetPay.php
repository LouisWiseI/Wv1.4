<?php

namespace App\Services;

use App\Events\PaymentProcessed;
use App\Events\PaymentReferrerBonus;
use App\Models\Payment;
use App\Models\PaymentPlatform;
use App\Models\PrepaidPlan;
use App\Models\Subscriber;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Statistics\UserService;
use App\Traits\ConsumesExternalServiceTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Exception;


/**
 * CinetPay class
 * Almost everything has been done here following the Paypal example
 */
class CinetPay
{
    use ConsumesExternalServiceTrait;

    protected $baseURI;
    protected $clientID;
    protected $clientSecret;
    private $api;

    public function __construct()
    {
        $this->api = new UserService();

        $verify = $this->api->verify_license();

        if ($verify['status'] != true) {
            return false;
        }

        $this->baseURI = config('services.cinetpay.base_url');
        $this->clientID = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
    }

    public function resolveAuthorization(&$queryParams, &$formParams, &$headers)
    {
        $headers['Authorization'] = $this->resolveAccessToken();
    }

    public function resolveAccessToken()
    {
        if ($this->api->api_url != 'https://license.berkine.space/') {
            return;
        }

        $credentials = base64_encode("{$this->clientID}:{$this->clientSecret}");

        return "Basic {$credentials}";
    }

    public function decodeResponse($response)
    {
        return json_decode($response, true);
    }

    public function handlePaymentSubscription(Request $request, SubscriptionPlan $id)
    {

    }

    public function handleApproval(Request $request)
    {
        try {
            // VERIFY TO SEE IF THE PAYMENT IS SUCCESSFULL
            $result = $this->makeRequest('POST', config('services.cinetpay.verify_url'), [], [
                "apikey" => config('services.cinetpay.api_key'),
                "site_id" => config('services.cinetpay.site_id'),
                "transaction_id" => $request->get('transaction_id')
            ]);


            if ($result['code'] == "00" && $result['data']['status'] == "ACCEPTED") {

                if ($request->get('transaction_frequency') == "prepaid") {


                    // IF ALL IS OK, THEN WE PERSIST THE PAYMENT AND SEND NOTIFICATIONS
                    $plan = PrepaidPlan::findOrFail($request->get('plan'));
                    $tax_value = (config('payment.payment_tax') > 0) ? $plan->price * config('payment.payment_tax') / 100 : 0;
                    $total_price = $tax_value + $plan->price;

                    if (config('payment.referral.payment.enabled') == 'on') {
                        if (config('payment.referral.payment.policy') == 'first') {
                            if (Payment::where('user_id', auth()->user()->id)->where('status', 'Success')->exists()) {
                                /** User already has at least 1 payment and referrer already received credit for it */
                            } else {
                                event(new PaymentReferrerBonus(auth()->user(), $request->get('transaction_id'), $total_price, 'CinetPay'));
                            }
                        } else {
                            event(new PaymentReferrerBonus(auth()->user(), $request->get('transaction_id'), $total_price, 'CinetPay'));
                        }
                    }


                    $record_payment = new Payment();
                    $record_payment->user_id = auth()->user()->id;
                    $record_payment->order_id = $request->get('transaction_id');
                    $record_payment->plan_id = $plan->id;
                    $record_payment->plan_name = $plan->plan_name;
                    $record_payment->price = $plan->price;
                    $record_payment->currency = $plan->currency;
                    $record_payment->gateway = 'CinetPay';
                    $record_payment->frequency = 'prepaid';
                    $record_payment->status = 'completed';
                    $record_payment->words = $plan->words;
                    $record_payment->images = $plan->images;
                    $record_payment->save();

                    //$group = (auth()->user()->hasRole('admin'))? 'admin' : 'subscriber';

                    $user = User::where('id', auth()->user()->id)->first();
                    //$user->syncRoles($group);
                    //$user->group = $group;
                    //$user->plan_id = $plan->id;
                    $user->available_words_prepaid = $user->available_words_prepaid + $plan->words;
                    $user->available_images_prepaid = $user->available_images_prepaid + $plan->images;
                    $user->save();

                    event(new PaymentProcessed(auth()->user()));

                } else {

                    if ($this->validateSubscriptions($request)) {
                        $plan = SubscriptionPlan::where('id', $request->get('plan'))->firstOrFail();
                        $user = $request->user();

                        $gateway = PaymentPlatform::where('name', 'CinetPay')->firstOrFail();
                        $duration = $plan->payment_frequency;
                        $days = ($duration == 'monthly') ? 30 : 365;

                        Subscriber::create([
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'status' => 'Active',
                            'created_at' => now(),
                            'gateway' => $gateway->name,
                            'frequency' => $plan->payment_frequency,
                            'plan_name' => $plan->plan_name,
                            'words' => $plan->words,
                            'images' => $plan->images,
                            'subscription_id' => $request->get('transaction_id'),
                            'active_until' => Carbon::now()->addDays($days),
                        ]);


                        session()->forget('gatewayID');


                        $this->registerSubscriptionPayment($plan, $user, $request->get('transaction_id'), $gateway->name);
                    }else{
                        toastr()->error(__('There was an error while checking your subscription. Please try again'));
                        return redirect()->back();
                    }
                }

                $order_id = $request->get('transaction_id');

                return view('user.plans.success', compact('plan', 'order_id'));
            }
        } catch (Exception $e) {
            toastr()->error(__('Payment was not successful, please try again'));
            return redirect()->back();
        }

        toastr()->error(__('Payment was not successful, please try again'));
        return redirect()->back();

    }

    private function registerSubscriptionPayment(SubscriptionPlan $plan, User $user, $subscriptionID, $gateway)
    {
        $tax_value = (config('payment.payment_tax') > 0) ? $plan->price * config('payment.payment_tax') / 100 : 0;
        $total_price = $tax_value + $plan->price;

        if (config('payment.referral.payment.enabled') == 'on') {
            if (config('payment.referral.payment.policy') == 'first') {
                if (Payment::where('user_id', $user->id)->where('status', 'completed')->exists()) {
                    /** User already has at least 1 payment */
                } else {
                    event(new PaymentReferrerBonus(auth()->user(), $subscriptionID, $total_price, $gateway));
                }
            } else {
                event(new PaymentReferrerBonus(auth()->user(), $subscriptionID, $total_price, $gateway));
            }
        }

        $record_payment = new Payment();
        $record_payment->user_id = $user->id;
        $record_payment->plan_id = $plan->id;
        $record_payment->order_id = $subscriptionID;
        $record_payment->plan_name = $plan->plan_name;
        $record_payment->frequency = $plan->payment_frequency;
        $record_payment->price = $total_price;
        $record_payment->currency = $plan->currency;
        $record_payment->gateway = $gateway;
        $record_payment->status = 'completed';
        $record_payment->words = $plan->words;
        $record_payment->images = $plan->images;
        $record_payment->save();

        $group = ($user->hasRole('admin')) ? 'admin' : 'subscriber';

        $user = User::where('id', $user->id)->first();
        $user->syncRoles($group);
        $user->group = $group;
        $user->plan_id = $plan->id;
        $user->total_words = $plan->words;
        $user->total_images = $plan->images;
        $user->available_words = $plan->words;
        $user->available_images = $plan->images;
        $user->save();

        event(new PaymentProcessed(auth()->user()));

    }

    public function validateSubscriptions(Request $request)
    {
        if (session()->has('cinet_pay_id')) {
            $subscriptionID = session()->get('cinet_pay_id');

            session()->forget('cinet_pay_id');
            session()->forget('cinet_pay_plan');

            return $request->transaction_id == $subscriptionID;
        }

        return false;
    }
}
