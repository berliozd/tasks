<script setup>
import {Head, Link, usePage} from '@inertiajs/vue3';
import Ad from "@/Components/Ad.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import AuthenticationCardLogo from "@/Components/AuthenticationCardLogo.vue";

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Welcome"/>
    <GuestLayout :show-home-link="false">
        <template #header-right>
            <Link
                v-if="canLogin && !$page.props.auth.user"
                :href="route('login')"
                class="text-sm font-medium text-slate-200 hover:text-white transition"
            >
                Log in
            </Link>
        </template>

        <div class="relative -mx-4 sm:-mx-6 lg:-mx-8 -my-8 text-gray-600">

            <!-- General intro -->
            <section class="w-full bg-gray-50">
                <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
                    <div class="flex justify-center rounded-full mb-6">
                        <AuthenticationCardLogo/>
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-black">
                        Run your day. Fill your pipeline.
                    </h1>
                    <p class="mt-4 text-base font-medium text-brand-accent-dark">
                        One login for your tasks, your sales pipeline, your team's knowledge, and every
                        product need in between.
                    </p>
                    <p class="mt-4 text-sm/relaxed">
                        Built for solo professionals who want one place for everything, and for small
                        teams who need to track leads, document decisions, and ship features without
                        juggling five different tools.
                        <span class="font-semibold text-black">Tasks</span> keeps your day-to-day organized —
                        schedule, flag and recur what needs doing, at work or at home.
                        <span class="font-semibold text-black">Prospection</span> helps your team find leads,
                        log outreach, and send real emails, powered by AI.
                        <span class="font-semibold text-black">Documents</span> is your team's Markdown
                        knowledge base, auto-tagged by AI.
                        <span class="font-semibold text-black">Needs</span> is a drag-and-drop board tracking
                        business needs from discovery to production.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <Link v-if="canRegister && !$page.props.auth.user" :href="route('register')"
                              class="inline-flex items-center gap-2 rounded-lg bg-brand-navy px-5 py-2.5 text-sm font-semibold text-white shadow-card hover:bg-brand-navy-light transition">
                            Get started free
                        </Link>
                        <Link v-if="canLogin && !$page.props.auth.user" :href="route('login')"
                              class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 ring-1 ring-slate-900/[0.06] shadow-card hover:bg-gray-50 transition">
                            Log in
                        </Link>
                        <Link v-if="$page.props.auth.user" :href="route('dashboard')"
                              class="inline-flex items-center gap-2 rounded-lg bg-brand-navy px-5 py-2.5 text-sm font-semibold text-white shadow-card hover:bg-brand-navy-light transition">
                            Go to dashboard
                        </Link>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-600 ring-1 ring-slate-900/[0.06]">
                            Solo professionals
                        </span>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-600 ring-1 ring-slate-900/[0.06]">
                            Small teams
                        </span>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-600 ring-1 ring-slate-900/[0.06]">
                            Sales &amp; outreach
                        </span>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-600 ring-1 ring-slate-900/[0.06]">
                            Product &amp; ops
                        </span>
                    </div>
                </div>
            </section>

            <!-- Tasks -->
            <section class="w-full bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <div class="grid gap-10 lg:grid-cols-3 lg:items-start">
                        <div class="lg:col-span-1">
                            <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-brand-navy">
                                <svg class="size-8 stroke-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h2 class="mt-4 text-xl font-semibold text-black">Tasks</h2>
                            <p class="mt-4 text-sm/relaxed">
                                Effortless task management made simple. Add, schedule and track what needs
                                doing — no hassle, no complexity. Useful for work, just as handy for life
                                outside it.
                            </p>
                            <a href="/tasks"
                               class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-brand-accent-dark hover:underline">
                                Explore Tasks
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/>
                                </svg>
                            </a>
                        </div>

                        <div class="lg:col-span-2 grid gap-6 sm:grid-cols-3">
                            <div class="flex flex-col items-start gap-4 rounded-xl p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-surface text-brand-navy">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-black">Add Tasks for Today</h3>
                                    <p class="mt-2 text-sm/relaxed">
                                        Quickly capture tasks that need to be done today, ensuring you never
                                        forget what's on your plate.
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-4 rounded-xl p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-surface text-brand-navy">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-black">Mark Tasks as Done</h3>
                                    <p class="mt-2 text-sm/relaxed">
                                        Check off completed tasks with a single click, instantly clearing your
                                        mind and keeping you motivated.
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-4 rounded-xl p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-surface text-brand-navy">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-black">Recurring Tasks</h3>
                                    <p class="mt-2 text-sm/relaxed">
                                        Make repetitive tasks a breeze by setting them to recur automatically,
                                        ensuring nothing falls through the cracks.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Prospection -->
            <section class="w-full bg-gray-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <div class="grid gap-10 lg:grid-cols-3 lg:items-start">
                        <div class="lg:col-span-1">
                            <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-brand-navy">
                                <svg class="size-8 stroke-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                                </svg>
                            </div>
                            <h2 class="mt-4 text-xl font-semibold text-black">Prospection</h2>
                            <p class="mt-4 text-sm/relaxed">
                                Find leads, track outreach and send real emails — powered by AI, shared with
                                your whole team.
                            </p>
                            <a href="/products"
                               class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-brand-accent-dark hover:underline">
                                Explore Prospection
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/>
                                </svg>
                            </a>
                        </div>

                        <div class="lg:col-span-2 grid gap-6 sm:grid-cols-3">
                            <div class="flex flex-col items-start gap-4 rounded-xl bg-white p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-surface text-brand-navy">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-black">AI-Generated Directories</h3>
                                    <p class="mt-2 text-sm/relaxed">
                                        Describe who you're after — the app finds real companies with real
                                        websites to match.
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-4 rounded-xl bg-white p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-surface text-brand-navy">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-black">Outreach Log</h3>
                                    <p class="mt-2 text-sm/relaxed">
                                        Log every call and email against a prospect with a status — pending,
                                        sent, replied, bounced and more.
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-4 rounded-xl bg-white p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-surface text-brand-navy">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-black">Real Sending</h3>
                                    <p class="mt-2 text-sm/relaxed">
                                        Send logged emails for real through Mailjet, right from the action you
                                        just logged.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Documents -->
            <section class="w-full bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <div class="grid gap-10 lg:grid-cols-3 lg:items-start">
                        <div class="lg:col-span-1">
                            <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-brand-navy">
                                <svg class="size-8 stroke-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                </svg>
                            </div>
                            <h2 class="mt-4 text-xl font-semibold text-black">Documents</h2>
                            <p class="mt-4 text-sm/relaxed">
                                A Markdown knowledge base shared with your team, auto-tagged by AI so you can
                                find things by topic instead of hunting through files.
                            </p>
                            <a href="/documents"
                               class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-brand-accent-dark hover:underline">
                                Explore Documents
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/>
                                </svg>
                            </a>
                        </div>

                        <div class="lg:col-span-2 grid gap-6 sm:grid-cols-3">
                            <div class="flex flex-col items-start gap-4 rounded-xl p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-surface text-brand-navy">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-black">Write or Drag &amp; Drop</h3>
                                    <p class="mt-2 text-sm/relaxed">
                                        Write a document by hand, or drop in a <code>.md</code> file to import it
                                        instantly.
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-4 rounded-xl p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-surface text-brand-navy">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-black">Markdown + Live Preview</h3>
                                    <p class="mt-2 text-sm/relaxed">
                                        Edit the raw Markdown and see the rendered, human-readable version side
                                        by side.
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-4 rounded-xl p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-surface text-brand-navy">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-black">AI-Tagged &amp; Filterable</h3>
                                    <p class="mt-2 text-sm/relaxed">
                                        On save, AI scans the content and attaches topical flags — filter the
                                        list by them to find things fast.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Needs -->
            <section class="w-full bg-gray-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <div class="grid gap-10 lg:grid-cols-3 lg:items-start">
                        <div class="lg:col-span-1">
                            <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-brand-navy">
                                <svg class="size-8 stroke-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>
                                </svg>
                            </div>
                            <h2 class="mt-4 text-xl font-semibold text-black">Needs</h2>
                            <p class="mt-4 text-sm/relaxed">
                                A drag-and-drop Kanban board for product owners to track business needs from
                                first discovery through production, with a full activity log on every one.
                            </p>
                            <a href="/needs"
                               class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-brand-accent-dark hover:underline">
                                Explore Needs
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/>
                                </svg>
                            </a>
                        </div>

                        <div class="lg:col-span-2 grid gap-6 sm:grid-cols-3">
                            <div class="flex flex-col items-start gap-4 rounded-xl p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-surface text-brand-navy">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M9 3.75H6.912a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661V18a2.25 2.25 0 002.25 2.25h15.6A2.25 2.25 0 0022.35 18v-4.162c0-.224-.034-.447-.1-.661L19.838 5.338a2.25 2.25 0 00-2.15-1.588H15M9 3.75v11.25A2.25 2.25 0 0011.25 17h1.5a2.25 2.25 0 002.25-2.25V3.75M9 3.75h6"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-black">Configurable Pipeline</h3>
                                    <p class="mt-2 text-sm/relaxed">
                                        Define your own stages and stage groups per team — from first discovery
                                        through production — so every need's status fits how you actually work.
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-4 rounded-xl p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-surface text-brand-navy">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-black">Drag to Move</h3>
                                    <p class="mt-2 text-sm/relaxed">
                                        Drag a card between columns to change its stage, with Confluence and
                                        Jira links kept right on the card.
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-4 rounded-xl p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-surface text-brand-navy">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-black">Full Activity Log</h3>
                                    <p class="mt-2 text-sm/relaxed">
                                        Every stage change and note is logged, so you can see exactly what
                                        happened before, during, and after deployment.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Ad/>

                <footer class="py-16 text-center text-sm text-gray-600">
                    {{ usePage().props.appName }} - @copyright 2026 - Addeos
                </footer>
            </div>
        </div>
    </GuestLayout>
</template>
