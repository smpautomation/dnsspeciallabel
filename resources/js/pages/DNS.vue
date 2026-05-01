<script lang="js" setup>
import { ref, onMounted, computed, watch, reactive } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    printSettings: {
        type: Object,
        default: () => ({
            SATO_ip_address: '',
            horizontal_offset: 0,
            vertical_offset: 0,
        })
    }
});

const page = usePage();
const successMessage = computed(() => page.props.flash?.success || null);
const errorMessage = computed(() => page.props.flash?.error || null);
const settingsSaved = computed(() => page.props.flash?.settingsSaved || null);

const form = useForm({
    fgcode: '',
    qty: '',
    barcode: '',
    sessionID: generateSessionID(),
    name: '',
    reason: ''
});

const fgcodeInput = ref(null);
const qtyInput = ref(null);
const barcodeInput = ref(null);

const showSuccess = ref(false);
const showError = ref(false);
const showReprintModal = ref(false);
const showSettingsDrawer = ref(false);
const settingsSaving = ref(false);
const showSettingsSaved = ref(false);

const reprintForm = ref({
    name: '',
    reason: '',
    fgcode: '',
    qty: '',
    barcode: '',
    sessionID: ''
});

const settingsForm = reactive({
    sato_ip: props.printSettings?.SATO_ip_address ?? '',
    h_offset: props.printSettings?.horizontal_offset ?? 0,
    v_offset: props.printSettings?.vertical_offset ?? 0,
});

const errors = reactive({
    fgcode: '',
    qty: '',
    barcode: ''
});

const settingsErrors = reactive({
    sato_ip: '',
    h_offset: '',
    v_offset: '',
});

const showErrors = (field, message) => {
    errors[field] = message;
    setTimeout(() => {
        errors[field] = '';
    }, 3000);
};

function generateSessionID() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

watch(errorMessage, (newValue) => {
    if (newValue) {
        showError.value = true;
        setTimeout(() => {
            showError.value = false;
        }, 5000);
    }
});

watch(settingsSaved, (newValue) => {
    if (newValue) {
        showSettingsSaved.value = true;
        setTimeout(() => {
            showSettingsSaved.value = false;
        }, 3000);
    }
});

watch(() => page.props.flash?.showReprintModal, (newValue) => {
    if (newValue) {
        reprintForm.value.fgcode = form.fgcode;
        reprintForm.value.qty = form.qty;
        reprintForm.value.barcode = form.barcode;
        reprintForm.value.sessionID = form.sessionID;

        showReprintModal.value = true;
    }
});

// Sync settings form if props change (e.g., after save)
watch(() => props.printSettings, (newVal) => {
    if (newVal) {
        settingsForm.sato_ip = newVal.SATO_ip_address ?? '';
        settingsForm.h_offset = newVal.horizontal_offset ?? 0;
        settingsForm.v_offset = newVal.vertical_offset ?? 0;
    }
}, { deep: true });

const validateFGCode = () => {
    if (form.fgcode === '') {
        showErrors('fgcode', 'Scan FG Code first');
        form.fgcode = '';
        form.qty = '';
        form.barcode = '';
        fgcodeInput.value?.focus();
        return false;
    }
    if(form.fgcode.length < 7){
        showErrors('fgcode', 'Scan correct FG Code');
        form.fgcode = '';
        form.qty = '';
        form.barcode = '';
        fgcodeInput.value?.focus();
        return false;
    }
    return true;
};

const validateQty = () => {
    if (form.qty === '') {
        showErrors('qty', 'Scan quantity');
        form.qty = '';
        form.barcode = '';
        qtyInput.value?.focus();
        return false;
    }
    if (isNaN(form.qty))  {
        showErrors('qty', 'Scan correct quantity');
        form.qty = '';
        form.barcode = '';
        qtyInput.value?.focus();
        return false;
    }
    return true;
};

const validateBarcode = () => {
    if(form.barcode === ''){
        showErrors('barcode', 'Scan package number');
        form.barcode = '';
        barcodeInput.value?.focus();
        return false;
    }
    if(form.barcode.length < 22 && form.barcode.length > 29 ){
        showErrors('barcode', 'Scan correct package number');
        form.barcode = '';
        barcodeInput.value?.focus();
        return false;
    }
    return true;
};

