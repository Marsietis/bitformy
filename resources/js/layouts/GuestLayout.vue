<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <AppShell>
        <AppContent>
            <div class="mt-8 flex justify-center">
                <Link :href="route('home')">
                    <div class="grid flex-1 text-left text-sm">
                        <span class="font-serif text-7xl text-primary">Bitformy</span>
                    </div>
                </Link>
            </div>
            <slot />
        </AppContent>
    </AppShell>
</template>
