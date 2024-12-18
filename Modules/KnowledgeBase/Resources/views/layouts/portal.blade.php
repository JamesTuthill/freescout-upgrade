<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {!! \Helper::cspMetaTag() !!}

    <meta name="robots" content="noindex, nofollow">

    <title>@if (View::getSection('title') != \Kb::getKbName($mailbox))@yield('title') - {{ \Kb::getKbName($mailbox) }}@else{{ \Kb::getKbName($mailbox) }}@endif</title>

    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="@filter('layout.favicon', URL::asset('favicon.ico'))">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}" crossorigin="use-credentials">
    <link rel="mask-icon" href="{{ asset('safari-pinned-tab.svg') }}" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="@filter('layout.theme_color', '#ffffff')">
    @action('layout.head')
    @php
    try {
    @endphp
    {!! Minify::stylesheet(\Eventy::filter('stylesheets', array('/css/fonts.css', '/css/bootstrap.css', '/css/style.css', \Module::getPublicPath(KB_MODULE).'/css/style.css'))) !!}
    @php
    } catch (\Exception $e) {
    // Try...catch is needed to catch errors when activating a module and public symlink not created for module.
    \Helper::logException($e);
    }
    @endphp

    @yield('stylesheets')
</head>

<body @yield('body_attrs')>
    <div id="app">
        <nav class="navbar navbar-default navbar-static-top">
            <div class="container">
                <div class="navbar-header">

                    <!-- Collapsed Hamburger -->
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#app-navbar-collapse" aria-expanded="false">
                        <span class="sr-only">{{ __('Toggle Navigation') }}</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <!-- Branding Image -->
                    <a class="navbar-brand navbar-brand-with-text" href="{{ \Kb::getKbUrl($mailbox) }}">
                        @if (Eventy::filter('layout.header_logo', ''))
                        <img class="kb-nav-img" src="@filter('layout.header_logo', '')" height="100%" />
                        @endif
                    </a>
                </div>

                <div class="collapse navbar-collapse" id="app-navbar-collapse">
                    <!-- Left Side Of Navbar -->
                    <ul class="nav navbar-nav navbar-right">
                        @if (\Kb::getMenu($mailbox))
                        @foreach(\Kb::getMenu($mailbox) as $button_title => $button_url)
                        <li><a href="{{ $button_url }}">{{ $button_title }}</a></li>
                        @endforeach
                        @endif
                        @if (\Kb::isMultilingual($mailbox))
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle dropdown-toggle-icon" data-toggle="dropdown" title="{{ __('Search') }}">
                                <i class="glyphicon glyphicon-globe"></i> <small class="kb-locale-name">{{ \Helper::getLocaleData(\Kb::getLocale())['name'] ?? '' }}</small>
                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="12" viewBox="0 0 11 12" fill="none">
                                    <path d="M4.11796 1.91694L4.11796 7.23332L2.03335 5.16731C1.56833 4.70644 0.813772 4.70644 0.34876 5.16731C-0.116252 5.62817 -0.116252 6.376 0.34876 6.83686L4.46744 10.9188L4.46816 10.9195C4.4956 10.9467 4.52448 10.9725 4.55409 10.9968C4.56781 11.0082 4.58297 11.0183 4.59669 11.0283C4.6133 11.0404 4.6299 11.0533 4.64723 11.0655C4.66456 11.0769 4.68189 11.087 4.69994 11.097C4.71583 11.1063 4.73099 11.1163 4.7476 11.1249C4.76565 11.1342 4.7837 11.1421 4.80248 11.1507C4.81981 11.1585 4.83642 11.1671 4.85375 11.1743C4.8718 11.1814 4.88985 11.1871 4.90718 11.1936C4.92595 11.2 4.944 11.2072 4.9635 11.2129C4.98155 11.2186 4.9996 11.2222 5.01766 11.2265C5.03715 11.2315 5.05665 11.2365 5.07686 11.2408C5.0978 11.2451 5.11874 11.2473 5.13968 11.2501C5.15701 11.2523 5.17434 11.2558 5.19167 11.2573C5.26966 11.2652 5.34836 11.2652 5.42707 11.2573C5.4444 11.2558 5.46173 11.2523 5.47906 11.2501C5.5 11.2473 5.52094 11.2444 5.54188 11.2408C5.5621 11.2372 5.58087 11.2315 5.60109 11.2265C5.61914 11.2222 5.63719 11.2179 5.65524 11.2129C5.67402 11.2072 5.69279 11.2 5.71156 11.1936C5.72961 11.1871 5.74767 11.1814 5.765 11.1743C5.78233 11.1671 5.79893 11.1585 5.81626 11.1507C5.83431 11.1421 5.85309 11.1342 5.87114 11.1249C5.88775 11.1163 5.90291 11.1063 5.9188 11.097C5.93613 11.087 5.95418 11.0769 5.97151 11.0655C5.98884 11.054 6.00545 11.0412 6.02205 11.0283C6.03649 11.0175 6.05094 11.0075 6.06465 10.9968C6.09498 10.9725 6.12314 10.9467 6.15058 10.9195L6.1513 10.9188L10.1776 6.92846C10.4101 6.69803 10.5263 6.39604 10.5263 6.09333C10.5263 5.79062 10.4101 5.48862 10.1776 5.25819C9.71255 4.79733 8.95798 4.79733 8.49297 5.25819L6.50006 7.23332L6.50006 1.91765C6.50006 1.26572 5.96645 0.736869 5.30865 0.736869C4.65085 0.736869 4.11724 1.26572 4.11724 1.91765L4.11796 1.91694Z" fill="white" />
                                </svg>
                            </a>

                            <ul class="dropdown-menu">
                                @foreach(\Kb::getLocales($mailbox) as $locale)
                                <li @if ($locale==\Kb::getLocale()) class="active" @endif><a href="{{ \Kb::changeUrlLocale($locale) }}">{{ \Helper::getLocaleData($locale)['name'] ?? '' }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                        @endif
                        @if (!in_array(\Kb::getSettings($mailbox)['visibility'], [\Kb::VISIBILITY_PUBLIC, \Kb::VISIBILITY_USERS]))
                        @if (Kb::authCustomer())
                        <li class="dropdown">

                            <a href="#" class="dropdown-toggle dropdown-toggle-icon dropdown-toggle-account" data-toggle="dropdown">
                                <i class="glyphicon glyphicon-user"></i> <span class="nav-user">{{ Kb::authCustomer()->getMainEmail() }}</span> <span class="caret"></span>
                            </a>

                            <ul class="dropdown-menu">
                                <li>
                                    <a href="#" id="kb-customer-logout-link">
                                        {{ __('Log Out') }}
                                    </a>

                                    <form id="customer-logout-form" action="{{ route('knowledgebase.customer_logout', ['id' => \Kb::encodeMailboxId($mailbox->id)]) }}" method="POST" style="display: none;">
                                        {{ csrf_field() }}
                                    </form>
                                </li>
                            </ul>
                        </li>
                        @endif
                        @endif
                        @if (\Kb::getSettings($mailbox)['visibility'] != \Kb::VISIBILITY_PUBLIC)
                        @if (Auth::user())
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle dropdown-toggle-icon dropdown-toggle-account" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre title="{{ __('Account') }}" aria-label="{{ __('Account') }}">
                                <span class="photo-sm">@include('partials/person_photo', ['person' => Auth::user()])</span>&nbsp;<span class="nav-user">{{ Auth::user()->first_name }}@action('menu.user.name_append', Auth::user())</span> <span class="caret"></span>
                            </a>

                            <ul class="dropdown-menu">
                                <li>
                                    <a href="#" id="kb-logout-link">
                                        {{ __('Log Out') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        {{ csrf_field() }}
                                    </form>
                                </li>
                            </ul>
                        </li>
                        @endif
                        @endif
                    </ul>
                </div>
                <div class="kb-nav-hcwh-container row">
                    <form class="col-sm-6 col-sm-offset-3" action="{{ \Kb::route('knowledgebase.frontend.search', ['mailbox_id'=>\Kb::encodeMailboxId($mailbox->id)], $mailbox) }}">
                        <p class="text-center kb-nav-heading">{{ __('How can we help?') }}</p>
                        <div class="kb-nav-search-container input-group input-group-lg">
                            <img src="/img/search.svg" class="kb-search-icon" width="30" height="30" alt="Search icon">
                            <input type="text" class="kb-nav-search-bar form-control" name="q" placeholder="{{ _('Search') }}">
                        </div>
                    </form>
                </div>
            </div>
        </nav>
        <div class="content @yield('content_class')">
            <div id="kb-container">
                @yield('content')
            </div>
        </div>
        <div class="footer">
            {!! strtr(\Helper::stripDangerousTags($mailbox->meta['kb']['footer'] ?? '&copy; {%year%} {%mailbox.name%}'), ['{%year%}' => date('Y'), '{%mailbox.name%}' => $mailbox->name]) !!}
        </div>
    </div>

    @action('layout.body_bottom')

    {{-- Scripts --}}
    @php
    try {
    @endphp
    {!! Minify::javascript(\Eventy::filter('kb.javascripts', ['/js/jquery.js', '/js/bootstrap.js', '/js/lang.js', '/storage/js/vars.js', '/js/laroute.js', '/js/parsley/parsley.min.js', '/js/parsley/i18n/'.strtolower(Config::get('app.locale')).'.js', \Module::getPublicPath(KB_MODULE).'/js/main.js', '/js/main.js'])) !!}
    @php
    } catch (\Exception $e) {
    // To prevent 500 errors on update.
    // Also catches errors when activating a module and public symlink not created for module.
    if (strstr($e->getMessage(), 'vars.js')) {
    \Artisan::call('freescout:generate-vars');
    }
    \Helper::logException($e);
    }
    @endphp
    <script type="text/javascript" {!! \Helper::cspNonceAttr() !!}>
        @yield('kb_javascript')
    </script>
</body>

</html>
