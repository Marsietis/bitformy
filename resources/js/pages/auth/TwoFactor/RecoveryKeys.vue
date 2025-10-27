<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Check, Copy, Download, Key, Shield } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    recoveryKeys: string[];
}>();

const copied = ref(false);

const copyToClipboard = (keys: string[]) => {
    const text = keys.join('\n');
    navigator.clipboard.writeText(text);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};

const downloadKeys = (keys: string[]) => {
    const text = keys.join('\n');
    const blob = new Blob([text], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `recovery-keys-${new Date().toISOString().split('T')[0]}.txt`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};

const goToDashboard = () => {
    window.location.href = route('dashboard');
};
</script>

<template>
    <AuthBase
        title="Recovery Keys Generated"
        description="Save these recovery keys in a secure location. You can use them to access your account if you lose your authenticator device."
    >
        <Head title="Recovery Keys" />

        <div class="flex flex-col gap-6">
            <div class="grid gap-6">

                <!-- Warning Message -->
                <div class="space-y-2 rounded-lg border border-amber-500/50 bg-amber-500/10 p-4">
                    <div class="flex items-start gap-3">
                        <Shield class="h-5 w-5 text-amber-600 dark:text-amber-500 mt-0.5 flex-shrink-0" />
                        <div class="space-y-1">
                            <h3 class="font-semibold text-sm text-amber-900 dark:text-amber-200">Important Security Notice</h3>
                            <p class="text-sm text-amber-800 dark:text-amber-300">
                                Store these recovery keys in a safe place. Each key can only be used once. You won't be able to see them again after leaving this page.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Recovery Keys Display -->
                <div class="space-y-3">
                    <div class="rounded-lg border border-border bg-muted p-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div
                                v-for="(key, index) in recoveryKeys"
                                :key="index"
                                class="rounded-md bg-background px-3 py-2 font-mono text-sm break-all border border-border"
                            >
                                <span class="text-muted-foreground">{{ index + 1 }}.</span> {{ key }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        class="w-full"
                        @click="copyToClipboard(recoveryKeys)"
                    >
                        <Check v-if="copied" class="h-4 w-4" />
                        <Copy v-else class="h-4 w-4" />
                        {{ copied ? 'Copied!' : 'Copy to Clipboard' }}
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        class="w-full"
                        @click="downloadKeys(recoveryKeys)"
                    >
                        <Download class="h-4 w-4" />
                        Download as File
                    </Button>
                </div>

                <!-- Instructions -->
                <div class="space-y-2 rounded-lg border border-border bg-muted/50 p-4">
                    <h3 class="font-semibold text-sm">How to use recovery keys:</h3>
                    <ol class="list-decimal list-inside space-y-1 text-sm text-muted-foreground">
                        <li>Save these keys in a secure password manager or safe location</li>
                        <li>Each recovery key can be used only once</li>
                        <li>Use a recovery key if you lose access to your authenticator app</li>
                        <li>Generate new recovery keys if you run out</li>
                    </ol>
                </div>

                <!-- Continue Button -->
                <Button
                    type="button"
                    class="w-full"
                    @click="goToDashboard"
                >
                    I've Saved My Recovery Keys
                </Button>
            </div>
        </div>
    </AuthBase>
</template>
