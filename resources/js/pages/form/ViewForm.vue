<script setup lang="js">
    import AppLayout from '@/layouts/AppLayout.vue';
    import GuestLayout from '@/layouts/GuestLayout.vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import { ref, computed } from 'vue';

    const page = usePage();
    const user = computed(() => page.props.auth.user);

    const props = defineProps({
        form: Object,
        questions: Array,
    });

    const breadcrumbs = computed(() => {
        if (!user.value) {
            return [];
        }
        return [
            {
                title: 'Forms',
                href: '/dashboard',
            },
            {
                title: props.form.title,
                href: `/form/${props.form.id}`,
            },
        ];
    });

    const getOptions = (optionsString) => {
        if (!optionsString) return { items: [], multiple: false };
        return JSON.parse(optionsString);
    };

    const isFormCreator = computed(() => {
        return user.value && props.form.user_id === user.value.id;
    });

    const showSharePopover = ref(false);
    const formLink = computed(() => {
        if (typeof window !== 'undefined') {
            return `${window.location.origin}/form/${props.form.id}`;
        }
        return '';
    });
    const copyStatus = ref('Copy');

    const layout = computed(() => (user.value ? AppLayout : GuestLayout));

    const copyLink = () => {
        if (formLink.value) {
            navigator.clipboard.writeText(formLink.value)
                .then(() => {
                    copyStatus.value = 'Copied!';
                    setTimeout(() => {
                        copyStatus.value = 'Copy';
                        showSharePopover.value = false;
                    }, 2000);
                })
                .catch(err => {
                    console.error('Failed to copy: ', err);
                    copyStatus.value = 'Failed';
                    setTimeout(() => {
                        copyStatus.value = 'Copy';
                    }, 2000);
                });
        }
    };
</script>

<template>
    <Head :title="form.title" />

    <component :is="layout" :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50/50">
            <div class="mx-auto max-w-5xl p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">{{ form.title }}</h1>
                            <p v-if="form.description" class="mt-3 text-gray-600">{{ form.description }}</p>
                        </div>
                        <div v-if="isFormCreator" class="relative">
                            <button @click="showSharePopover = !showSharePopover" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                Share
                            </button>
                            <div v-if="showSharePopover" class="absolute right-0 mt-2 w-72 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                <div class="p-4">
                                    <p class="text-sm font-medium text-gray-900">Share form</p>
                                    <p class="mt-1 text-sm text-gray-500">Anyone with the link can view and submit this form.</p>
                                    <div class="mt-4 flex rounded-md shadow-sm">
                                        <input type="text" :value="formLink" readonly class="block w-full flex-1 rounded-none rounded-l-md border-gray-300 bg-gray-50 focus:border-primary focus:ring-primary sm:text-sm">
                                        <button @click="copyLink" type="button" class="relative -ml-px inline-flex items-center space-x-2 rounded-r-md border border-gray-300 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                                            <span>{{ copyStatus }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 mt-4 text-sm text-muted-foreground">
                        <span v-if="form.created_at">
                            Created: {{ new Date(form.created_at).toLocaleDateString() }}
                        </span>
                        <span v-if="form.updated_at">
                            Updated: {{ new Date(form.updated_at).toLocaleDateString() }}
                        </span>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div v-if="questions && questions.length > 0" class="space-y-8">
                        <div
                            v-for="(question, index) in questions"
                            :key="question.id"
                            class="border-b border-gray-200 pb-6 last:border-b-0"
                        >
                            <div class="mb-4">
                                <div class="flex items-start">
                                    <span class="inline-flex items-center justify-center w-8 h-8 bg-primary/10 text-primary rounded-full mr-3 font-medium">
                                        {{ index + 1 }}
                                    </span>
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900">
                                            {{ question.title }}
                                            <span v-if="question.required" class="text-red-500 ml-1">*</span>
                                        </h3>
                                    </div>
                                </div>

                                <!-- Text question type -->
                                <div v-if="question.type === 'text'" class="mt-4 pl-11">
                                    <input
                                        type="text"
                                        :required="question.required"
                                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors duration-200"
                                        :placeholder="question.required ? 'Your answer (required)' : 'Your answer'"
                                    />
                                </div>

                                <!-- Choice question type -->
                                <div v-else-if="question.type === 'choice'" class="mt-4 pl-11">
                                    <div v-if="getOptions(question.options).items.length > 0" class="space-y-3">
                                        <div
                                            v-for="(option, optionIndex) in getOptions(question.options).items"
                                            :key="optionIndex"
                                            class="flex items-center"
                                        >
                                            <input
                                                :type="getOptions(question.options).multiple ? 'checkbox' : 'radio'"
                                                :id="`q${index}_option${optionIndex}`"
                                                :name="`question_${question.id}`"
                                                :value="option"
                                                class="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                                                :required="question.required && !getOptions(question.options).multiple"
                                            />
                                            <label
                                                :for="`q${index}_option${optionIndex}`"
                                                class="ml-2 block text-gray-700"
                                            >
                                                {{ option }}
                                            </label>
                                        </div>
                                    </div>
                                    <div v-else class="text-gray-500 italic">No options available</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8">
                        <p class="text-gray-600">This form has no questions.</p>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>