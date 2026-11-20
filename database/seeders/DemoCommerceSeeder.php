<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Core\Models\Channel;
use Webkul\Customer\Models\Customer;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductReview;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;

class DemoCommerceSeeder extends Seeder
{
    /**
     * Add fictional customers, pending orders, and unapproved reviews.
     */
    public function run(): void
    {
        if ($this->demoDataExists()) {
            $this->command?->info('Demo commerce data already exists; no records were changed.');

            return;
        }

        $channel = core()->getDefaultChannel();

        $customerGroupId = DB::table('customer_groups')
            ->where('code', 'general')
            ->value('id');

        $currencyCode = DB::table('currencies')
            ->where('id', $channel->base_currency_id)
            ->value('code');

        $products = DB::table('product_flat')
            ->where('channel', $channel->code)
            ->where('locale', app()->getLocale())
            ->where('status', 1)
            ->where('visible_individually', 1)
            ->where('type', 'simple')
            ->whereNotNull('price')
            ->orderBy('product_id')
            ->get(['product_id', 'sku', 'type', 'name', 'price', 'weight']);

        if (! $customerGroupId || ! $currencyCode || $products->isEmpty()) {
            throw new \RuntimeException('Seed the Bagisto demo catalog before running the demo commerce seeder.');
        }

        DB::transaction(function () use ($channel, $customerGroupId, $currencyCode, $products) {
            $customers = $this->createCustomers($channel->id, $customerGroupId);

            $this->createOrders($customers, $products, $channel, $currencyCode);

            $this->createReviews($customers, $products);
        });

        ResponseCache::clear();

        $this->command?->info('Created 12 fictional customers, 24 pending demo orders, and 36 pending sample reviews.');
    }

    /**
     * Check for previously seeded demo records.
     */
    private function demoDataExists(): bool
    {
        return DB::table('customers')->where('email', 'like', 'demo.customer%@example.test')->exists()
            || DB::table('orders')->where('increment_id', 'like', 'DEMO-%')->exists()
            || DB::table('product_reviews')->where('comment', 'like', 'DEMO SAMPLE - fictional%')->exists();
    }

    /**
     * Create non-login-ready customer accounts with fictional contact details.
     */
    private function createCustomers(int $channelId, int $customerGroupId): Collection
    {
        $customers = collect();

        for ($number = 1; $number <= 12; $number++) {
            $identifier = str_pad((string) $number, 2, '0', STR_PAD_LEFT);

            $customers->push(Customer::factory()->create([
                'first_name' => 'Demo',
                'last_name' => 'Customer '.$identifier,
                'gender' => null,
                'date_of_birth' => null,
                'email' => 'demo.customer'.$identifier.'@example.test',
                'phone' => null,
                'password' => Hash::make(Str::random(48)),
                'customer_group_id' => $customerGroupId,
                'channel_id' => $channelId,
                'subscribed_to_news_letter' => false,
                'status' => 1,
                'is_verified' => 0,
                'is_suspended' => 0,
            ]));
        }

        return $customers;
    }

