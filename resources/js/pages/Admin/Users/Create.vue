<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'

defineOptions({ layout: AppLayout })

const form = ref({
    name: '',
    email: '',
    password: '',
})

const errors = ref({})

const submit = () => {
    router.post(route('admin.users.store'), form.value, {
        onError: (e) => { errors.value = e }
    })
}
</script>

<template>
    <div class="p-6 bg-white rounded-lg shadow">
        <h2 class="text-xl font-bold mb-4">Create User</h2>
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    Name
                </label>
                <Input v-model="form.name" placeholder="Enter your agent name" />
                <div v-if="errors.name" class="text-red-500 text-xs">{{ errors.name }}</div>
            </div>
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    Email
                </label>
                <Input v-model="form.email" placeholder="Enter a valid email" type="email" />
                <div v-if="errors.email" class="text-red-500 text-xs">{{ errors.email }}</div>
            </div>
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    Password
                </label>
                <Input v-model="form.password" placeholder="Type a password" type="password" />
                <div v-if="errors.password" class="text-red-500 text-xs">{{ errors.password }}</div>
            </div>
            <Button type="submit" variant="default">Create</Button>
        </form>
    </div>
</template>
