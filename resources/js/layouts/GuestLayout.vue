<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import { CheckCheck } from 'lucide-vue-next';
</script>

<template>
    <AppShell>
        <AppContent>
            <div class="mt-8 flex justify-center">
                <div class="flex items-center">
                    <CheckCheck class="mr-2 size-24 text-foreground" />
                    <span class="text-7xl font-bold text-foreground">Bitformy</span>
                </div>
            </div>
            <slot />
        </AppContent>
    </AppShell>
</template>
