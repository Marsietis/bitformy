<script setup lang="js">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import * as openpgp from 'openpgp';
import * as XLSX from 'xlsx';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

const props = defineProps({
    answers: Array,
    questions: Array,
    form: Object,
});

const privateKeyArmored = ref(null);
const decryptedSubmissions = ref([]);
const isLoading = ref(true);
const errorMessages = ref([]);

// Export options
const exportFormat = ref('csv');
const exportType = ref('decrypted');
const showExportOptions = ref(false);

const breadcrumbs = computed(() => [
    {
        title: 'Forms',
        href: '/dashboard',
    },
    {
        title: props.form.title,
        href: `/form/${props.form.id}`,
    },
    {
        title: 'Answers',
        href: `/form/${props.form.id}/answers`,
    },
]);

// Get the title of a question by its ID
const getQuestionTitle = (questionId) => {
    const question = props.questions.find(question => question.id === questionId);
    return question?.title ?? 'Unknown Question';
};

// Export functions
const downloadFile = (content, filename, mimeType) => {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};

const getExportData = () => {
    if (exportType.value === 'decrypted') {
        // Format decrypted data for export
        const data = [];
        decryptedSubmissions.value.forEach(submission => {
            submission.answers.forEach(answer => {
                data.push({
                    'Submission ID': submission.id,
                    'Submitted At': submission.submittedAt,
                    'Question': answer.questionTitle,
                    'Answer': answer.answer
                });
            });
        });
        return data;
    } else {
        // Format encrypted data for export
        const data = [];
        props.answers.forEach(answer => {
            data.push({
                'Submission ID': answer.submission_id,
                'Question ID': answer.question_id,
                'Question': getQuestionTitle(answer.question_id),
                'Encrypted Answer': answer.answer,
                'Created At': answer.created_at,
                'Updated At': answer.updated_at
            });
        });
        return data;
    }
};

