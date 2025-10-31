<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <AppShell>
        <AppContent>
            <div class="flex justify-center mt-8">
                <Link :href="route('home')">
                    <div class="grid flex-1 text-left text-sm">
                        <span class="font-bold text-black dark:text-white text-7xl">Bitformy</span>
                    </div>
                </Link>
            </div>
                <slot />
        </AppContent>
    </AppShell>
</template>
