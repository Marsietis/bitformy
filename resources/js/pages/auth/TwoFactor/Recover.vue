<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Key, LoaderCircle, Shield, ArrowLeft } from 'lucide-vue-next';

const form = useForm({
    recovery_key: '',
});

const submit = async () => {
    form.post(route('2fa.recover'), {
        onSuccess: () => {
            form.reset('recovery_key');
        },
        onError: () => {
            form.reset('recovery_key');
        }
    });
};
</script>

<template>
    <AuthBase
        title="Account Recovery"
        description="Enter one of your recovery keys to access your account"
    >
        <Head title="Account Recovery" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">

                <!-- Info Message -->
                <div class="space-y-2 rounded-lg border border-border bg-muted/50 p-4">
                    <div class="flex items-start gap-3">
                        <Shield class="h-5 w-5 text-muted-foreground mt-0.5 flex-shrink-0" />
                        <div class="space-y-1">
                            <h3 class="font-semibold text-sm">Using Recovery Keys</h3>
                            <p class="text-sm text-muted-foreground">
                                Enter one of your recovery keys that you saved when setting up two-factor authentication. Each key can only be used once.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Recovery Key Input -->
                <div class="grid gap-2">
                    <Label for="recovery_key">Recovery Key</Label>
                    <Input
                        id="recovery_key"
                        type="text"
                        required
                        autofocus
                        v-model="form.recovery_key"
                        placeholder="XXXXXXXX-XXXXXXXX"
                        class="font-mono text-center text-lg uppercase"
                        :disabled="form.processing"
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.recovery_key" />
                    <p class="text-xs text-muted-foreground text-center">
                        Format: XXXXXXXX-XXXXXXXX
                    </p>
                </div>

                <!-- Submit Button -->
                <Button type="submit" class="w-full" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Recover Account
                </Button>
            </div>

            <!-- Back to 2FA -->
            <div class="text-center text-sm text-muted-foreground">
                <TextLink :href="route('2fa.verify.form')">
                    <ArrowLeft class="h-4 w-4 inline" />
                    Back to 2FA verification
                </TextLink>
            </div>

            <!-- Logout Option -->
            <div class="text-center text-sm text-muted-foreground">
                Having trouble?
                <TextLink :href="route('logout')" method="post" as="button" type="button">
                    Log out
                </TextLink>
            </div>
        </form>
    </AuthBase>
</template>
