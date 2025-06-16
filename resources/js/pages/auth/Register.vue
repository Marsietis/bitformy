<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { encryptWithAes, generateSalt } from '@/utils/crypto/encryptionUtils';
import { argon2idHash, shaHash } from '@/utils/crypto/hashingUtils';
import { generatePgpKeys } from '@/utils/crypto/pgpKeyUtils';
import { validatePassword } from '@/utils/passwordValidation';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = async () => {
    form.clearErrors();

    const passwordError = validatePassword(form.password, form.password_confirmation);
    if (passwordError) {
        form.setError('password_confirmation', passwordError);
        return;
    }

    try {
        const salt = generateSalt();
        const passwordHash = await argon2idHash(form.password, salt);
        const { privateKey, publicKey } = await generatePgpKeys(form.name);
        const encryptedPrivateKey = JSON.stringify(await encryptWithAes(privateKey, passwordHash));
        const passwordValidator = await shaHash(passwordHash);

        form.transform((data) => ({
            name: data.name,
            email: data.email,
            password_validator: passwordValidator,
            salt: salt,
            private_key: encryptedPrivateKey,
            public_key: publicKey,
        })).post(route('register'), {
            onFinish: () => {
                form.reset('password', 'password_confirmation');
                sessionStorage.setItem('privateKey', privateKey);
            },
        });
    } catch (error) {
        console.error('Error:', error);
    }
};
</script>

<template>
    <AuthBase title="Create an account" description="Enter your details below to create your account">
        <Head title="Register" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        v-model="form.name"
                        placeholder="Full name"
                        :disabled="form.processing"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="email@example.com"
                        :disabled="form.processing"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Password"
                        :disabled="form.processing"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Confirm password"
                        :disabled="form.processing"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button type="submit" class="mt-2 w-full" tabindex="5" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="6"> Log in</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
