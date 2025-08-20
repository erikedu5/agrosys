import { defineStore } from 'pinia';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

export const useFormStore = defineStore('formStore', {
    state: () => ({
        forms: {},
    }),
    actions: {
        setForm(key, value) {
            this.forms[key] = value;
        },
        resetForm(key) {
            this.forms[key] = {};
        },
    },
});

export function usePersistedForm(key, initialData) {
    const store = useFormStore();
    const data = store.forms[key] && Object.keys(store.forms[key]).length ? store.forms[key] : initialData;
    const form = useForm(data);

    watch(form, (value) => store.setForm(key, value), { deep: true });

    const reset = () => {
        form.reset();
        store.resetForm(key);
    };

    return { form, reset };
}
