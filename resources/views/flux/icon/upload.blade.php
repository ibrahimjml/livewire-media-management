@props([
    'variant' => 'outline',
])

@php
$classes = Flux::classes('shrink-0')
    ->add(match ($variant) {
       'outline' => 'w-5 h-5',
        'solid'   => 'w-5 h-5',
        'mini'    => 'w-4 h-4',
        'micro'   => 'w-3 h-3',

    });
@endphp

<svg
    {{ $attributes->class($classes) }}
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
>
    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
    <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path>
    <path d="M7 9l5 -5l5 5"></path>
    <path d="M12 4l0 12"></path>
</svg>
