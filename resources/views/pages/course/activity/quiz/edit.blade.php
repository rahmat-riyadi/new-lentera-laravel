<?php

use function Livewire\Volt\{state, mount, on, form};
use App\Livewire\Forms\Activity\QuizForm;
use App\Models\{
    Course,
    CourseSection,
    Quiz,
};

state(['course', 'section']);
form(QuizForm::class);
mount(function (Course $course,CourseSection $section, Quiz $quiz){
    $this->course = $course;
    $this->section = $section;
    $this->form->setModel($course);
    $this->form->setSection($section->section);
    $this->form->setInstance($quiz);
});

$handle_change_due_date_type = function ($e){
    Log::info($e);

    if($e == 'time'){
        $this->form->fill([
            'start_date' => \Carbon\Carbon::now()->format('Y-m-d'),
            'due_date' => \Carbon\Carbon::now()->format('Y-m-d'),
        ]);
    }

};

$submit = function (){

    $this->form->validate();
    
    try {
        $this->form->update();
        $this->redirect('/course/'.$this->course->shortname, navigate: true);
    } catch (\Throwable $th) {
        // throw $th;
        Log::info($th->getMessage());
    }
}

?>

<x-layouts.app>
    @volt
    <div x-data="" class="h-full overflow-y-auto relative">
        

        <div class="bg-white course-page-header px-8 py-8 font-main flex flex-col" >
            <x-back-button @click="$store.alert.cancel = true" path="javascript:;" />
            <p class="text-sm text-[#656A7B] font-[400] flex items-center my-5" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> {{ $course->fullname }} <span class="mx-2 text-[9px]" > >> </span>  <span class="text-[#121212]" >Edit Quiz - {{ $section->name }}</span></p>
            <div class="flex  justify-center">
                <h1 class="mr-auto text-[#121212] text-xl font-semibold" >Edit Quiz - {{ $section->name }}</h1>
                <x-button
                    @click="Livewire.navigate(`/teacher/quiz/{{ $form->quiz->id }}/questions/create`)"
                >
                    Pengaturan Soal
                </x-button>
            </div>
        </div>

        <form wire:submit="submit">
            <div class="px-8 pt-8 pb-10 transition-all duration-300 space-y-4" >
                <x-collapse
                    title="Umum"
                    x-data="collapse"
                    x-show="expand"
                >
                    <div class="grid grid-cols-2 gap-x-7">
                        <label for="urlname" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Nama</span>
                            <input wire:model="form.name" type="text" id="urlname" placeholder="Masukkan Nama"  class="text-field">
                            @error('form.name')
                            <span class="text-error mt-3 text-sm" >{{ $message ?? 's' }}</span>
                            @enderror
                        </label>
                    </div>
                    <div class="mt-3">
                        <label for="description">
                            <span class="block label text-gray-600 text-[12px] mb-1" >Deskripsi</span>
                            <div wire:ignore >
                                <textarea>{{ $form->description }}</textarea>
                            </div>
                            <input type="hidden" name="intro" />
                            @error('form.description')
                                <span class="text-error mt-3 text-sm" >{{ $message }}</span>
                            @enderror
                        </label>
                    </div>
                </x-collapse>
    
                <x-collapse
                    title="Waktu Pengerjaan"
                    x-data="collapse"
                    x-show="expand"
                >
                    <div>
                        <span class="block label text-gray-600 mb-4 mt-3" >Pilih Berdasarkan</span>
                        <div class="grid grid-cols-4" >
                            <label for="date" class="flex items-center mb-4" >
                                <input wire:change="handle_change_due_date_type($event.target.value)" wire:model="form.due_date_type" value="date" name="duedatetype" id="date" type="radio" class="radio">
                                <span class="font-medium text-sm text-grey-700 ml-2" >Tanggal</span>
                            </label>
                            <label for="time" class="flex items-center mb-4" >
                                <input wire:change="handle_change_due_date_type($event.target.value)" wire:model="form.due_date_type" value="time" name="duedatetype" id="time" type="radio" class="radio">
                                <span class="font-medium text-sm text-grey-700 ml-2" >Waktu/Jam</span>
                            </label>
                        </div>
                        @error('form.due_date_type')
                            <span class="text-error text-sm mb-9" >{{ $message }}</span>
                        @enderror
                    </div>
                    <div
                        class="grid grid-cols-2 gap-x-7 gap-y-3"
                    >
                        <label for="stardate" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Tanggal Dimulai</span>
                            <input @readonly($form->due_date_type == 'time') wire:model="form.start_date" name="startdate" type="date" id="startdate" placeholder="Masukkan Nama"  class="ring-1 text-sm ring-gray-300 py-2 rounded-xl px-3 w-full bg-grey-100 focus-within:ring-primary focus-within:ring-2 transition-all box-border focus:outline-none placeholder:text-grey-400 placeholder:font-medium">
                        </label>
                        <label for="starttime" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Waktu</span>
                            <input wire:model="form.start_time" name="starttime" type="time" id="starttime" placeholder="Masukkan Nama"  class="text-field">
                        </label>
                        <label for="enddate" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Tanggal Berakhir</span>
                            <input @readonly($form->due_date_type == 'time') wire:model="form.due_date" name="enddate" type="date" id="enddate" placeholder="Masukkan Nama"  class="text-field">
                            @error('form.due_date')
                                <span class="text-error mt-3 text-sm" >{{ $message }}</span>
                            @enderror
                        </label>
                        <label for="endtime" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Waktu</span>
                            <input wire:model="form.due_time" name="endtime" type="time" id="endtime" placeholder="Masukkan Nama"  class="text-field">
                            @error('form.due_time')
                                <span class="text-error mt-3 text-sm" >{{ $message }}</span>
                            @enderror
                        </label>
                    </div>
                </x-collapse>
    
                <x-collapse
                    title="Nilai"
                    x-data="collapse"
                    x-show="expand"
                >
                    <div class="grid grid-cols-3 mb-4 gap-x-7" >
                        <label for="passgrade" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Nilai Untuk Lulus</span>
                            <input wire:model="form.pass_grade" name="passgrade" type="number" id="passgrade" placeholder="Masukkan Nama"  class="text-field">
                        </label>
                        <label for="passgrade" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Percobaan Menjawab</span>
                            <select wire:model="form.answer_attempt" name="attempts" class="text-field" >
                                <option value="" >-- Pilih Jumlah --</option>
                                @for ($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" >{{ $i }} Kali</option>
                                @endfor
                            </select>
                            <span class="peer-has-[:focus:invalid]:inline-block hidden text-error text-sm mt-1" >Nama harus diisi</span>
                        </label>
                    </div>
                </x-collapse>

                <x-collapse
                    title="Tentang Soal"
                    x-data="collapse"
                    x-show="expand"
                >
                    <div class="grid grid-cols-3" >
                        <div>
                            <span class="block label text-gray-600 mb-3 " >Apakah ingin mengacak soal</span>
                            <label for="shuffle" class="flex items-center" >
                                <input @checked($form->shuffle_questions == 1) wire:model="form.shuffle_questions" value="1" type="checkbox" name="shuffle" class="checkbox w-[18px] h-[18px]" id="file_t">
                                <span class="font-medium text-sm text-grey-700 ml-2" >Acak Soal</span>
                            </label>
                        </div>
                        <label for="perpage" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Tampilan Soal</span>
                            <select wire:model="form.question_show_number" class="text-field" >
                                <option value="" >-- Pilih Jumlah --</option>
                                @for ($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" >{{ $i }} Kali</option>
                                @endfor
                                <option>Tanpa Batas</option>
                            </select>
                        </label>
                    </div>
                </x-collapse>

                <x-collapse
                    title="Opsi Ulasan"
                    x-data="collapse"
                    x-show="expand"
                >
                    <div class="grid grid-cols-3" >
                        <div>
                            <span class="block label text-gray-600 mb-3 " >Tampilkan Nilai Ke Mahasiswa</span>
                            <label for="show_grade" class="flex items-center" >
                                <input @checked($form->show_grade == 1) wire:model="form.show_grade" value="1" type="checkbox" name="show_grade" class="checkbox w-[18px] h-[18px]" id="file_t">
                                <span class="font-medium text-sm text-grey-700 ml-2" >Ya Tampilkan</span>
                            </label>
                        </div>
                        <div>
                            <span class="block label text-gray-600 mb-3 " >Tampilkan Jawaban Ke Mahasiswa</span>
                            <label for="show_answer" class="flex items-center" >
                                <input @checked($form->show_answers == 1) wire:model="form.show_answers" value="1" type="checkbox" name="show_answer" class="checkbox w-[18px] h-[18px]" id="file_t">
                                <span class="font-medium text-sm text-grey-700 ml-2" >Ya Tampilkan</span>
                            </label>
                        </div>
                    </div>
                </x-collapse>
    
                <x-collapse
                    title="Pengingat Aktivitas"
                    x-data="collapse"
                    x-show="expand"
                >
                    <div
                        class="grid grid-cols-3 gap-x-7 gap-y-3"
                    >
                        <label for="remember" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Tanggal Pemberitahuan</span>
                            <input wire:model="form.activity_remember" name="" type="date" id="remember"  class="ring-1 text-sm ring-gray-300 py-2 rounded-xl px-3 w-full bg-grey-100 focus-within:ring-primary focus-within:ring-2 transition-all box-border focus:outline-none placeholder:text-grey-400 placeholder:font-medium">
                        </label>
                    </div>
                </x-collapse>
    
                <div class="flex justify-end gap-3 mt-4" >
                    <x-button type="submit" >
                        Submit
                    </x-button>
                    <x-button @click="$store.alert.cancel = true" variant="outlined" >
                        Batal
                    </x-button>
                </div>
            </div>
        </form>

        <x-alert
            show="$store.alert.cancel"
            onCancel="$store.alert.cancel = false"
            onOk="$wire.submit()"
            type="warning"
            title="Batal"
            message="Batalkan pembuatan aktivitas ?"
        />


    </div>

    @script
    <script>

        Alpine.store('alert', {
            cancel: false,
            save: false,
            loading: false
        })

        Alpine.data('collapse', (initialState = true) => ({
            expand: initialState,
            toggle() {
                this.expand = ! this.expand
            }
        }))

        tinymce.init({
            selector: 'textarea',
            plugins: 'anchor autolink charmap codesample emoticons link lists searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            setup: editor => {
                editor.on('change', e => {
                    document.querySelector('input[type=hidden]').value = tinymce.activeEditor.getContent()
                    $wire.$set('form.description', tinymce.activeEditor.getContent())
                })
            }
        });

        window.addEventListener("beforeunload", function(event) {
            event.preventDefault()
            event.returnValue = '';
        }, { capture: true });

    </script>
    @endscript
    @endvolt
</x-layouts.app>