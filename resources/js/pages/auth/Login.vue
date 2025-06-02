<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link } from '@inertiajs/vue3';
defineProps<{
    status?: string;
    canResetPassword: boolean;
    title?: string;
    description?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

</script>

<template>
    <AuthBase>

        <Head title="Log in" />

        <!-- Container untuk animasi air -->
        <div
            class="relative h-180 flex items-center rounded-3xl justify-center p-4 overflow-hidden bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800">
            <div class="ocean absolute inset-0 z-0">
                <div class="wave wave1"></div>
                <div class="wave wave2"></div>
                <div class="wave wave3"></div>
            </div>

            <!-- Form login dengan backdrop dan rounded yang lebih besar -->
            <div
                class="relative z-10 w-full max-w-md  bg-white/90 dark:bg-black/80 backdrop-blur-md rounded-2xl shadow-xl p-8 border border-gray-100 dark:border-gray-700">
                <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
                    {{ status }}
                </div>
                <div class="mb-6 flex justify-center ">
                    <Link :href="route('home')" class="flex flex-col items-center gap-2 font-medium">
                    <AppLogoIcon class=" rounded-2xl size-16 fill-current text-[var(--foreground)] dark:text-white" />
                    <span class="sr-only">{{ title }}</span>
                    </Link>
                </div>
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Log in to your account</h2>
                    <p class="text-gray-600 dark:text-gray-300 mt-2">Enter your email and password below to log in</p>
                </div>

                <form @submit.prevent="submit" class="flex flex-col gap-6">


                    <div class="grid gap-6">
                        <div class="grid gap-3">
                            <Label for="email" class="text-gray-700 dark:text-gray-300">Email address</Label>
                            <Input id="email" type="email" required autofocus :tabindex="1" autocomplete="email"
                                v-model="form.email" placeholder="email@example.com"
                                class="rounded-lg border-gray-300 dark:border-gray-600" />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-3">
                            <Label for="password" class="text-gray-700 dark:text-gray-300">Password</Label>
                            <Input id="password" type="password" required :tabindex="2" autocomplete="current-password"
                                v-model="form.password" placeholder="Password"
                                class="rounded-lg border-gray-300 dark:border-gray-600" />
                            <InputError :message="form.errors.password" />
                        </div>

                        <Button type="submit" class="mt-2 w-full py-6 rounded-xl font-semibold text-base" :tabindex="4"
                            :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="h-5 w-5 animate-spin mr-2" />
                            <span>Log in</span>
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AuthBase>
</template>

<style scoped>
/* Animasi Gelombang Air yang Lebih Smooth */
.ocean {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    pointer-events: none;
}

.wave {
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120' preserveAspectRatio='none'%3E%3Cpath d='M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z' fill='%2340a0ff'%3E%3C/path%3E%3C/svg%3E");
    position: top;
    width: 250%;
    height: 100%;
    animation: wave 25s linear infinite;
    transform: translate3d(0, 0, 0);
    opacity: 0.2;
    bottom: -5%;
    will-change: transform;
}

.wave1 {
    animation-duration: 25s;
    background-position-y: 10px;
}

.wave2 {
    animation-duration: 18s;
    animation-delay: -7s;
    opacity: 0.15;
    background-position-y: 20px;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120' preserveAspectRatio='none'%3E%3Cpath d='M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z' fill='%232c7be5'%3E%3C/path%3E%3C/svg%3E");
}

.wave3 {
    animation-duration: 30s;
    animation-delay: -12s;
    opacity: 0.1;
    background-position-y: 30px;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120' preserveAspectRatio='none'%3E%3Cpath d='M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z' fill='%231a56db'%3E%3C/path%3E%3C/svg%3E");
}

@keyframes wave {
    0% {
        transform: translateX(0) translateZ(0);
    }

    50% {
        transform: translateX(-25%) translateZ(0);
    }

    100% {
        transform: translateX(-50%) translateZ(0);
    }
}

/* Dark mode adjustment */
.dark .wave {
    opacity: 0.15;
}

.dark .wave1 {
    opacity: 0.12;
}

.dark .wave2 {
    opacity: 0.08;
}

.dark .wave3 {
    opacity: 0.05;
}
</style>