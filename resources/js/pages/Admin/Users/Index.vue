<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Heading from '@/Components/Heading.vue'
import {
    UserPlusIcon,
    PencilIcon,
    TrashIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    ShieldIcon
} from 'lucide-vue-next'

defineOptions({
    layout: AppLayout,
})

interface User {
    id: number
    name: string
    email: string
    role: string
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
    users: UserCollection
    currentFilters: {
        search: string | null
        role: string | null
        sort_by: string
        sort_dir: string
        per_page: number
    }
}

const props = defineProps<Props>()

const selectedRole = ref('')
const searchQuery = ref('')

const AVAILABLE_ROLES = [
    { value: '', label: 'All Roles' },
    { value: 'admin', label: 'Admin' },
    { value: 'agent', label: 'Agent' }
]

const filteredUsers = computed(() => {
    let filtered = [...props.users.data]

    // Filter by role
    if (selectedRole.value) {
        filtered = filtered.filter(user => user.role === selectedRole.value)
    }

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(user =>
            user.name.toLowerCase().includes(query) ||
            user.email.toLowerCase().includes(query)
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

// User Actions
const createUser = () => {
    router.visit(route('admin.users.create'))
}

const toggleRole = (user: User) => {
    const newRole = user.role === 'admin' ? 'agent' : 'admin'
    if (confirm(`Are you sure you want to change the role of "${user.name}" to "${newRole}"?`)) {
        router.put(route('admin.users.role.update', user.id), {
            role: newRole,
        }, {
            preserveScroll: true,
        })
    }
}

const deleteUser = (user: User) => {
    if (confirm(`Are you sure you want to delete "${user.name}"?`)) {
        router.delete(route('admin.users.destroy', user.id), {
            preserveScroll: true,
        })
    }
}
</script>

<template>
    <div class="p-6 space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <Heading>Users Management</Heading>
            <Button @click="createUser" variant="default">
                <UserPlusIcon class="w-4 h-4 mr-2" />
                Create User
            </Button>
        </div>

        <!-- Filters -->
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <div class="flex flex-wrap gap-4">
                <!-- Search -->
                <div class="flex-1 min-w-64">
                    <Input v-model="searchQuery" placeholder="Search users by name or email..." />
                </div>

                <!-- Role Filter -->
                <div class="min-w-32">
                    <select v-model="selectedRole"
                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:max-w-xs sm:text-sm sm:leading-6">
                        <option v-for="role in AVAILABLE_ROLES" :key="role.value" :value="role.value">
                            {{ role.label }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Users Table -->
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
                                Role
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
                        <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div
                                            class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                            <span class="text-gray-500 font-medium">{{ user.name.charAt(0) }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ user.email }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ user.email_verified_at ? 'Verified' : 'Not Verified' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full" :class="{
                                    'bg-purple-100 text-blue-800': user.role === 'admin',
                                    'bg-blue-100 text-green-800': user.role === 'agent'
                                }">
                                    {{ user.role }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(user.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <Button variant="outline" size="sm" @click="toggleRole(user)"
                                        :title="user.role === 'admin' ? 'Make Agent' : 'Make Admin'">
                                        <ShieldIcon class="w-4 h-4"
                                            :class="user.role === 'admin' ? 'text-purple-600' : 'text-green-600'" />
                                        <span class="ml-1 text-xs">
                                            {{ user.role === 'admin' ? 'Make Agent' : 'Make Admin' }}
                                        </span>
                                    </Button>
                                    <Button variant="outline" size="sm" @click="deleteUser(user)" title="Delete User">
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