const handleEnter = (e, nextInputRef, validationFn) => {
    e.preventDefault();

    if (nextInputRef && nextInputRef.value) {
        nextInputRef.value.focus();
        setTimeout(() => {
            if (validationFn && !validationFn()) {
                return;
            }
        }, 100);
    } else {
        if (validationFn && !validationFn()) {
            return;
        }
        handleSubmit();
    }
};

const handleSubmit = () => {
    if (validateFGCode() && validateQty() && validateBarcode()) {
        form.name = '';
        form.reason = '';
        form.post('/print', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                form.name = '';
                form.reason = '';
                form.sessionID = generateSessionID();

                showSuccess.value = true;
                setTimeout(() => {
                    showSuccess.value = false;
                }, 5000);

                setTimeout(() => {
                    fgcodeInput.value?.focus();
                }, 100);
            },
            onError: (errors) => {
                if (errors.barcode) {
                    alert(errors.barcode);
                    form.barcode = '';
                    barcodeInput.value?.focus();
                }
                if (errors.fgcode) {
                    alert(errors.fgcode);
                    form.fgcode = '';
                    fgcodeInput.value?.focus();
                }
                if (errors.qty) {
                    alert(errors.qty);
                    form.qty = '';
                    qtyInput.value?.focus();
                }
            }
        });
    }
};

const handleReprintSubmit = () => {
    if (!reprintForm.value.name || !reprintForm.value.reason) {
        alert('Please fill in both Name ID and Reason');
        return;
    }

    form.fgcode = reprintForm.value.fgcode;
    form.qty = reprintForm.value.qty;
    form.barcode = reprintForm.value.barcode;
    form.sessionID = reprintForm.value.sessionID;
    form.name = reprintForm.value.name;
    form.reason = reprintForm.value.reason;

    form.post('/print', {
        preserveScroll: true,
        onSuccess: () => {
            showReprintModal.value = false;
            reprintForm.value = { name: '', reason: '', fgcode: '', qty: '', barcode: '', sessionID: '' };
            form.reset();
            form.sessionID = generateSessionID();

            showSuccess.value = true;
            setTimeout(() => {
                showSuccess.value = false;
            }, 5000);

            setTimeout(() => {
                fgcodeInput.value?.focus();
            }, 100);
        },
        onError: (errors) => {
            if (errors.name) {
                alert(errors.name);
            }
        }
    });
};

const closeReprintModal = () => {
    showReprintModal.value = false;
    reprintForm.value = { name: '', reason: '', fgcode: '', qty: '', barcode: '', sessionID: '' };
    form.reset();
    form.sessionID = generateSessionID();
    setTimeout(() => {
        fgcodeInput.value?.focus();
    }, 100);
};

const validateSettingsForm = () => {
    let valid = true;
    settingsErrors.sato_ip = '';
    settingsErrors.h_offset = '';
    settingsErrors.v_offset = '';

    const ipRegex = /^(\d{1,3}\.){3}\d{1,3}$/;
    if (!settingsForm.sato_ip || !ipRegex.test(settingsForm.sato_ip)) {
        settingsErrors.sato_ip = 'Enter a valid IP address (e.g. 192.168.1.100)';
        valid = false;
    }

    const hVal = parseInt(settingsForm.h_offset);
    if (isNaN(hVal) || hVal < -9999 || hVal > 9999) {
        settingsErrors.h_offset = 'Must be a number between -9999 and 9999';
        valid = false;
    }

    const vVal = parseInt(settingsForm.v_offset);
    if (isNaN(vVal) || vVal < -9999 || vVal > 9999) {
        settingsErrors.v_offset = 'Must be a number between -9999 and 9999';
        valid = false;
    }

    return valid;
};

const handleSaveSettings = () => {
    if (!validateSettingsForm()) return;

    settingsSaving.value = true;
    router.post('/settings/update', {
        sato_ip: settingsForm.sato_ip,
        h_offset: parseInt(settingsForm.h_offset),
        v_offset: parseInt(settingsForm.v_offset),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            settingsSaving.value = false;
        },
        onError: (errs) => {
            settingsSaving.value = false;
            if (errs.sato_ip) settingsErrors.sato_ip = errs.sato_ip;
            if (errs.h_offset) settingsErrors.h_offset = errs.h_offset;
            if (errs.v_offset) settingsErrors.v_offset = errs.v_offset;
        }
    });
};

const adjustOffset = (field, delta) => {
    settingsForm[field] = parseInt(settingsForm[field] || 0) + delta;
};

