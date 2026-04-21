# Gestão de Assinaturas — Smart Returns

## Visão Geral do Sistema

O sistema de assinaturas cobre todo o ciclo de vida de contratos PRO:
contratação → renovação → upgrade → cancelamento → reembolso → expiração.

---

## Fluxos Implementados

### 1. Nova Assinatura (Starter → PRO)
- Usuário acessa `/upgrade` → escolhe plano Mensal (R$ 29,90) ou Anual (R$ 179,40)
- MercadoPago Brick coleta dados do cartão
- `POST /checkout` processa o pagamento com chave de idempotência `pay_{userId}_{planType}_{data}`
- Em aprovação: cria registro em `subscriptions`, atualiza `users`, envia e-mail de boas-vindas

### 2. Garantia de 7 Dias / Reembolso
- Janela de 7 dias a partir de `starts_at` (campo `refund_eligible_until`)
- Usuário acessa `/subscription/manage` → botão "Solicitar Reembolso" (visível enquanto elegível)
- `POST /subscription/refund` valida elegibilidade, chama MP Refunds API, atualiza banco, envia e-mail

### 3. Cancelamento
- **Fim do período**: assinatura permanece ativa até `expires_at`, sem novas cobranças
- **Imediato**: acesso PRO removido na hora
- `POST /subscription/cancel` com campo `cancel_type` (immediate | end_of_period)

### 4. Upgrade Mensal → Anual (com crédito proporcional)
- Usuário PRO mensal acessa `/upgrade` → sistema detecta modo upgrade
- Crédito = `(dias_restantes / 30) × R$ 29,90`
- Novo valor cobrado = `R$ 179,40 - crédito`
- Assinatura mensal é cancelada imediatamente, nova anual ativada

### 5. Renovação
- Sem auto-renovação (MP Transparent não suporta nativamente)
- E-mails automáticos enviados pelo cron em 7, 3 e 1 dia(s) antes da expiração
- Ao expirar, usuário pode re-assinar de qualquer plano em `/upgrade`

### 6. Webhook MP (IPN)
- URL: `{APP_URL}/index.php?url=subscription/webhook`
- Validação HMAC-SHA256 com `MERCADOPAGO_WEBHOOK_SECRET`
- Processa: `payment.created`, `payment.updated` (approved/rejected/refunded)

---

## Configuração

### .env — Variáveis Necessárias

```env
MERCADOPAGO_PUBLIC_KEY=TEST-...
MERCADOPAGO_ACCESS_TOKEN=TEST-...
MERCADOPAGO_WEBHOOK_SECRET=seu_segredo_aqui   # Gerado no painel MP
APP_URL=https://seudominio.com.br
```

### Configurar Webhook no Mercado Pago

1. Acesse [MP Developers](https://www.mercadopago.com.br/developers/panel)
2. Sua Aplicação → Webhooks → Configurar
3. URL: `https://seudominio.com.br/index.php?url=subscription/webhook`
4. Eventos: `payment` (created + updated)
5. Copie o `Segredo` e salve em `MERCADOPAGO_WEBHOOK_SECRET`

> **Desenvolvimento local**: use `ngrok http 8080` para expor localhost

### Cron — Lembretes e Expirações

Adicionar ao crontab do servidor (ou via Docker):

```bash
# A cada hora: expirar assinaturas e enviar lembretes
0 * * * * php /var/www/html/app/scripts/subscription_cron.php >> /var/log/cron.log 2>&1
```

**Via Docker** (adicionar ao `docker/php/Dockerfile`):
```dockerfile
RUN apt-get install -y cron
COPY crontab /etc/cron.d/subscription_cron
RUN chmod 0644 /etc/cron.d/subscription_cron && crontab /etc/cron.d/subscription_cron
```

---

## Banco de Dados

### Migração para ambientes existentes

Execute `migrations/003_subscription_management.sql` uma vez:

```bash
docker exec portfolio-db mysql -u portfolio_user -pSENHA portfolio_db < migrations/003_subscription_management.sql
```

### Tabela `subscriptions`

| Campo | Tipo | Descrição |
|---|---|---|
| `id` | INT AUTO_INCREMENT | PK |
| `user_id` | INT FK | Usuário |
| `mp_payment_id` | VARCHAR(100) | ID do pagamento no MP |
| `mp_idempotency_key` | VARCHAR(150) UNIQUE | Chave de idempotência |
| `plan_type` | ENUM(monthly,yearly) | Tipo do plano |
| `status` | ENUM | active/canceled/expired/refunded/pending/failed |
| `amount_paid` | DECIMAL(10,2) | Valor pago |
| `starts_at` / `expires_at` | DATETIME | Período da assinatura |
| `refund_eligible_until` | DATETIME | Prazo da garantia de 7 dias |
| `canceled_at` / `cancel_type` | DATETIME/ENUM | Info do cancelamento |
| `refunded_at` / `refund_mp_id` / `refund_amount` | — | Info do reembolso |
| `reminder_7/3/1_sent` | TINYINT(1) | Controle de e-mails enviados |

---

## Painel Admin

Acesse: `/admin/subscriptions`

- Métricas: MRR, assinaturas ativas, novas 30d, cancelamentos 30d, reembolsos totais
- Ações por assinatura: Cancelar · Reembolsar · Reativar
- Filtro por usuário em tempo real

---

## Rotas

| Rota | Método | Autenticação | Ação |
|---|---|---|---|
| `/upgrade` | GET | Sim | Página de planos / upgrade |
| `/checkout` | POST | Sim | Processar pagamento |
| `/subscription/manage` | GET | Sim | Gerenciar assinatura |
| `/subscription/cancel` | POST | Sim | Cancelar assinatura |
| `/subscription/refund` | POST | Sim | Solicitar reembolso |
| `/subscription/webhook` | POST | Não | Receber notificações MP |
| `/admin/subscriptions` | GET | Admin | Painel admin |
| `/admin/subscriptions/cancel/{id}` | POST | Admin | Forçar cancelamento |
| `/admin/subscriptions/refund/{id}` | POST | Admin | Forçar reembolso |
| `/admin/subscriptions/reactivate/{id}` | POST | Admin | Reativar assinatura |

