<template>
    <form v-on:submit.prevent="handleSubmit"
        class="book tw-shadow-md tw-rounded tw-px-8 tw-pt-6 tw-pb-8 tw-mb-4 sm:tw-w-1/2 md:tw-w-1/3">
        <div v-if="error" class="tw-bg-red-500 tw-text-white tw-font-bold tw-rounded-md tw-py-2 tw-px-4">
            {{ error }}
        </div>
        <div class="tw-mb-4">
            <label class="tw-block tw-text-gray-700 tw-text-sm tw-font-bold tw-mb-2" for="email">
                Email
            </label>
            <input
                class="tw-shadow tw-appearance-none tw-border tw-rounded tw-w-full tw-py-2 tw-px-3 tw-text-gray-700 tw-leading-tight focus:tw-outline-none focus:tw-shadow-outline"
                :class="{ 'tw-border-red-500': error }" id="email" v-model="email" type="email" placeholder="Email">
            <p class="tw-mt-1 tw-text-xs tw-text-gray-500">Try: <a href="#" tabindex="-1"
                    @click.prevent="loadEmailField">bernie@dragonmail.com</a></p>
        </div>
        <div class="mb-6">
            <label class="tw-block tw-text-gray-700 tw-text-sm tw-font-bold tw-mb-2" for="password">
                Password
            </label>
            <input
                class="tw-shadow tw-appearance-none tw-border tw-rounded tw-w-full tw-py-2 tw-px-3 tw-text-gray-700 tw-leading-tight focus:tw-outline-none focus:tw-shadow-outline"
                :class="{ 'tw-border-red-500': error }" id="password" v-model="password" type="password"
                placeholder="Password">
            <p class="tw-mt-1 tw-text-xs tw-text-gray-500">Try: <a href="#" tabindex="-1"
                    @click.prevent="loadPasswordField">roar</a></p>
        </div>
        <div class="tw-flex tw-items-center tw-justify-between">
            <button
                class="tw-bg-indigo-700 hover:tw-bg-indigo-900 tw-shadow-lg tw-text-white tw-font-semibold tw-py-2 tw-px-4 tw-rounded-lg focus:tw-outline-none focus:tw-shadow-outline tw-text-sm"
                type="submit" :disabled="isLoading"
                :class="{ 'tw-bg-indigo-400': isLoading, 'hover:tw-bg-indigo-400': isLoading }">
                Log In
            </button>
        </div>
    </form>
</template>

<script setup>

import { ref } from 'vue';

const email = ref('');
const password = ref('');
const error = ref('');
const isLoading = ref(false);
const emit = defineEmits(['user-authenticated']);

const loadEmailField = () => {
    email.value = 'bernie@dragonmail.com';
};
const loadPasswordField = () => {
    password.value = 'roar';
};

const handleSubmit = async () => {
    isLoading.value = true;
    error.value = '';

    const response = await fetch('/login/json_result', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            email: email.value,
            password: password.value
        })
    });

    isLoading.value = false;

    if (!response.ok) {
        const data = await response.json();
        error.value = data.error;

        return;
    }

    email.value = '';
    password.value = '';
    const userIri = response.headers.get('Location');
    emit('user-authenticated', userIri);
}

</script>
