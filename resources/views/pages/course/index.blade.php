<?php

use function Livewire\Volt\{state, mount, on};
use App\Helpers\GlobalHelper;
use App\Models\{
    Module,
    CourseModule,
    Course,
    CourseSection,
    Url,
    Resource,
    Assign,
    Context,
};
state([
    'sections', 'course', 'topic', 'role'
]);

mount(function(Course $course){
    $ctx = Context::where('contextlevel', 50)->where('instanceid', 4)->first();
    $data = DB::connection('moodle_mysql')->table('mdl_role_assignments as ra')
    ->join('mdl_role as r', 'r.id', '=', 'ra.roleid')
    ->where('ra.contextid', $ctx->id)
    ->where('ra.userid', auth()->user()->id)
    ->select(
        'r.shortname as role',
    )
    ->first();
    $this->role = $data->role;
    $this->topic = new stdClass();
    $this->get_sections($course);
    $this->course = $course;
});

$get_sections = function ($course){
    $sections = [];

    $courseSections = CourseSection::where('course', $course->id)->get();

    foreach($courseSections as $cs){

        $section = new stdClass();

        $section->id = $cs->id;
        $section->name = $cs->name;
        $section->section = $cs->section;
        $section->modules = [];

        if(!empty($cs->sequence)){

            $cmids = explode(',', $cs->sequence);

            $courseModules = CourseModule::whereIn('id', $cmids)
            ->where('deletioninprogress', 0)
            ->where('course', $course->id)
            ->get();

            foreach($courseModules as $cm){

                $module = new stdClass();

                $module->id = $cm->id;
                $module->instance = $cm->instance;
                $module->module = $cm->module;

                $selectedModule = Module::find($cm->module);

                switch ($selectedModule->name) {
                    case 'url':
                        $mod_table = 'url';
                        break;
                    case 'resource':
                        $mod_table = 'resource';
                        break;
                    case 'attendance':
                        $mod_table = 'attendances';
                        break;
                    case 'assign':
                        $mod_table = 'assignments';
                        break;
                    case 'quiz':
                        $mod_table = 'quizzes';
                        break;
                    
                    default:
                        # code...
                        break;
                }

                if($selectedModule->name == 'url'){
                    $fields = ['id', 'name', 'description', 'url'];
                } else {
                    $fields = ['id', 'name', 'description'];
                }

                $instance = DB::table($mod_table)
                ->where('id', $cm->instance)
                ->first($fields);

                $module->name = $instance->name ?? '';
                $module->description = $instance->description ?? '';
                $module->modname = $selectedModule->name;

                if($selectedModule->name == 'url'){
                    $module->url = $instance->url;   
                }

                if($selectedModule->name == 'resource'){
                    $file = DB::table('resource_files')->where('resource_id', $instance->id)->first('file');
                    $module->file = url('storage/'.$file->file);   
                }

                $section->modules[] = $module;
            }
        }

        $sections[] = $section;
    }

    $this->sections = $sections;
    
};

$change_section_title = function (){
    CourseSection::where('id',$this->topic->id)->update([
        'name' => $this->topic->text,
        'timemodified' => time()
    ]);
    GlobalHelper::rebuildCourseCache($this->course->id);
    $this->get_sections($this->course);
    $this->dispatch('title-changed');
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
    GlobalHelper::rebuildCourseCache($this->course->id);
    $this->get_sections($this->course);
    $this->dispatch('notify', 'success', 'Berhasil menambah topik');
};

$delete_section = function ($id){
    CourseSection::where('id', $id)->delete();
    GlobalHelper::rebuildCourseCache($this->course->id);
    $this->get_sections($this->course);
    $this->dispatch('section-deleted-notify', 'success', 'Berhasil menghapus topik', $id);
};

$delete_activity = function ($id){

    DB::beginTransaction();

    try {
        $cm = CourseModule::find($id);
        $selectedModule = Module::find($cm->module);
        switch ($selectedModule->name) {
            case 'url':
                $mod_table = 'url';
                break;
            case 'resource':
                $mod_table = 'resource';
                break;
            case 'attendance':
                $mod_table = 'attendances';
                break;
            case 'assign':
                $mod_table = 'assignments';
                break;
            case 'quiz':
                $mod_table = 'quizzes';
                break;
        }
        DB::table($mod_table)
        ->where('id', $cm->instance)
        ->delete();
        $cm->update(['deletioninprogress' => 1]);
        GlobalHelper::rebuildCourseCache($this->course->id);
        $this->get_sections($this->course);
        DB::commit();
        $this->dispatch('notify', 'success', 'Berhasil menghapus aktivitas');
    } catch (\Throwable $th) {
        DB::rollBack();
        Log::info($th->getMessage());
        $this->dispatch('notify', 'error', 'Terjadi Kesalahan');
    }

};

