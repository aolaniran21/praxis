<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-gray-900">{{ __('Academic setup') }}</h1>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800" role="alert">
                    <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="GET" action="{{ route('super-admin.academic.index') }}" class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row">
                <label for="academic-search" class="sr-only">Search academic records</label>
                <input id="academic-search" type="search" name="search" value="{{ request('search') }}" maxlength="100" placeholder="Search faculties, departments, programs, sessions, semesters, courses, or assignments" class="block w-full rounded-md border-gray-300 text-sm shadow-sm">
                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Search</button>
                @if (request()->filled('search'))
                    <a href="{{ route('super-admin.academic.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700">Clear</a>
                @endif
            </form>

            <section class="grid gap-6 lg:grid-cols-2">
                <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Add faculty</h2>
                    <form method="POST" action="{{ route('super-admin.academic.faculties.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                        @csrf
                        <label class="text-sm font-medium text-gray-700">Name
                            <input name="name" value="{{ old('name') }}" required maxlength="255" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <label class="text-sm font-medium text-gray-700">Code
                            <input name="code" value="{{ old('code') }}" required maxlength="20" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <button class="w-fit rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save faculty</button>
                    </form>
                </article>

                <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Add department</h2>
                    <form method="POST" action="{{ route('super-admin.academic.departments.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                        @csrf
                        <label class="text-sm font-medium text-gray-700">Faculty
                            <select name="faculty_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Select faculty</option>
                                @foreach ($faculties as $faculty)<option value="{{ $faculty->id }}">{{ $faculty->name }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-gray-700">Department name
                            <input name="name" required maxlength="255" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <label class="text-sm font-medium text-gray-700">Code
                            <input name="code" required maxlength="20" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <button class="w-fit self-end rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save department</button>
                    </form>
                </article>

                <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Add program</h2>
                    <form method="POST" action="{{ route('super-admin.academic.programs.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                        @csrf
                        <label class="text-sm font-medium text-gray-700">Department
                            <select name="department_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Select department</option>
                                @foreach ($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-gray-700">Program name
                            <input name="name" required maxlength="255" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <label class="text-sm font-medium text-gray-700">Code
                            <input name="code" required maxlength="30" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <button class="w-fit self-end rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save program</button>
                    </form>
                </article>

                <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Add academic session</h2>
                    <form method="POST" action="{{ route('super-admin.academic.sessions.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                        @csrf
                        <label class="text-sm font-medium text-gray-700">Session name
                            <input name="name" placeholder="2026/2027" required maxlength="20" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <label class="text-sm font-medium text-gray-700">Active
                            <select name="is_active" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="1">Yes</option><option value="0">No</option>
                            </select>
                        </label>
                        <label class="text-sm font-medium text-gray-700">Starts on
                            <input type="date" name="starts_on" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <label class="text-sm font-medium text-gray-700">Ends on
                            <input type="date" name="ends_on" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <button class="w-fit rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save session</button>
                    </form>
                </article>

                <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Add semester</h2>
                    <form method="POST" action="{{ route('super-admin.academic.semesters.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                        @csrf
                        <label class="text-sm font-medium text-gray-700">Academic session
                            <select name="academic_session_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Select session</option>
                                @foreach ($sessions as $session)<option value="{{ $session->id }}">{{ $session->name }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-gray-700">Semester
                            <select name="type" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                @foreach (\App\Enums\SemesterType::cases() as $type)<option value="{{ $type->value }}">{{ ucfirst($type->value) }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-gray-700">Starts on
                            <input type="date" name="starts_on" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <label class="text-sm font-medium text-gray-700">Ends on
                            <input type="date" name="ends_on" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <label class="text-sm font-medium text-gray-700">Registration opens
                            <input type="datetime-local" name="registration_opens_at" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <label class="text-sm font-medium text-gray-700">Registration closes
                            <input type="datetime-local" name="registration_closes_at" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <label class="text-sm font-medium text-gray-700">Maximum credit units
                            <input type="number" name="max_credit_units" min="1" max="60" value="24" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <label class="text-sm font-medium text-gray-700">Current semester
                            <select name="is_current" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="0">No</option><option value="1">Yes</option>
                            </select>
                        </label>
                        <button class="w-fit rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save semester</button>
                    </form>
                </article>

                <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Add course</h2>
                    <form method="POST" action="{{ route('super-admin.academic.courses.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                        @csrf
                        <label class="text-sm font-medium text-gray-700">Department
                            <select name="department_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Select department</option>
                                @foreach ($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-gray-700">Course code
                            <input name="code" required maxlength="30" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <label class="text-sm font-medium text-gray-700">Title
                            <input name="title" required maxlength="255" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <label class="text-sm font-medium text-gray-700">Credit units
                            <input type="number" name="credit_units" min="1" max="30" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <label class="text-sm font-medium text-gray-700">Level
                            <select name="level" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                @foreach ([100, 200, 300, 400, 500, 600, 700, 800, 900] as $level)<option value="{{ $level }}">{{ $level }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-gray-700">Semester type
                            <select name="semester_type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Any semester</option>
                                @foreach (\App\Enums\SemesterType::cases() as $type)<option value="{{ $type->value }}">{{ ucfirst($type->value) }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-gray-700 sm:col-span-2">Prerequisite courses
                            <select name="prerequisites[]" multiple class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                @foreach ($courses as $course)<option value="{{ $course->id }}">{{ $course->code }} - {{ $course->title }}</option>@endforeach
                            </select>
                        </label>
                        <input type="hidden" name="is_active" value="1">
                        <button class="w-fit rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save course</button>
                    </form>
                </article>

                <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Assign lecturer to course</h2>
                    <form method="POST" action="{{ route('super-admin.academic.assignments.store') }}" class="mt-4 grid gap-3">
                        @csrf
                        <label class="text-sm font-medium text-gray-700">Course
                            <select name="course_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Select course</option>
                                @foreach ($courses as $course)<option value="{{ $course->id }}">{{ $course->code }} - {{ $course->title }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-gray-700">Lecturer
                            <select name="lecturer_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Select lecturer</option>
                                @foreach ($lecturers as $lecturer)<option value="{{ $lecturer->id }}">{{ $lecturer->user->name }} - {{ $lecturer->department->name }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-gray-700">Semester
                            <select name="semester_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Select semester</option>
                                @foreach ($semesters as $semester)<option value="{{ $semester->id }}">{{ $semester->academicSession->name }} - {{ ucfirst($semester->type->value) }}</option>@endforeach
                            </select>
                        </label>
                        <button class="w-fit rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save assignment</button>
                    </form>
                </article>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-semibold text-gray-900">University structure</h2>
                    <ul class="mt-3 space-y-2 text-sm text-gray-700">
                        @foreach ($departmentRecords as $department)
                            <li><strong>{{ $department->code }}</strong> - {{ $department->name }} <span class="text-gray-500">({{ $department->faculty->name }})</span></li>
                        @endforeach
                        @foreach ($programRecords as $program)
                            <li><strong>{{ $program->code }}</strong> - {{ $program->name }} <span class="text-gray-500">({{ $program->department->name }})</span></li>
                        @endforeach
                    </ul>
                </article>
                <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-semibold text-gray-900">Academic calendar</h2>
                    <ul class="mt-3 space-y-2 text-sm text-gray-700">
                        @foreach ($semesterRecords as $semester)
                            <li>
                                <strong>{{ $semester->academicSession->name }} - {{ ucfirst($semester->type->value) }}</strong>
                                @if ($semester->is_current)<span class="text-green-700">(current)</span>@endif
                                <span class="text-gray-500">- {{ $semester->starts_on->format('Y-m-d') }} to {{ $semester->ends_on->format('Y-m-d') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </article>
                <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-semibold text-gray-900">Courses</h2>
                    <ul class="mt-3 space-y-2 text-sm text-gray-700">
                        @foreach ($courseRecords as $course)
                            <li><strong>{{ $course->code }}</strong> - {{ $course->title }} <span class="text-gray-500">({{ $course->department->name }}, level {{ $course->level }}, {{ $course->credit_units }} credits)</span></li>
                        @endforeach
                    </ul>
                </article>
                <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-semibold text-gray-900">Course assignments</h2>
                    <ul class="mt-3 space-y-2 text-sm text-gray-700">
                        @foreach ($assignmentRecords as $assignment)
                            <li>{{ $assignment->course->code }} - {{ $assignment->lecturer->user->name }} <span class="text-gray-500">({{ $assignment->semester->academicSession->name }})</span></li>
                        @endforeach
                    </ul>
                </article>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Edit or archive academic records</h2>
                <p class="mt-1 text-sm text-gray-600">Records with linked history are archived rather than deleted.</p>
                <div class="mt-4 grid gap-3 lg:grid-cols-2">
                    @foreach ($facultyRecords as $faculty)
                        <details class="rounded-md border border-gray-200 p-3">
                            <summary class="cursor-pointer text-sm font-medium">{{ $faculty->name }} ({{ $faculty->is_active ? 'active' : 'archived' }})</summary>
                            <form method="POST" action="{{ route('super-admin.academic.update', ['entity' => 'faculties', 'record' => $faculty]) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                                @csrf @method('PATCH')
                                <label class="text-xs">Name<input name="name" value="{{ $faculty->name }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm"></label>
                                <label class="text-xs">Code<input name="code" value="{{ $faculty->code }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm"></label>
                                <label class="text-xs">Status<select name="is_active" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="1" @selected($faculty->is_active)>Active</option><option value="0" @selected(! $faculty->is_active)>Archive</option></select></label>
                                <button class="self-end rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white">Save</button>
                            </form>
                        </details>
                    @endforeach

                    @foreach ($departmentRecords as $department)
                        <details class="rounded-md border border-gray-200 p-3">
                            <summary class="cursor-pointer text-sm font-medium">{{ $department->name }} ({{ $department->is_active ? 'active' : 'archived' }})</summary>
                            <form method="POST" action="{{ route('super-admin.academic.update', ['entity' => 'departments', 'record' => $department]) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                                @csrf @method('PATCH')
                                <label class="text-xs">Faculty<select name="faculty_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">@foreach ($faculties as $faculty)<option value="{{ $faculty->id }}" @selected($department->faculty_id === $faculty->id)>{{ $faculty->name }}</option>@endforeach</select></label>
                                <label class="text-xs">Name<input name="name" value="{{ $department->name }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm"></label>
                                <label class="text-xs">Code<input name="code" value="{{ $department->code }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm"></label>
                                <label class="text-xs">Status<select name="is_active" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="1" @selected($department->is_active)>Active</option><option value="0" @selected(! $department->is_active)>Archive</option></select></label>
                                <button class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white">Save</button>
                            </form>
                        </details>
                    @endforeach

                    @foreach ($programRecords as $program)
                        <details class="rounded-md border border-gray-200 p-3">
                            <summary class="cursor-pointer text-sm font-medium">{{ $program->name }} ({{ $program->is_active ? 'active' : 'archived' }})</summary>
                            <form method="POST" action="{{ route('super-admin.academic.update', ['entity' => 'programs', 'record' => $program]) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                                @csrf @method('PATCH')
                                <label class="text-xs">Department<select name="department_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">@foreach ($departments as $department)<option value="{{ $department->id }}" @selected($program->department_id === $department->id)>{{ $department->name }}</option>@endforeach</select></label>
                                <label class="text-xs">Name<input name="name" value="{{ $program->name }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm"></label>
                                <label class="text-xs">Code<input name="code" value="{{ $program->code }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm"></label>
                                <label class="text-xs">Status<select name="is_active" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="1" @selected($program->is_active)>Active</option><option value="0" @selected(! $program->is_active)>Archive</option></select></label>
                                <button class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white">Save</button>
                            </form>
                        </details>
                    @endforeach

                    @foreach ($sessionRecords as $session)
                        <details class="rounded-md border border-gray-200 p-3">
                            <summary class="cursor-pointer text-sm font-medium">{{ $session->name }} ({{ $session->is_active ? 'active' : 'archived' }})</summary>
                            <form method="POST" action="{{ route('super-admin.academic.update', ['entity' => 'sessions', 'record' => $session]) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                                @csrf @method('PATCH')
                                <label class="text-xs">Session<input name="name" value="{{ $session->name }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm"></label>
                                <label class="text-xs">Status<select name="is_active" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="1" @selected($session->is_active)>Active</option><option value="0" @selected(! $session->is_active)>Archive</option></select></label>
                                <label class="text-xs">Starts<input type="date" name="starts_on" value="{{ $session->starts_on->format('Y-m-d') }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm"></label>
                                <label class="text-xs">Ends<input type="date" name="ends_on" value="{{ $session->ends_on->format('Y-m-d') }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm"></label>
                                <button class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white">Save</button>
                            </form>
                        </details>
                    @endforeach

                    @foreach ($semesterRecords as $semester)
                        <details class="rounded-md border border-gray-200 p-3">
                            <summary class="cursor-pointer text-sm font-medium">{{ $semester->academicSession->name }} - {{ ucfirst($semester->type->value) }} ({{ $semester->is_active ? 'active' : 'archived' }})</summary>
                            <form method="POST" action="{{ route('super-admin.academic.update', ['entity' => 'semesters', 'record' => $semester]) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                                @csrf @method('PATCH')
                                <label class="text-xs">Session<select name="academic_session_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">@foreach ($sessions as $session)<option value="{{ $session->id }}" @selected($semester->academic_session_id === $session->id)>{{ $session->name }}</option>@endforeach</select></label>
                                <label class="text-xs">Type<select name="type" class="mt-1 w-full rounded-md border-gray-300 text-sm">@foreach (\App\Enums\SemesterType::cases() as $type)<option value="{{ $type->value }}" @selected($semester->type === $type)>{{ ucfirst($type->value) }}</option>@endforeach</select></label>
                                <label class="text-xs">Starts<input type="date" name="starts_on" value="{{ $semester->starts_on->format('Y-m-d') }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm"></label>
                                <label class="text-xs">Ends<input type="date" name="ends_on" value="{{ $semester->ends_on->format('Y-m-d') }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm"></label>
                                <label class="text-xs">Registration opens<input type="datetime-local" name="registration_opens_at" value="{{ $semester->registration_opens_at?->format('Y-m-d\TH:i') }}" class="mt-1 w-full rounded-md border-gray-300 text-sm"></label>
                                <label class="text-xs">Registration closes<input type="datetime-local" name="registration_closes_at" value="{{ $semester->registration_closes_at?->format('Y-m-d\TH:i') }}" class="mt-1 w-full rounded-md border-gray-300 text-sm"></label>
                                <label class="text-xs">Max credits<input type="number" name="max_credit_units" min="1" max="60" value="{{ $semester->max_credit_units }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm"></label>
                                <label class="text-xs">Current<select name="is_current" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="1" @selected($semester->is_current)>Yes</option><option value="0" @selected(! $semester->is_current)>No</option></select></label>
                                <label class="text-xs">Status<select name="is_active" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="1" @selected($semester->is_active)>Active</option><option value="0" @selected(! $semester->is_active)>Archive</option></select></label>
                                <button class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white">Save</button>
                            </form>
                        </details>
                    @endforeach

                    @foreach ($courseRecords as $course)
                        <details class="rounded-md border border-gray-200 p-3">
                            <summary class="cursor-pointer text-sm font-medium">{{ $course->code }} - {{ $course->title }} ({{ $course->is_active ? 'active' : 'archived' }})</summary>
                            <form method="POST" action="{{ route('super-admin.academic.update', ['entity' => 'courses', 'record' => $course]) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                                @csrf @method('PATCH')
                                <label class="text-xs">Department<select name="department_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">@foreach ($departments as $department)<option value="{{ $department->id }}" @selected($course->department_id === $department->id)>{{ $department->name }}</option>@endforeach</select></label>
                                <label class="text-xs">Code<input name="code" value="{{ $course->code }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm"></label>
                                <label class="text-xs">Title<input name="title" value="{{ $course->title }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm"></label>
                                <label class="text-xs">Credits<input type="number" name="credit_units" min="1" max="30" value="{{ $course->credit_units }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm"></label>
                                <label class="text-xs">Level<select name="level" class="mt-1 w-full rounded-md border-gray-300 text-sm">@foreach ([100, 200, 300, 400, 500, 600, 700, 800, 900] as $level)<option value="{{ $level }}" @selected($course->level === $level)>{{ $level }}</option>@endforeach</select></label>
                                <label class="text-xs">Semester type<select name="semester_type" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">Any semester</option>@foreach (\App\Enums\SemesterType::cases() as $type)<option value="{{ $type->value }}" @selected($course->semester_type === $type)>{{ ucfirst($type->value) }}</option>@endforeach</select></label>
                                <label class="text-xs sm:col-span-2">Prerequisites<select name="prerequisites[]" multiple class="mt-1 w-full rounded-md border-gray-300 text-sm">@foreach ($courses->where('department_id', $course->department_id)->where('id', '!=', $course->id) as $prerequisite)<option value="{{ $prerequisite->id }}" @selected($course->prerequisites->contains('id', $prerequisite->id))>{{ $prerequisite->code }} - {{ $prerequisite->title }}</option>@endforeach</select></label>
                                <label class="text-xs">Status<select name="is_active" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="1" @selected($course->is_active)>Active</option><option value="0" @selected(! $course->is_active)>Archive</option></select></label>
                                <button class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white">Save</button>
                            </form>
                        </details>
                    @endforeach

                    @foreach ($assignmentRecords as $assignment)
                        <details class="rounded-md border border-gray-200 p-3">
                            <summary class="cursor-pointer text-sm font-medium">{{ $assignment->course->code }} - {{ $assignment->lecturer->user->name }} ({{ $assignment->is_active ? 'active' : 'archived' }})</summary>
                            <form method="POST" action="{{ route('super-admin.academic.update', ['entity' => 'assignments', 'record' => $assignment]) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                                @csrf @method('PATCH')
                                <label class="text-xs">Course<select name="course_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">@foreach ($courses as $course)<option value="{{ $course->id }}" @selected($assignment->course_id === $course->id)>{{ $course->code }}</option>@endforeach</select></label>
                                <label class="text-xs">Lecturer<select name="lecturer_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">@foreach ($lecturers as $lecturer)<option value="{{ $lecturer->id }}" @selected($assignment->lecturer_id === $lecturer->id)>{{ $lecturer->user->name }} ({{ $lecturer->department->name }})</option>@endforeach</select></label>
                                <label class="text-xs">Semester<select name="semester_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">@foreach ($semesters as $semester)<option value="{{ $semester->id }}" @selected($assignment->semester_id === $semester->id)>{{ $semester->academicSession->name }} - {{ ucfirst($semester->type->value) }}</option>@endforeach</select></label>
                                <label class="text-xs">Status<select name="is_active" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="1" @selected($assignment->is_active)>Active</option><option value="0" @selected(! $assignment->is_active)>Archive</option></select></label>
                                <button class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white">Save</button>
                            </form>
                        </details>
                    @endforeach

                    <nav aria-label="Academic record pages" class="grid gap-3 border-t border-gray-200 pt-4 sm:grid-cols-2 lg:col-span-2">
                        <div><h3 class="text-sm font-medium text-gray-700">Faculties</h3>{{ $facultyRecords->links() }}</div>
                        <div><h3 class="text-sm font-medium text-gray-700">Departments</h3>{{ $departmentRecords->links() }}</div>
                        <div><h3 class="text-sm font-medium text-gray-700">Programs</h3>{{ $programRecords->links() }}</div>
                        <div><h3 class="text-sm font-medium text-gray-700">Academic sessions</h3>{{ $sessionRecords->links() }}</div>
                        <div><h3 class="text-sm font-medium text-gray-700">Semesters</h3>{{ $semesterRecords->links() }}</div>
                        <div><h3 class="text-sm font-medium text-gray-700">Courses</h3>{{ $courseRecords->links() }}</div>
                        <div><h3 class="text-sm font-medium text-gray-700">Course assignments</h3>{{ $assignmentRecords->links() }}</div>
                    </nav>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
