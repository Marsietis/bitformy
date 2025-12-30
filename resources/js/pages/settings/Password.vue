<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem, type User } from '@/types';
import Disable2fa from '@/components/Disable2fa.vue';
import { argon2idHash, shaHash } from '@/utils/crypto/hashingUtils';
import { encryptWithAes, generateSalt } from '@/utils/crypto/encryptionUtils';
import { validatePassword } from '@/utils/passwordValidation';
import axios from 'axios';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Security settings',
        href: '/settings/security',
    },
];

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);

const props = defineProps({
    userHas2fa: Boolean,
});

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const page = usePage();
const user = page.props.auth.user as User;

const updatePassword = async () => {
    form.clearErrors();


    const passwordError = validatePassword(form.password, form.password_confirmation);
    if (passwordError) {
        form.setError('password_confirmation', passwordError);
        return;
    }

    form.processing = true;

    try {
        const saltResponse = await axios.post(route('pull-salt'), {
            email: user.email,
        });
        const currentSalt = saltResponse.data.salt;

        const currentPasswordHash = await argon2idHash(form.current_password, currentSalt);
        const currentPasswordValidator = await shaHash(currentPasswordHash);

        const privateKey = sessionStorage.getItem('privateKey');
        if (!privateKey) {
            form.setError('current_password', 'Session expired. Please log in again.');
            return;
        }

        const newSalt = generateSalt();
        const newPasswordHash = await argon2idHash(form.password, newSalt);
        const newPasswordValidator = await shaHash(newPasswordHash);

        const encryptedPrivateKey = JSON.stringify(await encryptWithAes(privateKey, newPasswordHash));

        form.transform(() => ({
            current_password_validator: currentPasswordValidator,
            password_validator: newPasswordValidator,
            salt: newSalt,
            private_key: encryptedPrivateKey,
        })).put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
            },
            onError: (errors: any) => {
                if (errors.password) {
                    form.reset('password', 'password_confirmation');
                    if (passwordInput.value instanceof HTMLInputElement) {
                        passwordInput.value.focus();
                    }
                }

                if (errors.current_password_validator || errors.current_password) {
                    form.reset('current_password');
                    if (currentPasswordInput.value instanceof HTMLInputElement) {
                        currentPasswordInput.value.focus();
                    }
                    if (errors.current_password_validator) {
                        form.setError('current_password', errors.current_password_validator);
                    }
                }
            },
        });
    } catch (error: any) {
        console.error('Error updating password:', error);
        form.setError('current_password', 'An error occurred. Please try again.');
    } finally {
        form.processing = false;
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Security settings" />

        <SettingsLayout>
            <div>
                <div class="grid gap-4" v-if="!$page.props.userHas2fa">
                <HeadingSmall title="Two-Factor Authentication" description="Add another layer of security to your account. You’ll need to verify yourself with 2FA every time you sign in." />
                <a :href="route('2fa.show')"><Button>Set up 2FA</Button></a>
                </div>
                <div v-else><Disable2fa /></div>
            </div>
            <div class="space-y-6">
                <HeadingSmall title="Update password" description="Ensure your account is using a long, random password to stay secure" />

                <form @submit.prevent="updatePassword" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="current_password">Current password</Label>
                        <Input
                            id="current_password"
                            ref="currentPasswordInput"
                            v-model="form.current_password"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="current-password"
                            placeholder="Current password"
                        />
                        <InputError :message="form.errors.current_password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">New password</Label>
                        <Input
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            placeholder="New password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">Confirm password</Label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            placeholder="Confirm password"
                        />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save password</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
