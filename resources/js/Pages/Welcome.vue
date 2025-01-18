<script setup>
import {Head, Link, usePage} from '@inertiajs/vue3';
import Ad from "@/Components/Ad.vue";

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
    <div class="">
        <div
            class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
            <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl text-gray-600">
                <header class="grid grid-cols-2 items-center gap-2 py-10 lg:grid-cols-3">
                    <div class="flex lg:justify-center lg:col-start-2">
                        <img src="/img/complete-square-logo.png"/>
                    </div>
                    <nav v-if="canLogin" class="-mx-3 flex flex-1 justify-end">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:focus-visible:ring-white"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:focus-visible:ring-white"
                            >
                                Log in
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:focus-visible:ring-white"
                            >
                                Register
                            </Link>
                        </template>
                    </nav>
                </header>

                <main class="mt-6">
                    <div class="grid gap-6 lg:grid-cols-2 lg:gap-8">
                        <a
                            href="/tasks"
                            id="docs-card"
                            class="flex flex-col items-start gap-6 overflow-hidden rounded-lg shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)]
                            ring-1 ring-white/[0.05] transition duration-300 hover:text-gray-400/70 hover:ring-black/20 focus:outline-none
                            focus-visible:ring-[#FF2D20] md:row-span-3 lg:p-10 lg:pb-10 ring-zinc-800"
                        >
                            <div id="screenshot-container" class="relative flex w-full flex-1 items-stretch">
                                <img
                                    src="/img/screens/tasks.png"
                                    alt="Laravel documentation screenshot"
                                    class=" aspect-video h-full w-full flex-1 rounded-[10px] object-top object-cover drop-shadow-[0px_4px_34px_rgba(0,0,0,0.25)] block"
                                />
                                <div
                                    class="absolute -bottom-16 -left-16 h-40 w-[calc(100%+8rem)] bg-gradient-to-b from-transparent via-white to-white dark:via-green-600/30 dark:to-green-700/40"
                                ></div>
                            </div>

                            <div class="relative flex items-center gap-6 lg:items-end">
                                <div id="docs-card-content" class="flex items-start gap-6 lg:flex-col">
                                    <div
                                        class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#FF2D20]/10 sm:size-16">
                                        <div
                                            class="flex size-12 shrink-0 items-center justify-center rounded-full bg-green-800/30 sm:size-16">
                                            <img src="/img/hat.png" alt="Add Tasks for Today">
                                        </div>
                                    </div>

                                    <div class="pt-3 sm:pt-5 lg:pt-0">
                                        <h2 class="text-xl font-semibold text-black dark:text-white">Tasks</h2>

                                        <p class="mt-4 text-sm/relaxed">
                                            Effortless Task Management Made Simple
                                            Tasks, the easiest way to stay organized and on top of your day. With a
                                            focus on simplicity and efficiency, our smart task management tool helps you
                                            easily add, track, and manage your tasks—no hassle, no complexity. </p>
                                    </div>
                                </div>

                                <svg class="size-6 shrink-0 stroke-green-800/50" xmlns="http://www.w3.org/2000/svg"
                                     fill="none" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/>
                                </svg>
                            </div>
                        </a>

                        <div
                            class="flex items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] lg:pb-10 dark:ring-zinc-800">
                            <div
                                class="flex size-12 shrink-0 items-center justify-center rounded-full bg-green-800/30 sm:size-16">
                                <img src="/img/hat.png" alt="Add Tasks for Today">
                            </div>
                            <div class="pt-3 sm:pt-5">
                                <h2 class="text-xl font-semibold text-black">Add Tasks for Today</h2>

                                <p class="mt-4 text-sm/relaxed">
                                    Quickly capture tasks that need to be done today, ensuring you never forget what’s
                                    on your plate.
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] lg:pb-10 dark:ring-zinc-800">
                            <div
                                class="flex size-12 shrink-0 items-center justify-center rounded-full bg-green-800/30 sm:size-16">
                                <img src="/img/hat.png" alt="Mark Tasks as Done">
                            </div>
                            <div class="pt-3 sm:pt-5">
                                <h2 class="text-xl font-semibold text-black">Mark Tasks as Done</h2>

                                <p class="mt-4 text-sm/relaxed">
                                    Check off completed tasks with a single click, instantly clearing your mind and
                                    keeping you motivated.
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] lg:pb-10 dark:ring-zinc-800">
                            <div
                                class="flex size-12 shrink-0 items-center justify-center rounded-full bg-green-800/30 sm:size-16">
                                <img src="/img/hat.png" alt="Recurring Tasks">
                            </div>
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
    </div>
</template>
