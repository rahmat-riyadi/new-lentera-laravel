@props([
    'show' => false,
    'title' => '',
])

<div class="fixed bg-transparent z-[1001] inset-0 {{ !$show ? "invisible" : "visible" }} duration-300 flex transition-all ease-in-out" >
    <div class="m-auto bg-white min-w-[550px] rounded-lg overflow-hidden transition-all duration-300 {{ !$show ? "translate-y-full opacity-0" : "translate-y-0 opacity-100 " }} ease-in-out" >
        <div class="flex items-center justify-between px-6 py-4 border-b border-grey-300" >
            <span class="font-semibold text-lg" >{{ $title }}</span>
            <button on:click={onClose} >
                <x-icons.union class="fill-grey-500" />
            </button>
        </div>
        <div class="px-6 py-6" >
            {{ $slot }}
        </div>
        {{ $footer }}
    </div>
</div>

<div class="fixed inset-0 bg-black transition-all {{ !$show ? "invisible opacity-0 -z-50" : "visible opacity-50 z-[1000]" }} duration-300 ease-in-out" ></div>