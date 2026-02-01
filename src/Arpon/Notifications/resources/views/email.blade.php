@component('mail::message')
{{-- Header --}}
@if (isset($level))
    @switch($level)
        @case('success')
            @component('mail::header', ['color' => 'green'])
                {{ $greeting ?? 'Congratulations!' }}
            @endcomponent
            @break

        @case('error')
            @component('mail::header', ['color' => 'red'])
                {{ $greeting ?? 'Whoops!' }}
            @endcomponent
            @break

        @default
            @component('mail::header')
                {{ $greeting ?? 'Hello!' }}
            @endcomponent
    @endswitch
@else
    @component('mail::header')
        {{ $greeting ?? 'Hello!' }}
    @endcomponent
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
    {!! $line !!}

@endforeach

{{-- Action Button --}}
@if (isset($actionText) && isset($actionUrl))
    @component('mail::button', ['url' => $actionUrl])
        {{ $actionText }}
    @endcomponent
@endif

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
    {!! $line !!}

@endforeach

{{-- Salutation --}}
@if (isset($salutation))
    {!! $salutation !!}
@else
    Regards,<br>{{ config('app.name') }}
@endif

@endcomponent
