<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $title ?? 'Lentera' }}</title>
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/favico/apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('/assets/favico/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('/assets/favico/favicon-16x16.png') }}">
        <link rel="manifest" href="{{ asset('/assets/favico/site.webmanifest') }}">
        <link rel="mask-icon" href="{{ asset('/assets/favico/safari-pinned-tab.svg') }}" color="#5bbad5">
        <meta name="msapplication-TileColor" content="#da532c">
        <meta name="theme-color" content="#ffffff">
        <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/mask@3.x.x/dist/cdn.min.js"></script>
        <script src="https://cdn.tiny.cloud/1/mfwsl4xdczczqoigmfie0vd3tce8jna9eg7g5sq74qglzaz4/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
        @include('partials.fontface')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body x-data :class="{ 'overflow-hidden': $store.sidebar.show }" >
        <div class="w-full h-full md:h-screen flex scroll-smooth bg-[#F9FAFB]" >
            <x-sidebar/>
            <div class="grow flex flex-col" >
                <x-header/>
                <div class="grow overflow-hidden " >
                    {{ $slot }}
                </div>
            </div>
        </div>

        <div    
            x-data
            class="backdrop absolute bg-black/40 inset-0 z-[9] duration-300 ease-in-out"
            x-show="$store.sidebar.show"
            @click="$store.sidebar.show = false"
        >

        </div>

        @persist('loader')
        <div class="page-loader transition-all duration-[.7s]">
            <div>
                <svg class="fill-primary w-14"  viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><style>.spinner_9y7u{animation:spinner_fUkk 2.4s linear infinite;animation-delay:-2.4s}.spinner_DF2s{animation-delay:-1.6s}.spinner_q27e{animation-delay:-.8s}@keyframes spinner_fUkk{8.33%{x:13px;y:1px}25%{x:13px;y:1px}33.3%{x:13px;y:13px}50%{x:13px;y:13px}58.33%{x:1px;y:13px}75%{x:1px;y:13px}83.33%{x:1px;y:1px}}</style><rect class="spinner_9y7u" x="1" y="1" rx="1" width="10" height="10"/><rect class="spinner_9y7u spinner_DF2s" x="1" y="1" rx="1" width="10" height="10"/><rect class="spinner_9y7u spinner_q27e" x="1" y="1" rx="1" width="10" height="10"/></svg>
                <span class="mt-2 block animate-pulse"  >loading...</span>
            </div>
        </div>

        <script >
            window.addEventListener('load', () => {
                const loader = document.querySelector('.page-loader')
                loader.classList.add('!opacity-0')
                loader.addEventListener('transitionend', () => {
                    loader.remove()
                })
            })
        </script>
        @endpersist
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        @stack('script')
    </body>
</html>
