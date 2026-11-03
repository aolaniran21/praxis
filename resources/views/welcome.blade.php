<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'University Management') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-950 font-sans text-white antialiased">
        <header class="mx-auto flex max-w-7xl items-center justify-between px-6 py-6">
            <a href="{{ url('/') }}" class="text-lg font-semibold tracking-tight">University Management</a>
            <nav class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-100">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="rounded-md border border-slate-600 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Sign in
                    </a>
                @endauth
            </nav>
        </header>

        <main class="mx-auto grid max-w-7xl gap-12 px-6 pb-20 pt-16 lg:grid-cols-[1.2fr_0.8fr] lg:items-center lg:pt-28">
            <section>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-300">Academic operations</p>
                <h1 class="mt-5 max-w-3xl text-4xl font-semibold leading-tight tracking-tight sm:text-6xl">
                    One reliable place for registration and results.
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
                    A role-based university workspace connecting students, lecturers, and department administrators through clear academic workflows.
                </p>
                <div class="mt-9 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-md bg-indigo-500 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-400">
                            Open dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-md bg-indigo-500 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-400">
                            Sign in to continue
                        </a>
                    @endauth
                </div>
            </section>

            <section aria-label="Application workflows" class="grid gap-4 sm:grid-cols-2">
                <article class="rounded-2xl border border-slate-700 bg-slate-900 p-6">
                    <p class="text-sm font-semibold text-indigo-300">01 - Students</p>
                    <h2 class="mt-3 text-xl font-semibold">Register with confidence</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Check eligibility, prerequisites, and credit limits before registering.</p>
                </article>
                <article class="rounded-2xl border border-slate-700 bg-slate-900 p-6 sm:translate-y-8">
                    <p class="text-sm font-semibold text-indigo-300">02 - Lecturers</p>
                    <h2 class="mt-3 text-xl font-semibold">Submit controlled results</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Enter drafts for assigned courses and track each review transition.</p>
                </article>
                <article class="rounded-2xl border border-slate-700 bg-slate-900 p-6">
                    <p class="text-sm font-semibold text-indigo-300">03 - Departments</p>
                    <h2 class="mt-3 text-xl font-semibold">Review and publish</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Approve or return results with feedback before they become visible.</p>
                </article>
                <article class="rounded-2xl border border-slate-700 bg-slate-900 p-6 sm:translate-y-8">
                    <p class="text-sm font-semibold text-indigo-300">04 - Administration</p>
                    <h2 class="mt-3 text-xl font-semibold">Keep access scoped</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Manage accounts and protect records with roles and resource policies.</p>
                </article>
            </section>
        </main>
    </body>
</html>
