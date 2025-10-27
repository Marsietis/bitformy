<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    qrImage: string;
    secret: string;
}>();

const form = useForm({
    otp: '',
});

const submit = () => {
    form.post(route('2fa.setup'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <AuthBase title="Set Up Two-Factor Authentication" description="Scan the QR code with your authenticator app and enter the verification code">
        <Head title="Two-Factor Authentication" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <!-- QR Code Display -->
                <div class="flex flex-col items-center gap-4">
                    <div class="rounded-lg border border-border bg-muted p-4">
                        <img
                            :src="qrImage"
                            alt="QR Code"
                            class="h-48 w-48"
                        />
                    </div>

                    <!-- Secret Key -->
                    <div class="w-full space-y-2">
                        <p class="text-sm text-muted-foreground text-center">
                            Or enter this code manually in your authenticator app:
                        </p>
                        <div class="rounded-md bg-muted px-3 py-2 text-center font-mono text-sm break-all">
                            {{ secret }}
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="space-y-2 rounded-lg border border-border bg-muted/50 p-4">
                    <h3 class="font-semibold text-sm">Instructions:</h3>
                    <ol class="list-decimal list-inside space-y-1 text-sm text-muted-foreground">
                        <li>Install an authenticator app (Aegis, Proton Authenticator, Bitwarden authenticator, Microsoft Authenticator, etc.)</li>
                        <li>Scan the QR code or enter the secret key manually</li>
                        <li>Enter the 6-digit verification code below</li>
                    </ol>
                </div>

                <!-- OTP Input -->
                <div class="grid gap-2">
                    <Label for="otp">Verification Code</Label>
                    <Input
                        id="otp"
                        type="text"
                        required
                        autofocus
                        maxlength="6"
                        pattern="[0-9]{6}"
                        v-model="form.otp"
                        placeholder="000000"
                        class="text-center text-lg tracking-widest"
                    />
                    <InputError :message="form.errors.otp" />
                </div>

                <Button type="submit" class="w-full" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Verify and Enable 2FA
                </Button>
            </div>
        </form>
    </AuthBase>
</template>
