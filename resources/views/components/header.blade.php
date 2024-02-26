<?php 

use Function Livewire\Volt\{state};

state([
    'show_notif' => false,
    'show_account' => false
]);

?>

@volt
<div>
    <div class="flex pl-3 bg-white justify-end py-[9px] md:pr-8" style="box-shadow: 0px 1px 2px 0px rgba(16, 24, 40, 0.10);" >
        <button on:click={toggle} class="mr-auto block md:hidden" >
            <img class="select-none" src="{{ asset('assets/icons/menu.svg') }}" alt="">
        </button>
        <button>
            <x-icons.flag class="w-7 h-7" />
        </button>
        <button class="mx-3 px-3 hover:bg-grey-200 rounded-lg" >
            <x-icons.notification
                class="w-[22px] h-[22px]"
            />
        </button>
        <button class="mr-6" >
            <x-icons.chat
                class="w-[18px] h-[18px]"
            />
        </button>
        <button wire:click="$toggle('show_account')" class="flex items-center cursor-pointer" >
            <img src="{{ asset('assets/images/avatar.jpg') }}" class="w-[42px] h-[42px] rounded-full object-cover mr-3" alt="">
            <span class="hidden md:inline text-sm font-medium mr-4 capitalize" >{{ auth()->user()->firstname . " " . auth()->user()->lastname }}</span>
            <x-icons.arrow-down
                class="hidden md:block"
            />
        </button>
    </div>
    {{-- <x-notification-bar :show="$show_notif" /> --}}
    <x-account-popup :show="$show_account" />
</div>
@endvolt