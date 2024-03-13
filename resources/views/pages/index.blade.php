<x-layouts.app>
    @volt
    <div class="flex flex-col md:flex-row h-full p-7 pb-0 gap-x-7 no-scrollbar grow overflow-y-auto" >
        <div class="order-2 md:order-1" >
            <div class="bg-white p-4 rounded-xl" >
                <p class="font-semibold text-lg text-[#121212] mb-4" >Mata Kuliah Baru diakses</p>
                <div class="grid gap-5 sx:grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3" >
                    <x-course-card
                        studyProgram="Teknik Informatika"
                        course="kursus "
                    />
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl mt-7" >
                <p class="font-semibold text-lg text-[#121212] mb-4" >Mata Kuliah</p>
                <div class="mb-4" >
                    @foreach (['hehe', 'hehe'] as $i => $item)
                    <button class="mr-1 btn-tabs {{ $item == 1 ? 'active' : ''}}" >{{ $item }}</button>
                    @endforeach
                </div>
                <div class="grid gap-4 sx:grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3" >
                    @foreach ([1,2,3] as $item)
                    <x-course-card
                        studyProgram="Teknik Informatika"
                        course="kursus "
                    />
                    @endforeach
                </div>
            </div>
            <div class="h-10" ></div>
        </div>
        <div class="md:w-[290px] order-1 md:order-2" >
            <div class="flex justify-between items-center mb-3">
                <p class="body-1 text-lg md:text-base font-semibold" >Pengumuman</p>
                <button class="text-sm text-primary-dark" >Lihat Semua</button>
            </div>
            <div class="bg-primary-light p-3 rounded-xl" >
                <p class="text-xs mb-1" >12 Maret 2023, 10.24</p>
                <p class="font-semibold leading-5 md:text-sm mb-3 md:mb-2" >Semua dosen harap segera memasukkan materi pada mata kuliah yang diajarkan</p>
                <p class="text-sm font-medium md:font-normal md:text-xs" >Dosen diharap memasukkan materi disetiap pertemuan sebelum perkuliahan berlangsung. Agar mahasiswa dapat melihat dan mempelajari terlebih dahulu.</p>
            </div>
            <p class="body-1 font-semibold mt-4 mb-3" >Aktivitas Akan Datang</p>
            <div class="bg-white py-10 rounded-xl mb-10" >
                <p class="text-center text-sm text-grey-500 font-normal" >Belum ada aktivitas</p>
            </div>
        </div>
    </div>
    @endvolt
</x-layouts.app>