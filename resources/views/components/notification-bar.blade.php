@push('style')

<style>
    .wrapper {
        box-shadow: 0px 0px 12px 0px rgba(123, 123, 123, 0.10), 0px 2px 4px 0px rgba(16, 24, 40, 0.10);
    }
</style>
    
@endpush

<div 
    class="
        {{ $show ? 'translate-y-9 invisible -z-50 opacity-0' : 'opacity-100 translate-y-0 visible z-[9999]' }} 
        wrapper 
        absolute 
        bg-white 
        rounded-xl 
        overflow-hidden 
        transition-all 
        duration-300
        ease-in-out
        bottom-3
        top-16
        inset-x-3
        md:bottom-[unset]
        md:right-80 
        md:top-[50px] 
        md:w-[420px] 
        md:inset-x-[unset]
        " 
    >
    <div class="flex items-center justify-between px-6 py-4 border-b border-grey-300" >
        <span class="font-semibold text-base" >Notifikasi</span>
        <button >
            <x-icons.union
                class="fill-grey-500 w-5 h-5"
            />
        </button>
    </div>
    <div >
        <p class="text-[13px] text-left mx-6 my-3" >Terbaru(1)</p>
        <ul  >
            <li class="flex items-center px-6 py-6 bg-grey-100" >
                <img src="{{ asset('assets/icons/kehadiran.svg') }}" class="w-8 mr-4" alt="">
                <div class="text-left" >
                    <p class="text-grey-500 text-[11.5px]" >17 Maret 2023, 09.00</p>
                    <p class="text-grey-700 mb-[6px] text-sm mt-2 font-medium" >Aktivitas Kehadiran segera dimulai</p>
                    <p class="text-grey-700 text-xs font-normal" >Pemgrograman Web 1 - Kelas A</p>
                </div>
            </li>
            <li class="flex items-center px-6 py-6 hover:bg-grey-100" >
                <img src="{{ asset('assets/icons/penugasan.svg') }}" class="w-8 mr-4" alt="">
                <div class="text-left" >
                    <p class="text-grey-500 text-[11.5px]" >17 Maret 2023, 09.00</p>
                    <p class="text-grey-700 mb-[6px] text-sm mt-2 font-medium" >Silahkan Melakukan Penilaian Tugas</p>
                    <p class="text-grey-700 text-xs font-normal" >Pemgrograman Web 1 - Kelas A</p>
                </div>
            </li>
            <li class="flex items-center px-6 py-6 hover:bg-grey-100" >
                <img src="{{ asset('assets/icons/kuis.svg') }}" class="w-8 mr-4" alt="">
                <div class="text-left" >
                    <p class="text-grey-500 text-[11.5px]" >17 Maret 2023, 09.00</p>
                    <p class="text-grey-700 mb-[6px] text-sm mt-2 font-medium" >Silahkan Melakukan Penilaian Kuis</p>
                    <p class="text-grey-700 text-xs font-normal" >Pemgrograman Web 1 - Kelas A</p>
                </div>
            </li>
        </ul>
    </div>
    <div class="flex items-center px-6 py-4 bg-grey-100" >
        <a href="/" class="text-primary flex items-center font-medium " >Lihat Selengkapnya <x-icons.arrow class="fill-primary -rotate-90 ml-2" /> </a>
    </div>
</div>