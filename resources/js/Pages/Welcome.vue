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

function handleImageError() {
    document.getElementById('screenshot-container')?.classList.add('!hidden');
    document.getElementById('docs-card')?.classList.add('!row-span-1');
    document.getElementById('docs-card-content')?.classList.add('!flex-row');
    document.getElementById('background')?.classList.add('!hidden');
}
</script>

<template>
    <Head title="Welcome"/>
    <GuestLayout :show-home-link="false">
        <div class="relative w-full text-gray-600">
            <div class="relative">
                <header class="grid grid-cols-2 items-center gap-2 py-10 lg:grid-cols-3">
                    <div class="flex lg:justify-center lg:col-start-2 rounded-full">
                        <AuthenticationCardLogo/>
                    </div>
                </header>

                <div
                    v-if="canLogin && !$page.props.auth.user"
                    class="pb-4 flex items-center justify-center"
                >
                    <div class="flex flex-col sm:flex-row gap-3 w-full max-w-md">
                        <Link
                            :href="route('login')"
                            class="w-full inline-flex items-center justify-center rounded-xl bg-brand-navy px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-brand-navy/90 focus:outline-none focus:ring-2 focus:ring-brand-navy/40"
                        >
                            Log in
                        </Link>

                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="w-full inline-flex items-center justify-center rounded-xl bg-brand-navy px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-brand-navy/90 focus:outline-none focus:ring-2 focus:ring-brand-navy/40"
                        >
                            Register
                        </Link>
                    </div>
                </div>

                <main class="my-6">
                    <div class="grid gap-6 lg:grid-cols-2 lg:gap-8">
                        <a
                            href="/tasks"
                            id="docs-card"
                            class="flex flex-col items-start gap-6 overflow-hidden rounded-xl shadow-card
                            ring-1 ring-slate-900/[0.06] transition duration-300 hover:shadow-card-hover hover:ring-slate-900/10 focus:outline-none
                            focus-visible:ring-brand-accent md:row-span-3 lg:p-10 lg:pb-10"
                        >
                            <div id="screenshot-container" class="relative flex w-full flex-1 items-stretch">
                                <img
                                    src="/img/screens/tasks.png"
                                    alt="Laravel documentation screenshot"
                                    class=" aspect-video h-full w-full flex-1 rounded-[10px] object-top object-cover drop-shadow-[0px_4px_34px_rgba(0,0,0,0.25)] block"
                                />
                                <div
                                    class="absolute -bottom-16 -left-16 h-40 w-[calc(100%+8rem)] bg-gradient-to-b from-transparent via-white to-white"
                                ></div>
                            </div>

                            <div class="relative flex items-center gap-6 lg:items-end">
                                <div id="docs-card-content" class="flex items-start gap-6 lg:flex-col">
                                    <div
                                        class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand-accent/10 sm:size-16">
                                        <div
                                            class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand-navy sm:size-16">
                                            <img src="/img/tasks-nobg.png" class="size-8 object-contain" alt="Tasks">
                                        </div>
                                    </div>

                                    <div class="pt-3 sm:pt-5 lg:pt-0">
                                        <h2 class="text-xl font-semibold text-black">Tasks</h2>

                                        <p class="mt-4 text-sm/relaxed">
                                            Effortless Task Management Made Simple
                                            Tasks, the easiest way to stay organized and on top of your day. With a
                                            focus on simplicity and efficiency, our smart task management tool helps you
                                            easily add, track, and manage your tasks—no hassle, no complexity. </p>
                                    </div>
                                </div>

                                <svg class="size-6 shrink-0 stroke-brand-accent" xmlns="http://www.w3.org/2000/svg"
                                     fill="none" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/>
                                </svg>
                            </div>
                        </a>

                        <div
                            class="flex items-start gap-4 rounded-xl p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover lg:pb-10">
                            <BulletLogo/>
                            <div class="pt-3 sm:pt-5">
                                <h2 class="text-xl font-semibold text-black">Add Tasks for Today</h2>

                                <p class="mt-4 text-sm/relaxed">
                                    Quickly capture tasks that need to be done today, ensuring you never forget what’s
                                    on your plate.
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex items-start gap-4 rounded-xl p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover lg:pb-10">
                            <BulletLogo/>
                            <div class="pt-3 sm:pt-5">
                                <h2 class="text-xl font-semibold text-black">Mark Tasks as Done</h2>

                                <p class="mt-4 text-sm/relaxed">
                                    Check off completed tasks with a single click, instantly clearing your mind and
                                    keeping you motivated.
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex items-start gap-4 rounded-xl p-6 shadow-card ring-1 ring-slate-900/[0.06] transition hover:shadow-card-hover lg:pb-10">
                            <BulletLogo/>
                            <div class="pt-3 sm:pt-5">
                                <h2 class="text-xl font-semibold text-black">Recurring Tasks</h2>

                                <p class="mt-4 text-sm/relaxed">
                                    Make repetitive tasks a breeze by setting them to recur automatically, ensuring
                                    nothing falls through the cracks.
                                </p>
                            </div>
                        </div>
                    </div>
                </main>

                <Ad/>

                <footer class="py-16 text-center text-sm text-gray-600">
                    {{ usePage().props.appName }} - @copyright 2025 - Addeos
                </footer>
            </div>
        </div>
    </GuestLayout>
</template>
