<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'default_shop_id', 'store_name', 'logo_path', 'favicon_path',
        'currency_code', 'currency_symbol', 'special_offer_text',
        'blog_hero_kicker', 'blog_hero_title', 'blog_hero_subtitle', 'blog_hero_image',
        'blog_newsletter_title', 'blog_newsletter_text',
        'blog_articles_title',
        'blog_feature_1_title', 'blog_feature_1_text',
        'blog_feature_2_title', 'blog_feature_2_text',
        'blog_feature_3_title', 'blog_feature_3_text',
        'faq_hero_title', 'faq_hero_subtitle',
        'faq_help_title', 'faq_help_text', 'faq_help_button',
        'contact_hero_kicker', 'contact_hero_title', 'contact_hero_subtitle',
        'contact_chat_title', 'contact_chat_text', 'contact_chat_status',
        'contact_email_card_title', 'contact_email_card_text',
        'contact_phone_card_title', 'contact_phone_card_text',
        'contact_hours_title', 'contact_hours_weekday', 'contact_hours_weekend',
        'contact_form_title', 'contact_form_subtitle',
        'contact_map_embed', 'contact_website_url',
        'contact_newsletter_title', 'contact_newsletter_text',
        'trusted_by_text',
        'footer_tagline',
        'home_copy',
        'deals_kicker', 'deals_title', 'deals_title_accent', 'deals_subtitle',
        'contact_email', 'contact_phone',
        'contact_address', 'social_links',
        'delivery_inside_dhaka', 'delivery_outside_dhaka',
        'delivery_free_enabled', 'delivery_free_min_amount',
        'delivery_cod_enabled', 'delivery_confirmation_enabled',
        'delivery_confirmation_amount', 'delivery_confirmation_instructions',
    ];

    /** Social profiles editable under CMS → Contact; official filled logos (24×24 viewBox). */
    public const SOCIAL_NETWORKS = [
        'facebook' => [
            'label' => 'Facebook',
            'placeholder' => 'https://facebook.com/yourpage',
            // Official “f” mark
            'path' => 'M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z',
        ],
        'instagram' => [
            'label' => 'Instagram',
            'placeholder' => 'https://instagram.com/yourpage',
            // Official camera glyph
            'path' => 'M12 0C8.74 0 8.333.015 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.74 0 12s.014 3.668.072 4.948c.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.74 24 12 24s3.668-.014 4.948-.072c4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948s-.014-3.667-.072-4.947C23.704 2.69 21.282.273 16.948.073 15.668.014 15.259 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 3.269.148 4.771 1.691 4.919 4.919.055 1.265.068 1.645.068 4.849 0 3.205-.013 3.584-.068 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.056-1.646.068-4.85.068-3.203 0-3.585-.012-4.849-.068-3.26-.149-4.771-1.699-4.919-4.92-.056-1.265-.068-1.645-.068-4.849 0-3.204.013-3.583.068-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z',
        ],
        'tiktok' => [
            'label' => 'TikTok',
            'placeholder' => 'https://tiktok.com/@yourpage',
            // Official note mark
            'path' => 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z',
        ],
        'whatsapp' => [
            'label' => 'WhatsApp',
            'placeholder' => '01XXXXXXXXX or https://wa.me/8801XXXXXXXXX',
            // Official bubble + handset
            'path' => 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z',
        ],
        'youtube' => [
            'label' => 'YouTube',
            'placeholder' => 'https://youtube.com/@yourchannel',
            // Official play badge
            'path' => 'M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
        ],
        'twitter' => [
            'label' => 'X / Twitter',
            'placeholder' => 'https://x.com/yourpage',
            'path' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.438 6.231H2.71l7.728-8.835L1.254 2.25H8.08l4.252 5.672L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z',
        ],
        'pinterest' => [
            'label' => 'Pinterest',
            'placeholder' => 'https://pinterest.com/yourpage',
            'path' => 'M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z',
        ],
    ];

    /**
     * Filled social profiles in display order.
     *
     * @return list<array{key: string, label: string, url: string, path: string}>
     */
    public static function socialProfiles(?array $saved): array
    {
        $rows = [];
        foreach (self::SOCIAL_NETWORKS as $key => $meta) {
            $url = trim((string) ($saved[$key] ?? ''));
            if ($url !== '') {
                $rows[] = ['key' => $key, 'label' => $meta['label'], 'url' => $url, 'path' => $meta['path']];
            }
        }

        return $rows;
    }

    /** Accepts a WhatsApp link or a phone number (01XXXXXXXXX / +8801…) and returns a wa.me link. */
    public static function whatsappUrl(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || preg_match('#^https?://#i', $value)) {
            return $value !== '' ? $value : null;
        }
        $digits = preg_replace('/\D+/', '', $value);
        if (str_starts_with($digits, '01') && strlen($digits) === 11) {
            $digits = '88'.$digits;
        }

        return $digits !== '' ? 'https://wa.me/'.$digits : null;
    }

    protected $casts = [
        'social_links' => 'array',
        'home_copy' => 'array',
        'delivery_inside_dhaka' => 'decimal:2',
        'delivery_outside_dhaka' => 'decimal:2',
        'delivery_free_enabled' => 'boolean',
        'delivery_free_min_amount' => 'decimal:2',
        'delivery_cod_enabled' => 'boolean',
        'delivery_confirmation_enabled' => 'boolean',
        'delivery_confirmation_amount' => 'decimal:2',
    ];

    public function defaultShop()
    {
        return $this->belongsTo(Shop::class, 'default_shop_id');
    }

    public static function current(): self
    {
        return static::query()->first() ?? new static([
            'store_name' => config('app.name', 'Hira Moni'),
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
        ]);
    }
}
