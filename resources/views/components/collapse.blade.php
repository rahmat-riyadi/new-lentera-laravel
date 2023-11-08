@props([
    'expand' => false,
    'title' => '',
    'handle_toggle' => function (){}
])

<div class="bg-white py-4 px-6 rounded-lg {{ !$expand ? 'max-h-[53px]' : 'max-h-[1000px]'}} transition-all duration-300 overflow-hidden">
    <div class="flex mb-2">
        {{ $button }}
        {{-- <button class="{{ !$expand ? '-rotate-90' : 'rotate-0'}} transition-all" >
            <img src="{{ asset('assets/icons/arrow_carret_down.svg') }}" alt="">
        </button> --}}
        <p class="subtitle-1 text-base ml-2 " >{{ $title }} *</p>
    </div>
    <div class="ml-5" >
        {{ $slot }}
    </div>
</div>