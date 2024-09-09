<template>
    <div class="purple tw-flex tw-flex-col tw-min-h-screen">
        <div class="tw-px-8 tw-py-8">
            <img class="tw-h-16 tw-w-auto" :src="coinLogoPath" alt="Treasure Connect Logo">
        </div>
        <div class="tw-flex-auto tw-flex tw-flex-col sm:tw-flex-row tw-justify-center tw-px-8">
            <LoginForm v-on:user-authenticated="onUserAuthenticated"></LoginForm>
            <div
                class="book tw-shadow-md tw-rounded sm:tw-ml-3 tw-px-8 tw-pt-8 tw-pb-8 tw-mb-4 sm:tw-w-1/2 md:tw-w-1/3 tw-text-center">
                <div v-if="user">
                    Authenticated as: <strong>{{ user.email }}</strong>

                    | <a href="/logout" class="underline">Log out</a>
                </div>
                <div v-else>Not authenticated</div>

                <hr class="tw-my-10 tw-mx-auto" style="border-top: 1px solid #ccc; width: 70%;" />

                <p>Check out the <a :href="entrypoint" class="tw-underline">API Docs</a></p>
            </div>
        </div>
        <img :src="goldPilePath" alt="A pile of gold!">
    </div>
</template>

<script setup>
import { ref } from 'vue';
import LoginForm from '../LoginForm';
import coinLogoPath from '../../images/coinLogo.png';
import goldPilePath from '../../images/GoldPile.png';

const props = defineProps(['entrypoint', 'user']);
const user = ref(props.user);

const onUserAuthenticated = async (userUri) => {
    const response = await fetch(userUri);
    user.value = await response.json();
}
</script>