    /**
     * Create orders whose totals match their fictional line items.
     */
    private function createOrders(Collection $customers, Collection $products, Channel $channel, string $currencyCode): void
    {
        $cities = ['Addis Ababa', 'Adama', 'Bahir Dar', 'Hawassa', 'Dire Dawa'];

        for ($number = 1; $number <= 24; $number++) {
            $customer = $customers->get(($number - 1) % $customers->count());
            $itemCount = $number % 2 === 0 ? 2 : 1;
            $items = [];
            $subtotal = 0;
            $totalQuantity = 0;

            for ($offset = 0; $offset < $itemCount; $offset++) {
                $product = $products->get((($number - 1) * 2 + $offset) % $products->count());
                $quantity = 1 + (($number + $offset) % 2);
                $price = round((float) $product->price, 2);
                $lineTotal = round($price * $quantity, 2);

                $items[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'price' => $price,
                    'line_total' => $lineTotal,
                ];

                $subtotal += $lineTotal;
                $totalQuantity += $quantity;
            }

            $createdAt = now()->subDays(($number - 1) % 30);

            $order = Order::factory()->create([
                'increment_id' => 'DEMO-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
                'status' => Order::STATUS_PENDING,
                'channel_name' => $channel->name,
                'is_guest' => false,
                'customer_email' => $customer->email,
                'customer_first_name' => $customer->first_name,
                'customer_last_name' => $customer->last_name,
                'shipping_method' => 'free_free',
                'shipping_title' => 'Demo record - no shipment',
                'shipping_description' => 'Fictional sample order; no shipment or payment has been created.',
                'is_gift' => false,
                'total_item_count' => count($items),
                'total_qty_ordered' => $totalQuantity,
                'base_currency_code' => $currencyCode,
                'channel_currency_code' => $currencyCode,
                'order_currency_code' => $currencyCode,
                'grand_total' => $subtotal,
                'base_grand_total' => $subtotal,
                'grand_total_invoiced' => 0,
                'base_grand_total_invoiced' => 0,
                'grand_total_refunded' => 0,
                'base_grand_total_refunded' => 0,
                'sub_total' => $subtotal,
                'base_sub_total' => $subtotal,
                'sub_total_invoiced' => 0,
                'base_sub_total_invoiced' => 0,
                'sub_total_refunded' => 0,
                'base_sub_total_refunded' => 0,
                'customer_id' => $customer->id,
                'customer_type' => Customer::class,
                'channel_id' => $channel->id,
                'channel_type' => Channel::class,
                'cart_id' => 0,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            foreach ($items as $item) {
                $product = $item['product'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                $lineTotal = $item['line_total'];

                OrderItem::factory()->create([
                    'sku' => $product->sku,
                    'type' => $product->type,
                    'name' => $product->name,
                    'weight' => $product->weight,
                    'total_weight' => (float) $product->weight * $quantity,
                    'qty_ordered' => $quantity,
                    'price' => $price,
                    'base_price' => $price,
                    'total' => $lineTotal,
                    'base_total' => $lineTotal,
                    'price_incl_tax' => $price,
                    'base_price_incl_tax' => $price,
                    'total_incl_tax' => $lineTotal,
                    'base_total_incl_tax' => $lineTotal,
                    'product_id' => $product->product_id,
                    'product_type' => Product::class,
                    'order_id' => $order->id,
                    'additional' => ['demo_data' => true],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            OrderPayment::factory()->create([
                'order_id' => $order->id,
                'method' => 'cashondelivery',
                'method_title' => 'Demo record - no payment taken',
                'additional' => ['demo_data' => true],
            ]);

            foreach ([OrderAddress::ADDRESS_TYPE_BILLING, OrderAddress::ADDRESS_TYPE_SHIPPING] as $addressType) {
                OrderAddress::factory()->create([
                    'address_type' => $addressType,
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                    'first_name' => 'Demo',
                    'last_name' => 'Customer '.str_pad((string) $customer->id, 2, '0', STR_PAD_LEFT),
                    'email' => $customer->email,
                    'address' => 'DEMO DATA ONLY - NOT A DELIVERY ADDRESS',
                    'city' => $cities[($number - 1) % count($cities)],
                    'state' => 'Demo Region',
                    'country' => 'ET',
                    'postcode' => '00000',
                    'phone' => '0000000000',
                ]);
            }
        }
    }

    /**
     * Create pending reviews that are clearly identified as fictional samples.
     */
    private function createReviews(Collection $customers, Collection $products): void
    {
        $comments = [
            'The product details are clear and easy to compare.',
            'This sample helps preview how product feedback will appear.',
            'The listing includes useful information for shoppers.',
            'A fictional review for testing the store review workflow.',
            'The product page is easy to understand.',
            'This demo feedback is not from a real customer.',
        ];

        for ($number = 1; $number <= 36; $number++) {
            $customer = $customers->get(($number - 1) % $customers->count());
            $product = $products->get((($number - 1) * 3) % $products->count());
            $comment = $comments[($number - 1) % count($comments)];

            ProductReview::factory()->create([
                'name' => 'Demo Reviewer '.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'title' => 'Demo review - pending approval',
                'rating' => 3 + ($number % 3),
                'comment' => 'DEMO SAMPLE - fictional, not a real customer review. '.$comment,
                'status' => 'pending',
                'product_id' => $product->product_id,
                'customer_id' => $customer->id,
                'created_at' => now()->subDays($number % 30),
                'updated_at' => now()->subDays($number % 30),
            ]);
        }
    }
}
