<?php
require_once '/var/www/html/app/core/Database.php';
require_once '/var/www/html/app/core/Env.php';
Env::load('/var/www/html/.env');
require_once '/var/www/html/app/models/SubscriptionPlan.php';
require_once '/var/www/html/app/models/DiscountCoupon.php';

$plan = new SubscriptionPlan();
$prices = $plan->getActivePrices();
echo "Monthly price: " . $prices['monthly']['price'] . PHP_EOL;
echo "Yearly price:  " . $prices['yearly']['price'] . PHP_EOL;
echo "Installments max: " . $prices['yearly']['installment']['max_installments'] . PHP_EOL;
echo "Interest free up to: " . $prices['yearly']['installment']['interest_free_up_to'] . PHP_EOL;

$installRows = SubscriptionPlan::calculateInstallments(179.40, $prices['yearly']['installment']);
foreach ($installRows as $r) {
    $interest = $r['has_interest'] ? '+juros' : 'sem juros';
    echo "  {$r['installments']}x R$ " . number_format($r['installment_value'], 2, ',', '.') . " ($interest) total R$ " . number_format($r['total_value'], 2, ',', '.') . PHP_EOL;
}

$couponModel = new DiscountCoupon();
$stats = $couponModel->getStats();
echo "Coupons total: " . $stats['total_coupons'] . PHP_EOL;

// Test coupon validation
$result = $couponModel->validate('INVALIDO', 'monthly', 29.90, 0);
echo "Invalid coupon valid=" . ($result['valid'] ? 'true' : 'false') . " msg=" . $result['message'] . PHP_EOL;

echo "ALL OK" . PHP_EOL;

