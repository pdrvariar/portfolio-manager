<?php
require_once __DIR__ . '/YahooQuoteStrategy.php';

class QuoteStrategyFactory {
    public static function make($asset) {
        $source = $asset['source'] ?? 'Yahoo';
        $type = $asset['asset_type'] ?? 'COTACAO';

        if (strcasecmp($source, 'Yahoo') === 0) {
            // Para SELIC, retornaremos null para indicar não suportado (futuro)
            if (strtoupper($asset['code']) === 'SELIC' || $type === 'TAXA_MENSAL') {
                return null;
            }
            return new YahooQuoteStrategy();
        }
        // Outros provedores no futuro
        return null;
    }
}
