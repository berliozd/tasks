<script setup>
import {Head, Link, usePage} from '@inertiajs/vue3';
import Ad from "@/Components/Ad.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import BulletLogo from "@/Components/BulletLogo.vue";
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
                    <p class="mt-4 text-sm/relaxed">
                        <span class="font-semibold text-black">Tasks</span> keeps your day-to-day organized —
                        schedule, flag and recur what needs doing.
                        <span class="font-semibold text-black">Prospection</span> helps your team find leads,
                        log outreach, and send real emails, powered by AI. One app, one login.
                    </p>
                </div>
            </section>

            <!-- Tasks -->
            <section class="w-full bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <div class="grid gap-10 lg:grid-cols-3 lg:items-start">
                        <div class="lg:col-span-1">
                            <BulletLogo size="16"/>
                            <h2 class="mt-4 text-xl font-semibold text-black">Tasks</h2>
                            <p class="mt-4 text-sm/relaxed">
                                Effortless task management made simple. Add, schedule and track what needs
                                doing — no hassle, no complexity.
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
                                <BulletLogo/>
                                <div>
                                    <h3 class="text-base font-semibold text-black">Add Tasks for Today</h3>
                                    <p class="mt-2 text-sm/relaxed">
                                        Quickly capture tasks that need to be done today, ensuring you never
                                        forget what's on your plate.
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-4 rounded-xl p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover">
                                <BulletLogo/>
                                <div>
                                    <h3 class="text-base font-semibold text-black">Mark Tasks as Done</h3>
                                    <p class="mt-2 text-sm/relaxed">
                                        Check off completed tasks with a single click, instantly clearing your
                                        mind and keeping you motivated.
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-4 rounded-xl p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover">
                                <BulletLogo/>
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
                                <BulletLogo/>
                                <div>
                                    <h3 class="text-base font-semibold text-black">AI-Generated Directories</h3>
                                    <p class="mt-2 text-sm/relaxed">
                                        Describe who you're after — the app finds real companies with real
                                        websites to match.
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-4 rounded-xl bg-white p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover">
                                <BulletLogo/>
                                <div>
                                    <h3 class="text-base font-semibold text-black">Outreach Log</h3>
                                    <p class="mt-2 text-sm/relaxed">
                                        Log every call and email against a prospect with a status — pending,
                                        sent, replied, bounced and more.
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-4 rounded-xl bg-white p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover">
                                <BulletLogo/>
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

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Ad/>

                <footer class="py-16 text-center text-sm text-gray-600">
                    {{ usePage().props.appName }} - @copyright 2025 - Addeos
                </footer>
            </div>
        </div>
    </GuestLayout>
</template>
