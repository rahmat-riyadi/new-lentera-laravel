<div 
    class="fixed bg-transparent z-[1001] inset-0 flex" 
    x-transition:enter="transition-all duration-300 ease-in-out "
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-all duration-300 ease-in-out "
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-show="{{ $show }}"
>
    <div class="rounded-lg m-auto bg-white flex flex-col items-center px-14 py-9" >
        @if($type != 'success')
        <img src="{{ asset('assets/icons/alert_warning.svg') }}" />
        @else
        <img src="{{ asset('assets/icons/alert_success.svg') }}" />
        @endif
        <p class="subtitle-2 mt-4 mb-2" >{{ $title ?? '' }}</p>
        <p class="body-2 text-gray-500 mb-4" >{{ $message ?? '' }}</p>
        <div class="flex justify-center gap-x-4" >
            <x-button variant="light" @click="{{ $onCancel ?? '' }}" >
                {{ $cancelText ?? 'Batal' }}
            </x-button>
            <x-button @click="{{ $onOk ?? '' }}" >
                <span class="relative" >
                    <span class="absolute transition-all inset-0 text-center flex justify-center items-center" :class="{{ $loading ?? 'false' }} ? 'opacity-100' : 'opacity-0'" >
                        <x-spinner class="fill-white w-[22px]" />
                    </span>
                    <span :class="!{{ $loading ?? 'false' }} ? 'opacity-100' : 'opacity-0'" class="transition-all " >
                        {{ $okText ?? 'Simpan' }}
                    </span>
                </span>
            </x-button>
        </div>
    </div>
</div>

<div 
    class="inset-0 bg-black opacity-50 z-[1000] fixed" 
    x-transition:enter="transition-all duration-300 ease-in-out "
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-all duration-300 ease-in-out "
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-show="{{ $show }}"
>
</div>