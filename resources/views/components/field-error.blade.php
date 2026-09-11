@props(['message'])

@if ($message)

<p
    {{ $attributes->merge([
            'class' => '
                mt-1.5 flex items-start gap-1.5
                text-sm font-medium text-red-600
                dark:text-red-400
                sm:text-base
            ',
        ]) }}
    role="alert">

    <i
        data-lucide="triangle-alert"
        class="mt-0.5 h-5 w-5 shrink-0"
        aria-hidden="true">
    </i>

    <span>
        {{ $message }}
    </span>

</p>

@endif