const exportToCSV = () => {
    const data = getExportData();
    if (data.length === 0) {
        alert('No data to export');
        return;
    }

    const headers = Object.keys(data[0]);
    const csvContent = [
        headers.join(','),
        ...data.map(row => 
            headers.map(header => {
                const cell = row[header] || '';
                // Escape quotes and wrap in quotes if contains comma, quote, or newline
                return /[",\n\r]/.test(cell) 
                    ? `"${cell.replace(/"/g, '""')}"` 
                    : cell;
            }).join(',')
        )
    ].join('\n');

    const filename = `${props.form.title}_answers_${exportType.value}.csv`;
    downloadFile(csvContent, filename, 'text/csv');
};

const exportToXLS = () => {
    const data = getExportData();
    if (data.length === 0) {
        alert('No data to export');
        return;
    }

    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Answers');
    
    const filename = `${props.form.title}_answers_${exportType.value}.xlsx`;
    XLSX.writeFile(wb, filename);
};

const exportToJSON = () => {
    const data = exportType.value === 'decrypted' 
        ? decryptedSubmissions.value 
        : props.answers;
    
    if (data.length === 0) {
        alert('No data to export');
        return;
    }

    const jsonContent = JSON.stringify(data, null, 2);
    const filename = `${props.form.title}_answers_${exportType.value}.json`;
    downloadFile(jsonContent, filename, 'application/json');
};

const handleExport = () => {
    if (exportType.value === 'decrypted' && decryptedSubmissions.value.length === 0) {
        alert('No decrypted data available. Please wait for decryption to complete.');
        return;
    }

    switch (exportFormat.value) {
        case 'csv':
            exportToCSV();
            break;
        case 'xls':
            exportToXLS();
            break;
        case 'json':
            exportToJSON();
            break;
        default:
            alert('Unknown export format');
    }
    
    showExportOptions.value = false;
};

const decryptAnswer = async (encryptedAnswer, privateKey) => {
    try {
        const message = await openpgp.readMessage({ armoredMessage: encryptedAnswer });
        const { data: decryptedData } = await openpgp.decrypt({
            message,
            decryptionKeys: privateKey,
        });
        return decryptedData;
    } catch (e) {
        console.error('Decryption failed for an answer:', e);
        errorMessages.value.push(`Failed to decrypt an answer: ${e.message}`);
        return '[Decryption Failed]';
    }
};

const processAnswers = async () => {
    isLoading.value = true;
    errorMessages.value = [];
    const pkArmored = sessionStorage.getItem('privateKey');
    if (!pkArmored) {
        errorMessages.value.push('Private key not found in session storage. Please log in again.');
        isLoading.value = false;
        return;
    }
    privateKeyArmored.value = pkArmored;

    let privateKey;
    try {
        privateKey = await openpgp.readPrivateKey({ armoredKey: privateKeyArmored.value });
    } catch (e) {
        const errorMessage = 'Unable to read your encryption key. This usually happens when your login session has expired. Please try logging out and logging back in to fix this issue.';
        errorMessages.value.push(errorMessage);
        isLoading.value = false;
        return;
    }

    // Group answers by submission_id
    const submissionsMap = new Map();
    for (const answer of props.answers) {
        if (!submissionsMap.has(answer.submission_id)) {
            submissionsMap.set(answer.submission_id, []);
        }
        submissionsMap.get(answer.submission_id)?.push(answer);
    }

    const processedSubmissions = [];
    for (const [submissionId, answersInSubmission] of submissionsMap.entries()) {
        const decryptedAnswersForSubmission = [];
        let submissionTime = 'N/A';

        for (const encAnswer of answersInSubmission) {
            if (encAnswer.answer) {
                const decryptedText = await decryptAnswer(encAnswer.answer, privateKey);
                decryptedAnswersForSubmission.push({
                    questionTitle: getQuestionTitle(encAnswer.question_id),
                    answer: decryptedText,
                });
            }
            if (answersInSubmission.length > 0) {
                submissionTime = new Date(encAnswer.created_at).toLocaleString();
            }
        }
        processedSubmissions.push({
            id: submissionId,
            answers: decryptedAnswersForSubmission,
            submittedAt: submissionTime,
        });
    }

    decryptedSubmissions.value = processedSubmissions.sort((a, b) => new Date(b.submittedAt).getTime() - new Date(a.submittedAt).getTime());
    isLoading.value = false;
};

// Close export options when clicking outside
const closeExportOptions = (event) => {
    if (!event.target.closest('.export-dropdown')) {
        showExportOptions.value = false;
    }
};

onMounted(() => {
    processAnswers();
    document.addEventListener('click', closeExportOptions);
});

// Clean up event listener
onUnmounted(() => {
    document.removeEventListener('click', closeExportOptions);
});

</script>

<template>
    <Head :title="`Answers for ${form.title}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50/50">
            <div class="mx-auto max-w-5xl p-6">
                <!-- Header Card -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-8 mb-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Answers for {{ form.title }}</h1>
                            <p v-if="form.description" class="mt-3 text-gray-600 text-lg">{{ form.description }}</p>
                        </div>
                        
                        <!-- Export Dropdown -->
                        <div class="relative export-dropdown">
                            <Button 
                                @click="showExportOptions = !showExportOptions"
                                :disabled="isLoading"
                                variant="outline"
                                class="gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Export Answers
                            </Button>
                            
                            <!-- Export Options Modal -->
                            <div v-if="showExportOptions" class="absolute right-0 top-full mt-2 w-80 bg-white border border-gray-200 rounded-xl shadow-lg z-10">
                                <div class="p-6">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Export Options</h3>
                                    
                                    <!-- Format Selection -->
                                    <div class="mb-6">
                                        <Label class="block text-sm font-medium text-gray-700 mb-3">Export Format</Label>
                                        <div class="space-y-2">
                                            <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                                                <input v-model="exportFormat" type="radio" value="csv" class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                                                <span class="text-sm text-gray-700">CSV (.csv)</span>
                                            </label>
                                            <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                                                <input v-model="exportFormat" type="radio" value="xls" class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                                                <span class="text-sm text-gray-700">Excel (.xlsx)</span>
                                            </label>
                                            <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                                                <input v-model="exportFormat" type="radio" value="json" class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                                                <span class="text-sm text-gray-700">JSON (.json)</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Content Type Selection -->
                                    <div class="mb-6">
                                        <Label class="block text-sm font-medium text-gray-700 mb-3">Content Type</Label>
                                        <div class="space-y-2">
                                            <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                                                <input v-model="exportType" type="radio" value="decrypted" class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                                                <span class="text-sm text-gray-700">Decrypted answers (human readable)</span>
                                            </label>
                                            <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                                                <input v-model="exportType" type="radio" value="encrypted" class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                                                <span class="text-sm text-gray-700">Encrypted answers (raw data)</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="flex gap-3">
                                        <Button 
                                            @click="handleExport"
                                            class="flex-1"
                                        >
                                            Download
                                        </Button>
                                        <Button 
                                            @click="showExportOptions = false"
                                            variant="outline"
                                            class="flex-1"
                                        >
                                            Cancel
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="isLoading" class="rounded-xl border border-gray-200 bg-white shadow-sm p-12 text-center">
                    <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <p class="text-lg text-gray-600">Loading and decrypting answers...</p>
                </div>

                <!-- Error Messages -->
                <div v-if="errorMessages.length > 0" class="rounded-xl border border-red-200 bg-red-50 p-6 mb-6">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-red-800">Error occurred</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li v-for="(msg, index) in errorMessages" :key="index">{{ msg }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Submissions State -->
                <div v-if="!isLoading && decryptedSubmissions.length === 0 && errorMessages.length === 0" class="rounded-xl border border-gray-200 bg-white shadow-sm p-12 text-center">
                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No submissions yet</h3>
                    <p class="text-gray-600">No submissions found for this form yet.</p>
                </div>

                <!-- Submissions List -->
                <div v-if="!isLoading && decryptedSubmissions.length > 0" class="space-y-6">
                    <div v-for="submission in decryptedSubmissions" :key="submission.id" class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Submission ID: {{ submission.id.substring(0, 8) }}...</h2>
                                    <p class="text-sm text-gray-500 mt-1">Submitted at: {{ submission.submittedAt }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="space-y-6">
                                <div v-for="(decryptedAnswer, index) in submission.answers" :key="index" class="border-b border-gray-100 pb-6 last:border-b-0 last:pb-0">
                                    <Label class="block text-sm font-medium text-gray-900 mb-2">
                                        {{ decryptedAnswer.questionTitle }}
                                    </Label>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <p class="text-gray-700 whitespace-pre-wrap">{{ decryptedAnswer.answer }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
