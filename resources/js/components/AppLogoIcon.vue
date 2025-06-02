<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
 <img
        :src="`../icons/ShopLogo.png`"
        :class="className"
        v-bind="$attrs"
        alt="99 Cellular Logo"
    />
</template>
