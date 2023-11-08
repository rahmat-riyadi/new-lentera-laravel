@props(['path'])

<a {{ $attributes }} href="{{ $path }}" class="flex w-fit items-center" >
    <img src="{{ asset('assets/icons/arrow_back.svg') }}" class="mr-2 w-7" alt="">
    <p class="text-xs" >Kembali</p>
</a>