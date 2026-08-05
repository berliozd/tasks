<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div>
            <slot name="logo" />
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white shadow-card-hover ring-1 ring-slate-900/[0.06] overflow-hidden sm:rounded-2xl">
            <slot />
        </div>
    </div>
</template>
