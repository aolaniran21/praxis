<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold text-gray-900">{{ __('Academic result statement') }}</h1>
            <button type="button" onclick="window.print()"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 print:hidden">
                Print statement
            </button>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ $student->user->name }}</h2>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $student->student_number }} - {{ $student->program->name }} - Level {{ $student->level }}
                </p>
                <p class="mt-4 text-sm text-gray-700">
                    Cumulative GPA:
                    <strong class="text-gray-900">{{ number_format($cgpa, 2) }}</strong>
                </p>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th scope="col" class="px-4 py-3">Academic session</th>
                                <th scope="col" class="px-4 py-3">Course</th>
                                <th scope="col" class="px-4 py-3">Credits</th>
                                <th scope="col" class="px-4 py-3">Score</th>
                                <th scope="col" class="px-4 py-3">Grade</th>
                                <th scope="col" class="px-4 py-3">Grade point</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($courses as $result)
                                <tr>
                                    <td class="px-4 py-3">{{ $result->courseRegistration->semester->academicSession->name }}</td>
                                    <td class="px-4 py-3">
                                        <strong>{{ $result->courseRegistration->course->code }}</strong>
                                        - {{ $result->courseRegistration->course->title }}
                                    </td>
                                    <td class="px-4 py-3">{{ $result->courseRegistration->course->credit_units }}</td>
                                    <td class="px-4 py-3">{{ $result->total_score }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ $result->grade }}</td>
                                    <td class="px-4 py-3">{{ number_format($result->grade_point, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-600">
                                        No results have been published for your account.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="mt-4 text-xs text-gray-500">
                    CGPA uses the latest published attempt for each course, weighted by credit units.
                </p>
            </section>
        </div>
    </div>
</x-app-layout>
