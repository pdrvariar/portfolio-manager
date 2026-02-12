<?php
require_once __DIR__ . '/YahooQuoteStrategy.php';
require_once __DIR__ . '/FedQuoteStrategy.php';
require_once __DIR__ . '/SelicQuoteStrategy.php';

class QuoteStrategyFactory {
    public static function make($asset) {
        $source = !empty(trim($asset['source'] ?? '')) ? trim($asset['source']) : 'Yahoo';
        $type = !empty(trim($asset['asset_type'] ?? '')) ? trim($asset['asset_type']) : 'COTACAO';

        // Caso especial SELIC (pode vir de várias fontes ou ser identificada pelo código)
        if (strcasecmp($source, 'Selic') === 0) {
            if (strcasecmp($type, 'TAXA_MENSAL') === 0) {
                return new SelicQuoteStrategy();
            }
        }

        if (strcasecmp($source, 'Yahoo') === 0) {
            if (strcasecmp($type, 'COTACAO') === 0) {
                return new YahooQuoteStrategy();
            }
        }

        if (strcasecmp($source, 'FED') === 0) {
            if (strcasecmp($type, 'TAXA_MENSAL') === 0) {
                return new FedQuoteStrategy();
            }
        }
        // Outros provedores no futuro
        return null;
    }
}
