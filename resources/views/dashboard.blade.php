<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">{{ $roleLabel }}</p>
                <h1 class="text-2xl font-semibold text-gray-900">{{ __('Dashboard') }}</h1>
            </div>
            @if ($semester)
                <p class="text-sm text-gray-600">
                    {{ $semester->academicSession->name }} - {{ ucfirst($semester->type->value) }} semester
                </p>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <section aria-label="Summary" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($metrics as $label => $value)
                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $value }}</p>
                    </article>
                @endforeach
            </section>

            @if ($actions !== [])
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Quick actions') }}</h2>
                    <div class="mt-4 flex flex-wrap gap-3">
                        @foreach ($actions as $action)
                            <a href="{{ route($action['route']) }}"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                {{ $action['label'] }}
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