onMounted(() => {
    fgcodeInput.value?.focus();
});
</script>

<style scoped>
    .slide-fade-enter-active {
        transition: all 0.3s ease;
    }
    .slide-fade-leave-active {
        transition: all 0.2s ease;
    }
    .slide-fade-enter-from {
        transform: translateX(10px);
        opacity: 0;
    }
    .slide-fade-leave-to {
        transform: translateX(10px);
        opacity: 0;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    .animate-shake {
        animation: shake 0.3s ease-in-out;
    }

    .drawer-enter-active,
    .drawer-leave-active {
        transition: transform 0.3s ease;
    }
    .drawer-enter-from,
    .drawer-leave-to {
        transform: translateX(100%);
    }
</style>

<template>
    <Head title="DNS Special Label" />

    <div class="min-h-screen bg-linear-to-br from-slate-50 to-slate-100">

        <!-- ── Success Toast ── -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div v-if="showSuccess && successMessage" class="fixed top-4 right-4 z-50 max-w-md">
                <div class="bg-green-500 text-white px-6 py-4 rounded-lg shadow-2xl flex items-center space-x-3">
                    <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-semibold">{{ successMessage }}</span>
                    <button @click="showSuccess = false" class="ml-auto shrink-0 hover:bg-green-600 rounded p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </Transition>

        <!-- ── Error Toast ── -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div v-if="showError && errorMessage" class="fixed top-4 right-4 z-50 max-w-md">
                <div class="bg-red-500 text-white px-6 py-4 rounded-lg shadow-2xl flex items-center space-x-3">
                    <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-semibold">{{ errorMessage }}</span>
                    <button @click="showError = false" class="ml-auto shrink-0 hover:bg-red-600 rounded p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </Transition>

        <!-- ── Settings Saved Toast ── -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div v-if="showSettingsSaved" class="fixed top-4 left-1/2 -translate-x-1/2 z-50">
                <div class="bg-blue-600 text-white px-6 py-3 rounded-lg shadow-2xl flex items-center space-x-3">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-semibold text-sm">Print settings saved successfully!</span>
                </div>
            </div>
        </Transition>

        <!-- ── Reprint Modal ── -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showReprintModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeReprintModal"></div>
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 transform transition-all">
                        <button @click="closeReprintModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <div class="mb-6">
                            <div class="flex items-center justify-center w-12 h-12 bg-yellow-100 rounded-full mb-4">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900">Reprint Requirements</h3>
                            <p class="mt-2 text-sm text-gray-600">
                                This package has already been printed. Please provide your name ID and reason for reprinting.
                            </p>
                            <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <h4 class="text-xs font-semibold text-blue-900 mb-2">Package Details:</h4>
                                <div class="space-y-1 text-sm font-mono text-blue-800">
                                    <div><span class="font-semibold">FG Code:</span> {{ reprintForm.fgcode }}</div>
                                    <div><span class="font-semibold">Quantity:</span> {{ reprintForm.qty }}</div>
                                    <div><span class="font-semibold">Package #:</span> {{ reprintForm.barcode }}</div>
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="handleReprintSubmit" class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Scan Name ID Barcode <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    v-model="reprintForm.name"
                                    class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-900 font-mono uppercase"
                                    autocomplete="off"
                                    required
                                />
                            </div>
                            <div>
                                <label for="reason" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Reason for Reprinting <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    id="reason"
                                    v-model="reprintForm.reason"
                                    rows="3"
                                    placeholder="Enter reason for reprinting..."
                                    class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-900 resize-none"
                                    required
                                ></textarea>
                            </div>
                            <div class="flex space-x-3 pt-2">
                                <button type="button" @click="closeReprintModal"
                                    class="flex-1 px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="flex-1 px-4 py-3 bg-linear-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-lg shadow-lg transition-all">
                                    Submit Reprint
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── Settings Drawer Overlay ── -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showSettingsDrawer"
                class="fixed inset-0 bg-black bg-opacity-40 z-40"
                @click="showSettingsDrawer = false"
            ></div>
        </Transition>

        <!-- ── Settings Drawer ── -->
        <Transition name="drawer">
            <div
                v-if="showSettingsDrawer"
                class="fixed top-0 right-0 h-full w-80 bg-white shadow-2xl z-50 flex flex-col"
            >
                <!-- Drawer Header -->
                <div class="bg-linear-to-r from-slate-700 to-slate-800 px-6 py-5 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-white font-semibold text-sm">Print Settings</h2>
                            <p class="text-slate-300 text-xs">Adjust for your workstation</p>
                        </div>
                    </div>
                    <button
                        @click="showSettingsDrawer = false"
                        class="text-slate-300 hover:text-white hover:bg-white hover:bg-opacity-10 rounded-lg p-1.5 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Drawer Body -->
                <div class="flex-1 overflow-y-auto p-6 space-y-6">

                    <!-- IP Notice -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-start gap-2">
                        <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-xs text-blue-700">Settings are saved per workstation IP and will persist for future sessions.</p>
                    </div>

                    <!-- SATO Printer IP -->
                    <div class="space-y-2">
                        <label class="flex items-center text-sm font-semibold text-slate-700 gap-2">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            SATO Printer IP
                        </label>
                        <input
                            type="text"
                            v-model="settingsForm.sato_ip"
                            placeholder="e.g. 192.168.1.100"
                            :class="[
                                'w-full px-4 py-2.5 bg-slate-50 border-2 rounded-lg text-sm font-mono transition-colors',
                                settingsErrors.sato_ip
                                    ? 'border-red-400 focus:border-red-500'
                                    : 'border-slate-300 focus:border-blue-500'
                            ]"
                        />
                        <p v-if="settingsErrors.sato_ip" class="text-xs text-red-500 mt-1">{{ settingsErrors.sato_ip }}</p>
                    </div>

                    <!-- Horizontal Offset -->
                    <div class="space-y-2">
                        <label class="flex items-center text-sm font-semibold text-slate-700 gap-2">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8M8 12h8M8 17h8" />
                            </svg>
                            Horizontal Offset
                        </label>
                        <div class="flex items-center gap-2">
                            <button
                                @click="adjustOffset('h_offset', -10)"
                                class="w-9 h-9 flex items-center justify-center bg-slate-100 hover:bg-slate-200 rounded-lg text-slate-600 font-bold text-lg transition-colors select-none"
                                title="Decrease by 10"
                            >−</button>
                            <input
                                type="number"
                                v-model="settingsForm.h_offset"
                                :class="[
                                    'flex-1 px-3 py-2.5 bg-slate-50 border-2 rounded-lg text-sm font-mono text-center transition-colors',
                                    settingsErrors.h_offset
                                        ? 'border-red-400 focus:border-red-500'
                                        : 'border-slate-300 focus:border-blue-500'
                                ]"
                            />
                            <button
                                @click="adjustOffset('h_offset', 10)"
                                class="w-9 h-9 flex items-center justify-center bg-slate-100 hover:bg-slate-200 rounded-lg text-slate-600 font-bold text-lg transition-colors select-none"
                                title="Increase by 10"
                            >+</button>
                        </div>
                        <div class="flex gap-2">
                            <button @click="adjustOffset('h_offset', -1)"
                                class="flex-1 text-xs py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-md transition-colors">−1</button>
                            <button @click="adjustOffset('h_offset', 1)"
                                class="flex-1 text-xs py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-md transition-colors">+1</button>
                        </div>
                        <p v-if="settingsErrors.h_offset" class="text-xs text-red-500">{{ settingsErrors.h_offset }}</p>
                    </div>

                    <!-- Vertical Offset -->
                    <div class="space-y-2">
                        <label class="flex items-center text-sm font-semibold text-slate-700 gap-2">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8M8 12h8" />
                            </svg>
                            Vertical Offset
                        </label>
                        <div class="flex items-center gap-2">
                            <button
                                @click="adjustOffset('v_offset', -10)"
                                class="w-9 h-9 flex items-center justify-center bg-slate-100 hover:bg-slate-200 rounded-lg text-slate-600 font-bold text-lg transition-colors select-none"
                                title="Decrease by 10"
                            >−</button>
                            <input
                                type="number"
                                v-model="settingsForm.v_offset"
                                :class="[
                                    'flex-1 px-3 py-2.5 bg-slate-50 border-2 rounded-lg text-sm font-mono text-center transition-colors',
                                    settingsErrors.v_offset
                                        ? 'border-red-400 focus:border-red-500'
                                        : 'border-slate-300 focus:border-blue-500'
                                ]"
                            />
                            <button
                                @click="adjustOffset('v_offset', 10)"
                                class="w-9 h-9 flex items-center justify-center bg-slate-100 hover:bg-slate-200 rounded-lg text-slate-600 font-bold text-lg transition-colors select-none"
                                title="Increase by 10"
                            >+</button>
                        </div>
                        <div class="flex gap-2">
                            <button @click="adjustOffset('v_offset', -1)"
                                class="flex-1 text-xs py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-md transition-colors">−1</button>
                            <button @click="adjustOffset('v_offset', 1)"
                                class="flex-1 text-xs py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-md transition-colors">+1</button>
                        </div>
                        <p v-if="settingsErrors.v_offset" class="text-xs text-red-500">{{ settingsErrors.v_offset }}</p>
                    </div>

                    <!-- Current preview badge -->
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Current Values</p>
                        <div class="space-y-1 text-sm font-mono text-slate-700">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Printer IP:</span>
                                <span class="font-semibold">{{ settingsForm.sato_ip || '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">H Offset:</span>
                                <span :class="settingsForm.h_offset < 0 ? 'text-orange-600 font-semibold' : 'font-semibold'">
                                    {{ settingsForm.h_offset }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">V Offset:</span>
                                <span :class="settingsForm.v_offset < 0 ? 'text-orange-600 font-semibold' : 'font-semibold'">
                                    {{ settingsForm.v_offset }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Drawer Footer -->
                <div class="p-6 border-t border-slate-200 shrink-0">
                    <button
                        @click="handleSaveSettings"
                        :disabled="settingsSaving"
                        class="w-full flex items-center justify-center gap-2 bg-linear-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 disabled:opacity-60 text-white font-semibold py-3 px-4 rounded-lg shadow-md transition-all"
                    >
                        <svg v-if="settingsSaving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        {{ settingsSaving ? 'Saving...' : 'Save Settings' }}
                    </button>
                </div>
            </div>
        </Transition>

        <!-- ── Floating Settings Gear Button ── -->
        <button
            @click="showSettingsDrawer = !showSettingsDrawer"
            class="fixed bottom-6 right-6 z-30 w-12 h-12 bg-slate-700 hover:bg-slate-800 text-white rounded-full shadow-xl flex items-center justify-center transition-all hover:scale-110 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
            title="Print Settings"
        >
            <svg class="w-5 h-5" :class="showSettingsDrawer ? 'rotate-45' : ''" style="transition: transform 0.3s ease"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </button>

        <!-- ── Header ── -->
        <header class="bg-white shadow-sm border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <img src="/smp_logo.png" alt="Company Logo" class="w-12 h-12 object-contain" />
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900">Shin-Etsu Magnetic Philippines, Inc.</h1>
                            <p class="mt-1 text-sm text-slate-600">Scan and print DNS Special Label</p>
                        </div>
                    </div>
                    <div class="hidden sm:flex items-center gap-4">
                        <!-- Settings quick-glance badge -->
                        <button
                            @click="showSettingsDrawer = true"
                            class="flex items-center gap-2 bg-slate-100 hover:bg-slate-200 rounded-lg px-3 py-2 transition-colors"
                            title="Open print settings"
                        >
                            <span class="text-xs text-slate-500 font-mono">
                                H:{{ settingsForm.h_offset }} V:{{ settingsForm.v_offset }}
                            </span>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                        <div class="flex items-center space-x-2 text-xs text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Session: {{ form.sessionID.substring(0, 8) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- ── Main Form ── -->
        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
                <div class="bg-linear-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <h2 class="text-2xl font-semibold text-white flex items-center">
                        <svg class="w-7 h-7 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                        DNS Special Label
                    </h2>
                    <p class="text-blue-100 mt-1 text-sm">Please scan your package sticker in each field in order</p>
                </div>

                <form @submit.prevent="handleSubmit" class="p-8 space-y-6">
                    <!-- FG Code -->
                    <div class="space-y-2">
                        <label for="fgcode" class="flex items-center text-sm font-semibold text-slate-700">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3 4h2v16H3V4zm3 0h1v16H6V4zm2 0h1v16H8V4zm2 0h2v16h-2V4zm3 0h1v16h-1V4zm2 0h2v16h-2V4zm3 0h1v16h-1V4zm2 0h2v16h-2V4z"/>
                            </svg>
                            Scan FG Code <span class="ml-1 text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                ref="fgcodeInput"
                                type="text"
                                id="fgcode"
                                v-model="form.fgcode"
                                @keydown.enter="handleEnter($event, qtyInput, validateFGCode)"
                                :class="[
                                    'w-full px-4 py-3.5 bg-slate-50 border-2 rounded-lg focus:ring-2 focus:ring-blue-500 transition-all duration-200 text-slate-900 font-mono text-lg uppercase placeholder-slate-400',
                                    errors.fgcode ? 'border-red-500 focus:border-red-500' : 'border-slate-300 focus:border-blue-500'
                                ]"
                                placeholder="Scan FG code..."
                                autocomplete="off"
                            />
                            <transition name="slide-fade">
                                <div v-if="errors.fgcode" class="absolute right-0 top-1/2 -translate-y-1/2 mr-3 flex items-center gap-2">
                                    <div class="bg-red-500 text-white text-sm px-3 py-1.5 rounded-lg shadow-lg flex items-center gap-2 animate-shake">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ errors.fgcode }}
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </div>

                    <!-- Quantity -->
                    <div class="space-y-2">
                        <label for="qty" class="flex items-center text-sm font-semibold text-slate-700">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3 4h2v16H3V4zm3 0h1v16H6V4zm2 0h1v16H8V4zm2 0h2v16h-2V4zm3 0h1v16h-1V4zm2 0h2v16h-2V4zm3 0h1v16h-1V4zm2 0h2v16h-2V4z"/>
                            </svg>
                            Scan Quantity <span class="ml-1 text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                ref="qtyInput"
                                type="text"
                                id="qty"
                                v-model="form.qty"
                                @keydown.enter="handleEnter($event, barcodeInput, validateQty)"
                                :class="[
                                    'w-full px-4 py-3.5 bg-slate-50 border-2 rounded-lg focus:ring-2 focus:ring-blue-500 transition-all duration-200 text-slate-900 font-mono text-lg uppercase placeholder-slate-400',
                                    errors.qty ? 'border-red-500 focus:border-red-500' : 'border-slate-300 focus:border-blue-500'
                                ]"
                                placeholder="Scan quantity..."
                                autocomplete="off"
                            />
                            <transition name="slide-fade">
                                <div v-if="errors.qty" class="absolute right-0 top-1/2 -translate-y-1/2 mr-3 flex items-center gap-2">
                                    <div class="bg-red-500 text-white text-sm px-3 py-1.5 rounded-lg shadow-lg flex items-center gap-2 animate-shake">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ errors.qty }}
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </div>

                    <!-- Barcode -->
                    <div class="space-y-2">
                        <label for="barcode" class="flex items-center text-sm font-semibold text-slate-700">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3 4h2v16H3V4zm3 0h1v16H6V4zm2 0h1v16H8V4zm2 0h2v16h-2V4zm3 0h1v16h-1V4zm2 0h2v16h-2V4zm3 0h1v16h-1V4zm2 0h2v16h-2V4z"/>
                            </svg>
                            Scan Package Number <span class="ml-1 text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                ref="barcodeInput"
                                type="text"
                                id="barcode"
                                v-model="form.barcode"
                                @keydown.enter="handleEnter($event, null, validateBarcode)"
                                :class="[
                                    'w-full px-4 py-3.5 bg-slate-50 border-2 rounded-lg focus:ring-2 focus:ring-blue-500 transition-all duration-200 text-slate-900 font-mono text-lg uppercase placeholder-slate-400',
                                    errors.barcode ? 'border-red-500 focus:border-red-500' : 'border-slate-300 focus:border-blue-500'
                                ]"
                                placeholder="Scan package number..."
                                autocomplete="off"
                            />
                            <transition name="slide-fade">
                                <div v-if="errors.barcode" class="absolute right-0 top-1/2 -translate-y-1/2 mr-3 flex items-center gap-2">
                                    <div class="bg-red-500 text-white text-sm px-3 py-1.5 rounded-lg shadow-lg flex items-center gap-2 animate-shake">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ errors.barcode }}
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button
                            type="submit"
                            class="w-full bg-linear-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-4 px-6 rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center space-x-2 text-lg"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                            <span>Print Special Label</span>
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <footer class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center text-sm text-slate-500">
            <p>&copy; {{ new Date().getFullYear() }} DNS Special Label. All rights reserved.</p>
        </footer>
    </div>
</template>
