<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'country_code',
        'country_name',
        'language_code',
        'currency',
        'icon',
        'description',
        'is_active',
        'mode',
        'credentials',
        'settings',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credentials' => 'array',
        'settings' => 'array',
        'sort_order' => 'integer',
    ];

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'gateway_code', 'code');
    }

    /**
     * Ensure default gateways are populated in the database.
     */
    public static function ensureDefaultGateways(): void
    {
        if (static::count() === 0) {
            foreach (static::getDefaultGateways() as $gateway) {
                static::create($gateway);
            }
        }
    }

    /**
     * Get default seeded gateways for all supported language countries.
     */
    public static function getDefaultGateways(): array
    {
        return [
            [
                'code' => 'iyzico',
                'name' => 'Iyzico',
                'country_code' => 'TR',
                'country_name' => 'Türkiye',
                'language_code' => 'tr',
                'currency' => 'TRY',
                'icon' => 'CreditCard',
                'description' => 'Türkiye\'nin lider sanal POS altyapısı. Troy, Visa, Mastercard, 3D Secure ve taksit desteği.',
                'is_active' => true,
                'mode' => 'test',
                'credentials' => [
                    'api_key' => 'sandbox-t3st-iyzico-api-key',
                    'secret_key' => 'sandbox-t3st-iyzico-secret-key',
                    'base_url' => 'https://sandbox-api.iyzipay.com',
                ],
                'settings' => [
                    'installments' => true,
                    'three_d_secure' => true,
                ],
                'sort_order' => 1,
            ],
            [
                'code' => 'stripe',
                'name' => 'Stripe',
                'country_code' => 'US',
                'country_name' => 'Global / United States',
                'language_code' => 'en',
                'currency' => 'USD',
                'icon' => 'Globe',
                'description' => 'Global online ödeme ve abonelik altyapısı. Apple Pay, Google Pay ve 135+ para birimi.',
                'is_active' => true,
                'mode' => 'test',
                'credentials' => [
                    'publishable_key' => 'pk_test_sample_stripe_publishable_key',
                    'secret_key' => 'sk_test_sample_stripe_secret_key',
                    'webhook_secret' => 'whsec_sample_stripe_webhook_secret',
                ],
                'settings' => [
                    'apple_pay' => true,
                    'google_pay' => true,
                ],
                'sort_order' => 2,
            ],
            [
                'code' => 'klarna',
                'name' => 'Klarna / Sofort',
                'country_code' => 'DE',
                'country_name' => 'Deutschland',
                'language_code' => 'de',
                'currency' => 'EUR',
                'icon' => 'ShieldCheck',
                'description' => 'Almanya ve DACH bölgesinin en popüler doğrudan banka havalesi ve sonradan ödeme (BNPL) sistemi.',
                'is_active' => true,
                'mode' => 'test',
                'credentials' => [
                    'merchant_id' => 'de_klarna_merchant_test_id',
                    'shared_secret' => 'klarna_shared_secret_sandbox',
                ],
                'settings' => [
                    'sofort_banking' => true,
                    'pay_now' => true,
                ],
                'sort_order' => 3,
            ],
            [
                'code' => 'payplug',
                'name' => 'Payplug & CB',
                'country_code' => 'FR',
                'country_name' => 'France',
                'language_code' => 'fr',
                'currency' => 'EUR',
                'icon' => 'CheckCircle2',
                'description' => 'Fransa\'nın 1 numaralı KOBİ ve e-ticaret kart ödeme ağı. Cartes Bancaires (CB) ve 3DS2.',
                'is_active' => true,
                'mode' => 'test',
                'credentials' => [
                    'secret_key' => 'live_or_test_payplug_token_key',
                ],
                'settings' => [
                    'cartes_bancaires' => true,
                ],
                'sort_order' => 4,
            ],
            [
                'code' => 'redsys',
                'name' => 'Redsýs & Bizum',
                'country_code' => 'ES',
                'country_name' => 'España',
                'language_code' => 'es',
                'currency' => 'EUR',
                'icon' => 'CreditCard',
                'description' => 'İspanya bankalarının resmi sanal POS altyapısı ve popüler anlık mobil ödeme çözümü Bizum.',
                'is_active' => true,
                'mode' => 'test',
                'credentials' => [
                    'merchant_code' => '999008881',
                    'terminal' => '1',
                    'encryption_key' => 'sq7HjrUOBfKmC576ILgskD5srU870g==',
                ],
                'settings' => [
                    'bizum_enabled' => true,
                ],
                'sort_order' => 5,
            ],
            [
                'code' => 'satispay',
                'name' => 'Satispay & Nexi',
                'country_code' => 'IT',
                'country_name' => 'Italia',
                'language_code' => 'it',
                'currency' => 'EUR',
                'icon' => 'Smartphone',
                'description' => 'İtalya\'nın en hızlı büyüyen bağımsız akıllı ödeme ağı ve bankalararası Nexi POS entegrasyonu.',
                'is_active' => true,
                'mode' => 'test',
                'credentials' => [
                    'token' => 'sandbox_bearer_satispay_auth_token',
                    'key_id' => 'sandbox_key_id_italian_pos',
                ],
                'settings' => [
                    'qr_code_payment' => true,
                ],
                'sort_order' => 6,
            ],
            [
                'code' => 'mercadopago',
                'name' => 'Mercado Pago & Pix',
                'country_code' => 'PT',
                'country_name' => 'Brasil & Portugal',
                'language_code' => 'pt',
                'currency' => 'BRL',
                'icon' => 'Zap',
                'description' => 'Brezilya ve Portekiz dünyasının en büyük ödeme platformu. Pix anlık transfer ve Multibanco desteği.',
                'is_active' => true,
                'mode' => 'test',
                'credentials' => [
                    'public_key' => 'TEST-mercadopago-public-key',
                    'access_token' => 'TEST-mercadopago-access-token',
                ],
                'settings' => [
                    'pix_instant' => true,
                    'multibanco' => true,
                ],
                'sort_order' => 7,
            ],
            [
                'code' => 'yookassa',
                'name' => 'YooKassa (ЮKassa)',
                'country_code' => 'RU',
                'country_name' => 'Россия / CIS',
                'language_code' => 'ru',
                'currency' => 'RUB',
                'icon' => 'CreditCard',
                'description' => 'Rusya ve Avrasya coğrafyasının en büyük online ödeme çözümü. Mir kartları, SberPay ve SBP desteği.',
                'is_active' => true,
                'mode' => 'test',
                'credentials' => [
                    'shop_id' => 'sandbox_shop_yookassa_123456',
                    'secret_key' => 'test_secret_yookassa_key_abcdef',
                ],
                'settings' => [
                    'sbp_fast_payments' => true,
                    'mir_accepted' => true,
                ],
                'sort_order' => 8,
            ],
            [
                'code' => 'alipay',
                'name' => 'Alipay & WeChat Pay',
                'country_code' => 'ZH',
                'country_name' => '中国 (China & APAC)',
                'language_code' => 'zh',
                'currency' => 'CNY',
                'icon' => 'QrCode',
                'description' => 'Çin ve Asya-Pasifik ekosisteminin %90+ pazar payına sahip dijital cüzdan ve QR ödeme ağı.',
                'is_active' => true,
                'mode' => 'test',
                'credentials' => [
                    'app_id' => '20210001test_alipay_app_id',
                    'merchant_private_key' => 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcw...',
                    'alipay_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCg...',
                ],
                'settings' => [
                    'wechat_pay' => true,
                    'qr_scan' => true,
                ],
                'sort_order' => 9,
            ],
            [
                'code' => 'tappayments',
                'name' => 'Tap Payments & Mada',
                'country_code' => 'AR',
                'country_name' => 'المملكة العربية السعودية / Gulf (MENA)',
                'language_code' => 'ar',
                'currency' => 'SAR',
                'icon' => 'CreditCard',
                'description' => 'Körfez ve MENA bölgesinin lider Sanal POS sistemi. Mada, KNET, Benefit, Apple Pay ve Sadad.',
                'is_active' => true,
                'mode' => 'test',
                'credentials' => [
                    'publishable_key' => 'pk_test_tap_payments_publishable_token',
                    'secret_key' => 'sk_test_tap_payments_secret_token',
                ],
                'settings' => [
                    'mada_cards' => true,
                    'apple_pay_mena' => true,
                ],
                'sort_order' => 10,
            ],
        ];
    }
}
