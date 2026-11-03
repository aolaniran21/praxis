<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">
                {{ $courseAssignment->course->code }} - {{ $courseAssignment->course->title }}
            </h1>
            <p class="mt-1 text-sm text-gray-600">
                {{ $courseAssignment->semester->academicSession->name }} - {{ ucfirst($courseAssignment->semester->type->value) }} semester
            </p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800" role="alert">
                    <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @forelse ($registrations as $registration)
                @php($result = $registration->result)
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold text-gray-900">{{ $registration->student->user->name }}</h2>
                            <p class="text-sm text-gray-600">{{ $registration->student->student_number }}</p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                            {{ $result?->status?->value ?? 'No result' }}
                        </span>
                    </div>

                    @if (! $result || in_array($result->status->value, ['draft', 'rejected'], true))
                        <form method="POST" action="{{ route('lecturer.results.draft', $registration) }}"
                            class="mt-4 grid gap-4 sm:grid-cols-3">
                            @csrf
                            @method('PUT')
                            <div>
                                <label for="ca-{{ $registration->id }}" class="block text-sm font-medium text-gray-700">CA score (0-30)</label>
                                <input id="ca-{{ $registration->id }}" name="ca_score" type="number" min="0" max="30" required
                                    value="{{ old('ca_score', $result?->ca_score ?? '') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="exam-{{ $registration->id }}" class="block text-sm font-medium text-gray-700">Exam score (0-70)</label>
                                <input id="exam-{{ $registration->id }}" name="exam_score" type="number" min="0" max="70" required
                                    value="{{ old('exam_score', $result?->exam_score ?? '') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                    Save draft
                                </button>
                            </div>
                        </form>
                        @if ($result?->latest_feedback)
                            <p class="mt-3 rounded-md bg-amber-50 p-3 text-sm text-amber-900">
                                Department feedback: {{ $result->latest_feedback }}
                            </p>
                        @endif
                        @if ($result?->status?->value === 'draft')
                            <form method="POST" action="{{ route('lecturer.results.submit', $result) }}" class="mt-3">
                                @csrf
                                <button type="submit" class="rounded-md border border-indigo-600 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">
                                    Submit result
                                </button>
                            </form>
                        @endif
                    @else
                        <p class="mt-3 text-sm text-gray-700">
                            CA {{ $result->ca_score }}/30 - Exam {{ $result->exam_score }}/70 - Total {{ $result->total_score }}/100 -
                            Grade {{ $result->grade }}
                        </p>
                    @endif
                </article>
            @empty
                <div class="rounded-xl border border-gray-200 bg-white p-8 text-center text-gray-600">
                    No students are registered for this course in this semester.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
