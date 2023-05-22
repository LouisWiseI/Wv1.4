<?php $__env->startSection('page-header'); ?>
    <!-- PAGE HEADER -->
    <div class="page-header mt-5-7">
        <div class="page-leftheader">
            <h4 class="page-title mb-0"><?php echo e(__('Secure Checkout')); ?></h4>
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?php echo e(route('user.dashboard')); ?>"><i class="fa-solid fa-box-circle-check mr-2 fs-12"></i><?php echo e(__('User')); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><a href="<?php echo e(route('user.plans')); ?>"> <?php echo e(__('Pricing Plans')); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><a href="<?php echo e(url('#')); ?>"> <?php echo e(__('Prepaid Plan Checkout')); ?></a></li>
            </ol>
        </div>
    </div>
    <!-- END PAGE HEADER -->
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-xl-9 col-lg-12 col-md-12 col-sm-12">
            <div class="card border-0 pt-2">
                <div class="card-body">

                    <form id="payment-form" action="<?php echo e(route('user.payments.pay.prepaid', $id)); ?>" method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>

                        <div class="row">
                            <div class="col-md-6 col-sm-12 pr-4">
                                <div class="checkout-wrapper-box pb-0">

                                    <p class="checkout-title mt-2"><i class="fa-solid fa-lock-hashtag text-success mr-2"></i><?php echo e(__('Secure Checkout')); ?></p>

                                    <div class="divider mb-5">
                                        <div class="divider-text text-muted">
                                            <small><?php echo e(__('Select Payment Option')); ?></small>
                                        </div>
                                    </div>

                                    <div class="form-group" id="toggler">

                                        <div class="text-center">
                                            <div class="btn-group btn-group-toggle w-100" data-toggle='buttons'>
                                                <div class="row w-100">
                                                    <?php $__currentLoopData = $payment_platforms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment_platform): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12">
                                                            <label class="gateway btn rounded p-0" href="#<?php echo e($payment_platform->name); ?>Collapse" data-bs-toggle="collapse">
                                                                <input
                                                                    type="radio"
                                                                    class="gateway-radio"
                                                                    name="payment_platform"
                                                                    data-name="<?php echo e($payment_platform->name); ?>"
                                                                    onchange="handleChange(this)"
                                                                    value="<?php echo e($payment_platform->id); ?>" required>
                                                                <img src="<?php echo e(URL::asset($payment_platform->image)); ?>"
                                                                     class="<?php if($payment_platform->name == 'Paystack' || $payment_platform->name == 'Razorpay' || $payment_platform->name == 'PayPal'): ?> payment-image
																		 <?php elseif($payment_platform->name == 'Braintree'): ?> payment-image-braintree
																		 <?php elseif($payment_platform->name == 'Mollie'): ?> payment-image-mollie
																		 <?php elseif($payment_platform->name == 'Coinbase'): ?> payment-image-coinbase
																		 <?php elseif($payment_platform->name == 'Stripe'): ?> payment-image-stripe
																		 <?php endif; ?>" alt="<?php echo e($payment_platform->name); ?>">
                                                            </label>
                                                        </div>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </div>
                                            </div>
                                        </div>

                                        <?php $__currentLoopData = $payment_platforms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment_platform): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php if($payment_platform->name !== 'BankTransfer'): ?>
                                                <div id="<?php echo e($payment_platform->name); ?>Collapse" class="collapse" data-bs-parent="#toggler">
                                                    <?php if ($__env->exists('components.'.strtolower($payment_platform->name).'-collapse')) echo $__env->make('components.'.strtolower($payment_platform->name).'-collapse', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                                </div>
                                            <?php else: ?>
                                                <div id="<?php echo e($payment_platform->name); ?>Collapse" class="collapse" data-bs-parent="#toggler">
                                                    <div class="text-center pb-2">
                                                        <p class="text-muted fs-12 mb-4"><?php echo e($bank['bank_instructions']); ?></p>
                                                        <p class="text-muted fs-12 mb-4">Order ID: <span class="font-weight-bold text-primary"><?php echo e($bank_order_id); ?></span></p>
                                                        <pre class="text-muted fs-12 mb-4"><?php echo e($bank['bank_requisites']); ?></pre>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>

                                    <input type="hidden" name="value" value="<?php echo e($total_value); ?>">
                                    <input type="hidden" name="currency" value="<?php echo e($currency); ?>">

                                </div>
                            </div>

                            <div class="col-md-6 col-sm-12 pl-4">
                                <div class="checkout-wrapper-box">

                                    <p class="checkout-title mt-2"><i class="fa fa-archive mr-2"></i><?php echo e(__('Plan Name')); ?>: <span class="text-info"><?php echo e($id->plan_name); ?> (<?php echo e(ucfirst($id->payment_frequency) . ' Plan'); ?>)</span></p>

                                    <div class="divider mb-4">
                                        <div class="divider-text text-muted">
                                            <small><?php echo e(__('Purchase Summary')); ?></small>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="fs-12 p-family"><?php echo e(__('Subtotal')); ?> <span class="checkout-cost"><?php echo config('payment.default_system_currency_symbol'); ?><?php echo e(number_format((float)$id->price, 2, '.', '')); ?></span></p>
                                        <p class="fs-12 p-family"><?php echo e(__('Taxes')); ?> <span class="text-muted">(<?php echo e(config('payment.payment_tax')); ?>%)</span><span class="checkout-cost"><?php echo config('payment.default_system_currency_symbol'); ?><?php echo e(number_format((float)$tax_value, 2, '.', '')); ?></span></p>
                                    </div>

                                    <div class="divider mb-5">
                                        <div class="divider-text text-muted">
                                            <small><?php echo e(__('Total')); ?></small>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="fs-12 p-family"><?php echo e(__('Total Payment')); ?> </span><span class="checkout-cost text-info"><?php echo config('payment.default_system_currency_symbol'); ?><span id="total_payment"><?php echo e(number_format((float)$total_value, 2, '.', '')); ?></span> <?php echo e($currency); ?> <span id="total_payment">≈ (<?php echo e(number_format((float)$convertedAmount, 0, '.', ' ')); ?></span> <?php echo e($devise); ?>)</span></p>
                                    </div>

                                    <div class="text-center pt-4 pb-1">
                                        <button type="submit" id="payment-button"
                                                class="btn btn-primary pl-6 pr-6 mb-1"><?php echo e(__('Checkout Now')); ?></button>
                                        <button
                                            style="display: none;"
                                            type="button"
                                            id="cinetpay-button"
                                            class="btn btn-primary pl-6 pr-6 mb-1"
                                            onclick="checkout()"
                                        ><?php echo e(__('Checkout Now')); ?></button>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </form>

                    <form
                        action="<?php echo e(route('user.transaction.approved.cinet')); ?>"
                        method="POST"
                        id="cinetpay-form"
                    >
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="transaction_id" value="<?php echo e(session()->get('cinet_pay_id')); ?>">
                        <input type="hidden" name="transaction_frequency" value="<?php echo e(session()->get('cinet_pay_plan')); ?>">
                        <input type="hidden" name="plan" value="<?php echo e($plan->id); ?>">
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('additional-scripts'); ?>
    <script src="https://cdn.cinetpay.com/seamless/main.js" type="text/javascript"></script>

    <script>
        function handleChange(target) {
            if (target.attributes['data-name'].value == 'CinetPay') {
                document.querySelector("#payment-button").style.display = 'none';
                document.querySelector("#cinetpay-button").style.display = 'inline-block';
            } else {
                document.querySelector("#payment-button").style.display = 'inline-block';
                document.querySelector("#cinetpay-button").style.display = 'none';
            }
        }

        function checkout() {
            CinetPay.setConfig({
                apikey: '<?php echo e(config('services.cinetpay.api_key')); ?>',
                site_id: '<?php echo e(config('services.cinetpay.site_id')); ?>',
                mode: 'PRODUCTION'
            });
            CinetPay.getCheckout({
                transaction_id: '<?php echo e($transactionID); ?>',
                amount: Number('<?php echo e($convertedAmountXAF); ?>'),
                currency: 'XAF',
                channels: 'MOBILE_MONEY',
                description: '<?php echo e($plan->plan_name); ?>',
                customer_name: "<?php echo e(auth()->user()->name); ?>",
                customer_email: "<?php echo e(auth()->user()->email); ?>",
                customer_address: "<?php echo e(auth()->user()->address); ?>",
                customer_city: "<?php echo e(auth()->user()->city); ?>",
                customer_zip_code: "<?php echo e(auth()->user()->postal_code); ?>",

            });
            CinetPay.waitResponse(function (data) {
                if (data.status == "REFUSED") {
                    toastr.error("<?php echo e(__('Payment was not successful, please try again')); ?>")
                } else if (data.status == "ACCEPTED") {
                    document.querySelector("#cinetpay-form").submit()
                }
            });
            CinetPay.onError(function (data) {
                console.log(data);
            });
        }
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/wisesqsn/public_html/resources/views/user/plans/prepaid-checkout.blade.php ENDPATH**/ ?>