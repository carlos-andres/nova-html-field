<template>
    <div :class="`text-${field.textAlign}`">
        <span
            v-if="hasValue"
            class="html-field-content whitespace-nowrap"
            v-html="field.value"
        />
        <p v-else>&mdash;</p>
    </div>
</template>

<script>
import { FieldValue } from 'laravel-nova'

export default {
    mixins: [FieldValue],

    props: {
        resourceName: {
            type: String,
            required: true,
        },
        field: {
            type: Object,
            required: true,
        },
    },

    computed: {
        hasValue() {
            return (
                this.field.value !== null &&
                this.field.value !== undefined &&
                this.field.value !== ''
            )
        },
    },
}
</script>

<style scoped>
.html-field-content :deep(img) {
    max-width: 100%;
    max-height: 2rem;
    height: auto;
}

.html-field-content :deep(a) {
    color: rgb(var(--colors-primary-500));
    text-decoration: underline;
}
</style>