on(['delete-section' => 'delete_section']);

on(['delete-module' => 'delete_activity']);

?>

<x-layouts.app>
    @volt
    <div x-data="pages({{ $course }})" class="overflow-y-auto h-full pb-3" >
        <div class=" bg-white course-page-header px-8 pt-8 font-main flex flex-col" >
            <p class="text-sm text-[#656A7B] font-[400] flex items-center" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> <span class="text-[#121212]" > Pemgrograman Web 1 - Kelas A</span></p>
            <h1 class="text-[#121212] text-2xl font-semibold mt-8" >{{ $course->fullname }} - Kelas A</h1>
            <div class="flex items-center mt-3" >
                <p class="text-lg" >Teknik Informatika</p>
                @if ($role != 'student')
                <x-button 
                    class="ml-auto"
                    wire:click="add_section"
                >
                    <svg class="w-[16.5px] fill-white mr-2 " width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16.5 7.5H10.5V1.5C10.5 1.10218 10.342 0.720645 10.0607 0.43934C9.77936 0.158036 9.39782 0 9 0C8.60218 0 8.22064 0.158036 7.93934 0.43934C7.65804 0.720645 7.5 1.10218 7.5 1.5V7.5H1.5C1.10218 7.5 0.720645 7.65804 0.43934 7.93934C0.158036 8.22064 0 8.60218 0 9C0 9.39782 0.158036 9.77936 0.43934 10.0607C0.720645 10.342 1.10218 10.5 1.5 10.5H7.5V16.5C7.5 16.8978 7.65804 17.2794 7.93934 17.5607C8.22064 17.842 8.60218 18 9 18C9.39782 18 9.77936 17.842 10.0607 17.5607C10.342 17.2794 10.5 16.8978 10.5 16.5V10.5H16.5C16.8978 10.5 17.2794 10.342 17.5607 10.0607C17.842 9.77936 18 9.39782 18 9C18 8.60218 17.842 8.22064 17.5607 7.93934C17.2794 7.65804 16.8978 7.5 16.5 7.5Z"/>
                    </svg>
                    <span>Tambah Pertemuan</span>
                </x-button>
                @endif
                {{-- <button class="btn-icon-light ml-3 hidden md:flex" >
                    <VerticalMoreSvg/>
                </button> --}}
            </div>
            <div class="flex mt-4" >
                {{-- <button wire:click="$set('currTab', 'progress')" class="flex items-center {{ $currTab == 'progress' ? 'border-b-[3px]' : 'border-0' }} border-primary pb-2 px-1 transition-all " >
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
                </button> --}}
            </div>
        </div>

        <div class="px-8">

            <div
                class="flex items-start overflow-hidden bg-white px-8 py-4 rounded-xl my-6 transition-[height] duration-1000"
                :class="!showAnnouncement ? 'h-[80px]' : 'h-fit'"
                style="box-shadow: 0px 3px 6px 0px rgba(16, 24, 40, 0.10);" 
            >
                <img src="{{ asset('/assets/icons/pengumuman.svg') }}" alt="">
                <div class="pt-3 flex flex-col w-full" >
                    <template x-if="showAnnouncement">
                        <input type="text" class="focus:placeholder:visible peer font-medium ml-4 caret-primary focus:bg-transparent  focus:outline-none" placeholder="Judul"  autofocus>
                    </template>    
                    <template x-if="!showAnnouncement">
                        <p class="ml-4 font-medium text-grey-700 " >Umumkan sesuatu di Kelas anda</p>
                    </template>    
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
                            focus:border-none
                        "
                        :class="showAnnouncement ? 'visible' : 'invisible'"
                        cols="30" 
                        rows="5"
                    ></textarea>
                </div>
            </div>

            @foreach ($sections as $i => $section)
            <div class="bg-white px-8 py-5 rounded-xl mb-3" >
                <div class="flex">
                    <p class="font-semibold text-lg mr-1" >
                        @if (empty($section->name))
                        {{ $i == 0 ? 'General' : 'Topic '. $i }} 
                        @else
                        {{ $section->name  }}
                        @endif
                    </p>
                    @if ($role != 'student')
                    <button @click="topic.edit(@js($section->id),@js($section->name))" >
                        <img src="{{ asset('assets/icons/edit-2.svg') }}" alt="">
                    </button>
                    <button @click="activity.show(@js($section->section))" class="btn-icon-light w-8 h-8 ml-auto hidden md:flex" >
                        <x-icons.plus class="w-4 fill-primary" />
                    </button>
                    <div class="relative" >
                        <button :class="{ 'bg-gray-200': dropdownSection.includes(@js($section->id)) }" @click="toggleDropdownSection(@js($section->id))" class="w-8 h-8 ml-2 hidden md:flex group rounded" >
                            <x-icons.more-svg class="fill-primary" />
                        </button>
                        <ul
                            x-show="dropdownSection.includes(@js($section->id))"
                            x-transition:enter="transition ease duration-300"
                            x-transition:enter-start="opacity-0 scale-75 translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease duration-300"
                            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-2 scale-75"
                            class="absolute w-[200px] z-10 mt-2 bg-white rounded-lg py-3 px-3 right-0 top-6 shadow-[0_8px_30px_rgb(0,0,0,0.12)] transform group-hover:opacity-100 group-hover:scale-y-100"
                        >
                            <li @click="deleteTopic(@js($section->id))" class="text-xs cursor-pointer rounded-lg hover:bg-grey-100 px-3 py-2 text-left" >
                                Hapus Pertemuan
                            </li>
                        </ul>
                    </div>
                    @endif
                </div>
                @foreach ($section->modules as $mod_idx => $module)
                @php
                    switch ($module->modname) {
                        case 'quiz':
                            $icon = asset('assets/icons/kuis.svg');
                            $detail_url = "/course/{$course->shortname}/activity/quiz/detail/{$module->id}";
                            break;
                        case 'url':
                            $icon = asset('assets/icons/url.svg');
                            $detail_url = "";
                            break;
                        case 'assign':
                            $icon = asset('assets/icons/penugasan.svg');
                            $detail_url = "/course/{$course->shortname}/activity/assignment/detail/{$module->id}";
                            break;
                        case 'attendance':
                            $icon = asset('assets/icons/kehadiran.svg');
                            $detail_url = "/course/{$course->shortname}/activity/attendance/detail/{$module->id}";
                            break;
                        case 'resource':
                            $icon = asset('assets/icons/berkas_md.svg');
                            $detail_url = "";
                            break;
                    }
                @endphp
                <div class="flex border hover:bg-grey-100 items-center border-grey-300 p-5 rounded-xl mt-5" >
                    <img src="{{ $icon }}" class="mr-3 w-10" alt="">
                    <div>
                        @switch($module->modname)
                            @case('url')
                                <a target="blank" href="{{ $module->url }}" class="text-sm font-semibold mb-1" >
                                    {{ $module->name }}
                                </a>
                                @break
                            @case('resource')
                                <a target="blank" href="{{ $module->file }}" class="text-sm font-semibold mb-1" >
                                    {{ $module->name }}
                                </a>
                                @break
                            @default
                            <a wire:navigate href="{{ $detail_url }}" class="text-sm font-semibold mb-1" >
                                {{ $module->name }}
                            </a>
                        @endswitch
                        <div class="text-sm" >
                            {!! $module->description !!}
                        </div>
                    </div>
                    @if ($role != 'student')
                    <div class="relative ml-auto">
                        <button type="button" @click="toggleDropdownModule({{ $module->id }})" class="w-8 h-8 ml-auto" >
                            <x-icons.more-svg class="fill-primary" />
                        </button>
                        <ul
                            x-show="dropdownModule.includes({{ $module->id }})"
                            x-transition:enter="transition ease duration-300"
                            x-transition:enter-start="opacity-0 translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease duration-300"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-2"
                            class="absolute w-[200px] z-10 mt-2 bg-white rounded-lg py-3 px-3 right-0 top-6 shadow-[0_8px_30px_rgb(0,0,0,0.12)] transform group-hover:opacity-100 group-hover:scale-y-100">
                            <li @click="editModule(@js($module->modname), {{ $module->instance }}, {{ $section->section }})" class="text-xs cursor-pointer rounded-lg hover:bg-grey-100 px-3 py-2 text-left" >
                                Edit Aktivitas
                            </li>
                            <li @click="deleteModule({{ $module->id }})" class="text-xs cursor-pointer rounded-lg hover:bg-grey-100 px-3 py-2 text-left" >
                                Hapus Aktivitas
                            </li>
                        </ul>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endforeach
        </div>

        <form wire:submit="change_section_title" >
            <x-modal
                show="topic.isEdit"
                onClose="topic.isEdit = false"
                title="Edit Pertemuan"
            >
                <label for="" class="block mb-2 text-sm font-medium text-grey-700">Judul Pertemuan</label>
                <input wire:model.live="topic.text" type="text" class="text-field text-field-base bg-grey-100 py-[10px] text-base w-[410px]" placeholder="Masukan judul" >
                <x-slot:footer>
                    <div class="flex items-center justify-end px-6 py-4 bg-grey-100" >
                        <x-button
                            class="mr-2"
                            type="submit"
                        >
                            Simpan
                        </x-button>
                        <x-button
                            variant="outlined"
                        >
                            Batal
                        </x-button>
                    </div>
                </x-slot>
            </x-modal>
        </form>

        <x-modal 
            title="Tambah Aktivitas" 
            show="activity.modal" 
            onClose="activity.modal = false"
        >
            <label for="kehadiran" class="flex items-center mb-4" >
                <input x-model="activity.current" value="attendance" name="activity" id="kehadiran" type="radio" class="radio">
                <img src="{{ asset('assets/icons/kehadiran.svg') }}" alt="" class="mx-[14px] w-12">
                <span class="font-medium" >Kehadiran</span>
            </label>
    
            <label for="berkas" class="flex items-center mb-4" >
                <input x-model="activity.current" value="file" name="activity" id="berkas" type="radio" class="radio">
                <img src="{{ asset('assets/icons/berkas_lg.svg') }}" alt="" class="mx-[14px] w-12">
                <span class="font-medium" >Berkas</span>
            </label>
    
            <label for="penugasan" class="flex items-center mb-4" >
                <input x-model="activity.current" value="assignment" name="activity" id="penugasan" type="radio" class="radio">
                <img src="{{ asset('assets/icons/penugasan.svg') }}" alt="" class="mx-[14px] w-12">
                <span class="font-medium" >Penugasan</span>
            </label>
    
            <label for="kuis" class="flex items-center mb-4" >
                <input x-model="activity.current" value="quiz" name="activity" id="kuis" type="radio" class="radio">
                <img src="{{ asset('assets/icons/kuis.svg') }}" alt="" class="mx-[14px] w-12">
                <span class="font-medium" >Kuis</span>
            </label>
    
            <label for="url" class="flex items-center" >
                <input x-model="activity.current" value="url" name="activity" id="url" type="radio" class="radio">
                <img src="{{ asset('assets/icons/url.svg') }}" alt="" class="mx-[14px] w-12">
                <span class="font-medium" >Url</span>
            </label>
            
            <x-slot:footer>
                <div class="flex items-center justify-end px-6 py-4 bg-grey-100" >
                    <x-button @click="createActivity()" class="text-sm px-3 mr-2" >Simpan</x-button>
                    <x-button
                        variant="outlined"
                        @click="activity.modal = false"
                    >
                        Batal
                    </x-button>
                </div>
            </x-slot:footer>

        </x-modal>

        <x-toast/>

    </div>

    @script
    <script>

        Alpine.data('pages', (course = null) => ({
            course,
            showAnnouncement: false,
            activity: {
                modal: false,
                section: null,
                current: null,
                show(section){
                    this.section = section
                    this.modal = true
                },
            },
            topic: {
                isEdit: false,
                loading: false,
                current: {
                    id: null,
                    text: null,
                },
                edit: function(id, text){
                    $wire.$set('topic.id', id)
                    $wire.$set('topic.text', text)
                    this.isEdit = true
                }
            },
            dropdownSection: [],
            dropdownModule: [],
            toggleDropdownSection(id){
                if(this.dropdownSection.includes(id))
                    this.dropdownSection = this.dropdownSection.filter(e => e !== id)
                else
                    this.dropdownSection.push(id)
            },
            toggleDropdownModule(id){
                console.log(id)
                if(this.dropdownModule.includes(id))
                    this.dropdownModule = this.dropdownModule.filter(e => e !== id)
                else
                    this.dropdownModule.push(id)
            },
            deleteTopic(id){
                this.dropdownSection = this.dropdownSection.filter(e => e !== id)
                Livewire.dispatch('delete-section', { id })
            },
            createActivity(){
                Livewire.navigate(`/course/${this.course.shortname}/activity/create/${this.activity.current}/section/${this.activity.section}`)
            },
            editModule(mod, id, section){
                console.log({ mod, id})
                Livewire.navigate(`/course/${this.course.shortname}/activity/update/${mod}/instance/${id}/section/${section}`)
            },
            deleteModule(id){
                this.dropdownModule = this.dropdownModule.filter(e => e !== id)
                $wire.$dispatch('delete-module', { id })
            }
        }))

        Livewire.on('title-change', () => {
            console.log('sdfds')
        })

        Livewire.on('notify', ([ type, message ]) => {
            Alpine.store('toast').show = true
            Alpine.store('toast').type = type
            Alpine.store('toast').message = message
            setTimeout(() => {
                Alpine.store('toast').show = false
            }, 2000);
        })

        Livewire.on('section-deleted-notify', ([ type, message ]) => {
            Alpine.store('toast').show = true
            Alpine.store('toast').type = type
            Alpine.store('toast').message = message
            setTimeout(() => {
                Alpine.store('toast').show = false
            }, 2000);
        })
        
    </script>
    @endscript

    @endvolt
</x-layouts.app>