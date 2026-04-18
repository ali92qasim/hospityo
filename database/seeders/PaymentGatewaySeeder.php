<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            [
                'slug' => 'payfast',
                'name' => 'PayFast Pakistan',
                'description' => 'Accept payments via PayFast (gopayfast.com) — credit/debit cards, bank transfers, and mobile wallets in Pakistan.',
                'is_enabled' => false,
                'mode' => 'sandbox',
                'sort_order' => 1,
                'config_fields' => [
                    ['key' => 'merchant_id', 'label' => 'Merchant ID', 'type' => 'text', 'required' => true, 'placeholder' => 'Your PayFast Merchant ID'],
                    ['key' => 'secured_key', 'label' => 'Secured Key', 'type' => 'password', 'required' => true, 'placeholder' => 'Your PayFast Secured Key'],
                    ['key' => 'store_id', 'label' => 'Store ID', 'type' => 'text', 'required' => true, 'placeholder' => 'Your PayFast Store ID'],
                    ['key' => 'api_url', 'label' => 'Live API URL', 'type' => 'url', 'required' => true, 'placeholder' => 'https://ipguat.apps.net.pk/Ecommerce/api/Transaction/PostTransaction'],
                    ['key' => 'sandbox_api_url', 'label' => 'Sandbox API URL', 'type' => 'url', 'required' => false, 'placeholder' => 'https://ipguat.apps.net.pk/Ecommerce/api/Transaction/PostTransaction'],
                    ['key' => 'return_url', 'label' => 'Return URL', 'type' => 'url', 'required' => true, 'placeholder' => 'https://yourdomain.com/billing/callback', 'hint' => 'URL where customer is redirected after payment'],
                    ['key' => 'grant_type', 'label' => 'Grant Type', 'type' => 'text', 'required' => false, 'placeholder' => 'client_credentials'],
                ],
            ],
            [
                'slug' => 'jazzcash',
                'name' => 'JazzCash',
                'description' => 'Accept payments via JazzCash — mobile wallets, MWALLET, and credit/debit cards in Pakistan.',
                'is_enabled' => false,
                'mode' => 'sandbox',
                'sort_order' => 2,
                'config_fields' => [
                    ['key' => 'merchant_id', 'label' => 'Merchant ID', 'type' => 'text', 'required' => true, 'placeholder' => 'Your JazzCash Merchant ID'],
                    ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'placeholder' => 'Your JazzCash Password'],
                    ['key' => 'integrity_salt', 'label' => 'Integrity Salt', 'type' => 'password', 'required' => true, 'placeholder' => 'Your Integrity Salt'],
                    ['key' => 'api_url', 'label' => 'Live API URL', 'type' => 'url', 'required' => true, 'placeholder' => 'https://payments.jazzcash.com.pk/ApplicationAPI/API/2.0/Purchase/DoMWalletTransaction'],
                    ['key' => 'sandbox_api_url', 'label' => 'Sandbox API URL', 'type' => 'url', 'required' => false, 'placeholder' => 'https://sandbox.jazzcash.com.pk/ApplicationAPI/API/2.0/Purchase/DoMWalletTransaction'],
                    ['key' => 'return_url', 'label' => 'Return URL', 'type' => 'url', 'required' => true, 'placeholder' => 'https://yourdomain.com/billing/jazzcash/callback'],
                ],
            ],
            [
                'slug' => 'easypaisa',
                'name' => 'Easypaisa',
                'description' => 'Accept payments via Easypaisa — mobile accounts and OTC payments across Pakistan.',
                'is_enabled' => false,
                'mode' => 'sandbox',
                'sort_order' => 3,
                'config_fields' => [
                    ['key' => 'store_id', 'label' => 'Store ID', 'type' => 'text', 'required' => true, 'placeholder' => 'Your Easypaisa Store ID'],
                    ['key' => 'account_num', 'label' => 'Account Number', 'type' => 'text', 'required' => true, 'placeholder' => 'Your Easypaisa Account Number'],
                    ['key' => 'hash_key', 'label' => 'Hash Key', 'type' => 'password', 'required' => true, 'placeholder' => 'Your Easypaisa Hash Key'],
                    ['key' => 'api_url', 'label' => 'Live API URL', 'type' => 'url', 'required' => true, 'placeholder' => 'https://easypay.easypaisa.com.pk/easypay/Index.jsf'],
                    ['key' => 'sandbox_api_url', 'label' => 'Sandbox API URL', 'type' => 'url', 'required' => false, 'placeholder' => 'https://easypaystg.easypaisa.com.pk/easypay/Index.jsf'],
                    ['key' => 'return_url', 'label' => 'Return URL', 'type' => 'url', 'required' => true, 'placeholder' => 'https://yourdomain.com/billing/easypaisa/callback'],
                ],
            ],
            [
                'slug' => 'stripe',
                'name' => 'Stripe',
                'description' => 'Accept international payments via Stripe — credit/debit cards, Apple Pay, Google Pay worldwide.',
                'is_enabled' => false,
                'mode' => 'sandbox',
                'sort_order' => 4,
                'config_fields' => [
                    ['key' => 'publishable_key', 'label' => 'Publishable Key', 'type' => 'text', 'required' => true, 'placeholder' => 'pk_test_...', 'hint' => 'Starts with pk_test_ (sandbox) or pk_live_ (live)'],
                    ['key' => 'secret_key', 'label' => 'Secret Key', 'type' => 'password', 'required' => true, 'placeholder' => 'sk_test_...', 'hint' => 'Starts with sk_test_ (sandbox) or sk_live_ (live)'],
                    ['key' => 'webhook_secret', 'label' => 'Webhook Secret', 'type' => 'password', 'required' => false, 'placeholder' => 'whsec_...', 'hint' => 'From Stripe Dashboard → Webhooks'],
                    ['key' => 'currency', 'label' => 'Currency', 'type' => 'select', 'required' => true, 'options' => ['pkr' => 'PKR', 'usd' => 'USD', 'eur' => 'EUR', 'gbp' => 'GBP']],
                ],
            ],
            [
                'slug' => 'paddle',
                'name' => 'Paddle',
                'description' => 'Accept global payments via Paddle — handles tax compliance, invoicing, and subscriptions as merchant of record.',
                'is_enabled' => false,
                'mode' => 'sandbox',
                'sort_order' => 5,
                'config_fields' => [
                    ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'placeholder' => 'Your Paddle API Key', 'hint' => 'Developer Tools → Authentication → API Keys'],
                    ['key' => 'client_side_token', 'label' => 'Client-Side Token', 'type' => 'text', 'required' => true, 'placeholder' => 'test_... or live_...', 'hint' => 'Developer Tools → Authentication → Client-side tokens'],
                    ['key' => 'webhook_secret', 'label' => 'Webhook Secret Key', 'type' => 'password', 'required' => false, 'placeholder' => 'pdl_ntfset_...', 'hint' => 'Notifications → Webhook destination → Secret key'],
                ],
            ],
        ];

        foreach ($gateways as $data) {
            PaymentGateway::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
