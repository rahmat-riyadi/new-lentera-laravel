<div 
    class="p-3 bg-white rounded w-[250px] fixed z-[999999] right-6 top-6 shadow-lg" 
    x-transition:enter="transition-all duration-300 ease-in-out "
    x-transition:enter-start="opacity-0 translate-x-full"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave="transition-all duration-300 ease-in-out "
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-full"
    x-show="$store.toast.show"
>
    <div class="flex" >
        <div style="height: 60px; width: 5px;" class="bg-primary rounded mr-4" ></div>
        <div class="flex flex-col" >
            <b style="color: #36B37E;" :class="$store.toast.type == 'success' ? 'text-primary' : 'text-[#FF5630]'"  class="mb-[3px] mt-[6px]" x-text="$store.toast.type == 'success' ? 'Berhasil' : 'Gagal'" ></b>
            <p style="color: #121212; font-size: 12px;" class="m-0 font-medium text-grey-600" x-text="$store.toast.message" ></p>
        </div>
    </div>
</div>