<?php

use Illuminate\Support\Facades\Http;
use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{state, mount};    
use App\Models\Course;
use App\Models\CourseSection;
use App\Helpers\Helper;
use App\Http\Controllers\CourseModuleController;

middleware(['auth']);
name('course');

state([
    'currTab' => 'progress',
    'course' => null,
    'show_announcement' => false,
    'more_dropdown_idx' => -1,
    'more_dropdown_module_idx' => [
        'module_idx' => -1,
        'course_idx' => -1
    ],
    'update_section_idx' => -1,
    'sections' => [],
    'show_modal_change_title' => false,
    'current_title' => null,
    'current_title_id' => null,
    'show_activity_modal' => false,
    'selected_section' => -1,
    'activity' => '',
    'activity_section' => -1
]);

mount(function(Course $course) {
    $this->course = $course;
    $this->fetch_content();
});

$fetch_content = function (){
    
    $response = Http::asForm()->post(env('MOODLE_URL').'/webservice/rest/server.php',[
        'wstoken' => '067af81d0a1a177566f9e0c886e7644c',
        'wsfunction' => 'core_course_get_contents',
        'moodlewsrestformat' => 'json',
        'courseid' => $this->course->id
    ]);
    // Log::debug($response->json());
    $this->sections = $response->json();
};

$handle_toggle_more_dropdown = function ($val){
    $this->more_dropdown_idx = $this->more_dropdown_idx == $val ? -1 : $val;
};

$handle_toggle_module_more_dropdown = function ($course_idx, $mod_idx){
    $this->more_dropdown_module_idx['course_idx'] = $course_idx;
    $this->more_dropdown_module_idx['module_idx'] = $this->more_dropdown_module_idx['module_idx'] == $mod_idx ? -1 : $mod_idx;
};

$handle_show_modal_change_title = function($id, $title) {
    $this->current_title_id = $id;
    $this->current_title = $title;
    $this->show_modal_change_title = true;
};

$handle_close_modal_change_title = function () {
    $this->show_modal_change_title = false;
};

$change_title_name = function (){

    CourseSection::find($this->current_title_id)->update([
        'name' => $this->current_title,
        'timemodified' => strtotime(Carbon\Carbon::now())
    ]);
    Helper::purge_caches();
    $this->fetch_content();
    $this->show_modal_change_title = false;

};

$add_section = function (){
    
    $currSection = $this->course->section->max('section');

    $this->course->section()->create([
        'section' => $currSection+1,
        'summaryformat' => 1,
        'name' => "Topic ".$currSection+1,
        'sequence' => ' ',
        'summary' => ' ',
        'timemodified' => time()
    ]);
    Helper::purge_caches();
    $this->fetch_content();

};

$handle_show_modal_add_activity = function ($val, $section){
    $this->show_activity_modal = true;
    $this->selected_section = $val;
    $this->activity_section = $section;
};

$handle_close_modal_add_activity = function (){
    $this->show_activity_modal = false;
    $this->selected_section = -1;
};

$handle_add_activity = function (){
    switch ($this->activity) {
        case 'url':
            $this->redirect("/course/{$this->course->shortname}/activity/url/create?section={$this->activity_section}");
            break;
        case 'berkas':
            $this->redirect("/course/{$this->course->shortname}/activity/file/create?section={$this->activity_section}");
            break;
        case 'penugasan':
            $this->redirect("/course/{$this->course->shortname}/activity/assignment/create?section={$this->activity_section}");
            break;
        
        default:
            # code...
            break;
    }
};

$handle_delete_module = function ($id){

    try {
        CourseModuleController::delete($id);
        $this->fetch_content();
    } catch (\Throwable $th) {
        Log::debug($th->getMessage());
    }

}

?>

<x-layouts.app
    title="Kursus: {{ $course->shortname }}"
