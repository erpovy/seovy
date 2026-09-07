<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting value by key with a fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting || $setting->value === null) {
            return $default;
        }

        $val = $setting->value;
        if ($val === 'true') return true;
        if ($val === 'false') return false;
        if (is_numeric($val)) return $val + 0;

        $decoded = json_decode($val, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $val;
    }

    /**
     * Set or update a setting value by key.
     */
    public static function set(string $key, mixed $value): void
    {
        if (is_bool($value)) {
            $valString = $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            $valString = json_encode($value);
        } elseif ($value === null) {
            $valString = null;
        } else {
            $valString = (string) $value;
        }

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $valString]
        );
    }

    /**
     * Default platform features for landing and features page.
     */
    public static function getDefaultFeatures(): array
    {
        return [
            [
                'id' => 'crawler',
                'title' => 'Güvenli ve Asenkron Tarayıcı',
                'description' => 'SSRF korumalı, robots.txt ve sitemap indekslerini çözümleyen, concurrency ve hız sınırlamalı yüksek hızlı crawler.',
                'icon' => 'Search',
                'color' => 'indigo',
                'badge' => 'Yüksek Hızlı',
                'is_active' => true,
            ],
            [
                'id' => 'analyzer',
                'title' => '25+ Teknik SEO Analiz Kuralı',
                'description' => 'Title, meta description, canonical, hiyerarşi, yönlendirme döngüleri, JSON-LD doğrulaması ve şeffaf sağlık skoru.',
                'icon' => 'ShieldCheck',
                'color' => 'violet',
                'badge' => 'Derin Analiz',
                'is_active' => true,
            ],
            [
                'id' => 'workspaces',
                'title' => 'İzole Çalışma Alanları & Takım',
                'description' => 'Ekip üyeleri için rol yönetimi, veri izolasyonu ve bağımsız sunucu ya da çok müşterili SaaS desteği.',
                'icon' => 'Layers',
                'color' => 'cyan',
                'badge' => 'Multi-Tenant',
                'is_active' => true,
            ],
            [
                'id' => 'rank_tracking',
                'title' => 'Anahtar Kelime & Sıra Takibi',
                'description' => 'Google SERP sıralamalarınızı günlük takip edin, trendleri analiz edin ve rakiplerinizin önüne geçin.',
                'icon' => 'BarChart3',
                'color' => 'emerald',
                'badge' => 'SERP Takip',
                'is_active' => true,
            ],
            [
                'id' => 'ai_seo',
                'title' => 'Yapay Zeka Destekli İçerik & SEO',
                'description' => 'Eksik başlıkları, açıklamaları ve içerik boşluklarını yapay zeka önerileriyle anında optimize edin.',
                'icon' => 'Sparkles',
                'color' => 'amber',
                'badge' => 'AI Entegrasyonu',
                'is_active' => true,
            ],
            [
                'id' => 'payments',
                'title' => 'Esnek Abonelik & Sanal POS',
                'description' => 'Stripe, Iyzico, PayTR ve daha fazlası ile kolay ödeme alma, kota yönetimi ve otomatik faturalandırma.',
                'icon' => 'CreditCard',
                'color' => 'pink',
                'badge' => 'Ödeme Altyapısı',
                'is_active' => true,
            ],
        ];
    }
}

