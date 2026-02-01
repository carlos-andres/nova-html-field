<template>
    <PanelItem :index="index" :field="field">
        <template #value>
            <div
                v-if="hasValue"
                class="html-field-content"
                :class="`text-${field.textAlign}`"
                v-html="field.value"
            />
            <p v-else>&mdash;</p>
        </template>
    </PanelItem>
</template>

<script>
import { FieldValue } from 'laravel-nova'

export default {
    mixins: [FieldValue],

    props: {
        index: {
            type: Number,
            required: true,
        },
        resource: {
            type: Object,
            required: true,
        },
        resourceName: {
            type: String,
            required: true,
        },
        resourceId: {
            type: [String, Number],
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
    height: auto;
}

.html-field-content :deep(a) {
    color: rgb(var(--colors-primary-500));
    text-decoration: underline;
}

.html-field-content :deep(a:hover) {
    color: rgb(var(--colors-primary-600));
}

.html-field-content :deep(ul),
.html-field-content :deep(ol) {
    padding-left: 1.5rem;
    margin: 0.5rem 0;
}

.html-field-content :deep(ul) {
    list-style-type: disc;
}

.html-field-content :deep(ol) {
    list-style-type: decimal;
}

.html-field-content :deep(p) {
    margin: 0.5rem 0;
}

.html-field-content :deep(p:first-child) {
    margin-top: 0;
}

.html-field-content :deep(p:last-child) {
    margin-bottom: 0;
}
</style>
