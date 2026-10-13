{{--
    Iconiță SVG desenată în linie, fără nicio cerere de rețea și fără JavaScript.

    Dimensiunea implicită e 1em, ca iconița să crească odată cu textul din jur —
    la fel ca emoji-ul pe care îl înlocuiește, deci clasele existente
    (`text-lg`, `text-2xl`) continuă să funcționeze.

    Se numește `ebac-icon`, nu `icon`: `<x-icon>` e deja înregistrat de
    blade-ui-kit/blade-icons, o dependență a Filament. Componentul lui de clasă
    câștigă în fața celui anonim de aici, iar rezultatul era un 500 pe paginile
    publice („Svg by name «target» from set «default» not found").

    Fără `label`, iconița e decorativă și e ascunsă de la cititoarele de ecran:
    în aproape toate locurile stă lângă textul care spune deja același lucru,
    iar o etichetă în plus ar fi citită de două ori.
--}}
@props([
    'name',
    'label' => null,
    'stroke' => 2,
])

@php($body = \App\Support\Icons::body($name))

@if ($body)
    <svg
        {{ $attributes->class('inline-block shrink-0 align-[-0.125em]') }}
        width="1em"
        height="1em"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="{{ $stroke }}"
        stroke-linecap="round"
        stroke-linejoin="round"
        @if ($label)
            role="img" aria-label="{{ $label }}"
        @else
            aria-hidden="true" focusable="false"
        @endif
    >{!! $body !!}</svg>
@endif
