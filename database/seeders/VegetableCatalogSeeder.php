<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VegetableCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categoryId = DB::table('category_translations')->where('slug', 'vegetables')->value('category_id');

        if (! $categoryId) {
            $categoryId = DB::table('categories')->insertGetId([
                'position' => 99,
                'status' => 1,
                'display_mode' => 'products_and_description',
                'parent_id' => 1,
                '_lft' => 100,
                '_rgt' => 101,
                'additional' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('category_translations')->insert([
                'category_id' => $categoryId,
                'name' => 'Vegetables',
                'slug' => 'vegetables',
                'url_path' => 'vegetables',
                'description' => 'Fresh vegetables sourced for everyday Ethiopian cooking.',
                'meta_title' => 'Fresh Vegetables',
                'meta_description' => 'Shop fresh and locally available vegetables online.',
                'meta_keywords' => 'vegetables, fresh produce, Ethiopian groceries',
                'locale_id' => null,
                'locale' => 'en',
            ]);
        }

        $channelId = (int) DB::table('channels')->where('code', 'default')->value('id');
        $inventorySourceId = (int) (DB::table('inventory_sources')->where('status', 1)->value('id') ?? 1);

        $attributeIds = DB::table('attributes')
            ->whereIn('code', [
                'name', 'url_key', 'short_description', 'description', 'price', 'weight',
                'status', 'visible_individually', 'new', 'featured', 'guest_checkout',
                'manage_stock', 'meta_title', 'meta_keywords', 'meta_description',
            ])
            ->pluck('id', 'code');

        $vegetables = [
            ['Tomato', 'ET-TOM-001', 2.49, 120], ['Red Onion', 'ET-ONI-002', 1.99, 150],
            ['White Onion', 'ET-ONI-003', 1.89, 140], ['Green Cabbage', 'ET-CAB-004', 3.49, 80],
            ['Red Cabbage', 'ET-CAB-005', 4.29, 55], ['Carrot', 'ET-CAR-006', 2.29, 110],
            ['Irish Potato', 'ET-POT-007', 2.79, 180], ['Sweet Potato', 'ET-POT-008', 3.19, 95],
            ['Green Pepper', 'ET-PEP-009', 3.99, 85], ['Red Pepper', 'ET-PEP-010', 4.49, 70],
            ['Yellow Pepper', 'ET-PEP-011', 4.79, 65], ['Eggplant', 'ET-EGG-012', 3.29, 75],
            ['Green Beans', 'ET-BEA-013', 5.49, 60], ['Fresh Beetroot', 'ET-BET-014', 3.69, 90],
            ['Fresh Garlic', 'ET-GAR-015', 6.49, 100], ['Fresh Ginger', 'ET-GIN-016', 5.29, 95],
            ['Spinach', 'ET-SPI-017', 2.89, 70], ['Swiss Chard', 'ET-CHA-018', 2.79, 65],
            ['Kale', 'ET-KAL-019', 3.59, 80], ['Lettuce', 'ET-LET-020', 2.99, 75],
            ['Cucumber', 'ET-CUC-021', 2.69, 105], ['Zucchini', 'ET-ZUC-022', 3.89, 68],
            ['Broccoli', 'ET-BRO-023', 5.99, 45], ['Cauliflower', 'ET-CAU-024', 5.79, 48],
            ['Okra', 'ET-OKR-025', 4.89, 55], ['Fresh Pumpkin', 'ET-PUM-026', 4.59, 40],
            ['Butternut Squash', 'ET-SQU-027', 5.29, 42], ['Fresh Corn', 'ET-COR-028', 3.79, 85],
            ['Green Peas', 'ET-PEA-029', 4.99, 72], ['Fresh Parsley', 'ET-PAR-030', 1.49, 130],
        ];

        foreach ($vegetables as [$name, $sku, $price, $quantity]) {
            $productId = DB::table('products')->where('sku', $sku)->value('id');

            if (! $productId) {
                $productId = DB::table('products')->insertGetId([
                    'sku' => $sku,
                    'type' => 'simple',
                    'parent_id' => null,
                    'attribute_family_id' => 1,
                    'additional' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('products')->where('id', $productId)->update([
                    'type' => 'simple',
                    'attribute_family_id' => 1,
                    'updated_at' => now(),
                ]);
            }

            $slug = Str::slug($name);
            $values = [
                'name' => [$name, 'text'],
                'url_key' => [$slug, 'text'],
                'short_description' => ['Fresh '.$name.' selected for quality and everyday cooking.', 'text'],
                'description' => ['Fresh '.$name.' available for delivery in Addis Ababa. Packed carefully and sold in market-ready quantities.', 'text'],
                'price' => [$price, 'float'],
                'weight' => [1, 'float'],
                'status' => [1, 'boolean'],
                'visible_individually' => [1, 'boolean'],
                'new' => [1, 'boolean'],
                'featured' => [1, 'boolean'],
                'guest_checkout' => [1, 'boolean'],
                'manage_stock' => [1, 'boolean'],
                'meta_title' => [$name.' | NOVA Market', 'text'],
                'meta_keywords' => [strtolower($name).', fresh vegetable, Addis Market', 'text'],
                'meta_description' => ['Buy fresh '.$name.' from NOVA Market with local delivery.', 'text'],
            ];

            foreach ($values as $code => [$value, $kind]) {
                $attributeId = $attributeIds[$code];
                $channel = $code === 'status' || $code === 'manage_stock' ? 'default' : null;
                $locale = in_array($code, ['name', 'url_key', 'short_description', 'description', 'meta_title', 'meta_keywords', 'meta_description']) ? 'en' : null;
                $uniqueId = collect([$channel, $locale, $productId, $attributeId])->filter()->implode('|');

                $row = [
                    'locale' => $locale,
                    'channel' => $channel,
                    'text_value' => null,
                    'boolean_value' => null,
                    'integer_value' => null,
                    'float_value' => null,
                    'datetime_value' => null,
                    'date_value' => null,
                    'json_value' => null,
                    'product_id' => $productId,
                    'attribute_id' => $attributeId,
                    'unique_id' => $uniqueId,
                ];

                if ($kind === 'text') {
                    $row['text_value'] = $value;
                } elseif ($kind === 'boolean') {
                    $row['boolean_value'] = $value;
                } else {
                    $row['float_value'] = $value;
                }

                DB::table('product_attribute_values')->updateOrInsert([
                    'product_id' => $productId,
                    'attribute_id' => $attributeId,
                    'unique_id' => $uniqueId,
                ], $row);
            }

            DB::table('product_channels')->updateOrInsert([
                'product_id' => $productId,
                'channel_id' => $channelId,
            ], []);

            DB::table('product_categories')->updateOrInsert([
                'product_id' => $productId,
                'category_id' => $categoryId,
            ], []);

            DB::table('product_inventories')->updateOrInsert([
                'product_id' => $productId,
                'inventory_source_id' => $inventorySourceId,
                'vendor_id' => 0,
            ], ['qty' => $quantity]);
        }

        $this->command?->info('Seeded 30 vegetable products in the Vegetables category.');
    }
}
