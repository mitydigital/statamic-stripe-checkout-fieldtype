<template>
    <div>
        <div v-if="message" class="font-bold text-red-500 text-sm">{{ message }}</div>
        <div v-else>
            <div>{{ value.label }}</div>
            <div v-if="value.checkout_session_id" class="mt-2 text-xs text-gray-700">
                <div class="font-medium">Checkout Session ID</div>
                <div class="text-gray-600">{{ value.checkout_session_id }}</div>
            </div>
        </div>
    </div>
</template>

<script>

import {FieldtypeMixin} from '@statamic/cms';

export default {
    mixins: [FieldtypeMixin],

    computed: {
        message() {
            if (typeof this.value === 'object' && this.value.message) {
                return this.value.message;
            }
            return false;
        }
    }
}
</script>
