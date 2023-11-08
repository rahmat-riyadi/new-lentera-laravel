<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $title ?? 'Page Title' }}</title>
        @include('partials.fontface')
        <style>

            .wrapper {
                box-shadow: 0px 0px 12px 0px rgba(123, 123, 123, 0.10), 0px 2px 4px 0px rgba(16, 24, 40, 0.10);
            }
        
        </style>
        <script src="https://cdn.tiny.cloud/1/mfwsl4xdczczqoigmfie0vd3tce8jna9eg7g5sq74qglzaz4/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
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


        {{-- <script src="https://cdn.tiny.cloud/1/mfwsl4xdczczqoigmfie0vd3tce8jna9eg7g5sq74qglzaz4/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script> --}}
        {{-- <script src="https://cdn.tiny.cloud/1/mfwsl4xdczczqoigmfie0vd3tce8jna9eg7g5sq74qglzaz4/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script> --}}
        
        @stack('script')
    </body>
</html>
