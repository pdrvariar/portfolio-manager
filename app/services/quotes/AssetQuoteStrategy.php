<?php
interface AssetQuoteStrategy {
    /**
     * Atualiza cotações do ativo conforme regras:
     * - Compara as 2 últimas cotações do sistema com as do provedor
     * - Se iguais, inclui somente meses ausentes (incremental)
     * - Se diferentes, sinaliza necessidade de atualização total
     *
     * @param array $asset Registro do ativo em system_assets
     * @param bool $confirmFull Quando true, força atualização total
     * @return array { success, updated_count, requires_full_refresh, provider_start, provider_end, message }
     */
    public function updateQuotes($asset, $confirmFull = false);
}
