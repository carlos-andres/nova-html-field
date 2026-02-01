<template>
    <DefaultField
        :field="currentField"
        :errors="errors"
        :show-help-text="showHelpText"
        :full-width-content="fullWidthContent"
    >
        <template #field>
            <div
                v-if="hasValue"
                class="html-field-content py-2"
                v-html="currentField.value"
            />
            <p v-else class="py-2 text-gray-400">&mdash;</p>
        </template>
    </DefaultField>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'

export default {
    mixins: [FormField, HandlesValidationErrors],

    props: {
        resourceName: {
            type: String,
            required: true,
        },
        resourceId: {
            type: [String, Number],
            default: null,
        },
        field: {
            type: Object,
            required: true,
        },
    },

    computed: {
        currentField() {
            return this.field
        },

        fullWidthContent() {
            return this.currentField.fullWidth || false
        },

        hasValue() {
            return (
                this.currentField.value !== null &&
                this.currentField.value !== undefined &&
                this.currentField.value !== ''
            )
        },
    },

    methods: {
        /**
         * No-op: This is a display-only field
         */
        // eslint-disable-next-line no-unused-vars
        fill(formData) {
            // Display-only field - does not contribute to form data
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
