<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { decryptWithAes } from '@/utils/crypto/encryptionUtils';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, ShieldCheck } from 'lucide-vue-next';
import axios from 'axios';

const form = useForm({
    otp: '',
});

const submit = async () => {
    form.processing = true;
    form.clearErrors();

    try {
        const verifyResponse = await axios.post(route('2fa.verify'), {
            otp: form.otp,
        });

        if (verifyResponse.data.success) {
            // Get the temporary password hash from session storage
            const passwordHash = sessionStorage.getItem('tempPasswordHash');

            if (passwordHash && verifyResponse.data.private_key) {
                const encryptedPrivateKey = JSON.parse(verifyResponse.data.private_key);
                const decryptedPrivateKey = await decryptWithAes(encryptedPrivateKey, passwordHash);

                sessionStorage.setItem('privateKey', decryptedPrivateKey);
                sessionStorage.removeItem('tempPasswordHash');
            }

            window.location.href = verifyResponse.data.redirect_url;
        }
    } catch (error: any) {
        if (error.response?.data?.errors?.otp) {
            form.setError('otp', error.response.data.errors.otp[0]);
        } else {
            form.setError('otp', 'Invalid verification code. Please try again.');
        }
        form.reset('otp');
    } finally {
        form.processing = false;
    }
};
</script>

<template>
    <AuthBase title="Two-Factor Authentication" description="Enter the 6-digit code from your authenticator app">
        <Head title="Verify 2FA Code" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <!-- Icon -->
                <div class="flex justify-center">
                    <div class="rounded-full bg-primary/10 p-4">
                        <ShieldCheck class="h-12 w-12 text-primary" />
                    </div>
                </div>

                <!-- OTP Input -->
                <div class="grid gap-2">
                    <Label for="otp">Authentication Code</Label>
                    <Input
                        id="otp"
                        type="text"
                        required
                        autofocus
                        maxlength="6"
                        pattern="[0-9]{6}"
                        v-model="form.otp"
                        placeholder="000000"
                        class="text-center text-2xl"
                        :disabled="form.processing"
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.otp" />
                </div>

                <Button type="submit" class="w-full" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Verify Code
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Having trouble?
                <TextLink :href="route('logout')" method="post" as="button" type="button">
                    Log out
                </TextLink>
            </div>
        </form>
    </AuthBase>
</template>
