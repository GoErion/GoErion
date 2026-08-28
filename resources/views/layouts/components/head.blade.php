<link
        rel="shortcut icon"
        href="{{ asset(config('marketing.icon')) }}"
        type="image/x-icon"
    >
    <title>
        {{ $meta['title'] ?? config('marketing.default.title') }}
    </title>
    <meta
        name="description"
        content="{{ $meta['description'] ?? config('marketing.default.description') }}"
    >
    @if (!empty($meta['keywords']))
        <meta
            name="keywords"
            content="{{ implode(', ', $meta['keywords']) }}"
        >
    @endif
    <link
        rel="canonical"
        href="{{ $meta['url'] ?? url()->current() }}"
    >
    <meta property="og:type" content="website">
    <meta
        property="og:site_name"
        content="{{ config('marketing.site_name') }}"
    >
    <meta
        property="og:title"
        content="{{ $meta['title'] ?? config('marketing.default.title') }}"
    >
    <meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="author" content="GoErion" />
<meta name="google" content="notranslate" data-rh="true" />
<meta name="robots" content="index, follow" data-rh="true" />
<meta name="applicable-device" content="pc, mobile" data-rh="true" />
<meta name="mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-title" content="GoErion" />
<meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <meta
        property="og:description"
        content="{{ $meta['description'] ?? config('marketing.default.description') }}"
    >
    <meta
        property="og:url"
        content="{{ $meta['url'] ?? url()->current() }}"
    >
    <meta
        property="og:image"
        content="{{ asset($meta['image'] ?? config('marketing.default.image')) }}"
    >
    <meta
        name="twitter:card"
        content="summary_large_image"
    >
    <meta
        name="twitter:title"
        content="{{ $meta['title'] ?? config('marketing.default.title') }}"
    >
    <meta
        name="twitter:description"
        content="{{ $meta['description'] ?? config('marketing.default.description') }}"
    >
    <meta
        name="twitter:image"
        content="{{ asset($meta['image'] ?? config('marketing.default.image')) }}"
    >