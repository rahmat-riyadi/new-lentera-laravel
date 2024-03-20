<?php 

use function Livewire\Volt\{state, mount};
use App\Models\Enrol;

state([
  'courses' => [],
]);

mount(function() {

  $enrolid = auth()->user()->enrolments->pluck('enrolid');
  $enrol = Enrol::whereIn('id', $enrolid)->get();
  $course = $enrol->map(function ($e){
      return $e->course;
  });

  $this->courses = $course;

});

?>

@volt
<div
  x-data="{
    expand: '{{ Route::currentRouteName() }}' == 'course',
  }"
  class="
    h-full
    bg-white
    pt-6
    min-w-[260px]
    absolute
    z-10
    md:static
    flex
    flex-col
    side-bar-shadow
    md:translate-x-0
    -translate-x-full
    duration-300
    transition-all
    ease-in-out
  "
  :class="{ 'translate-x-0' : $store.sidebar.show }"
>

  <img
    src="{{ asset('assets/images/sidebar_logo.svg') }}"
    class="mb-10 ml-3 object-center w-[225px]"
    alt=""
  />
  <div class="px-3 grow flex flex-col pb-3">
    <a 
      wire:navigate.hover
      class="flex items-center {{ Route::currentRouteName() == 'home' ? 'bg-primary-light' : 'bg-white' }} w-full py-[15px] pl-4 rounded-xl transition-all group"
      href="/"
    >
    <x-icons.home
        class="{{ Route::currentRouteName() == 'home' ? 'fill-primary-dark' : 'fill-grey-600' }} transition-all w-5 group-hover:fill-primary-dark"
    />
      <span
        class="{{ Route::currentRouteName() == 'home' ? 'text-primary-dark font-semibold' : 'font-medium text-grey-600' }} ml-3 text-sm group-hover:text-primary-dark group-hover:font-semibold"
        >Beranda</span
      >
    </a>
    <button
      :class="expand ? 'bg-primary-light' : 'bg-white'"
      class="flex items-center w-full py-[13px] pl-4 rounded-xl transition-all my-2 group"
      @click="expand = !expand"
    >
        <svg :class="expand ? 'fill-primary-dark' : 'fill-grey-600'" class=" w-[22px] group-hover:fill-primary-dark" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M16.8291 15.6402C17.4991 15.2002 18.3791 15.6802 18.3791 16.4802V17.7702C18.3791 19.0402 17.3891 20.4002 16.1991 20.8002L13.0091 21.8602C12.4491 22.0502 11.5391 22.0502 10.9891 21.8602L7.79914 20.8002C6.59914 20.4002 5.61914 19.0402 5.61914 17.7702V16.4702C5.61914 15.6802 6.49914 15.2002 7.15914 15.6302L9.21914 16.9702C10.0091 17.5002 11.0091 17.7602 12.0091 17.7602C13.0091 17.7602 14.0091 17.5002 14.7991 16.9702L16.8291 15.6402Z"/>
          <path d="M19.9795 6.46006L13.9895 2.53006C12.9095 1.82006 11.1295 1.82006 10.0495 2.53006L4.02953 6.46006C2.09953 7.71006 2.09953 10.5401 4.02953 11.8001L5.62953 12.8401L10.0495 15.7201C11.1295 16.4301 12.9095 16.4301 13.9895 15.7201L18.3795 12.8401L19.7495 11.9401V15.0001C19.7495 15.4101 20.0895 15.7501 20.4995 15.7501C20.9095 15.7501 21.2495 15.4101 21.2495 15.0001V10.0801C21.6495 8.79006 21.2395 7.29006 19.9795 6.46006Z"/>
        </svg>
      <span
        :class="expand ? 'text-primary-dark font-semibold' : 'font-medium text-grey-600'"
        class=" ml-3 text-sm group-hover:text-primary-dark group-hover:font-semibold"
        >Matakuliah</span
      >
      
      <svg :class="expand ? 'fill-grey-600' : 'fill-primary-dark rotate-[-180deg]' " class=" ml-auto mr-3 transition-all duration-[.4s]" width="14" height="6" viewBox="0 0 14 6" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M7 6C6.61685 6.00062 6.23733 5.93274 5.88312 5.80024C5.52891 5.66773 5.20697 5.47321 4.9357 5.22779L0.171495 0.906599C0.0616886 0.807004 0 0.671923 0 0.531074C0 0.390224 0.0616886 0.255143 0.171495 0.155548C0.281301 0.0559523 0.430231 0 0.585521 0C0.74081 0 0.88974 0.0559523 0.999546 0.155548L5.76375 4.47674C6.09177 4.77388 6.53641 4.94078 7 4.94078C7.46359 4.94078 7.90823 4.77388 8.23625 4.47674L13.0005 0.155548C13.1103 0.0559523 13.2592 0 13.4145 0C13.5698 0 13.7187 0.0559523 13.8285 0.155548C13.9383 0.255143 14 0.390224 14 0.531074C14 0.671923 13.9383 0.807004 13.8285 0.906599L9.0643 5.22779C8.79303 5.47321 8.47109 5.66773 8.11688 5.80024C7.76267 5.93274 7.38315 6.00062 7 6Z"/>
      </svg>
    </button>
    <div
      :class="expand ? 'grow mb-2' : 'grow-0' "
      class="relative overflow-y-auto no-scrollbar transition-all duration-[.4s]"
    >
      <div class="transition-all absolute w-full">
        @foreach ($courses as $item)
        <a wire:navigate.hover href="/course/{{ $item->shortname }}" class="flex items-center w-full py-3 pl-6 rounded-xl transition-all mb-1 pr-2 group">
          <x-icons.dot
            class="{{ str_contains(url()->current(), $item->shortname) ? 'fill-primary-dark' : 'fill-grey-600' }} group-hover:fill-primary-dark"
          />
          <span
            class="{{ str_contains(url()->current(), $item->shortname) ? 'text-primary-dark font-semibold' : 'font-medium text-grey-600' }} ml-5 text-sm font-medium text-left group-hover:text-primary-dark group-hover:font-semibold"
          >
            {{ $item->fullname }}
          </span>
        </a> 
        @endforeach
      </div>
    </div>
    <a
      class="flex items-center {{ Route::currentRouteName() == 'calendar' ? 'bg-primary-light' : 'bg-white' }} w-full py-[13px] pl-4 rounded-xl transition-all group"
      href="/calendar"
      wire:navigate
    >
    <x-icons.calendar
        class="{{ Route::currentRouteName() == 'calendar' ? 'fill-primary-dark' : 'fill-grey-600' }} w-6 group-hover:fill-primary-dark"
    />
      <span
        class="{{ Route::currentRouteName() == 'calendar' ? 'text-primary-dark font-semibold' : 'font-medium text-grey-600' }} ml-3 text-sm group-hover:text-primary-dark group-hover:font-semibold"
        >Kalender </span
      >
    </a>
  </div>

</div>
@endvolt