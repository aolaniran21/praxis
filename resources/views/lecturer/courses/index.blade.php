<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-gray-900">{{ __('Assigned courses') }}</h1>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">Course</th>
                                <th scope="col" class="px-5 py-3">Department</th>
                                <th scope="col" class="px-5 py-3">Semester</th>
                                <th scope="col" class="px-5 py-3">Students</th>
                                <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($assignments as $assignment)
                                <tr>
                                    <td class="px-5 py-4">
                                        <strong>{{ $assignment->course->code }}</strong>
                                        <span class="block text-gray-600">{{ $assignment->course->title }}</span>
                                    </td>
                                    <td class="px-5 py-4">{{ $assignment->course->department->name }}</td>
                                    <td class="px-5 py-4">
                                        {{ $assignment->semester->academicSession->name }} - {{ ucfirst($assignment->semester->type->value) }}
                                    </td>
                                    <td class="px-5 py-4">
                                        {{ $assignment->course->registrations()->where('semester_id', $assignment->semester_id)->count() }}
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('lecturer.assignments.show', $assignment) }}"
                                            class="font-semibold text-indigo-700 hover:text-indigo-900">Enter results</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-gray-600">No course assignments found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
