<div {{ $attributes->merge([ 'class' => 'bg-white py-4 px-6 rounded-lg' ])->filter(fn($val, $key) => $key != 'x-show') }} >
    <div class="flex mb-2">
        <button @click="toggle()" type="button" :class="{{ 'expand' }} ? 'rotate-0' : '-rotate-90'  " class="transition-all">
            <img src="{{ asset('assets/icons/arrow_carret_down.svg') }}" alt="">
        </button>
        <p class="subtitle-1 text-base ml-2 " >{{ $title ?? '' }}</p>
    </div>
    <div  {{ $attributes->merge([ 'class' => 'ml-5' ])->filter(fn($val, $key) => $key != 'x-data') }} x-collapse.duration.800ms>
        {{ $slot }}
    </div>
</div>