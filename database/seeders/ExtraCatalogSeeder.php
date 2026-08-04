<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Variant;
use App\Services\ProductCatalogService;
use Illuminate\Database\Seeder;

class ExtraCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalogService = app(ProductCatalogService::class);

        $colorVariant = Variant::query()->firstOrCreate(
            ['slug' => 'renk'],
            ['name' => 'Renk'],
        );

        $phones = Category::query()->where('slug', 'telefonlar')->first();
        $headphones = Category::query()->where('slug', 'kulakliklar')->first();
        $smartwatches = Category::query()->where('slug', 'akilli-saatler')->first();
        $tablets = Category::query()->where('slug', 'tabletler')->first();
        $tshirts = Category::query()->where('slug', 'tisortler')->first();
        $pants = Category::query()->where('slug', 'pantolonlar')->first();
        $jackets = Category::query()->where('slug', 'ceketler')->first();
        $bags = Category::query()->where('slug', 'cantalar')->first();
        $wallets = Category::query()->where('slug', 'cuzdanlar')->first();
        $glasses = Category::query()->where('slug', 'gozlukler')->first();
        $kitchen = Category::query()->where('slug', 'mutfak')->first();
        $decoration = Category::query()->where('slug', 'dekorasyon')->first();
        $personalCare = Category::query()->where('slug', 'kisisel-bakim')->first();

        if ($phones === null) {
            $this->command?->warn('Kategoriler bulunamadı. Önce CatalogSeeder çalıştırın.');

            return;
        }

        $this->seedPhone($catalogService, $colorVariant->id, $phones->id, 'Pixel 9 Pro', 54999.00, 'Google Tensor G4, Pro kamera ve 7 yıl güncelleme garantisi.', [
            ['sku' => 'PIX9-BLACK-128', 'stock' => 9, 'color' => 'Siyah', 'memory' => '128 GB', 'model' => 'Pro'],
            ['sku' => 'PIX9-BLACK-256', 'stock' => 6, 'color' => 'Siyah', 'memory' => '256 GB', 'model' => 'Pro'],
            ['sku' => 'PIX9-WHITE-128', 'stock' => 5, 'color' => 'Beyaz', 'memory' => '128 GB', 'model' => 'Pro'],
            ['sku' => 'PIX9-GRAY-256', 'stock' => 4, 'color' => 'Gri', 'memory' => '256 GB', 'model' => 'Pro XL'],
            ['sku' => 'PIX9-GREEN-128', 'stock' => 3, 'color' => 'Yeşil', 'memory' => '128 GB', 'model' => 'Pro XL'],
        ]);

        $this->seedPhone($catalogService, $colorVariant->id, $phones->id, 'Xiaomi 14 Ultra', 42999.00, 'Leica optik, 1 inç sensör ve 90W hızlı şarj.', [
            ['sku' => 'XM14U-BLACK-512', 'stock' => 7, 'color' => 'Siyah', 'memory' => '512 GB', 'model' => 'Ultra'],
            ['sku' => 'XM14U-WHITE-512', 'stock' => 4, 'color' => 'Beyaz', 'memory' => '512 GB', 'model' => 'Ultra'],
            ['sku' => 'XM14U-BLUE-256', 'stock' => 5, 'color' => 'Mavi', 'memory' => '256 GB', 'model' => 'Ultra'],
        ]);

        $this->seedPhone($catalogService, $colorVariant->id, $phones->id, 'Samsung Galaxy S25', 69999.00, 'Galaxy AI, AMOLED ekran ve dayanıklı titanium çerçeve.', [
            ['sku' => 'S25-BLACK-256', 'stock' => 10, 'color' => 'Siyah', 'memory' => '256 GB', 'model' => 'Standard'],
            ['sku' => 'S25-SILVER-256', 'stock' => 8, 'color' => 'Gümüş', 'memory' => '256 GB', 'model' => 'Standard'],
            ['sku' => 'S25-BLUE-512', 'stock' => 6, 'color' => 'Mavi', 'memory' => '512 GB', 'model' => 'Plus'],
            ['sku' => 'S25-GREEN-512', 'stock' => 4, 'color' => 'Yeşil', 'memory' => '512 GB', 'model' => 'Plus'],
            ['sku' => 'S25-GRAY-1TB', 'stock' => 2, 'color' => 'Uzay Grisi', 'memory' => '1 TB', 'model' => 'Ultra'],
        ]);

        if ($headphones !== null) {
            $this->seedProduct($catalogService, $colorVariant->id, $headphones->id, 'Sony WH-1000XM5', 12999.00, 'Sektör lideri ANC, 30 saat pil ve çok noktalı bağlantı.', [
                ['sku' => 'SONY-XM5-BLACK', 'stock' => 12, 'color' => 'Siyah', 'model' => 'Over-Ear'],
                ['sku' => 'SONY-XM5-SILVER', 'stock' => 8, 'color' => 'Gümüş', 'model' => 'Over-Ear'],
                ['sku' => 'SONY-XM5-NAVY', 'stock' => 5, 'color' => 'Lacivert', 'model' => 'Over-Ear'],
            ]);

            $this->seedProduct($catalogService, $colorVariant->id, $headphones->id, 'Bose QuietComfort Ultra', 11499.00, 'Immersive Audio, premium konfor ve aktif gürültü engelleme.', [
                ['sku' => 'BOSE-QCU-BLACK', 'stock' => 9, 'color' => 'Siyah', 'model' => 'Over-Ear'],
                ['sku' => 'BOSE-QCU-WHITE', 'stock' => 6, 'color' => 'Beyaz', 'model' => 'Over-Ear'],
                ['sku' => 'BOSE-QCU-SAND', 'stock' => 4, 'color' => 'Bej', 'model' => 'Over-Ear'],
            ]);

            $this->seedProduct($catalogService, $colorVariant->id, $headphones->id, 'JBL Tune 770NC', 3499.00, 'Kablosuz ANC kulaklık, derin bas ve hızlı şarj.', [
                ['sku' => 'JBL770-BLACK', 'stock' => 18, 'color' => 'Siyah', 'model' => '770NC'],
                ['sku' => 'JBL770-BLUE', 'stock' => 14, 'color' => 'Mavi', 'model' => '770NC'],
                ['sku' => 'JBL770-WHITE', 'stock' => 11, 'color' => 'Beyaz', 'model' => '770NC'],
                ['sku' => 'JBL770-PINK', 'stock' => 7, 'color' => 'Pembe', 'model' => '770NC'],
            ]);
        }

        if ($smartwatches !== null) {
            $this->seedProduct($catalogService, $colorVariant->id, $smartwatches->id, 'Apple Watch Series 10', 18999.00, 'Geniş ekran, gelişmiş sağlık sensörleri ve hızlı şarj.', [
                ['sku' => 'AW10-BLACK-42', 'stock' => 8, 'color' => 'Siyah', 'model' => '42mm'],
                ['sku' => 'AW10-SILVER-42', 'stock' => 6, 'color' => 'Gümüş', 'model' => '42mm'],
                ['sku' => 'AW10-GOLD-46', 'stock' => 4, 'color' => 'Altın', 'model' => '46mm'],
                ['sku' => 'AW10-NAVY-46', 'stock' => 5, 'color' => 'Lacivert', 'model' => '46mm'],
            ]);

            $this->seedProduct($catalogService, $colorVariant->id, $smartwatches->id, 'Garmin Venu 3', 14999.00, 'AMOLED ekran, GPS ve gelişmiş uyku takibi.', [
                ['sku' => 'GV3-BLACK-41', 'stock' => 7, 'color' => 'Siyah', 'model' => '41mm'],
                ['sku' => 'GV3-WHITE-41', 'stock' => 5, 'color' => 'Beyaz', 'model' => '41mm'],
                ['sku' => 'GV3-GREEN-45', 'stock' => 4, 'color' => 'Yeşil', 'model' => '45mm'],
            ]);
        }

        if ($tablets !== null) {
            $this->seedProduct($catalogService, $colorVariant->id, $tablets->id, 'Samsung Galaxy Tab S10', 32999.00, 'AMOLED ekran, S Pen desteği ve çoklu pencere deneyimi.', [
                ['sku' => 'TAB-S10-GRAY-128', 'stock' => 6, 'color' => 'Uzay Grisi', 'memory' => '128 GB', 'model' => 'Wi-Fi'],
                ['sku' => 'TAB-S10-SILVER-256', 'stock' => 4, 'color' => 'Gümüş', 'memory' => '256 GB', 'model' => 'Wi-Fi'],
                ['sku' => 'TAB-S10-BLACK-256', 'stock' => 5, 'color' => 'Siyah', 'memory' => '256 GB', 'model' => '5G'],
            ]);

            $this->seedProduct($catalogService, $colorVariant->id, $tablets->id, 'Lenovo Tab P12', 17999.00, '12.7 inç ekran, JBL ses ve uzun pil ömrü.', [
                ['sku' => 'LP12-GRAY-128', 'stock' => 8, 'color' => 'Gri', 'memory' => '128 GB', 'model' => 'Wi-Fi'],
                ['sku' => 'LP12-GREEN-256', 'stock' => 5, 'color' => 'Yeşil', 'memory' => '256 GB', 'model' => 'Wi-Fi'],
            ]);
        }

        if ($tshirts !== null) {
            $this->seedProduct($catalogService, $colorVariant->id, $tshirts->id, 'Premium V Yaka Tişört', 429.90, 'Penye pamuk, v yaka kesim ve yumuşak dokulu kumaş.', [
                ['sku' => 'VTEE-BLACK-S', 'stock' => 22, 'color' => 'Siyah', 'model' => 'V Yaka', 'extra_attributes' => [['name' => 'Beden', 'value' => 'S']]],
                ['sku' => 'VTEE-BLACK-M', 'stock' => 25, 'color' => 'Siyah', 'model' => 'V Yaka', 'extra_attributes' => [['name' => 'Beden', 'value' => 'M']]],
                ['sku' => 'VTEE-BLACK-L', 'stock' => 20, 'color' => 'Siyah', 'model' => 'V Yaka', 'extra_attributes' => [['name' => 'Beden', 'value' => 'L']]],
                ['sku' => 'VTEE-WHITE-M', 'stock' => 18, 'color' => 'Beyaz', 'model' => 'V Yaka', 'extra_attributes' => [['name' => 'Beden', 'value' => 'M']]],
                ['sku' => 'VTEE-WHITE-L', 'stock' => 16, 'color' => 'Beyaz', 'model' => 'V Yaka', 'extra_attributes' => [['name' => 'Beden', 'value' => 'L']]],
                ['sku' => 'VTEE-NAVY-XL', 'stock' => 12, 'color' => 'Lacivert', 'model' => 'V Yaka', 'extra_attributes' => [['name' => 'Beden', 'value' => 'XL']]],
                ['sku' => 'VTEE-RED-M', 'stock' => 10, 'color' => 'Kırmızı', 'model' => 'V Yaka', 'extra_attributes' => [['name' => 'Beden', 'value' => 'M']]],
            ]);

            $this->seedProduct($catalogService, $colorVariant->id, $tshirts->id, 'Baskılı Oversize Tişört', 499.00, 'Oversize kesim, su bazlı baskı ve kalın kumaş.', [
                ['sku' => 'PRNT-BLACK-M', 'stock' => 15, 'color' => 'Siyah', 'model' => 'Oversize', 'extra_attributes' => [['name' => 'Beden', 'value' => 'M']]],
                ['sku' => 'PRNT-BLACK-L', 'stock' => 14, 'color' => 'Siyah', 'model' => 'Oversize', 'extra_attributes' => [['name' => 'Beden', 'value' => 'L']]],
                ['sku' => 'PRNT-GRAY-M', 'stock' => 12, 'color' => 'Gri', 'model' => 'Oversize', 'extra_attributes' => [['name' => 'Beden', 'value' => 'M']]],
                ['sku' => 'PRNT-CREAM-L', 'stock' => 11, 'color' => 'Krem', 'model' => 'Oversize', 'extra_attributes' => [['name' => 'Beden', 'value' => 'L']]],
                ['sku' => 'PRNT-OLIVE-XL', 'stock' => 8, 'color' => 'Haki', 'model' => 'Oversize', 'extra_attributes' => [['name' => 'Beden', 'value' => 'XL']]],
            ]);
        }

        if ($pants !== null) {
            $this->seedProduct($catalogService, $colorVariant->id, $pants->id, 'Relaxed Fit Kargo Pantolon', 799.00, 'Çok cepli, relaxed fit kargo pantolon.', [
                ['sku' => 'KARGO-BLACK-30', 'stock' => 10, 'color' => 'Siyah', 'model' => 'Relaxed', 'extra_attributes' => [['name' => 'Beden', 'value' => '30']]],
                ['sku' => 'KARGO-BLACK-32', 'stock' => 12, 'color' => 'Siyah', 'model' => 'Relaxed', 'extra_attributes' => [['name' => 'Beden', 'value' => '32']]],
                ['sku' => 'KARGO-OLIVE-32', 'stock' => 9, 'color' => 'Haki', 'model' => 'Relaxed', 'extra_attributes' => [['name' => 'Beden', 'value' => '32']]],
                ['sku' => 'KARGO-OLIVE-34', 'stock' => 7, 'color' => 'Haki', 'model' => 'Relaxed', 'extra_attributes' => [['name' => 'Beden', 'value' => '34']]],
                ['sku' => 'KARGO-BEIGE-34', 'stock' => 6, 'color' => 'Bej', 'model' => 'Relaxed', 'extra_attributes' => [['name' => 'Beden', 'value' => '34']]],
            ]);

            $this->seedProduct($catalogService, $colorVariant->id, $pants->id, 'Tapered Fit Jean', 899.00, 'Esnek denim, tapered fit ve orta bel.', [
                ['sku' => 'JEAN-BLUE-30', 'stock' => 14, 'color' => 'Mavi', 'model' => 'Tapered', 'extra_attributes' => [['name' => 'Beden', 'value' => '30']]],
                ['sku' => 'JEAN-BLUE-32', 'stock' => 16, 'color' => 'Mavi', 'model' => 'Tapered', 'extra_attributes' => [['name' => 'Beden', 'value' => '32']]],
                ['sku' => 'JEAN-BLACK-32', 'stock' => 13, 'color' => 'Siyah', 'model' => 'Tapered', 'extra_attributes' => [['name' => 'Beden', 'value' => '32']]],
                ['sku' => 'JEAN-BLACK-34', 'stock' => 10, 'color' => 'Siyah', 'model' => 'Tapered', 'extra_attributes' => [['name' => 'Beden', 'value' => '34']]],
                ['sku' => 'JEAN-GRAY-34', 'stock' => 8, 'color' => 'Gri', 'model' => 'Tapered', 'extra_attributes' => [['name' => 'Beden', 'value' => '34']]],
            ]);
        }

        if ($jackets !== null) {
            $this->seedProduct($catalogService, $colorVariant->id, $jackets->id, 'Su İtici Mont', 1899.00, 'Hafif şişme mont, su itici dış yüzey ve sıcak tutan dolgu.', [
                ['sku' => 'MONT-BLACK-M', 'stock' => 8, 'color' => 'Siyah', 'model' => 'Regular', 'extra_attributes' => [['name' => 'Beden', 'value' => 'M']]],
                ['sku' => 'MONT-BLACK-L', 'stock' => 7, 'color' => 'Siyah', 'model' => 'Regular', 'extra_attributes' => [['name' => 'Beden', 'value' => 'L']]],
                ['sku' => 'MONT-NAVY-M', 'stock' => 6, 'color' => 'Lacivert', 'model' => 'Regular', 'extra_attributes' => [['name' => 'Beden', 'value' => 'M']]],
                ['sku' => 'MONT-NAVY-L', 'stock' => 5, 'color' => 'Lacivert', 'model' => 'Regular', 'extra_attributes' => [['name' => 'Beden', 'value' => 'L']]],
                ['sku' => 'MONT-OLIVE-XL', 'stock' => 4, 'color' => 'Haki', 'model' => 'Regular', 'extra_attributes' => [['name' => 'Beden', 'value' => 'XL']]],
            ]);

            $this->seedProduct($catalogService, $colorVariant->id, $jackets->id, 'Yün Karışımlı Blazer', 2199.00, 'Yün karışımı kumaş, slim fit blazer ceket.', [
                ['sku' => 'BLZ-BLACK-46', 'stock' => 5, 'color' => 'Siyah', 'model' => 'Slim', 'extra_attributes' => [['name' => 'Beden', 'value' => '46']]],
                ['sku' => 'BLZ-BLACK-48', 'stock' => 4, 'color' => 'Siyah', 'model' => 'Slim', 'extra_attributes' => [['name' => 'Beden', 'value' => '48']]],
                ['sku' => 'BLZ-NAVY-46', 'stock' => 6, 'color' => 'Lacivert', 'model' => 'Slim', 'extra_attributes' => [['name' => 'Beden', 'value' => '46']]],
                ['sku' => 'BLZ-GRAY-48', 'stock' => 3, 'color' => 'Gri', 'model' => 'Slim', 'extra_attributes' => [['name' => 'Beden', 'value' => '48']]],
            ]);
        }

        if ($bags !== null) {
            $this->seedProduct($catalogService, $colorVariant->id, $bags->id, 'Deri Omuz Çantası', 1599.00, 'Gerçek deri, ayarlanabilir askı ve fermuarlı bölmeler.', [
                ['sku' => 'SHLD-BROWN', 'stock' => 6, 'color' => 'Kahverengi', 'model' => 'Medium'],
                ['sku' => 'SHLD-BLACK', 'stock' => 8, 'color' => 'Siyah', 'model' => 'Medium'],
                ['sku' => 'SHLD-TAN', 'stock' => 4, 'color' => 'Bej', 'model' => 'Medium'],
            ]);

            $this->seedProduct($catalogService, $colorVariant->id, $bags->id, 'Seyahat Sırt Çantası 40L', 1299.00, '40 litre kapasite, göğüs kemeri ve çoklu cep.', [
                ['sku' => 'TRVL40-BLACK', 'stock' => 9, 'color' => 'Siyah', 'model' => '40L'],
                ['sku' => 'TRVL40-NAVY', 'stock' => 7, 'color' => 'Lacivert', 'model' => '40L'],
                ['sku' => 'TRVL40-OLIVE', 'stock' => 5, 'color' => 'Haki', 'model' => '40L'],
                ['sku' => 'TRVL40-GRAY', 'stock' => 6, 'color' => 'Gri', 'model' => '40L'],
            ]);
        }

        if ($wallets !== null) {
            $this->seedProduct($catalogService, $colorVariant->id, $wallets->id, 'Minimal Kartlık', 299.00, 'İnce kartlık, 6 kart yuvası ve RFID koruma.', [
                ['sku' => 'CARD-BLACK', 'stock' => 20, 'color' => 'Siyah', 'model' => 'Slim'],
                ['sku' => 'CARD-BROWN', 'stock' => 15, 'color' => 'Kahverengi', 'model' => 'Slim'],
                ['sku' => 'CARD-NAVY', 'stock' => 12, 'color' => 'Lacivert', 'model' => 'Slim'],
                ['sku' => 'CARD-GRAY', 'stock' => 10, 'color' => 'Gri', 'model' => 'Slim'],
            ]);
        }

        if ($glasses !== null) {
            $this->seedProduct($catalogService, $colorVariant->id, $glasses->id, 'Wayfarer Güneş Gözlüğü', 899.00, 'Klasik wayfarer form, polarize cam.', [
                ['sku' => 'WAY-BLACK', 'stock' => 14, 'color' => 'Siyah', 'model' => 'Wayfarer'],
                ['sku' => 'WAY-TORT', 'stock' => 10, 'color' => 'Kaplumbağa', 'model' => 'Wayfarer'],
                ['sku' => 'WAY-NAVY', 'stock' => 8, 'color' => 'Lacivert', 'model' => 'Wayfarer'],
            ]);

            $this->seedProduct($catalogService, $colorVariant->id, $glasses->id, 'Sport Polarize Gözlük', 749.00, 'Hafif sport çerçeve, kaymaz burun pedi.', [
                ['sku' => 'SPT-BLACK', 'stock' => 11, 'color' => 'Siyah', 'model' => 'Sport'],
                ['sku' => 'SPT-BLUE', 'stock' => 9, 'color' => 'Mavi', 'model' => 'Sport'],
                ['sku' => 'SPT-RED', 'stock' => 6, 'color' => 'Kırmızı', 'model' => 'Sport'],
            ]);
        }

        if ($kitchen !== null) {
            $this->seedProduct($catalogService, $colorVariant->id, $kitchen->id, 'Seramik Tencere Seti', 2499.00, '5 parça seramik kaplama tencere seti.', [
                ['sku' => 'POTSET-BLACK', 'stock' => 7, 'color' => 'Siyah', 'model' => '5 Parça'],
                ['sku' => 'POTSET-GRAY', 'stock' => 5, 'color' => 'Gri', 'model' => '5 Parça'],
                ['sku' => 'POTSET-CREAM', 'stock' => 4, 'color' => 'Krem', 'model' => '5 Parça'],
            ]);

            $this->seedProduct($catalogService, $colorVariant->id, $kitchen->id, 'Vakumlu Termos 750ml', 449.00, '24 saat sıcak/soğuk tutma, sızdırmaz kapak.', [
                ['sku' => 'THRM-BLACK', 'stock' => 16, 'color' => 'Siyah', 'model' => '750ml'],
                ['sku' => 'THRM-WHITE', 'stock' => 13, 'color' => 'Beyaz', 'model' => '750ml'],
                ['sku' => 'THRM-BLUE', 'stock' => 11, 'color' => 'Mavi', 'model' => '750ml'],
                ['sku' => 'THRM-GREEN', 'stock' => 9, 'color' => 'Yeşil', 'model' => '750ml'],
            ]);
        }

        if ($decoration !== null) {
            $this->seedProduct($catalogService, $colorVariant->id, $decoration->id, 'Seramik Vazo Seti', 599.00, 'El yapımı seramik, 3\'lü vazo seti.', [
                ['sku' => 'VAZE-WHITE', 'stock' => 10, 'color' => 'Beyaz', 'model' => 'Set'],
                ['sku' => 'VAZE-CREAM', 'stock' => 8, 'color' => 'Krem', 'model' => 'Set'],
                ['sku' => 'VAZE-TERRA', 'stock' => 6, 'color' => 'Kahverengi', 'model' => 'Set'],
            ]);

            $this->seedProduct($catalogService, $colorVariant->id, $decoration->id, 'Dekoratif Yastık', 349.00, 'Kadife kumaş, fermuarlı kılıf dekoratif yastık.', [
                ['sku' => 'PLW-BLACK', 'stock' => 14, 'color' => 'Siyah', 'model' => '45x45', 'extra_attributes' => [['name' => 'Koku', 'value' => 'Nötr']]],
                ['sku' => 'PLW-GREEN', 'stock' => 12, 'color' => 'Yeşil', 'model' => '45x45', 'extra_attributes' => [['name' => 'Koku', 'value' => 'Nötr']]],
                ['sku' => 'PLW-BEIGE', 'stock' => 11, 'color' => 'Bej', 'model' => '45x45', 'extra_attributes' => [['name' => 'Koku', 'value' => 'Nötr']]],
                ['sku' => 'PLW-NAVY', 'stock' => 9, 'color' => 'Lacivert', 'model' => '45x45', 'extra_attributes' => [['name' => 'Koku', 'value' => 'Nötr']]],
            ]);
        }

        if ($personalCare !== null) {
            $this->seedProduct($catalogService, $colorVariant->id, $personalCare->id, 'Akıllı Diş Fırçası', 1899.00, 'Basınç sensörü, 5 mod ve uygulama desteği.', [
                ['sku' => 'TB-BLACK', 'stock' => 10, 'color' => 'Siyah', 'model' => 'Pro'],
                ['sku' => 'TB-WHITE', 'stock' => 8, 'color' => 'Beyaz', 'model' => 'Pro'],
                ['sku' => 'TB-PINK', 'stock' => 6, 'color' => 'Pembe', 'model' => 'Pro'],
            ]);

            $this->seedProduct($catalogService, $colorVariant->id, $personalCare->id, 'IPL Epilasyon Cihazı', 4999.00, 'Ev tipi IPL, 5 enerji seviyesi ve cilt sensörü.', [
                ['sku' => 'IPL-WHITE', 'stock' => 5, 'color' => 'Beyaz', 'model' => 'Advanced'],
                ['sku' => 'IPL-PINK', 'stock' => 4, 'color' => 'Pembe', 'model' => 'Advanced'],
                ['sku' => 'IPL-GOLD', 'stock' => 3, 'color' => 'Altın', 'model' => 'Advanced'],
            ]);
        }

        $this->command?->info('ExtraCatalogSeeder tamamlandı.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function seedPhone(
        ProductCatalogService $catalogService,
        int $colorVariantId,
        int $categoryId,
        string $name,
        float $price,
        string $description,
        array $variants,
    ): void {
        $this->seedProduct($catalogService, $colorVariantId, $categoryId, $name, $price, $description, $variants);
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function seedProduct(
        ProductCatalogService $catalogService,
        int $colorVariantId,
        int $categoryId,
        string $name,
        float $price,
        string $description,
        array $variants,
    ): void {
        if (Product::query()->where('name', $name)->exists()) {
            $this->command?->line("Atlandı (zaten var): {$name}");

            return;
        }

        $product = Product::query()->create([
            'name' => $name,
            'category_id' => $categoryId,
            'base_variant_id' => $colorVariantId,
            'price' => $price,
            'description' => $description,
        ]);

        $catalogService->syncVariants($product, $variants);

        $this->command?->line("Eklendi: {$name} (".count($variants).' varyant)');
    }
}
