<?php

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{state, mount, form};    
use App\Models\Course;
use App\Helpers\CourseHelper;
use App\Http\Controller\CourseModuleController;
use App\Livewire\Forms\Activity\URLForm;

middleware(['auth']);
name('activity.url.create');
form(URLForm::class);

state([
    'course' => null,
    'expand' => [
        'url' => false,
        'general' => false
    ],
]);

mount(function (Course $course){
    $this->form->setSection(request()->query('section'));
    $this->course = $course;
    $this->form->setModel($course);
});

$handle_toggle_collapse = function ($section){
    $this->expand[$section] = !$this->expand[$section];
    Log::debug(!$this->expand[$section]);
};

$submit = function (){
    try {
        $this->form->store();
        $this->redirect("/course/{$this->course->shortname}");
    } catch (\Throwable $th) {
        Log::debug($th->getMessage());
    }
};

?>

<x-layouts.app>
    @volt
    <div class="h-full overflow-y-auto relative">
        <div class=" bg-white course-page-header px-8 py-8 font-main flex flex-col" >
            <x-back-button path="/" />
            <p class="text-sm text-[#656A7B] font-[400] flex items-center my-5" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> Pemgrograman Web 1 - Kelas A <span class="mx-2 text-[9px]" > >> </span>  <span class="text-[#121212]" >Tambah URL - Pertemuan 1</span></p>
            <h1 class="text-[#121212] text-xl font-semibold" >Tambah URL - Pertemuan 1</h1>
        </div>
    
        <form wire:submit="submit">
            <div class="px-8 pt-8 pb-10 {!pageLoading ? 'opacity-100' : 'opacity-0'} transition-all duration-300">
                <div class="bg-white py-4 px-6 rounded-lg { !expand.general ? 'max-h-[53px]' : 'max-h-[500px]'} transition-all duration-500 overflow-hidden">
                    <div class="flex mb-2">
                        <button class="{ !expand.general ? '-rotate-90' : 'rotate-0'} transition-all">
                            <img src="{{ asset('assets/icons/arrow_carret_down.svg') }}" alt="">
                        </button>
                        <p class="subtitle-1 text-base ml-2 " >Umum *</p>
                    </div>
                    <div class="ml-5" >
                        <div class=" gap-x-5">
                            <label for="urlname">
                                <span class="block label text-gray-600 text-[12px] mb-1" >Nama</span>
                                <div class="text-field flex w-1/2" >
                                    <input wire:model="form.name" type="text" id="urlname" placeholder="Masukkan Nama"  class="text-field-base peer grow">
                                </div>
                            </label>
                        </div>
                        <div class="mt-3">
                            <label for="desc">
                                <span class="block label text-gray-600 text-[12px] mb-1" >Deskripsi</span>
                                <div class="text-field flex w-1/2" >
                                    <input type="text"  wire:model="form.intro" id="desc" placeholder="Masukkan Nama"  class="text-field-base peer grow">
                                </div>
                            </label>
                        </div>
                        {{-- <span class="block label text-gray-600 text-[12px] mb-1 mt-4" >Deskripsi</span>
                        <script>
                            tinymce.init({
                              selector: 'textarea',
                              plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
                              toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
                            });
                        </script>
                        <textarea wire:ignore name="" id="" cols="30" rows="10"></textarea> --}}
                    </div>
                </div>
                <div class="h-4" ></div>
                <x-collapse
                    title="URL"
                    :expand="$expand['url']"
                >
                    <x-slot:button>
                        <button type="button" wire:click="handle_toggle_collapse('url')" class="{{ !$expand['url'] ? '-rotate-90' : 'rotate-0'}} transition-all" >
                            <img src="{{ asset('assets/icons/arrow_carret_down.svg') }}" alt="">
                        </button>
                    </x-slot:button>
    
                    <div class=" gap-x-5">
                        <label for="url">
                            <span class="block label text-gray-600 text-[12px] mb-1" >Link Url</span>
                            <div class="text-field flex" >
                                <input type="text" wire:model="form.externalurl" id="url" placeholder="Masukkan URL"  class="text-field-base peer grow">
                            </div>
                        </label>
                    </div>
                </x-collapse>
                <div class="h-4" ></div>
                <div class="flex justify-end gap-3 mt-4" >
                    <x-button type="submit" >
                        <span wire:loading wire:target="submit" >loadingg</span>
                        <span wire:loading.remove wire:target="submit" >Simpan</span>
                    </x-button>
                    <x-button-outlined>Batal</x-button-outlined>
                </div>
            </div>
        </form>
    </div>
    
    
    @push('script')
        
        {{-- <scrip2 --}}

    @endpush
    @endvolt
</x-layouts.app>