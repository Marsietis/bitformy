<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
</script>

<template>
    <AppShell>
        <AppContent>
            <slot />
        </AppContent>
    </AppShell>
</template> 