>
    @volt
    <div class="overflow-y-auto h-full pb-3" >
        <div class="" >
            <div class=" bg-white course-page-header px-8 pt-8 font-main flex flex-col" >
                <p class="text-sm text-[#656A7B] font-[400] flex items-center" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> <span class="text-[#121212]" > Pemgrograman Web 1 - Kelas A</span></p>
                <h1 class="text-[#121212] text-2xl font-semibold mt-8" >{{ $course->fullname }} - Kelas A</h1>
                <div class="flex items-center mt-3" >
                    <p class="text-lg" >Teknik Informatika</p>
                    <x-button wire:click="add_section" :disabled="false" class="ml-auto hidden md:flex" >
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
                    <button wire:click="handle_show_modal_change_title({{ $section['id'] }} ,'{{ $section['name'] }}')" >
                        <img src="{{ asset('assets/icons/edit-2.svg') }}" alt="">
                    </button>
                    <button wire:click="handle_show_modal_add_activity({{ $section['id'] }}, {{ $section['section'] }})" class="btn-icon-light w-8 h-8 ml-auto hidden md:flex" >
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
                <a href="javascript:;" class="flex border hover:bg-grey-100 items-center border-grey-300 p-5 rounded-xl mt-5" >
                    <img src="{{ $icon }}" class="mr-3 w-10" alt="">
                    <div>
                        <p class="text-sm font-semibold mb-1" >{{ $module['name'] }}</p>
                        <p class="text-xs" >{!! $module['description'] !!}</p>
                    </div>
                    <div class="relative ml-auto">
                        <button type="button" wire:click="handle_toggle_module_more_dropdown({{ $i }},{{ $mod_idx }})" class="w-8 h-8 ml-auto" >
                            <x-icons.more-svg class="fill-primary" />
                        </button>
                        @if ($mod_idx == $more_dropdown_module_idx['module_idx'] && $i == $more_dropdown_module_idx['course_idx'])
                        <div class="absolute z-10 mt-2 bg-white flex flex-col gap-y-1 rounded-md p-2 px-3 w-max right-0 top-6 shadow-[0_8px_30px_rgb(0,0,0,0.12)] transform transition ease-in-out duration-300 opacity-100 scale-y-100 group-hover:opacity-100 group-hover:scale-y-100">
                            {{-- <a href="/course/{courseId}/url/form/{contentModule.instance}?section={e}" class="text-xs cursor-pointer hover:bg-grey-100 px-3 py-2 text-left" >Edit Aktivitas</a> --}}
                            <button wire:click="handle_delete_module({{ $module['id'] }})" class="text-xs cursor-pointer hover:bg-grey-100 px-3 py-2 text-left" >
                                <span wire:loading wire:target="handle_delete_module({{ $module['id'] }})" >loadingg</span>
                                <span wire:loading.remove wire:target="handle_delete_module({{ $module['id'] }})" >Hapus Aktivitas</span>
                            </button>
                        </div>
                        @endif
                    </div>
                </a>
                @endforeach
            </div>
            @endforeach
        </div>

        <x-modal title="sdfsd" :show="$show_modal_change_title" >
            <label for="" class="block mb-2 text-sm font-medium text-grey-700">Judul Pertemuan</label>
            <input wire:model.live="current_title" wire:keydown.enter="change_title_name" type="text" class="text-field text-field-base bg-grey-100 py-[10px] text-base w-[410px]" placeholder="Masukan judul" >
            <x-slot:footer>
                <div class="flex items-center justify-end px-6 py-4 bg-grey-100" >
                    <x-button wire:loading.attr="disabled" wire:click="change_title_name" class="text-sm px-3" >Simpan</x-button>
                    <x-button-outlined wire:click="handle_close_modal_change_title" class="text-sm px-3 border-[1.5px] ml-3 bg-transparent">Batal</x-button-outlined>
                </div>
            </x-slot:footer>
        </x-modal>


        <x-modal title="Tambah Aktivitas" :show="$show_activity_modal" >

            <label for="kehadiran" class="flex items-center mb-4" >
                <input wire:model="activity" value="kehadiran" name="activity" id="kehadiran" type="radio" class="radio">
                <img src="{{ asset('assets/icons/kehadiran.svg') }}" alt="" class="mx-[14px] w-12">
                <span class="font-medium" >Kehadiran</span>
            </label>
    
            <label for="berkas" class="flex items-center mb-4" >
                <input wire:model="activity" value="berkas" name="activity" id="berkas" type="radio" class="radio">
                <img src="{{ asset('assets/icons/berkas_lg.svg') }}" alt="" class="mx-[14px] w-12">
                <span class="font-medium" >Berkas</span>
            </label>
    
            <label for="penugasan" class="flex items-center mb-4" >
                <input wire:model="activity" value="penugasan" name="activity" id="penugasan" type="radio" class="radio">
                <img src="{{ asset('assets/icons/penugasan.svg') }}" alt="" class="mx-[14px] w-12">
                <span class="font-medium" >Penugasan</span>
            </label>
    
            <label for="kuis" class="flex items-center mb-4" >
                <input wire:model="activity" value="kuis" name="activity" id="kuis" type="radio" class="radio">
                <img src="{{ asset('assets/icons/kuis.svg') }}" alt="" class="mx-[14px] w-12">
                <span class="font-medium" >Kuis</span>
            </label>
    
            <label for="url" class="flex items-center" >
                <input wire:model="activity" value="url" name="activity" id="url" type="radio" class="radio">
                <img src="{{ asset('assets/icons/url.svg') }}" alt="" class="mx-[14px] w-12">
                <span class="font-medium" >Url</span>
            </label>
            
            <x-slot:footer>
                <div class="flex items-center justify-end px-6 py-4 bg-grey-100" >
                    <x-button wire:click="handle_add_activity" class="text-sm px-3" >Simpan</x-button>
                    <x-button-outlined wire:click="handle_close_modal_add_activity" class="text-sm px-3 border-[1.5px] ml-3 bg-transparent">Batal</x-button-outlined>
                </div>
            </x-slot:footer>

        </x-modal>

    </div>
    @endvolt
</x-layouts.app>