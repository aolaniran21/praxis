<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold text-gray-900">{{ __('Published result reports') }}</h1>
            <a href="{{ route('reports.export', request()->query()) }}"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Export CSV
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('reports.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="academic_session_id" class="block text-sm font-medium text-gray-700">Academic session</label>
                        <select id="academic_session_id" name="academic_session_id" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">All sessions</option>
                            @foreach ($sessions as $session)
                                <option value="{{ $session->id }}" @selected(($filters['academic_session_id'] ?? null) == $session->id)>
                                    {{ $session->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="semester_id" class="block text-sm font-medium text-gray-700">Semester</label>
                        <select id="semester_id" name="semester_id" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">All semesters</option>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->id }}" @selected(($filters['semester_id'] ?? null) == $semester->id)>
                                    {{ $semester->academicSession->name }} - {{ ucfirst($semester->type->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="program_id" class="block text-sm font-medium text-gray-700">Program</label>
                        <select id="program_id" name="program_id" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">All programs</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}" @selected(($filters['program_id'] ?? null) == $program->id)>
                                    {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="level" class="block text-sm font-medium text-gray-700">Student level</label>
                        <select id="level" name="level" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">All levels</option>
                            @foreach ([100, 200, 300, 400, 500, 600, 700, 800, 900] as $level)
                                <option value="{{ $level }}" @selected(($filters['level'] ?? null) == $level)>{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="course_id" class="block text-sm font-medium text-gray-700">Course</label>
                        <select id="course_id" name="course_id" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">All courses</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}" @selected(($filters['course_id'] ?? null) == $course->id)>
                                    {{ $course->code }} - {{ $course->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Result status</label>
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300">
                            @foreach (\App\Enums\ResultStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>
                                    {{ ucfirst($status->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if (auth()->user()->hasRole('super-admin'))
                        <div>
                            <label for="department_id" class="block text-sm font-medium text-gray-700">Department</label>
                            <select id="department_id" name="department_id" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">All departments</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected(($filters['department_id'] ?? null) == $department->id)>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="flex items-end gap-2">
                        <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                            Apply filters
                        </button>
                        <a href="{{ route('reports.index') }}" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">
                            Clear
                        </a>
                    </div>
                </form>
            </section>

            @if ($filters['semester_id'] && $filters['status'] === 'published' && ! $filters['course_id'])
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Semester GPA</h2>
                    <p class="mt-1 text-sm text-gray-600">Published results only; weighted by course credits and rounded to two decimals.</p>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Student</th>
                                    <th scope="col" class="px-4 py-3">Program</th>
                                    <th scope="col" class="px-4 py-3">Credits</th>
                                    <th scope="col" class="px-4 py-3">GPA</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($semesterGpas as $semesterGpa)
                                    <tr>
                                        <td class="px-4 py-3">{{ $semesterGpa->student_name }} <span class="block text-xs text-gray-500">{{ $semesterGpa->student_number }}</span></td>
                                        <td class="px-4 py-3">{{ $semesterGpa->program_name }}</td>
                                        <td class="px-4 py-3">{{ $semesterGpa->credit_units }}</td>
                                        <td class="px-4 py-3 font-semibold">{{ number_format((float) $semesterGpa->gpa, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-600">No published grades are available for this semester.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            <section class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-600">Published results</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $totalResults }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-600">Pass rate</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ $totalResults ? number_format(($passCount / $totalResults) * 100, 1) : '0.0' }}%
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-600">Average score</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($averageScore, 1) }}</p>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th scope="col" class="px-4 py-3">Student</th>
                                <th scope="col" class="px-4 py-3">Department</th>
                                <th scope="col" class="px-4 py-3">Session / semester</th>
                                <th scope="col" class="px-4 py-3">Course</th>
                                <th scope="col" class="px-4 py-3">Score</th>
                                <th scope="col" class="px-4 py-3">Grade</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($results as $result)
                                @php
                                    $registration = $result->courseRegistration;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        {{ $registration->student->user->name }}
                                        <span class="block text-xs text-gray-500">{{ $registration->student->student_number }}</span>
                                    </td>
                                    <td class="px-4 py-3">{{ $registration->course->department->name }}</td>
                                    <td class="px-4 py-3">
                                        {{ $registration->semester->academicSession->name }}
                                        <span class="block text-xs text-gray-500">{{ ucfirst($registration->semester->type->value) }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <strong>{{ $registration->course->code }}</strong>
                                        <span class="block text-xs text-gray-500">{{ $registration->course->title }}</span>
                                    </td>
                                    <td class="px-4 py-3">{{ $result->total_score }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ $result->grade }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-600">
                                        No published results match these filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $results->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
