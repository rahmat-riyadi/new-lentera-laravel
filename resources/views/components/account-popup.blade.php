@push('style')

<style>

    .wrapper {
        box-shadow: 0px 0px 12px 0px rgba(123, 123, 123, 0.10), 0px 2px 4px 0px rgba(16, 24, 40, 0.10);
    }

</style>
    
@endpush

<div class="{{ !$show ? 'translate-y-9 invisible -z-50 opacity-0' : 'opacity-100 translate-y-0 visible z-[9999]' }} wrapper absolute bg-white right-8 top-[60px] rounded-lg transition-all duration-300 ease-in-out" >
    <ul class="p-4" >
        <li class="w-40 text-sm mb-2 hover:bg-grey-200 px-2 py-1" ><a href="/">Pengaturan Akun</a></li>
        <li class="w-40 text-sm px-2 py-1 hover:bg-grey-200" >
            <a href="/logout">Log Out</a>
        </li>
    </ul>
</div>
