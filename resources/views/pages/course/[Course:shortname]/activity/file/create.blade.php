<?php

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{state, mount, form};    

middleware(['auth']);
name('activity.file.create');

?>
<x-layouts.app>
    @volt
    <div class="h-full overflow-y-auto relative">
        <div class=" bg-white course-page-header px-8 py-8 font-main flex flex-col" >
            <x-back-button path="/" />
            <p class="text-sm text-[#656A7B] font-[400] flex items-center my-5" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> Pemgrograman Web 1 - Kelas A <span class="mx-2 text-[9px]" > >> </span>  <span class="text-[#121212]" >Tambah URL - Pertemuan 1</span></p>
            <h1 class="text-[#121212] text-xl font-semibold" >Tambah File - Pertemuan 1</h1>
        </div>

        <form wire:submit>

        </form>

    </div>
    @endvolt
</x-layouts.app>