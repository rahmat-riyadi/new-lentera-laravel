@props([
    'link',
    'title',
    'description',
    'icon',
    'show'
])

<a href="{{ $link }}" class="flex border hover:bg-grey-100 items-center border-grey-300 p-5 rounded-xl mt-5" >
    <img src="{{ $icon }}" class="mr-3 w-10" alt="">
    <div>
        <p class="text-sm font-semibold mb-1" >{{ $title }}</p>
        <p class="text-xs" >{!! $description !!}</p>
    </div>
    <div class="relative ml-auto">
        <button class="w-8 h-8 ml-auto" >
            <x-icons.more-svg class="fill-primary" />
        </button>
        @if ($show ?? false)
        <div class="absolute z-10 mt-2 bg-white flex flex-col gap-y-1 rounded-md p-2 px-3 w-max right-0 top-6 shadow-[0_8px_30px_rgb(0,0,0,0.12)] transform transition ease-in-out duration-300 opacity-100 scale-y-100 group-hover:opacity-100 group-hover:scale-y-100">
            <a href="/course/{courseId}/url/form/{contentModule.instance}?section={e}" class="text-xs cursor-pointer hover:bg-grey-100 px-3 py-2 text-left" >Edit Aktivitas</a>
            <button class="text-xs cursor-pointer hover:bg-grey-100 px-3 py-2 text-left" >{ loading.deleteActivity ? 'menghapus..' : 'Hapus Aktivitas'}</button>
        </div>
        @endif
    </div>
</a>