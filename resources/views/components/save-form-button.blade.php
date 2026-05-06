@props(['status'])

<div class="flex items-center gap-4">
    <x-primary-button>{{ __('Save') }}</x-primary-button>

    @if (session('status') === $status)
        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
            class="text-sm text-green-600 dark:text-green-400">{{ __('Saved.') }}</p>
    @endif
</div>
