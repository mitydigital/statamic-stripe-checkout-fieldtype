<template>
    <div>
        <div v-if="message" class="font-bold text-red-500 text-sm">{{ message }}</div>
        <div v-else>{{ value }}</div>
    </div>
</template>

<script>
export default {
    mixins: [Fieldtype],

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
