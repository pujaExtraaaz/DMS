<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#101b3a">

    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="AVIT DIGITAL">

    <title>AVIT DIGITAL — Sign In</title>

    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="{{ asset('css/pwa-mobile.css') }}">
    <link rel="stylesheet" href="{{ asset('css/avit-login.css') }}">
</head>

<body class="avit-login-page">

    <main class="avit-login-shell">

        {{-- =====================================================
             LEFT: AVIT DIGITAL SHOWCASE
             ===================================================== --}}
        <section class="avit-showcase">

            <a href="{{ url('/') }}" class="avit-brand" aria-label="AVIT DIGITAL">
                <img
                    src="{{ asset('images/avit-digital-logo.png') }}"
                    alt="AVIT DIGITAL"
                >
            </a>

            <div class="avit-showcase-content">

                <div class="avit-eyebrow">
                    Business Management Platform
                </div>

                <h1 class="avit-heading">
                    Distribution
                    <br>
                    Management
                    <br>
                    <span>made simple.</span>
                </h1>

                <p class="avit-description">
                    Manage orders, inventory, purchasing, billing and
                    distribution operations from one professional platform.
                    Keep your business connected from one place.
                </p>

                <div class="avit-features">
                    <div class="avit-feature">
                        <span class="avit-feature-dot"></span>
                        Sales & Orders
                    </div>

                    <div class="avit-feature">
                        <span class="avit-feature-dot"></span>
                        Inventory
                    </div>

                    <div class="avit-feature">
                        <span class="avit-feature-dot"></span>
                        Purchasing
                    </div>

                    <div class="avit-feature">
                        <span class="avit-feature-dot"></span>
                        Logistics
                    </div>

                    <div class="avit-feature">
                        <span class="avit-feature-dot"></span>
                        Billing
                    </div>
                </div>

            </div>

            {{-- =================================================
                 MODERN DISTRIBUTION ILLUSTRATION
                 ================================================= --}}
            <div class="avit-illustration" aria-hidden="true">

                <svg
                    viewBox="0 0 700 430"
                    xmlns="http://www.w3.org/2000/svg"
                    role="presentation"
                >

                    <defs>

                        <linearGradient
                            id="avitBuilding"
                            x1="0"
                            y1="0"
                            x2="1"
                            y2="1"
                        >
                            <stop offset="0%" stop-color="#eef4ff"/>
                            <stop offset="100%" stop-color="#dce8ff"/>
                        </linearGradient>

                        <linearGradient
                            id="avitBlue"
                            x1="0"
                            y1="0"
                            x2="1"
                            y2="1"
                        >
                            <stop offset="0%" stop-color="#3c6de8"/>
                            <stop offset="100%" stop-color="#2457d6"/>
                        </linearGradient>

                        <linearGradient
                            id="avitPurple"
                            x1="0"
                            y1="0"
                            x2="1"
                            y2="1"
                        >
                            <stop offset="0%" stop-color="#766cf0"/>
                            <stop offset="100%" stop-color="#5548df"/>
                        </linearGradient>

                        <filter
                            id="avitShadow"
                            x="-30%"
                            y="-30%"
                            width="160%"
                            height="160%"
                        >
                            <feDropShadow
                                dx="0"
                                dy="12"
                                stdDeviation="14"
                                flood-color="#18305f"
                                flood-opacity="0.10"
                            />
                        </filter>

                    </defs>

                    <!-- Ground -->
                    <ellipse
                        cx="350"
                        cy="374"
                        rx="300"
                        ry="27"
                        fill="#e8eef8"
                    />

                    <!-- Connection lines -->
                    <path
                        d="M115 300 C180 265 205 250 260 236"
                        fill="none"
                        stroke="#b9cdf7"
                        stroke-width="2"
                        stroke-dasharray="6 7"
                    />

                    <path
                        d="M445 238 C505 260 535 276 600 300"
                        fill="none"
                        stroke="#c7c2fa"
                        stroke-width="2"
                        stroke-dasharray="6 7"
                    />

                    <!-- Warehouse -->
                    <g filter="url(#avitShadow)">

                        <path
                            d="M135 185 L315 115 L500 185 L500 322 L135 322 Z"
                            fill="url(#avitBuilding)"
                        />

                        <path
                            d="M135 185 L315 115 L500 185"
                            fill="none"
                            stroke="#b7c9eb"
                            stroke-width="4"
                        />

                        <!-- Roof -->
                        <path
                            d="M116 192 L315 105 L520 192 L500 208 L315 132 L136 208 Z"
                            fill="#ffffff"
                            stroke="#d7e2f4"
                            stroke-width="3"
                        />

                        <!-- Warehouse entrance -->
                        <path
                            d="M260 322 V220 Q315 187 370 220 V322 Z"
                            fill="#173064"
                        />

                        <path
                            d="M278 322 V230 Q315 208 352 230 V322 Z"
                            fill="#dbe7ff"
                        />

                        <!-- Windows -->
                        <rect
                            x="165"
                            y="220"
                            width="62"
                            height="42"
                            rx="5"
                            fill="#ffffff"
                            stroke="#c8d7ef"
                            stroke-width="3"
                        />

                        <rect
                            x="405"
                            y="220"
                            width="62"
                            height="42"
                            rx="5"
                            fill="#ffffff"
                            stroke="#c8d7ef"
                            stroke-width="3"
                        />

                        <path
                            d="M196 220 V262 M165 241 H227"
                            stroke="#d4e0f2"
                            stroke-width="2"
                        />

                        <path
                            d="M436 220 V262 M405 241 H467"
                            stroke="#d4e0f2"
                            stroke-width="2"
                        />

                    </g>

                    <!-- Dashboard card -->
                    <g filter="url(#avitShadow)">

                        <rect
                            x="350"
                            y="35"
                            width="205"
                            height="126"
                            rx="14"
                            fill="#ffffff"
                            stroke="#e1e8f3"
                            stroke-width="2"
                        />

                        <rect
                            x="368"
                            y="53"
                            width="56"
                            height="7"
                            rx="3.5"
                            fill="#dbe5f5"
                        />

                        <rect
                            x="368"
                            y="73"
                            width="72"
                            height="30"
                            rx="7"
                            fill="#edf3ff"
                        />

                        <text
                            x="379"
                            y="93"
                            font-size="11"
                            font-family="Inter, sans-serif"
                            font-weight="700"
                            fill="#2457d6"
                        >
                            ₹1.24M
                        </text>

                        <path
                            d="M370 137 L405 121 L430 128 L456 102 L483 111 L514 84 L540 96"
                            fill="none"
                            stroke="url(#avitBlue)"
                            stroke-width="4"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                        <circle
                            cx="540"
                            cy="96"
                            r="5"
                            fill="#2457d6"
                        />

                    </g>

                    <!-- Left package -->
                    <g filter="url(#avitShadow)">

                        <path
                            d="M58 276 L103 255 L148 276 L103 299 Z"
                            fill="#dfeaff"
                            stroke="#b9cdf7"
                            stroke-width="2"
                        />

                        <path
                            d="M58 276 V320 L103 344 V299 Z"
                            fill="#c8d9fb"
                            stroke="#b2c7ed"
                            stroke-width="2"
                        />

                        <path
                            d="M103 299 V344 L148 320 V276 Z"
                            fill="#b4cafa"
                            stroke="#a5bde7"
                            stroke-width="2"
                        />

                        <path
                            d="M103 255 V299"
                            stroke="#2457d6"
                            stroke-width="3"
                        />

                    </g>

                    <!-- Right package -->
                    <g filter="url(#avitShadow)">

                        <path
                            d="M552 276 L590 258 L628 276 L590 296 Z"
                            fill="#eeeefe"
                            stroke="#ccc9f8"
                            stroke-width="2"
                        />

                        <path
                            d="M552 276 V314 L590 334 V296 Z"
                            fill="#dddafa"
                            stroke="#c2bef1"
                            stroke-width="2"
                        />

                        <path
                            d="M590 296 V334 L628 314 V276 Z"
                            fill="#c9c5f5"
                            stroke="#b8b3ea"
                            stroke-width="2"
                        />

                        <path
                            d="M590 258 V296"
                            stroke="#5b4ee8"
                            stroke-width="3"
                        />

                    </g>

                    <!-- Delivery truck -->
                    <g filter="url(#avitShadow)">

                        <path
                            d="M465 318 H575 V352 H465 Z"
                            fill="url(#avitBlue)"
                        />

                        <path
                            d="M575 334 H617 L646 352 V365 H575 Z"
                            fill="#3c6de8"
                        />

                        <path
                            d="M602 337 H620 L636 351 H602 Z"
                            fill="#dfeaff"
                        />

                        <rect
                            x="484"
                            y="332"
                            width="52"
                            height="7"
                            rx="3"
                            fill="#ffffff"
                            opacity=".55"
                        />

                        <circle
                            cx="500"
                            cy="365"
                            r="17"
                            fill="#182b55"
                        />

                        <circle
                            cx="500"
                            cy="365"
                            r="7"
                            fill="#dbe5f5"
                        />

                        <circle
                            cx="611"
                            cy="365"
                            r="17"
                            fill="#182b55"
                        />

                        <circle
                            cx="611"
                            cy="365"
                            r="7"
                            fill="#dbe5f5"
                        />

                    </g>

                    <!-- Floating analytics icon -->
                    <g filter="url(#avitShadow)">

                        <rect
                            x="80"
                            y="110"
                            width="76"
                            height="76"
                            rx="17"
                            fill="#ffffff"
                            stroke="#e0e8f4"
                            stroke-width="2"
                        />

                        <rect
                            x="99"
                            y="147"
                            width="9"
                            height="20"
                            rx="3"
                            fill="#b9cdf7"
                        />

                        <rect
                            x="114"
                            y="137"
                            width="9"
                            height="30"
                            rx="3"
                            fill="#6e8fe9"
                        />

                        <rect
                            x="129"
                            y="126"
                            width="9"
                            height="41"
                            rx="3"
                            fill="#2457d6"
                        />

                    </g>

                    <!-- Floating efficiency icon -->
                    <g filter="url(#avitShadow)">

                        <circle
                            cx="600"
                            cy="180"
                            r="34"
                            fill="#ffffff"
                            stroke="#e0e8f4"
                            stroke-width="2"
                        />

                        <path
                            d="M585 181 L595 191 L616 168"
                            fill="none"
                            stroke="#2457d6"
                            stroke-width="6"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                    </g>

                </svg>

            </div>

            <div class="avit-showcase-footer">
                &copy; {{ date('Y') }} AVIT DIGITAL. All rights reserved.
            </div>

        </section>


        {{-- =====================================================
             RIGHT: AUTHENTICATION
             ===================================================== --}}
        <section class="avit-auth-side">

            <div class="avit-auth-wrapper">

                <div class="avit-mobile-brand">
                    <img
                        src="{{ asset('images/avit-digital-logo.png') }}"
                        alt="AVIT DIGITAL"
                    >
                </div>

                <div class="avit-login-card">
                    {{ $slot }}
                </div>

            </div>

        </section>

    </main>


    {{-- Existing PWA behaviour preserved --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker
                    .register('{{ asset('sw.js') }}')
                    .catch(() => {});
            });
        }
    </script>

</body>
</html>