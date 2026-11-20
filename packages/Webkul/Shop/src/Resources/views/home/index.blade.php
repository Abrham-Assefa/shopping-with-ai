@php
    $channel = core()->getCurrentChannel();
@endphp

<!-- SEO Meta Content -->
@push ('meta')
    <meta
        name="title"
        content="{{ $channel->home_seo['meta_title'] ?? '' }}"
    />

    <meta
        name="description"
        content="{{ $channel->home_seo['meta_description'] ?? '' }}"
    />

    <meta
        name="keywords"
        content="{{ $channel->home_seo['meta_keywords'] ?? '' }}"
    />
@endPush

@push('scripts')
    @if(! empty($categories))
        <script>
            localStorage.setItem('categories', JSON.stringify(@json($categories)));
        </script>
    @endif
@endpush

<x-shop::layouts>
    <section class="ethiopia-hero">
        <div class="ethiopia-hero-copy">    
           
            <h1>Fresh from Ethiopia,<br><span>delivered to you.</span></h1>
            <p class="ethiopia-description">
                Discover Ethiopian coffee, injera, pantry staples, fresh produce, and everyday essentials, delivered across Addis Ababa.
            </p>

            <div class="ethiopia-actions">
                <a href="{{ route('shop.search.index') }}" class="ethiopia-primary">Shop groceries</a>
                <a href="{{ route('shop.search.index', ['query' => 'coffee']) }}" class="ethiopia-secondary">Shop Ethiopian coffee</a>
            </div>

            <div class="ethiopia-delivery">
                <span class="ethiopia-delivery-dot" aria-hidden="true"></span>
                <strong>Same-day delivery</strong>
                <small>In selected Addis Ababa areas</small>
            </div>
        </div>

        <div class="ethiopia-hero-image" aria-label="Fresh Ethiopian market produce">
            <img
                src="https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1400&q=85"
                alt="Fresh fruit and vegetables at a market"
            />
            <div class="ethiopia-coffee-note">
                <span>Fresh arrival</span>
                <strong>Ethiopian Coffee</strong>
            </div>
        </div>
    </section>

    <section class="ethiopia-categories" aria-label="Popular shopping categories">
        <a href="{{ route('shop.search.index', ['query' => 'coffee']) }}">
            <span>01</span>
            <strong>Traditional coffee</strong>
            <small>Sidamo, Jimma & Yirgacheffe</small>
        </a>

        <a href="{{ route('shop.search.index', ['query' => 'fresh']) }}">
            <span>02</span>
            <strong>Fresh produce</strong>
            <small>Fruit and vegetables</small>
        </a>

        <a href="{{ route('shop.search.index', ['query' => 'pantry']) }}">
            <span>03</span>
            <strong>Pantry essentials</strong>
            <small>Teff, grains & spices</small>
        </a>

        <a href="{{ route('shop.search.index', ['query' => 'household']) }}">
            <span>04</span>
            <strong>Household</strong>
            <small>Care and everyday needs</small>
        </a>
    </section>

    <!-- Page Title -->
    <x-slot:title>
        {{  $channel->home_seo['meta_title'] ?? '' }}
    </x-slot>

    <!-- Loop over the storefront sections -->
    @foreach ($sections as $section)
        @php ($data = $section->options) @endphp

        {{-- The layout marks the types it draws on every page, so this page marks the rest. --}}
        @php ($marks = ($preview ?? false) && ! $section->getTypeInstance()?->rendersInLayout())

        @if ($marks)
            <div
                data-section-id="{{ $section->id }}"
                data-section-name="{{ $section->name }}"
            >
        @endif

        <!-- Static Content -->
        @switch ($section->type)
            @case (\Webkul\Theme\Enums\SectionTypeEnum::IMAGE_CAROUSEL->value)
                <!-- Image Carousel -->
                <x-shop::carousel
                    :options="$section->getTypeInstance()?->sanitize((array) $data) ?? $data"
                    aria-label="{{ trans('shop::app.home.index.image-carousel') }}"
                />

                @break
            @case (\Webkul\Theme\Enums\SectionTypeEnum::STATIC_CONTENT->value)
                <!-- Push Style -->
                @if (! empty($data['css']))
                    @push ('styles')
                        <style>
                            {!! bagisto_theme_storage()->resolveMarkup($data['css']) !!}
                        </style>
                    @endpush
                @endif

                <!-- Render HTML -->
                @if (! empty($data['html']))
                    {!! bagisto_theme_storage()->resolveMarkup($data['html']) !!}
                @endif

                @break
            @case (\Webkul\Theme\Enums\SectionTypeEnum::CATEGORY_CAROUSEL->value)
                <!-- Categories carousel -->
                <x-shop::categories.carousel
                    :title="$data['title'] ?? ''"
                    :src="route('shop.api.categories.index', $data['filters'] ?? [])"
                    :navigation-link="route('shop.home.index')"
                    aria-label="{{ trans('shop::app.home.index.categories-carousel') }}"
                />

                @break
            @case (\Webkul\Theme\Enums\SectionTypeEnum::PRODUCT_CAROUSEL->value)
                <!-- Product Carousel -->
                <x-shop::products.carousel
                    :title="$data['title'] ?? ''"
                    :src="route('shop.api.products.index', $data['filters'] ?? [])"
                    :navigation-link="route('shop.search.index', $data['filters'] ?? [])"
                    aria-label="{{ trans('shop::app.home.index.product-carousel') }}"
                />

                @break
        @endswitch

        @if ($marks)
            </div>
        @endif
    @endforeach

    @if ($preview ?? false)
        @include('shop::home.preview-bridge')
    @endif
</x-shop::layouts>
