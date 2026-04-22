<?php
require_once '/var/www/html/app/core/Database.php';
require_once '/var/www/html/app/core/Env.php';
Env::load('/var/www/html/.env');
require_once '/var/www/html/app/models/SubscriptionPlan.php';

$planModel = new SubscriptionPlan();
$installments = [
    'monthly' => $planModel->getInstallmentConfig('monthly'),
    'yearly'  => $planModel->getInstallmentConfig('yearly'),
];

// Simula o JS gerado pela view
$jsOutput = "const INSTALLMENT_CONFIG = {\n";
$jsOutput .= "    monthly: {\n";
$jsOutput .= "        maxInstallments: " . (int)($installments['monthly']['max_installments'] ?? 1) . ",\n";
$jsOutput .= "        minInstallments: 1,\n";
$jsOutput .= "    },\n";
$jsOutput .= "    yearly: {\n";
$jsOutput .= "        maxInstallments: " . (int)($installments['yearly']['max_installments'] ?? 12) . ",\n";
$jsOutput .= "        minInstallments: 1,\n";
$jsOutput .= "    },\n";
$jsOutput .= "};";

echo $jsOutput . PHP_EOL . PHP_EOL;
echo "Hint interest_free_up_to (yearly): " . (int)($installments['yearly']['interest_free_up_to'] ?? 3) . PHP_EOL;
echo "Hint monthly_interest_rate (yearly): " . number_format((float)($installments['yearly']['monthly_interest_rate'] ?? 0.0199) * 100, 2) . "% a.m." . PHP_EOL;
echo PHP_EOL;
echo "✅ MP Brick receberá maxInstallments=" . (int)$installments['yearly']['max_installments'] . " para plano Anual" . PHP_EOL;
echo "✅ MP Brick receberá maxInstallments=" . (int)$installments['monthly']['max_installments'] . " para plano Mensal" . PHP_EOL;

