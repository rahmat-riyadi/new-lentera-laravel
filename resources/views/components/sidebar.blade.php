<?php 

use function Livewire\Volt\{state, mount};
use App\Models\Enrol;

state([
  'courses' => [],
  'expand' => true
]);

mount(function() {

  $this->expand = Route::currentRouteName() == 'course';

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
    {{-- { !$isOpen ? '-translate-x-full' : 'translate-x-0'} --}}
    md:translate-x-0
    duration-300
    transition-all
  "
>
  <img
    src="{{ asset('assets/images/sidebar_logo.svg') }}"
    class="mb-10 ml-3 object-center w-[225px]"
    alt=""
  />
  <div class="px-3 grow flex flex-col pb-3">
    <a 
        class="flex items-center {{ Route::currentRouteName() == 'home' ? 'bg-primary-light' : 'bg-white' }} w-full py-[15px] pl-4 rounded-xl transition-all group"
        href="/"
        wire:navigate
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
      class="flex items-center {{ $expand ? 'bg-primary-light' : 'bg-white' }} w-full py-[13px] pl-4 rounded-xl transition-all my-2 group"
      wire:click="$toggle('expand')"
    >
        <x-icons.teacher
            class="{{ $expand ? 'fill-primary-dark' : 'fill-grey-600' }} w-[22px] group-hover:fill-primary-dark"
        />
      <span
        class="{{  $expand ? 'text-primary-dark font-semibold' : 'font-medium text-grey-600' }} ml-3 text-sm group-hover:text-primary-dark group-hover:font-semibold"
        >Matakuliah</span
      >
      <x-icons.arrow
        class="{{ $expand ? 'fill-grey-600' : 'fill-primary-dark rotate-[-180deg]' }} ml-auto mr-3 transition-all duration-[.4s]"
      />
    </button>
    <div
      class="relative {{ $expand ? 'grow mb-2' : 'grow-0' }} overflow-y-auto no-scrollbar transition-all duration-[.4s]"
    >
      <div class="transition-all absolute w-full">
        @foreach ($courses as $item)
        <a wire:navigate href="/course/{{ $item->shortname }}" class="flex items-center w-full py-3 pl-6 rounded-xl transition-all mb-1 pr-2 group">
          <x-icons.dot
            class="{{ Route::currentRouteName() == 'course' ? 'fill-primary-dark' : 'fill-grey-600' }} group-hover:fill-primary-dark"
          />
          <span
            class="{{ Route::currentRouteName() == 'course' ? 'text-primary-dark font-semibold' : 'font-medium text-grey-600' }} ml-5 text-sm font-medium text-left group-hover:text-primary-dark group-hover:font-semibold"
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
        class="{{ Route::currentRouteName() == 'calendar' ? 'fill-primary-dark' : 'fill-grey-600'}} w-6 group-hover:fill-primary-dark"
    />
      <span
        class="{{ Route::currentRouteName() == 'calendar' ? 'text-primary-dark font-semibold' : 'font-medium text-grey-600' }} ml-3 text-sm group-hover:text-primary-dark group-hover:font-semibold"
        >Kalender </span
      >
    </a>
  </div>
</div>
@endvolt