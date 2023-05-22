<?php

namespace App\Http\Controllers\User;
//---CinetPay-- Add --02/04/23 ---start
use AmrShawky\LaravelCurrency\Facade\Currency;

//---CinetPay-- Add --02/04/23 ---End
use App\Http\Controllers\Controller;

//---CinetPay-- Add --02/04/23 ---start
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

//---CinetPay-- Add --02/04/23 ---End
use Illuminate\Support\Str;
use App\Models\PaymentPlatform;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\PrepaidPlan;

//---CinetPay-- Add --02/04/23 ---start
use Stevebauman\Location\Facades\Location;

//---CinetPay-- Add --02/04/23 ---End
class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $monthly = SubscriptionPlan::where('status', 'active')->where('payment_frequency', 'monthly')->count();
        $yearly = SubscriptionPlan::where('status', 'active')->where('payment_frequency', 'yearly')->count();
        $prepaid = PrepaidPlan::where('status', 'active')->count();

        $monthly_subscriptions = SubscriptionPlan::where('status', 'active')->where('payment_frequency', 'monthly')->get();
        $yearly_subscriptions = SubscriptionPlan::where('status', 'active')->where('payment_frequency', 'yearly')->get();
        $prepaids = PrepaidPlan::where('status', 'active')->get();

        return view('user.plans.index', compact('monthly', 'yearly', 'monthly_subscriptions', 'yearly_subscriptions', 'prepaids', 'prepaid'));
    }


    /**
     * Checkout for Subscription plans only.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */


    //---CinetPay-- Add --02/04/23 ---start
    public function subscribe(Request $request, SubscriptionPlan $id)
    {

        session()->forget('cinet_pay_id');
        session()->forget('cinet_pay_plan');

        //---CinetPay-- Add --02/04/23 ---End

        $payment_platforms = PaymentPlatform::where('subscriptions_enabled', 1)->get();

        $tax_value = (config('payment.payment_tax') > 0) ? $tax = $id->price * config('payment.payment_tax') / 100 : 0;

        $total_value = $tax_value + $id->price;
        $currency = $id->currency;
        $gateway_plan_id = $id->gateway_plan_id;

        $bank_information = ['bank_instructions', 'bank_requisites'];
        $bank = [];
        $settings = Setting::all();

        foreach ($settings as $row) {
            if (in_array($row['name'], $bank_information)) {
                $bank[$row['name']] = $row['value'];
            }
        }

        $bank_order_id = 'BT-' . strtoupper(Str::random(10));
        session()->put('bank_order_id', $bank_order_id);

        //---CinetPay-- Add --02/04/23 ---start

        $ip = $request->ip();  /*Dynamic IP address */
        $countryCode = Location::get($ip)->countryCode;

        $jsonFile = file_get_contents(public_path('page.json'));
        $data = json_decode($jsonFile, true);
        $devise = ((object)collect($data)->where('countryCode', $countryCode)->first())->currencyCode;

        $convertedAmount = ceil(Http::get("https://api.exchangerate.host/convert?from=" . strtoupper($id->currency) . "&to=" . strtoupper($devise) . "&amount=" . $total_value)->json('result'));
        if ($convertedAmount % 5 != 0 && $devise == 'XAF') {
            $convertedAmount = $convertedAmount + (5 - ($convertedAmount % 5));
        }

        $randomId = "CNT-" . strtoupper(Str::random(10));
        session()->put('cinet_pay_id', $randomId);
        session()->put('cinet_pay_plan', 'subscription');
        $plan = $id;
        $transactionID = $randomId;
        $convertedAmountXAF = ceil(Http::get("https://api.exchangerate.host/convert?from=" . strtoupper($id->currency) . "&to=XAF&amount=" . $total_value)->json('result'));

        if ($convertedAmountXAF % 5 != 0) {
            $convertedAmountXAF = $convertedAmountXAF + (5 - ($convertedAmountXAF % 5));
        }
        return view('user.plans.subscribe-checkout', compact('convertedAmountXAF', 'transactionID', 'plan', 'convertedAmount', 'devise', 'id', 'payment_platforms', 'tax_value', 'total_value', 'currency', 'gateway_plan_id', 'bank', 'bank_order_id'));
    }
    //---CinetPay-- Add --02/04/23 ---End

    /**
     * Checkout for Prepaid plans only.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */

    //---CinetPay-- Add --02/04/23 ---start
    public function checkout(Request $request, PrepaidPlan $id)
    {
        session()->forget('cinet_pay_id');
        session()->forget('cinet_pay_plan');

        //---CinetPay-- Add --02/04/23 ---End

        $payment_platforms = PaymentPlatform::where('enabled', 1)->get();

        $tax_value = (config('payment.payment_tax') > 0) ? $tax = $id->price * config('payment.payment_tax') / 100 : 0;

        $total_value = $tax_value + $id->price;
        $currency = $id->currency;

        $bank_information = ['bank_instructions', 'bank_requisites'];
        $bank = [];
        $settings = Setting::all();

        foreach ($settings as $row) {
            if (in_array($row['name'], $bank_information)) {
                $bank[$row['name']] = $row['value'];
            }
        }

        $bank_order_id = 'BT-' . strtoupper(Str::random(10));
        session()->put('bank_order_id', $bank_order_id);


        //---CinetPay-- Add --02/04/23 ---start
        $ip = $request->ip();  /*Dynamic IP address */
        $countryCode = Location::get($ip)->countryCode;

        $jsonFile = file_get_contents(public_path('page.json'));
        $data = json_decode($jsonFile, true);
        $devise = ((object)collect($data)->where('countryCode', $countryCode)->first())->currencyCode;

        $convertedAmount = ceil(Http::get("https://api.exchangerate.host/convert?from=" . strtoupper($id->currency) . "&to=" . strtoupper($devise) . "&amount=" . $total_value)->json('result'));
        if ($convertedAmount % 5 != 0 && $devise == 'XAF') {
            $convertedAmount = $convertedAmount + (5 - ($convertedAmount % 5));
        }
        $randomId = "CNT-" . strtoupper(Str::random(10));
        session()->put('cinet_pay_id', $randomId);
        session()->put('cinet_pay_plan', 'prepaid');
        $plan = $id;
        $transactionID = $randomId;

        //---CinetPay-- Add --02/04/23 ---End
        $convertedAmountXAF = ceil(Http::get("https://api.exchangerate.host/convert?from=" . strtoupper($id->currency) . "&to=XAF&amount=" . $total_value)->json('result'));
        if ($convertedAmountXAF % 5 != 0) {
            $convertedAmountXAF = $convertedAmountXAF + (5 - ($convertedAmountXAF % 5));
        }
        return view('user.plans.prepaid-checkout', compact('convertedAmountXAF', 'plan', 'transactionID', 'convertedAmount', 'devise', 'id', 'payment_platforms', 'tax_value', 'total_value', 'currency', 'bank', 'bank_order_id'));
    }
}
