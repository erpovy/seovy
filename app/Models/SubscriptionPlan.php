<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'price',
        'currency',
        'billing_period',
        'features',
        'limits',
        'is_active',
        'is_popular',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'limits' => 'array',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function getDefaultPlans(): array
    {
        return [
            [
                'code' => 'free',
                'name' => 'Ücretsiz Plan',
                'price' => 0.00,
                'currency' => 'TRY',
                'billing_period' => 'monthly',
                'features' => [
                    '1 Proje / Web Sitesi',
                    'Aylık 500 Taranan Sayfa',
                    '10 Anahtar Kelime Sıra Takibi',
                    '1 Kullanıcı / Yönetici',
                    'Temel Teknik SEO Raporları',
                ],
                'limits' => [
                    'max_projects' => 1,
                    'max_pages_monthly' => 500,
                    'max_keywords' => 10,
                    'team_members' => 1,
                ],
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
            ],
            [
                'code' => 'pro',
                'name' => 'Pro Plan',
                'price' => 499.00,
                'currency' => 'TRY',
                'billing_period' => 'monthly',
                'features' => [
                    '5 Proje / Web Sitesi',
                    'Aylık 20.000 Taranan Sayfa',
                    '100 Anahtar Kelime Sıra Takibi',
                    '5 Ekip Üyesi',
                    'GSC & PageSpeed Entegrasyonu',
                    'Otomatik PDF Denetim Raporları',
                    'GEO & LLM Yapay Zeka Uyumluluğu',
                ],
                'limits' => [
                    'max_projects' => 5,
                    'max_pages_monthly' => 20000,
                    'max_keywords' => 100,
                    'team_members' => 5,
                ],
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'agency',
                'name' => 'Ajans Planı',
                'price' => 1499.00,
                'currency' => 'TRY',
                'billing_period' => 'monthly',
                'features' => [
                    '50 Proje / Web Sitesi',
                    'Aylık 200.000 Taranan Sayfa',
                    '2.000 Anahtar Kelime Sıra Takibi',
                    '25 Ekip Üyesi',
                    'Beyaz Etiket (White Label) PDF',
                    'Öncelikli Arka Plan Tarama Kuyruğu',
                    'Gelişmiş SERP & Rakip Analizi',
                ],
                'limits' => [
                    'max_projects' => 50,
                    'max_pages_monthly' => 200000,
                    'max_keywords' => 2000,
                    'team_members' => 25,
                ],
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 3,
            ],
        ];
    }

    public static function ensureDefaultPlans(): void
    {
        if (static::count() === 0) {
            foreach (static::getDefaultPlans() as $planData) {
                static::create($planData);
            }
        }
    }
}
