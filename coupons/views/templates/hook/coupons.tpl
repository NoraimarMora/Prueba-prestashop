<div class="coupon-body">
    <div class="title">
        <h1>{l s='Scratch and Win' mod='coupons'}</h1>
        <span>{l s='Get fabulous discounts!' mod='coupons'}</span>
    </div>
    <div class="card">
        <div class="base">
            {l s='Coupon Code' mod='coupons'}: {$coupon_code}
        </div>
        <canvas id="scratch" width="300" height="60"></canvas>
    </div>
    <div class="card">
        <div class="base">
            {l s='Coupon Amount' mod='coupons'}: {$coupon_amount}
        </div>
        <canvas id="scratch2" width="300" height="60"></canvas>
    </div>
</div>