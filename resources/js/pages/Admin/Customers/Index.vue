<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Heading from '@/Components/Heading.vue'
import { TrashIcon } from 'lucide-vue-next'

defineOptions({
    layout: AppLayout,
})

interface User {
    id: number
    name: string
    email: string
    email_verified_at: string | null
    created_at: string
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface UserCollection {
    data: User[]
    links: PaginationLink[]
    from: number
    to: number
    total: number
}

interface Props {
    customers: UserCollection
    currentFilters: {
        search: string | null
        per_page: number
    }
}

const props = defineProps<Props>()

const searchQuery = ref('')

const filteredCustomers = computed(() => {
    let filtered = [...props.customers.data]

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(customer =>
            customer.name.toLowerCase().includes(query) ||
            customer.email.toLowerCase().includes(query)
        )
    }

    return filtered
})

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const deleteCustomer = (customer: User) => {
    if (confirm(`Are you sure you want to delete "${customer.name}"?`)) {
        router.delete(route('admin.customers.destroy', customer.id), {
            preserveScroll: true,
        })
    }
}
</script>

<template>
    <div class="p-6 space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <Heading>Customers</Heading>
        </div>

        <!-- Filters -->
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <div class="flex flex-wrap gap-4">
                <!-- Search -->
                <div class="flex-1 min-w-64">
                    <Input v-model="searchQuery" placeholder="Search customers by name or email..." />
                </div>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Joined
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="customer in filteredCustomers" :key="customer.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                            <span class="text-gray-500 font-medium">{{ customer.name.charAt(0) }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ customer.name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ customer.email }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ customer.email_verified_at ? 'Verified' : 'Not Verified' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(customer.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end">
                                    <Button variant="outline" size="sm" @click="deleteCustomer(customer)" title="Delete Customer">
                                        <TrashIcon class="w-4 h-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
