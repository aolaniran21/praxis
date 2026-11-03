<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-gray-900">{{ __('Course registration') }}</h1>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800" role="alert">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! $semester)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-900">
                    No current academic semester is available.
                </div>
            @else
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="mb-1 text-sm text-gray-600">{{ $student->user->name }} - {{ $student->student_number }} - {{ $student->program->name }}</p>
                            <h2 class="text-lg font-semibold text-gray-900">
                                {{ $semester->academicSession->name }} - {{ ucfirst($semester->type->value) }} semester
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Select courses for your program and level. The semester limit is
                                {{ $semester->max_credit_units }} credit units.
                            </p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $semester->registrationIsOpen() ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                            {{ $semester->registrationIsOpen() ? 'Registration open' : 'Registration closed' }}
                        </span>
                        <button type="button" onclick="window.print()"
                            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 print:hidden">
                            Print registration slip
                        </button>
                    </div>

                    @if ($courses->isEmpty())
                        <p class="mt-6 text-sm text-gray-600">There are no courses available for your level in this semester.</p>
                    @else
                        <form method="POST" action="{{ route('student.registrations.store') }}" class="mt-6">
                            @csrf
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                        <tr>
                                            <th scope="col" class="px-4 py-3">Select</th>
                                            <th scope="col" class="px-4 py-3">Code</th>
                                            <th scope="col" class="px-4 py-3">Course</th>
                                            <th scope="col" class="px-4 py-3">Credits</th>
                                            <th scope="col" class="px-4 py-3">Prerequisites</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($courses as $course)
                                            @php($alreadyRegistered = $registrations->contains(fn ($registration) => $registration->course_id === $course->id && $registration->status === 'registered'))
                                            <tr>
                                                <td class="px-4 py-3">
                                                    <input type="checkbox" name="courses[]" value="{{ $course->id }}"
                                                        @checked(in_array($course->id, old('courses', [])))
                                                        @disabled($alreadyRegistered || ! $semester->registrationIsOpen())
                                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                    @if ($alreadyRegistered)
                                                        <span class="sr-only">Already registered</span>
                                                    @endif
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">{{ $course->code }}</td>
                                                <td class="px-4 py-3 text-gray-700">{{ $course->title }}</td>
                                                <td class="px-4 py-3 text-gray-700">{{ $course->credit_units }}</td>
                                                <td class="px-4 py-3 text-gray-600">
                                                    {{ $course->prerequisites->pluck('code')->join(', ') ?: 'None' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" @disabled(! $semester->registrationIsOpen())
                                class="mt-5 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                                Register selected courses
                            </button>
                        </form>
                    @endif
                </section>

                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Your registrations</h2>
                    @php($activeRegistrations = $registrations->where('status', 'registered'))
                    @if ($activeRegistrations->isEmpty())
                        <p class="mt-3 text-sm text-gray-600">You have no active course registrations for this semester.</p>
                    @else
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th scope="col" class="px-4 py-3">Course</th>
                                        <th scope="col" class="px-4 py-3">Credits</th>
                                        <th scope="col" class="px-4 py-3">Status</th>
                                        <th scope="col" class="px-4 py-3 print:hidden">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($registrations as $registration)
                                        <tr>
                                            <td class="px-4 py-3"><strong>{{ $registration->course->code }}</strong> - {{ $registration->course->title }}</td>
                                            <td class="px-4 py-3">{{ $registration->course->credit_units }}</td>
                                            <td class="px-4 py-3">{{ ucfirst($registration->status) }}</td>
                                            <td class="px-4 py-3 print:hidden">
                                                @if ($registration->status === 'registered' && $semester->registrationIsOpen() && ! $registration->result)
                                                    <form method="POST" action="{{ route('student.registrations.drop', $registration) }}"
                                                        onsubmit="return confirm('Drop this course? You may add it again before registration closes.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="font-medium text-red-700 hover:text-red-900">Drop course</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-4 text-sm font-semibold text-gray-900">
                            Total active credits: {{ $activeRegistrations->sum(fn ($registration) => $registration->course->credit_units) }}
                        </p>
                    @endif
                    <p class="mt-2 text-xs text-gray-500">Courses can be dropped or re-added until the registration deadline, unless result entry has started.</p>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
