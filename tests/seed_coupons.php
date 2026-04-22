<?php
/**
 * Seed de cupons de exemplo para demonstração
 */
require_once '/var/www/html/app/core/Database.php';
require_once '/var/www/html/app/core/Env.php';
Env::load('/var/www/html/.env');
require_once '/var/www/html/app/models/DiscountCoupon.php';

$model = new DiscountCoupon();

$coupons = [
    [
        'code'           => 'BEMVINDO20',
        'display_name'   => 'Boas-vindas 20% OFF 🎉',
        'discount_type'  => 'percent',
        'discount_value' => 20,
        'applies_to'     => 'both',
        'max_uses'       => 100,
        'valid_from'     => null,
        'valid_until'    => null,
        'is_active'      => 1,
    ],
    [
        'code'           => 'BLACKFRIDAY',
        'display_name'   => 'Black Friday 50% OFF 🔥',
        'discount_type'  => 'percent',
        'discount_value' => 50,
        'applies_to'     => 'yearly',
        'max_uses'       => 50,
        'valid_from'     => '2026-11-28 00:00:00',
        'valid_until'    => '2026-11-30 23:59:59',
        'is_active'      => 0, // Inativo até a Black Friday
    ],
    [
        'code'           => 'MENSAL10',
        'display_name'   => '10% no Plano Mensal',
        'discount_type'  => 'percent',
        'discount_value' => 10,
        'applies_to'     => 'monthly',
        'max_uses'       => null,
        'valid_from'     => null,
        'valid_until'    => null,
        'is_active'      => 1,
    ],
];

foreach ($coupons as $c) {
    if (!$model->codeExists($c['code'])) {
        $id = $model->create($c, 1);
        echo "Cupom criado: {$c['code']} (ID: $id)" . PHP_EOL;
    } else {
        echo "Cupom já existe: {$c['code']}" . PHP_EOL;
    }
}

// Testar validação
echo PHP_EOL . "=== TESTE DE VALIDAÇÃO ===" . PHP_EOL;
$tests = [
    ['BEMVINDO20', 'monthly', 29.90],
    ['BEMVINDO20', 'yearly', 179.40],
    ['BLACKFRIDAY', 'yearly', 179.40],
    ['MENSAL10', 'monthly', 29.90],
    ['MENSAL10', 'yearly', 179.40], // deve falhar (só mensal)
    ['INEXISTENTE', 'monthly', 29.90], // deve falhar
];

foreach ($tests as [$code, $plan, $price]) {
    $r = $model->validate($code, $plan, $price, 0);
    $status = $r['valid'] ? '✓ VÁLIDO' : '✗ INVÁLIDO';
    $detail = $r['valid'] ? "desconto=R$ " . number_format($r['discount'], 2, ',', '.') . " final=R$ " . number_format($r['final_price'], 2, ',', '.') : $r['message'];
    echo "$status  [$code/$plan/R$ $price] → $detail" . PHP_EOL;
}

