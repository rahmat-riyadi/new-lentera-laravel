<?php

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{state, mount, form};    
use App\Models\Course;
use App\Helpers\CourseHelper;
use App\Http\Controller\CourseModuleController;
use App\Livewire\Forms\Activity\AssignForm;

middleware(['auth']);
name('activity.url.create');
form(AssignForm::class);

state([
    'course' => null,
    'expand' => [
        'url' => true,
        'general' => true,
        'file' => true,
        'time' => true,
        'submissiontype' => true,
        'activityremember' => true
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
    // $this->validate();
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
            <h1 class="text-[#121212] text-xl font-semibold" >Tambah Penugasan - Pertemuan 1</h1>
        </div>
    
        <form wire:submit="submit">
            <div class="px-8 pt-8 pb-10 {!pageLoading ? 'opacity-100' : 'opacity-0'} transition-all duration-300">

                <x-collapse
                    title="Umum"
                    :expand="$expand['general']"
                >

                    <x-slot:button>
                        <button type="button" wire:click="handle_toggle_collapse('general')" class="{{ !$expand['url'] ? '-rotate-90' : 'rotate-0'}} transition-all" >
                            <img src="{{ asset('assets/icons/arrow_carret_down.svg') }}" alt="">
                        </button>
                    </x-slot:button>

                    <div class="gap-x-5">
                        <label for="url">
                            <span class="block label text-gray-600 text-[12px] mb-1" >Nama</span>
                            <div class="text-field flex w-1/2" >
                                <input type="text" wire:model="form.name" id="url" placeholder="Masukkan Nama"  class="text-field-base peer grow">
                            </div>
                            @error('form.name')
                            <span class="text-error text-xs " >{{ $message }}</span> 
                            @enderror
                        </label>
                    </div>

                    <div class="mt-3">
                        <label for="description">
                            <span class="block label text-gray-600 text-[12px] mb-1" >Deskripsi</span>
                            <div class="text-field flex w-1/2" >
                                <input type="text" wire:model="form.intro" id="description" placeholder="Masukkan Nama"  class="text-field-base peer grow">
                            </div>
                        </label>
                    </div>
                    
                </x-collapse>

                <div class="h-4" ></div>

                <x-collapse
                    title="Upload berkas"
                    :expand="$expand['file']"
                >

                    <x-slot:button>
                        <button type="button" wire:click="handle_toggle_collapse('file')" class="{{ !$expand['url'] ? '-rotate-90' : 'rotate-0'}} transition-all" >
                            <img src="{{ asset('assets/icons/arrow_carret_down.svg') }}" alt="">
                        </button>
                    </x-slot:button>

                    <div class="gap-x-5">
                        <label for="url">
                            <span class="block label text-gray-600 text-[12px] mb-1" >Nama</span>
                            <div class="text-field flex w-1/2" >
                                <input type="text" wire:model="form.name" id="url" placeholder="Masukkan Nama"  class="text-field-base peer grow">
                            </div>
                        </label>
                    </div>

                    <div class="mt-3">
                        <label for="description">
                            <span class="block label text-gray-600 text-[12px] mb-1" >Deskripsi</span>
                            <div class="text-field flex w-1/2" >
                                <input type="text" wire:model="form.intro" id="description" placeholder="Masukkan Nama"  class="text-field-base peer grow">
                            </div>
                        </label>
                    </div>

                </x-collapse>

                <div class="h-4" ></div>

                <x-collapse
                    title="Waktu Pengerjaan"
                    :expand="$expand['time']"
                >

                    <x-slot:button>
                        <button type="button" wire:click="handle_toggle_collapse('time')" class="{{ !$expand['url'] ? '-rotate-90' : 'rotate-0'}} transition-all" >
                            <img src="{{ asset('assets/icons/arrow_carret_down.svg') }}" alt="">
                        </button>
                    </x-slot:button>

                    <div class="flex gap-x-6">
                        <div class="flex-1" >
                            <span class="block label text-gray-600 text-[12px] mb-3" >Pilih Berdasarkan</span>
                            <div class="flex">
                                <label for="date" class="flex items-center mb-4" >
                                    <input value="date" wire:model.live="form.duedatetype" name="duedate" id="date" type="radio" class="radio">
                                    <span class="font-medium text-sm text-grey-700 ml-2" >Tanggal</span>
                                </label>
                        
                                <label for="time" class="flex items-center mb-4 ml-20" >
                                    <input value="time" wire:model.live="form.duedatetype" name="duedate" id="time" type="radio" class="radio">
                                    <span class="font-medium text-sm text-grey-700 ml-2" >Waktu/Jam</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex-1" ></div>
                        <div class="flex-1"></div>
                    </div>

                    <div class="flex gap-x-5 mt-3">
                        <div class="flex-1" >
                            <label for="start_date">
                                <span class="block label text-gray-600 text-[12px] mb-1" >Tanggal dimulai</span>
                                <div class="text-field flex" >
                                    <input @if($form->duedatetype == 'time') disabled @endif type="date" wire:model="form.duedate_start_date" id="start_date"  class="text-field-base grow peer">
                                    <x-icons.date class="fill-grey-500 peer-focus:fill-primary" />
                                </div>
                            </label>
                        </div>
                        <div class="flex-1" >
                            <label for="start_time">
                                <span class="block label text-gray-600 text-[12px] mb-1" >Waktu dimulai</span>
                                <div class="text-field flex" >
                                    <input type="time" id="maxfilesubmissions" wire:model="form.duedate_start_time"  class="text-field-base grow peer">
                                    <x-icons.clock class="fill-grey-500 peer-focus:fill-primary" />
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex gap-x-5 mt-3">
                        <div class="flex-1" >
                            <label for="end_date">
                                <span class="block label text-gray-600 text-[12px] mb-1" >Tanggal akhir</span>
                                <div class="text-field flex" >
                                    <input wire:model="form.duedate_end_date" type="date" @if($form->duedatetype == 'time') disabled @endif id="end_date"  class="text-field-base grow peer">
                                    <x-icons.date class="fill-grey-500 peer-focus:fill-primary" />
                                </div>
                            </label>
                        </div>
                        <div class="flex-1" >
                            <label for="end_time">
                                <span class="block label text-gray-600 text-[12px] mb-1" >Waktu akhir</span>
                                <div class="text-field flex" >
                                    <input wire:model="form.duedate_end_time" type="time"  id="end_time"  class="text-field-base grow peer">
                                    <x-icons.clock class="fill-grey-500 peer-focus:fill-primary" />
                                </div>
                            </label>
                        </div>
                    </div>

                </x-collapse>

                <div class="h-4" ></div>

                <x-collapse
                    title="Jenis Pengiriman"
                    :expand="$expand['submissiontype']"
                >

                    <x-slot:button>
                        <button type="button" wire:click="handle_toggle_collapse('submissiontype')" class="{{ !$expand['url'] ? '-rotate-90' : 'rotate-0'}} transition-all" >
                            <img src="{{ asset('assets/icons/arrow_carret_down.svg') }}" alt="">
                        </button>
                    </x-slot:button>

                    <div class="flex gap-x-5">
                        <div class="flex-1" >
                            <span class="block label text-gray-600 text-[12px] mb-3" >Apakah anda ingin mengulang sesi</span>
                            <div class="flex">
                                <label for="kehadiran" class="flex items-center mb-4" >
                                    <input wire:model.live="form.submissiontype" value="onlinetext" name="activity" id="kehadiran" type="radio" class="radio">
                                    <span class="font-medium text-sm text-grey-700 ml-2" >Text Daring</span>
                                </label>
                        
                                <label for="berkas" class="flex items-center mb-4 ml-20" >
                                    <input wire:model.live="form.submissiontype" value="file" name="activity" id="berkas" type="radio" class="radio">
                                    <span class="font-medium text-sm text-grey-700 ml-2" >File</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex-1" ></div>
                        <div class="flex-1"></div>
                    </div>

                    @if ($form->submissiontype == 'onlinetext')
                    <label for="word_length">
                        <span class="block label text-gray-600 text-[12px] mb-1" >Jumlah maksimum kata</span>
                        <div class="text-field flex w-[270px]" >
                            <input type="number" wire:model="form.wordlimit" id="word_length" placeholder="Masukkan jumlah kata"  class="text-field-base peer grow">
                        </div>
                    </label>
                    @else
                    <div class="flex gap-x-5 mt-3">
                        <div class="flex-1" >
                            <label for="end_date">
                                <span class="block label text-gray-600 text-[12px] mb-1" >Tipe File</span>
                                <div class="text-field flex" >
                                    <select wire:model="form.file_types" class="text-field-base grow peer" >
                                        <option value="">Pilih Tipe File</option>
                                        <option value="document" >Document(.pdf, .docs, .pptx, dll)</option>
                                        <option value="image" >Gambar(.jpg, .png, .jpeg, dll)</option>
                                        <option value="*" >Semua Tipe File</option>
                                    </select>
                                </div>
                            </label>
                        </div>
                        <div class="flex-1" >
                            <label for="end_time">
                                <span class="block label text-gray-600 text-[12px] mb-1" >Jumlah Berkas</span>
                                <div class="text-field flex" >
                                    <input type="number" wire:model="form.maxfilesubmissions"  id="maxfilesubmissions"  class="text-field-base grow peer">
                                </div>
                            </label>
                        </div>
                        <div class="flex-1" >
                            <label for="end_time">
                                <span class="block label text-gray-600 text-[12px] mb-1" >Ukuran Maksimum Berkas</span>
                                <div class="text-field flex" >
                                    <select wire:model="form.max_file_size" class="text-field-base grow peer" >
                                        <option value="3145728" >Batas Maks (3 Mb)</option>
                                        <option value="2097152" >2 Mb</option>
                                        <option value="1048576" >1 Mb</option>
                                        <option value="512000" >500 kb</option>
                                        <option value="204800" >200 kb</option>
                                    </select>
                                </div>
                            </label>
                        </div>
                    </div>
                    @endif

                </x-collapse>

                <div class="h-4" ></div>

                <x-collapse
                    title="Pengingat Aktivitas"
                    :expand="$expand['activityremember']"
                >

                    <x-slot:button>
                        <button type="button" wire:click="handle_toggle_collapse('activityremember')" class="{{ !$expand['url'] ? '-rotate-90' : 'rotate-0'}} transition-all" >
                            <img src="{{ asset('assets/icons/arrow_carret_down.svg') }}" alt="">
                        </button>
                    </x-slot:button>

                    <label for="remember">
                        <span class="block label text-gray-600 text-[12px] mb-1" >Pengingat aktivitas</span>
                        <div class="text-field flex w-1/2" >
                            <input type="date" bind:value={formData.assignment_title} id="remember"  class="text-field-base grow peer">
                            <x-icons.date class="fill-grey-500 peer-focus:fill-primary" />
                        </div>
                    </label>

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