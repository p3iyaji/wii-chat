<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
    width?: string | number;
    height?: string | number;
    alt?: string;
}

const props = withDefaults(defineProps<Props>(), {
    width: 60,
    height: 40,
    alt: 'RealPay Logo',
});
</script>

<template>
    <img src="/favicon.ico" :alt="alt" :class="className"
        :style="{ width: typeof width === 'number' ? `${width}px` : width, height: typeof height === 'number' ? `${height}px` : height }"
        v-bind="$attrs" />
</template>