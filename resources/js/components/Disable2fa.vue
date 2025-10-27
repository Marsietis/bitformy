<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

// Components
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import axios from 'axios';
import { argon2idHash, shaHash } from '@/utils/crypto/hashingUtils';
import type { User } from '@/types';

const passwordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    password: '',
});

const page = usePage();
const user = page.props.auth.user as User;
const disable2fa = async (e: Event) => {
    e.preventDefault();

    try {
        const response = await axios.post(route('pull-salt'), {
            email: user.email,
        });

        const salt = response.data.salt;
        const passwordHash = await argon2idHash(form.password, salt);
        const passwordValidator = await shaHash(passwordHash);

        form.transform(() => ({
            password: passwordValidator,
        })).post(route('2fa.disable'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.value?.focus,
            onFinish: () => form.reset(),
        });
    } catch (error) {
        console.error('Error during 2FA disable:', error);
        form.setError('password', 'An error occurred. Please try again.');
        passwordInput.value?.focus();
    }
};

const closeModal = () => {
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <div class="space-y-6">
        <HeadingSmall
            title="Disable two-factor authentication"
            description="You currently have two-factor authentication if you wish to disable it, click the button below"
        />
        <div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive">Disable 2FA</Button>
                </DialogTrigger>
                <DialogContent>
                    <form class="space-y-6" @submit="disable2fa">
                        <DialogHeader class="space-y-3">
                            <DialogTitle>Are you sure you want to disable two-factor authentication?</DialogTitle>
                            <DialogDescription>
                                Disabling two-factor authentication will remove the additional layer of security from your account.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">Password</Label>
                            <Input id="password" type="password" name="password" ref="passwordInput" v-model="form.password" placeholder="Password" />
                            <InputError :message="form.errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary" @click="closeModal"> Cancel </Button>
                            </DialogClose>

                            <Button type="submit" variant="destructive" :disabled="form.processing"> Disable 2FA </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
