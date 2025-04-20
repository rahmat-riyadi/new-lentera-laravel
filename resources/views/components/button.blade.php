@php
    $sizes = [
        'medium' => 'btn-medium',
        'large' => 'btn-large'
    ];

    $variants = [
        'filled' => 'btn-primary',
        'outlined' => 'btn-outlined',
        'light' => 'btn-light'
    ];

    $class = $variants[$variant ?? 'filled'] . ' ' . $sizes[$size ?? 'medium'] . ' flex';
    $disabled = $loading ?? false;
    $type = $type ?? 'button';
@endphp

<button {{ $attributes->merge(['class' => $class, 'disabled' => $disabled, 'type' => $type]) }}>
    {{ $slot }}
</button>