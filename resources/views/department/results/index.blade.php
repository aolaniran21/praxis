<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-gray-900">{{ __('Department result review') }}</h1>
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

            @forelse ($results as $result)
                @php($registration = $result->courseRegistration)
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold text-gray-900">
                                {{ $registration->course->code }} - {{ $registration->course->title }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ $registration->student->user->name }} ({{ $registration->student->student_number }}) -
                                {{ $registration->semester->academicSession->name }}
                            </p>
                        </div>
                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-800">
                            {{ ucfirst($result->status->value) }}
                        </span>
                    </div>
                    <p class="mt-4 text-sm text-gray-700">
                        CA {{ $result->ca_score }}/30 - Exam {{ $result->exam_score }}/70 -
                        Total {{ $result->total_score }}/100 - Grade {{ $result->grade }}
                    </p>

                    @if ($result->status->value === 'submitted')
                        <div class="mt-4 flex flex-wrap items-start gap-3">
                            <form method="POST" action="{{ route('department.results.approve', $result) }}">
                                @csrf
                                <button type="submit" class="rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">
                                    Approve
                                </button>
                            </form>
                            <form method="POST" action="{{ route('department.results.reject', $result) }}" class="flex flex-1 flex-wrap gap-2">
                                @csrf
                                <label for="feedback-{{ $result->id }}" class="sr-only">Rejection feedback</label>
                                <textarea id="feedback-{{ $result->id }}" name="feedback" rows="2" required minlength="5" maxlength="2000"
                                    placeholder="Explain what needs correction"
                                    class="min-w-64 flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                <button type="submit" class="rounded-md border border-red-600 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                                    Reject with feedback
                                </button>
                            </form>
                        </div>
                    @elseif ($result->status->value === 'approved')
                        <form method="POST" action="{{ route('department.results.publish', $result) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                Publish to student
                            </button>
                        </form>
                    @elseif ($result->status->value === 'published')
                        <details class="mt-4 border-t border-gray-100 pt-3">
                            <summary class="cursor-pointer text-sm font-medium text-red-700">Request audited correction</summary>
                            <form method="POST" action="{{ route('department.results.correction', $result) }}" class="mt-3 space-y-2">
                                @csrf
                                <label for="reason-{{ $result->id }}" class="block text-sm text-gray-700">
                                    Explain why this published result must be corrected. The current score is retained in audit history.
                                </label>
                                <textarea id="reason-{{ $result->id }}" name="reason" rows="2" required minlength="10" maxlength="2000"
                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm"></textarea>
                                <button type="submit" class="rounded-md border border-red-600 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                                    Reopen for correction
                                </button>
                            </form>
                        </details>
                    @endif

                    @if ($result->auditLogs->isNotEmpty())
                        <details class="mt-4 border-t border-gray-100 pt-3">
                            <summary class="cursor-pointer text-sm font-medium text-gray-700">Audit history</summary>
                            <ul class="mt-2 space-y-1 text-xs text-gray-600">
                                @foreach ($result->auditLogs as $audit)
                                    <li>
                                        {{ $audit->created_at->format('Y-m-d H:i') }} - {{ ucfirst(str_replace('_', ' ', $audit->action)) }}
                                        - {{ $audit->actor?->name ?? 'Former user' }}
                                        @if ($audit->feedback) - {{ $audit->feedback }} @endif
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </article>
            @empty
                <div class="rounded-xl border border-gray-200 bg-white p-8 text-center text-gray-600">
                    No results are awaiting review or publication.
                </div>
            @endforelse
            <div>{{ $results->links() }}</div>
        </div>
    </div>
</x-app-layout>
