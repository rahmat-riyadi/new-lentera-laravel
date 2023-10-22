<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $title ?? 'Page Title' }}</title>
        @include('partials.fontface')
        @stack('style')
        @vite('resources/css/app.css')
    </head>
    <body>
        <div class="w-full h-full md:h-screen flex scroll-smooth bg-[#F9FAFB]" >
            <x-sidebar/>
            <div class="grow flex flex-col" >
                <x-header/>
                <div class="grow overflow-hidden " >
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
