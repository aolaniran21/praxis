<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-gray-900">{{ __('Department course management') }}</h1>
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

            <section class="grid gap-6 lg:grid-cols-2">
                <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Add course</h2>
                    <form method="POST" action="{{ route('department.courses.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                        @csrf
                        <label class="text-sm font-medium text-gray-700">Course code
                            <input name="code" required maxlength="30" value="{{ old('code') }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <label class="text-sm font-medium text-gray-700">Title
                            <input name="title" required maxlength="255" value="{{ old('title') }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <label class="text-sm font-medium text-gray-700">Credit units
                            <input type="number" name="credit_units" min="1" max="30" required value="{{ old('credit_units') }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </label>
                        <label class="text-sm font-medium text-gray-700">Level
                            <select name="level" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                @foreach ([100, 200, 300, 400, 500, 600, 700, 800, 900] as $level)
                                    <option value="{{ $level }}" @selected((string) old('level') === (string) $level)>{{ $level }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-gray-700">Semester type
                            <select name="semester_type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Any semester</option>
                                @foreach (\App\Enums\SemesterType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ ucfirst($type->value) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-gray-700 sm:col-span-2">Prerequisite courses
                            <select name="prerequisites[]" multiple class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->code }} - {{ $course->title }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="w-fit rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save course</button>
                    </form>
                </article>

                <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Assign lecturer</h2>
                    <form method="POST" action="{{ route('department.assignments.store') }}" class="mt-4 grid gap-3">
                        @csrf
                        <label class="text-sm font-medium text-gray-700">Course
                            <select name="course_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Select course</option>
                                @foreach ($courses->where('is_active', true) as $course)
                                    <option value="{{ $course->id }}">{{ $course->code }} - {{ $course->title }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-gray-700">Lecturer
                            <select name="lecturer_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Select lecturer</option>
                                @foreach ($lecturers as $lecturer)
                                    <option value="{{ $lecturer->id }}">{{ $lecturer->user->name }} ({{ $lecturer->staff_number }})</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-gray-700">Semester
                            <select name="semester_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Select semester</option>
                                @foreach ($semesters as $semester)
                                    <option value="{{ $semester->id }}">{{ $semester->academicSession->name }} - {{ ucfirst($semester->type->value) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="w-fit rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save assignment</button>
                    </form>
                </article>
            </section>

            <section class="space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 class="text-lg font-semibold text-gray-900">Department courses</h2>
                    <form method="GET" action="{{ route('department.courses.index') }}" class="flex flex-wrap gap-2">
                        <label class="sr-only" for="course-search">Search courses</label>
                        <input id="course-search" name="search" value="{{ request('search') }}" placeholder="Course code or title"
                            class="rounded-md border-gray-300 text-sm">
                        <label class="sr-only" for="course-active-filter">Filter course status</label>
                        <select id="course-active-filter" name="active" class="rounded-md border-gray-300 text-sm">
                            <option value="">All statuses</option>
                            <option value="1" @selected(request('active') === '1')>Active</option>
                            <option value="0" @selected(request('active') === '0')>Archived</option>
                        </select>
                        <button class="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white">Filter</button>
                    </form>
                </div>
                @forelse ($courseList as $course)
                    <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <form method="POST" action="{{ route('department.courses.update', $course) }}" class="grid gap-3 sm:grid-cols-2">
                            @csrf
                            @method('PATCH')
                            <label class="text-sm font-medium text-gray-700">Course code
                                <input name="code" required maxlength="30" value="{{ $course->code }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            </label>
                            <label class="text-sm font-medium text-gray-700">Title
                                <input name="title" required maxlength="255" value="{{ $course->title }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            </label>
                            <label class="text-sm font-medium text-gray-700">Credit units
                                <input type="number" name="credit_units" min="1" max="30" required value="{{ $course->credit_units }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            </label>
                            <label class="text-sm font-medium text-gray-700">Level
                                <select name="level" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                    @foreach ([100, 200, 300, 400, 500, 600, 700, 800, 900] as $level)
                                        <option value="{{ $level }}" @selected($course->level === $level)>{{ $level }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="text-sm font-medium text-gray-700">Semester type
                                <select name="semester_type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                    <option value="">Any semester</option>
                                    @foreach (\App\Enums\SemesterType::cases() as $type)
                                        <option value="{{ $type->value }}" @selected($course->semester_type === $type)>{{ ucfirst($type->value) }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="text-sm font-medium text-gray-700">Availability
                                <select name="is_active" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                    <option value="1" @selected($course->is_active)>Active</option>
                                    <option value="0" @selected(! $course->is_active)>Archived</option>
                                </select>
                            </label>
                            <label class="text-sm font-medium text-gray-700 sm:col-span-2">Prerequisites
                                <select name="prerequisites[]" multiple class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                    @foreach ($courses->where('id', '!=', $course->id) as $prerequisite)
                                        <option value="{{ $prerequisite->id }}" @selected($course->prerequisites->contains('id', $prerequisite->id))>
                                            {{ $prerequisite->code }} - {{ $prerequisite->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <button class="w-fit rounded-md border border-indigo-600 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">
                                Save {{ $course->code }}
                            </button>
                        </form>
                    </article>
                @empty
                    <div class="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600">No department courses yet.</div>
                @endforelse
                <div>{{ $courseList->links() }}</div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Lecturer assignments</h2>
                <ul class="mt-3 divide-y divide-gray-100 text-sm">
                    @forelse ($assignments as $assignment)
                        <li class="py-3">
                            <strong>{{ $assignment->course->code }}</strong> - {{ $assignment->lecturer->user->name }}
                            <span class="text-gray-500">- {{ $assignment->semester->academicSession->name }} ({{ ucfirst($assignment->semester->type->value) }})</span>
                            <span class="{{ $assignment->is_active ? 'text-green-700' : 'text-gray-500' }}">({{ $assignment->is_active ? 'active' : 'archived' }})</span>
                            <details class="mt-2">
                                <summary class="cursor-pointer text-xs font-medium text-indigo-700">Edit / archive assignment</summary>
                                <form method="POST" action="{{ route('department.assignments.update', $assignment) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                                    @csrf
                                    @method('PATCH')
                                    <label class="text-xs">Course
                                        <select name="course_id" required class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                            @foreach ($courses as $course)<option value="{{ $course->id }}" @selected($assignment->course_id === $course->id)>{{ $course->code }} - {{ $course->title }}</option>@endforeach
                                        </select>
                                    </label>
                                    <label class="text-xs">Lecturer
                                        <select name="lecturer_id" required class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                            @foreach ($lecturers as $lecturer)<option value="{{ $lecturer->id }}" @selected($assignment->lecturer_id === $lecturer->id)>{{ $lecturer->user->name }}</option>@endforeach
                                        </select>
                                    </label>
                                    <label class="text-xs">Semester
                                        <select name="semester_id" required class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                            @foreach ($semesters as $semester)<option value="{{ $semester->id }}" @selected($assignment->semester_id === $semester->id)>{{ $semester->academicSession->name }} - {{ ucfirst($semester->type->value) }}</option>@endforeach
                                        </select>
                                    </label>
                                    <label class="text-xs">Status
                                        <select name="is_active" required class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                            <option value="1" @selected($assignment->is_active)>Active</option>
                                            <option value="0" @selected(! $assignment->is_active)>Archived</option>
                                        </select>
                                    </label>
                                    <button class="w-fit rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white">Save assignment</button>
                                </form>
                            </details>
                        </li>
                    @empty
                        <li class="py-3 text-gray-600">No lecturer assignments found.</li>
                    @endforelse
                </ul>
                <div class="mt-4">{{ $assignments->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
