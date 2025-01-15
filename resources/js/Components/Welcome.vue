<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import {usePage} from "@inertiajs/vue3";
</script>

<template>
    <div>
        <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
            <h2 class="text-5xl w-full items-center text-center">🚧 In progress! 🚧</h2>
            <ApplicationLogo class="block w-72"/>

            <h1 class="mt-8 text-2xl font-medium text-gray-900">
                Welcome to your smart tasks management tool!
            </h1>

            <p class="mt-6 text-gray-500 leading-relaxed">
                Effortless Task Management Made Simple
                <br/>
                {{ usePage().props.appName }}, the easiest way to stay organized and on top of your day. With
                a focus on
                simplicity and efficiency, our smart task management tool helps you easily add, track, and manage your
                tasks—no hassle, no complexity.

            </p>
        </div>

        <div class="bg-gray-200 bg-opacity-25 gap-6 lg:gap-8 p-6 lg:p-8">
            <div>
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         class="size-6 stroke-gray-400">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                    </svg>
                    <h2 class="ms-3 text-xl font-semibold text-gray-900">
                        Key Features:
                    </h2>
                </div>

                <ul class="mt-4 text-gray-500 text-sm leading-relaxed list-disc list-inside">
                    <li>
                        <span class="font-semibold text-gray-900">Add Tasks for Today:</span> Quickly capture tasks
                        that need to be done today, ensuring you
                        never forget
                        what’s on your plate.
                    </li>
                    <li>
                        <span class="font-semibold text-gray-900">Mark Tasks as Done:</span> Check off completed tasks
                        with a single click, instantly
                        clearing your mind
                        and keeping you motivated.
                    </li>
                    <li>
                        <span class="font-semibold text-gray-900">Recurring Tasks:</span> Make repetitive tasks a breeze
                        by setting them to recur
                        automatically, ensuring
                        nothing falls through the cracks.
                    </li>

                </ul>

                <p class="mt-4 text-sm">
                    <a href="/tasks" class="inline-flex items-center font-semibold text-green-800/50">
                        Try it now
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                             class="ms-1 size-5 fill-green-800/50">
                            <path fill-rule="evenodd"
                                  d="M5 10a.75.75 0 01.75-.75h6.638L10.23 7.29a.75.75 0 111.04-1.08l3.5 3.25a.75.75 0 010 1.08l-3.5 3.25a.75.75 0 11-1.04-1.08l2.158-1.96H5.75A.75.75 0 015 10z"
                                  clip-rule="evenodd"/>
                        </svg>
                    </a>
                </p>
            </div>
        </div>
    </div>
</template>
