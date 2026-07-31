<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use App\Services\ProductCatalogService;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalogService = app(ProductCatalogService::class);

        $colorVariant = Variant::query()->firstOrCreate(
            ['slug' => 'renk'],
            ['name' => 'Renk'],
        );
        Variant::query()->firstOrCreate(['slug' => 'hafiza'], ['name' => 'Hafıza']);
        Variant::query()->firstOrCreate(['slug' => 'model'], ['name' => 'Model']);
        Variant::query()->firstOrCreate(['slug' => 'beden'], ['name' => 'Beden']);
        Variant::query()->firstOrCreate(['slug' => 'koku'], ['name' => 'Koku']);

        $electronics = $this->category('elektronik', 'Elektronik', 'Telefon, tablet ve teknoloji ürünleri.');
        $phones = $this->category('telefonlar', 'Telefonlar', 'Akıllı telefon modelleri.', $electronics->id);
        $headphones = $this->category('kulakliklar', 'Kulaklıklar', 'Kablosuz ve kablolu kulaklıklar.', $electronics->id);
        $smartwatches = $this->category('akilli-saatler', 'Akıllı Saatler', 'Sağlık ve bildirim takibi.', $electronics->id);
        $tablets = $this->category('tabletler', 'Tabletler', 'Tablet bilgisayarlar.', $electronics->id);

        $clothing = $this->category('giyim', 'Giyim', 'Günlük giyim ürünleri.');
        $tshirts = $this->category('tisortler', 'Tişörtler', 'Pamuklu tişört modelleri.', $clothing->id);
        $pants = $this->category('pantolonlar', 'Pantolonlar', 'Günlük pantolon modelleri.', $clothing->id);
        $jackets = $this->category('ceketler', 'Ceketler', 'Mevsimlik ceket ve hoodieler.', $clothing->id);

        $accessories = $this->category('aksesuar', 'Aksesuar', 'Çanta, cüzdan ve gözlük.');
        $bags = $this->category('cantalar', 'Çantalar', 'Günlük çanta modelleri.', $accessories->id);
        $glasses = $this->category('gozlukler', 'Gözlükler', 'Güneş gözlüğü modelleri.', $accessories->id);
        $wallets = $this->category('cuzdanlar', 'Cüzdanlar', 'Deri cüzdan modelleri.', $accessories->id);

        $home = $this->category('ev-yasam', 'Ev & Yaşam', 'Ev ve kişisel bakım ürünleri.');
        $kitchen = $this->category('mutfak', 'Mutfak', 'Mutfak gereçleri.', $home->id);
        $decoration = $this->category('dekorasyon', 'Dekorasyon', 'Ev dekorasyon ürünleri.', $home->id);
        $personalCare = $this->category('kisisel-bakim', 'Kişisel Bakım', 'Kişisel bakım cihazları.', $home->id);

        $novaPhone = $this->product(
            'Nova X Pro',
            $phones->id,
            $colorVariant->id,
            24999.99,
            '120 Hz AMOLED ekran, çift kamera ve hızlı şarj destekli amiral gemisi telefon.',
        );
        $catalogService->syncVariants($novaPhone, [
            ['sku' => 'NOVA-X-BLACK-128', 'stock' => 8, 'color' => 'Siyah', 'memory' => '128 GB', 'model' => 'Pro'],
            ['sku' => 'NOVA-X-WHITE-128', 'stock' => 5, 'color' => 'Beyaz', 'memory' => '128 GB', 'model' => 'Pro'],
            ['sku' => 'NOVA-X-BLUE-256', 'stock' => 3, 'color' => 'Mavi', 'memory' => '256 GB', 'model' => 'Pro Max'],
        ]);

        $iphone = $this->product(
            'iPhone 16',
            $phones->id,
            $colorVariant->id,
            79999.00,
            'A18 çip, gelişmiş kamera sistemi ve all-day pil ömrü.',
        );
        $catalogService->syncVariants($iphone, [
            ['sku' => 'IP16-BLACK-128', 'stock' => 6, 'color' => 'Siyah', 'memory' => '128 GB', 'model' => 'Standard'],
            ['sku' => 'IP16-WHITE-256', 'stock' => 4, 'color' => 'Beyaz', 'memory' => '256 GB', 'model' => 'Standard'],
            ['sku' => 'IP16-PINK-128', 'stock' => 2, 'color' => 'Pembe', 'memory' => '128 GB', 'model' => 'Standard'],
        ]);

        $airpods = $this->product(
            'AirPods Pro 2',
            $headphones->id,
            $colorVariant->id,
            8499.00,
            'Aktif gürültü engelleme ve şeffaf mod destekli kablosuz kulaklık.',
        );
        $catalogService->syncVariants($airpods, [
            ['sku' => 'APP2-WHITE', 'stock' => 15, 'color' => 'Beyaz', 'model' => 'Pro 2'],
            ['sku' => 'APP2-BLACK', 'stock' => 2, 'color' => 'Siyah', 'model' => 'Pro 2'],
        ]);

        $watch = $this->product(
            'Galaxy Watch 7',
            $smartwatches->id,
            $colorVariant->id,
            11999.00,
            'Sağlık takibi, uyku analizi ve çoklu spor modu.',
        );
        $catalogService->syncVariants($watch, [
            ['sku' => 'GW7-BLACK-44', 'stock' => 7, 'color' => 'Siyah', 'model' => '44mm'],
            ['sku' => 'GW7-SILVER-40', 'stock' => 5, 'color' => 'Gümüş', 'model' => '40mm'],
            ['sku' => 'GW7-GREEN-44', 'stock' => 3, 'color' => 'Yeşil', 'model' => '44mm'],
        ]);

        $ipad = $this->product(
            'iPad Air',
            $tablets->id,
            $colorVariant->id,
            27999.00,
            'M2 çip, Liquid Retina ekran ve Apple Pencil desteği.',
        );
        $catalogService->syncVariants($ipad, [
            ['sku' => 'IPAD-AIR-128-BLUE', 'stock' => 5, 'color' => 'Mavi', 'memory' => '128 GB', 'model' => 'Wi-Fi'],
            ['sku' => 'IPAD-AIR-256-GRAY', 'stock' => 3, 'color' => 'Uzay Grisi', 'memory' => '256 GB', 'model' => 'Wi-Fi'],
        ]);

        $tshirt = $this->product(
            'Essential Pamuklu Tişört',
            $tshirts->id,
            $colorVariant->id,
            349.90,
            '%100 pamuk, regular fit, günlük kullanım için konforlu tişört.',
        );
        $catalogService->syncVariants($tshirt, [
            ['sku' => 'TEE-BLACK-M', 'stock' => 20, 'color' => 'Siyah', 'model' => 'Regular', 'extra_attributes' => [['name' => 'Beden', 'value' => 'M']]],
            ['sku' => 'TEE-BLACK-L', 'stock' => 15, 'color' => 'Siyah', 'model' => 'Regular', 'extra_attributes' => [['name' => 'Beden', 'value' => 'L']]],
            ['sku' => 'TEE-WHITE-M', 'stock' => 12, 'color' => 'Beyaz', 'model' => 'Regular', 'extra_attributes' => [['name' => 'Beden', 'value' => 'M']]],
            ['sku' => 'TEE-OLIVE-L', 'stock' => 10, 'color' => 'Haki', 'model' => 'Oversize', 'extra_attributes' => [['name' => 'Beden', 'value' => 'L']]],
        ]);

        $chino = $this->product(
            'Slim Fit Chino',
            $pants->id,
            $colorVariant->id,
            649.00,
            'Esnek kumaş, slim fit kesim günlük pantolon.',
        );
        $catalogService->syncVariants($chino, [
            ['sku' => 'CHINO-NAVY-32', 'stock' => 8, 'color' => 'Lacivert', 'model' => 'Slim', 'extra_attributes' => [['name' => 'Beden', 'value' => '32']]],
            ['sku' => 'CHINO-BEIGE-32', 'stock' => 6, 'color' => 'Bej', 'model' => 'Slim', 'extra_attributes' => [['name' => 'Beden', 'value' => '32']]],
            ['sku' => 'CHINO-BLACK-34', 'stock' => 4, 'color' => 'Siyah', 'model' => 'Slim', 'extra_attributes' => [['name' => 'Beden', 'value' => '34']]],
        ]);

        $hoodie = $this->product(
            'Oversize Hoodie',
            $jackets->id,
            $colorVariant->id,
            849.00,
            'Yumuşak iç yüzey, oversize kesim kapüşonlu sweatshirt.',
        );
        $catalogService->syncVariants($hoodie, [
            ['sku' => 'HOOD-BLACK-M', 'stock' => 9, 'color' => 'Siyah', 'model' => 'Oversize', 'extra_attributes' => [['name' => 'Beden', 'value' => 'M']]],
            ['sku' => 'HOOD-GRAY-L', 'stock' => 7, 'color' => 'Gri', 'model' => 'Oversize', 'extra_attributes' => [['name' => 'Beden', 'value' => 'L']]],
            ['sku' => 'HOOD-CREAM-XL', 'stock' => 2, 'color' => 'Krem', 'model' => 'Oversize', 'extra_attributes' => [['name' => 'Beden', 'value' => 'XL']]],
        ]);

        $backpack = $this->product(
            'Urban Sırt Çantası',
            $bags->id,
            $colorVariant->id,
            899.00,
            '15 inç laptop bölmeli, su itici kumaşlı günlük sırt çantası.',
        );
        $catalogService->syncVariants($backpack, [
            ['sku' => 'BAG-BLACK', 'stock' => 7, 'color' => 'Siyah', 'model' => 'Urban'],
            ['sku' => 'BAG-NAVY', 'stock' => 4, 'color' => 'Lacivert', 'model' => 'Urban'],
        ]);

        $wallet = $this->product(
            'Deri Cüzdan',
            $wallets->id,
            $colorVariant->id,
            449.00,
            'El yapımı dana derisi, RFID korumalı kart bölmeli cüzdan.',
        );
        $catalogService->syncVariants($wallet, [
            ['sku' => 'WALLET-BROWN', 'stock' => 12, 'color' => 'Kahverengi', 'model' => 'Classic'],
            ['sku' => 'WALLET-BLACK', 'stock' => 10, 'color' => 'Siyah', 'model' => 'Classic'],
        ]);

        $sunglasses = $this->product(
            'Polarize Güneş Gözlüğü',
            $glasses->id,
            $colorVariant->id,
            1199.00,
            'UV400 polarize cam, hafif çerçeve güneş gözlüğü.',
        );
        $catalogService->syncVariants($sunglasses, [
            ['sku' => 'SUN-BLACK-01', 'stock' => 8, 'color' => 'Siyah', 'model' => 'Aviator'],
            ['sku' => 'SUN-TORT-02', 'stock' => 5, 'color' => 'Kaplumbağa', 'model' => 'Round'],
        ]);

        $frenchPress = $this->product(
            'French Press',
            $kitchen->id,
            $colorVariant->id,
            349.00,
            'Borosilikat cam gövde, 600 ml kapasiteli French press.',
        );
        $catalogService->syncVariants($frenchPress, [
            ['sku' => 'FP-CLEAR', 'stock' => 14, 'color' => 'Şeffaf', 'model' => '600ml'],
            ['sku' => 'FP-COPPER', 'stock' => 6, 'color' => 'Bakır', 'model' => '600ml'],
        ]);

        $candles = $this->product(
            'Kokulu Mum Seti',
            $decoration->id,
            $colorVariant->id,
            279.00,
            '3\'lü soya mum seti, uzun yanma süresi.',
        );
        $catalogService->syncVariants($candles, [
            ['sku' => 'CANDLE-LAV', 'stock' => 11, 'color' => 'Mor', 'model' => 'Set', 'extra_attributes' => [['name' => 'Koku', 'value' => 'Lavanta']]],
            ['sku' => 'CANDLE-VAN', 'stock' => 9, 'color' => 'Krem', 'model' => 'Set', 'extra_attributes' => [['name' => 'Koku', 'value' => 'Vanilya']]],
        ]);

        $hairDryer = $this->product(
            'Saç Kurutma Makinesi',
            $personalCare->id,
            $colorVariant->id,
            2499.00,
            'İyon teknolojisi, 3 ısı kademesi profesyonel saç kurutma.',
        );
        $catalogService->syncVariants($hairDryer, [
            ['sku' => 'DRYER-BLACK', 'stock' => 5, 'color' => 'Siyah', 'model' => 'Pro'],
            ['sku' => 'DRYER-WHITE', 'stock' => 3, 'color' => 'Beyaz', 'model' => 'Pro'],
        ]);

        $customer = User::query()->where('email', 'user@blog.test')->first();

        if ($customer !== null) {
            Address::query()->firstOrCreate(
                [
                    'user_id' => $customer->id,
                    'title' => 'Ev',
                ],
                [
                    'first_name' => 'Demo',
                    'last_name' => 'Müşteri',
                    'phone' => '05551234567',
                    'address_line_1' => 'Bağdat Caddesi No: 120',
                    'address_line_2' => 'Daire 5',
                    'city' => 'İstanbul',
                    'state' => 'Kadıköy',
                    'postal_code' => '34710',
                    'country' => 'Türkiye',
                    'is_default' => true,
                ],
            );
        }
    }

    private function category(
        string $slug,
        string $name,
        string $description,
        ?int $parentId = null,
    ): Category {
        return Category::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'parent_id' => $parentId,
                'name' => $name,
                'description' => $description,
            ],
        );
    }

    private function product(
        string $name,
        int $categoryId,
        int $baseVariantId,
        float $price,
        string $description,
    ): Product {
        return Product::query()->updateOrCreate(
            ['name' => $name],
            [
                'category_id' => $categoryId,
                'base_variant_id' => $baseVariantId,
                'price' => $price,
                'description' => $description,
            ],
        );
    }
}
