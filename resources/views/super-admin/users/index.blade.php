<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-gray-900">{{ __('User administration') }}</h1>
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

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Create account</h2>
                <p class="mt-1 text-sm text-gray-600">Student and lecturer accounts receive the required profile in the same transaction.</p>
                <form method="POST" action="{{ route('super-admin.users.store') }}" class="mt-5 grid gap-4 md:grid-cols-2">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Full name</label>
                        <input id="name" name="name" value="{{ old('name') }}" required maxlength="255"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                        <select id="role" name="role" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select a role</option>
                            @foreach (\App\Enums\UserRole::cases() as $role)
                                <option value="{{ $role->value }}" @selected(old('role') === $role->value)>
                                    {{ ucwords(str_replace('-', ' ', $role->value)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="department_id" class="block text-sm font-medium text-gray-700">Department (required for department admins and lecturers)</label>
                        <select id="department_id" name="department_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select a department</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="program_id" class="block text-sm font-medium text-gray-700">Program (required for students)</label>
                        <select id="program_id" name="program_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select a program</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}" @selected((string) old('program_id') === (string) $program->id)>
                                    {{ $program->name }} ({{ $program->department->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="student_number" class="block text-sm font-medium text-gray-700">Student number</label>
                        <input id="student_number" name="student_number" value="{{ old('student_number') }}" maxlength="40"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="level" class="block text-sm font-medium text-gray-700">Student level</label>
                        <select id="level" name="level"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select a level</option>
                            @foreach ([100, 200, 300, 400, 500, 600, 700, 800, 900] as $level)
                                <option value="{{ $level }}" @selected((string) old('level') === (string) $level)>{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="staff_number" class="block text-sm font-medium text-gray-700">Staff number</label>
                        <input id="staff_number" name="staff_number" value="{{ old('staff_number') }}" maxlength="40"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Initial password</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Create user
                        </button>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-end justify-between gap-4 border-b border-gray-100 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">Accounts</h2>
                    <form method="GET" action="{{ route('super-admin.users.index') }}" class="grid gap-2 sm:grid-cols-4">
                        <label class="sr-only" for="user-search">Search accounts</label>
                        <input id="user-search" name="search" value="{{ request('search') }}" placeholder="Name or email"
                            class="rounded-md border-gray-300 text-sm">
                        <label class="sr-only" for="user-role">Filter by role</label>
                        <select id="user-role" name="role" class="rounded-md border-gray-300 text-sm">
                            <option value="">All roles</option>
                            @foreach (\App\Enums\UserRole::cases() as $role)
                                <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ ucwords(str_replace('-', ' ', $role->value)) }}</option>
                            @endforeach
                        </select>
                        <label class="sr-only" for="user-active">Filter by status</label>
                        <select id="user-active" name="active" class="rounded-md border-gray-300 text-sm">
                            <option value="">All statuses</option>
                            <option value="1" @selected(request('active') === '1')>Active</option>
                            <option value="0" @selected(request('active') === '0')>Inactive</option>
                        </select>
                        <button class="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white">Filter</button>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">User</th>
                                <th scope="col" class="px-5 py-3">Role</th>
                                <th scope="col" class="px-5 py-3">Profile</th>
                                <th scope="col" class="px-5 py-3">Status / actions</th>
                                <th scope="col" class="px-5 py-3">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($users as $user)
                                <tr>
                                    <td class="px-5 py-4">
                                        <strong class="text-gray-900">{{ $user->name }}</strong>
                                        <span class="block text-gray-600">{{ $user->email }}</span>
                                    </td>
                                    <td class="px-5 py-4">{{ $user->roles->pluck('name')->map(fn ($role) => ucwords(str_replace('-', ' ', $role)))->join(', ') }}</td>
                                    <td class="px-5 py-4 text-gray-600">
                                        @if ($user->student)
                                            {{ $user->student->student_number }} - {{ $user->student->program->name }}
                                        @elseif ($user->lecturer)
                                            {{ $user->lecturer->staff_number }} - {{ $user->lecturer->department->name }}
                                        @elseif ($user->department)
                                            {{ $user->department->name }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="{{ $user->is_active ? 'text-green-700' : 'text-red-700' }}">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        <details class="mt-2">
                                            <summary class="cursor-pointer text-xs font-medium text-indigo-700">Edit / archive account</summary>
                                            <form method="POST" action="{{ route('super-admin.users.update', $user) }}" class="mt-3 min-w-64 space-y-2">
                                                @csrf
                                                @method('PATCH')
                                                <label class="block text-xs text-gray-700">Name
                                                    <input name="name" value="{{ $user->name }}" required maxlength="255" class="mt-1 w-full rounded-md border-gray-300 text-xs">
                                                </label>
                                                <label class="block text-xs text-gray-700">Email
                                                    <input name="email" type="email" value="{{ $user->email }}" required maxlength="255" class="mt-1 w-full rounded-md border-gray-300 text-xs">
                                                </label>
                                                @if ($user->student)
                                                    <label class="block text-xs text-gray-700">Program
                                                        <select name="program_id" required class="mt-1 w-full rounded-md border-gray-300 text-xs">
                                                            @foreach ($programs as $program)
                                                                <option value="{{ $program->id }}" @selected($user->student->program_id === $program->id)>{{ $program->name }} ({{ $program->department->name }})</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                    <label class="block text-xs text-gray-700">Student number
                                                        <input name="student_number" value="{{ $user->student->student_number }}" required maxlength="40" class="mt-1 w-full rounded-md border-gray-300 text-xs">
                                                    </label>
                                                    <label class="block text-xs text-gray-700">Level
                                                        <select name="level" required class="mt-1 w-full rounded-md border-gray-300 text-xs">
                                                            @foreach ([100, 200, 300, 400, 500, 600, 700, 800, 900] as $level)
                                                                <option value="{{ $level }}" @selected($user->student->level === $level)>{{ $level }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                @elseif ($user->lecturer)
                                                    <label class="block text-xs text-gray-700">Department
                                                        <select name="department_id" required class="mt-1 w-full rounded-md border-gray-300 text-xs">
                                                            @foreach ($departments as $department)
                                                                <option value="{{ $department->id }}" @selected($user->lecturer->department_id === $department->id)>{{ $department->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                    <label class="block text-xs text-gray-700">Staff number
                                                        <input name="staff_number" value="{{ $user->lecturer->staff_number }}" required maxlength="40" class="mt-1 w-full rounded-md border-gray-300 text-xs">
                                                    </label>
                                                @elseif ($user->hasRole('department-admin'))
                                                    <label class="block text-xs text-gray-700">Department
                                                        <select name="department_id" required class="mt-1 w-full rounded-md border-gray-300 text-xs">
                                                            @foreach ($departments as $department)
                                                                <option value="{{ $department->id }}" @selected($user->department_id === $department->id)>{{ $department->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                @endif
                                                <label class="block text-xs text-gray-700">Account status
                                                    <select name="is_active" required class="mt-1 w-full rounded-md border-gray-300 text-xs">
                                                        <option value="1" @selected($user->is_active)>Active</option>
                                                        <option value="0" @selected(! $user->is_active)>Archived / inactive</option>
                                                    </select>
                                                </label>
                                                <button class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white">Save account</button>
                                            </form>
                                        </details>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $user->created_at->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-gray-600">No accounts found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-5 py-4">{{ $users->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
