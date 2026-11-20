<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Installer\Database\Seeders\ProductTableSeeder;

class DemoCatalogSeeder extends Seeder
{
    /**
     * Seed the catalog with Bagisto's bundled demo products when it is empty.
     *
     * @param  array  $parameters
     */
    public function run(array $parameters = []): void
    {
        if (DB::table('products')->exists()) {
            $this->command?->info('Demo catalog seeding skipped because products already exist.');

            return;
        }

        $defaultLocale = $parameters['default_locale'] ?? config('app.locale');

        $parameters = array_merge([
            'default_locale' => $defaultLocale,
            'allowed_locales' => [$defaultLocale],
        ], $parameters);

        DB::transaction(fn () => $this->call(ProductTableSeeder::class, false, [
            'parameters' => $parameters,
        ]));

        if (Artisan::call('indexer:index', ['--mode' => ['full']]) !== 0) {
            throw new \RuntimeException('Failed to index demo product prices and inventory.');
        }

        ResponseCache::clear();
    }
}
