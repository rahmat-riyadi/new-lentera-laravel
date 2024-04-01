@props([
    'path',
    'title',
    'course',
    'section',
    'hold',
    'onclick',
])

<div class="bg-white course-page-header px-8 py-8 font-main flex flex-col" >
    @if (isset($hold))
    <x-back-button @click="{{ $onclick }}" path="javascript:;" />
    @else
    <x-back-button wire:navigate.hover path="{{ $path }}" />
    @endif
    <p class="text-sm text-[#656A7B] font-[400] flex flex-wrap leading-7 items-center my-5" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> {{ $course->fullname }} <span class="mx-2 text-[9px]" > >> </span>  <span class="text-[#121212]" >{{ $title }} - {{ $section->name }}</span></p>
    <h1 class="text-[#121212] text-xl font-semibold" >{{ $title }} - {{ $section->name }}</h1>
</div>