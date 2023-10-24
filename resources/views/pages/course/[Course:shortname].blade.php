<?php

use Illuminate\Support\Facades\Http;
use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{state, mount};    
use App\Models\Course;

middleware(['auth']);
name('course');

state([
    'currTab' => 'progress',
    'course' => null,
    'show_announcement' => false,
    'more_dropdown_idx' => -1,
    'more_dropdown_module_idx' => -1,
    'sections' => []
]);

mount(function(Course $course) {
    $this->course = $course;
    $response = Http::asForm()->post(env('MOODLE_URL').'/webservice/rest/server.php',[
        'wstoken' => '067af81d0a1a177566f9e0c886e7644c',
        'wsfunction' => 'core_course_get_contents',
        'moodlewsrestformat' => 'json',
        'courseid' => $course->id
    ]);
    Log::debug($response->json());
    $this->sections = $response->json();
});

$handle_toggle_more_dropdown = function ($val){
    $this->more_dropdown_idx = $this->more_dropdown_idx == $val ? -1 : $val;
}

?>

<x-layouts.app>
    @volt
    <div class="overflow-y-auto h-full pb-3" >
        <div class="" >
            <div class=" bg-white course-page-header px-8 pt-8 font-main flex flex-col" >
                <p class="text-sm text-[#656A7B] font-[400] flex items-center" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> <span class="text-[#121212]" > Pemgrograman Web 1 - Kelas A</span></p>
                <h1 class="text-[#121212] text-2xl font-semibold mt-8" >{{ $course->fullname }} - Kelas A</h1>
                <div class="flex items-center mt-3" >
                    <p class="text-lg" >Teknik Informatika</p>
                    <x-button :disabled="false" class="ml-auto hidden md:flex" >
                        <x-slot:startIcon>
                            <x-icons.plus class="w-[16.5px] fill-white mr-2" />
                        </x-slot>
                        Tambah Pertemuan
                    </x-button>
                    <button class="btn-icon-light ml-3 hidden md:flex" >
                        {{-- <VerticalMoreSvg/> --}}
                    </button>
                </div>
                <div class="flex mt-4" >
                    <button wire:click="$set('currTab', 'progress')" class="flex items-center {{ $currTab == 'progress' ? 'border-b-[3px]' : 'border-0' }} border-primary pb-2 px-1 transition-all " >
                        <x-icons.chart class="{{ $currTab == 'progress' ? 'fill-[#09244B]' : 'fill-grey-400' }} w-5 transition-all" />
                        <p class="font-medium {{ $currTab == 'progress' ? 'text-black' : 'text-grey-400' }}  ml-2 text-sm transition-all" >Progres</p>
                    </button>
                    <button wire:click="$set('currTab', 'value')" class="flex items-center border-primary {{ $currTab == 'value' ? 'border-b-[3px]' : 'border-0' }} pb-2 px-1 mx-6 transition-all" >
                        <x-icons.coin class="{{  $currTab == 'value' ? 'fill-[#09244B]' : 'fill-grey-400'  }} transition-all w-5 " />
                        <p class="font-medium  {{ $currTab == 'value' ? 'text-black' : 'text-grey-400' }} transition-all ml-2 text-sm " >Nilai</p>
                    </button>
                    <button wire:click="$set('currTab', 'parcitipants')" class="flex items-center border-primary {{ $currTab == 'parcitipants' ? 'border-b-[3px]' : 'border-0' }} pb-2 px-1 transition-all" >
                        <x-icons.user-fill class="{{  $currTab == 'parcitipants' ? 'fill-[#09244B]' : 'fill-grey-400'  }} transition-al w-[22px]" />
                        <p class="font-medium {{  $currTab == 'parcitipants' ? 'text-black' : 'text-grey-400'  }} transition-all ml-2 text-sm" >Peserta</p>
                    </button>
                </div>
            </div>
        </div>
        <div class="px-8">

            <div 
                class="{{ !$show_announcement ? 'h-[80px]' : 'h-fit' }}  flex items-start overflow-hidden bg-white px-8 py-4 rounded-xl my-6 transition-[height] duration-1000" 
                style="box-shadow: 0px 3px 6px 0px rgba(16, 24, 40, 0.10);" 
            >
                <img src="{{ asset('assets/icons/pengumuman.svg') }}" alt="">
                <div class="pt-3 flex flex-col w-full" >
                    @if ($show_announcement)
                    <input type="text" class="focus:placeholder:visible peer font-medium ml-4 caret-primary focus:bg-transparent  focus:outline-none" placeholder="Judul"  autofocus>
                    @else
                    <p class="ml-4 font-medium text-grey-700 " >Umumkan sesuatu di Kelas anda</p>
                    @endif
                    <textarea 
                        placeholder="keterangan" 
                        class="
                            placeholder:invisible 
                            resize-none 
                            peer-focus:placeholder:visible  
                            ml-4 
                            mt-2 
                            text-sm 
                            focus:outline-none 
                            caret-primary 
                            {{ $show_announcement ? "visible" : 'invisible' }}
                            focus:border-none
                        "
                        cols="30" 
                        rows="5"
                    ></textarea>
                    <div class="flex justify-end" >
                        <x-button class="mr-3" >Simpan</x-button>
                        <x-button-light>Batal</x-button-light>
                    </div>
                </div>
            </div>

            @foreach ($sections as $i => $section)
            <div class="bg-white px-8 py-5 rounded-xl mb-3" >
                <div class="flex">
                    <p class="font-semibold text-lg mr-1" >{{ $section['name'] }} </p>
                    <button >
                        <img src="{{ asset('assets/icons/edit-2.svg') }}" alt="">
                    </button>
                    <button class="btn-icon-light w-8 h-8 ml-auto hidden md:flex" >
                        <x-icons.plus class="w-4 fill-primary" />
                    </button>
                    <div class="relative" >
                        <button wire:click="handle_toggle_more_dropdown('{{ $i }}')" class="w-8 h-8 ml-2 hidden md:flex group" >
                            <x-icons.more-svg class="fill-primary" />
                        </button>
                        @if ($more_dropdown_idx == $i)
                        <div class="absolute z-10 mt-2 bg-white rounded-lg p-4 px-5 w-max right-0 top-6 shadow-[0_8px_30px_rgb(0,0,0,0.12)] transform transition ease-in-out duration-300 opacity-100 scale-y-100 group-hover:opacity-100 group-hover:scale-y-100">
                            <button class="text-sm cursor-pointer" >hapus pertemuan</button>
                        </div>
                        @endif
                    </div>
                </div>
                @foreach ($section['modules'] as $mod_idx => $module)
                @php
                    switch ($module['modname']) {
                        case 'quiz':
                            $icon = asset('assets/icons/kuis.svg');
                            break;
                        case 'url':
                            $icon = asset('assets/icons/url.svg');
                            break;
                        case 'assign':
                            $icon = asset('assets/icons/penugasan.svg');
                            break;
                        case 'resource':
                            $icon = asset('assets/icons/berkas_md.svg');
                            break;
                    }
                @endphp
                <x-course-module-card
                    :title="$module['name']"
                    :description="$module['description']"
                    link="sfsd"
                    icon="{{ $icon }}"
                    :show="$mod_idx == $more_dropdown_module_idx"
                />
                @endforeach
            </div>
            @endforeach
        </div>
    </div>
    @endvolt
</x-layouts.app>