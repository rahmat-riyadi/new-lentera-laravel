<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $title ?? 'Page Title' }}</title>
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
        <script src="https://cdn.tiny.cloud/1/mfwsl4xdczczqoigmfie0vd3tce8jna9eg7g5sq74qglzaz4/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
        @include('partials.fontface')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
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

        <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
        <script>
            function toast(type, message){
                return Toastify({
                    escapeMarkup: false,
                    position : 'right',
                    text:  `
                        <div class="flex " >
                            <div style="height: 60px; width: 5px;" class="${type == 'success' ? 'bg-primary' : 'bg-error'} rounded mr-4" ></div>
                            <div class="flex flex-col" >
                                <b style="color: #36B37E;" class="mb-[3px] mt-[6px]" >Berhasil</b>
                                <p style="color: #121212; font-size: 12px;" class="m-0 font-medium text-grey-600" >${message}</p>
                            </div>
                        </div>
                    `,
                    style: {
                        background: '#fff',
                        fontSize: '14px',
                        padding: '12px',
                        width: '250px',
                        borderRadius: '4px'
                    }
                }).showToast();
            }
        </script>
        @stack('script')
    </body>
</html>
