<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { PhoneCall, MessageCircle, TicketIcon } from 'lucide-vue-next';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import TiptapContent from '@/Components/TiptapContent.vue'

import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible'
import { ChevronDown } from 'lucide-vue-next'

const supportCards = [
    {
        title: 'Call Support',
        description: 'Talk to our support team',
        icon: PhoneCall,
        details: [
            'Business Hours: 9 AM - 5 PM',
            'Monday to Friday',
            'Contact: (555) 123-4567'
        ],
        action: {
            text: 'Call Now',
            href: 'tel:+15551234567'
        }
    },
    {
        title: 'Live Chat',
        description: 'Chat with our agents',
        icon: MessageCircle,
        details: [
            'Available: 10 AM - 6 PM',
            'Response time: < 5 minutes',
            'Business days only'
        ],
        action: {
            text: 'Start Chat',
            href: '/chat'
        }
    },
    {
        title: 'Submit Ticket',
        description: 'Create a support ticket',
        icon: TicketIcon,
        details: [
            '24/7 Ticket Creation',
            'Response within 24 hours',
            'Track your requests'
        ],
        action: {
            text: 'Create Ticket',
            href: '/tickets/create'
        }
    }
];


const parseContent = (content: string) => {
  try {
    return typeof content === 'string' ? content : JSON.stringify(content)
  } catch (e) {
    return content
  }
}
// Define props interface
interface Props {
    knowledgeBase: {
        id: number
        title: string
        body: string
        slug: string
    }[]
}

// Accept props
const props = defineProps<Props>();

</script>

<template>

    <Head title="Welcome to Support" />

    <div class="min-h-screen bg-background">
        <!-- Header -->
        <header class="border-b">
            <div class="container mx-auto px-4 py-4 flex justify-between items-center">
                <h1 class="text-xl font-semibold">HelpDesk</h1>
                <nav class="flex gap-4">
                    <template v-if="$page.props.auth.user">
                        <Link :href="route('dashboard')" class="text-sm">
                        Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="text-sm bg-white text-black px-4 py-2 rounded-md">
                        Log in
                        </Link>
                        <Link :href="route('register')"
                            class="text-sm bg-primary text-primary-foreground px-4 py-2 rounded-md">
                        Register
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <div class="container px-4 py-8 mx-auto max-w-7xl">
            <!-- Hero Section -->
            <div class="text-center mb-12 py-6">
                <h1 class="text-4xl font-bold tracking-tight mb-4">How can we help you?</h1>
                <p class="text-muted-foreground max-w-2xl mx-auto">
                    Find quick answers in our knowledge base or get in touch with our support team.
                </p>
            </div>

            <!-- Support Cards -->
            <div class="container max-w-5xl mx-auto"> <!-- Added container with max width -->
                <div class="grid gap-6 md:grid-cols-3 mb-12">
                    <Card v-for="card in supportCards" :key="card.title" class="flex flex-col">
                        <CardHeader class="space-y-2"> <!-- Reduced spacing -->
                            <div class="mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10">
                                <!-- Reduced icon size -->
                                <component :is="card.icon" class="h-5 w-5 text-primary" />
                            </div>
                            <CardTitle class="text-lg">{{ card.title }}</CardTitle> <!-- Reduced title size -->
                            <CardDescription class="text-sm">{{ card.description }}</CardDescription>
                        </CardHeader>
                        <CardContent class="flex-1 pt-2"> <!-- Adjusted padding -->
                            <ul class="space-y-1 mb-4"> <!-- Reduced list spacing -->
                                <li v-for="detail in card.details" :key="detail" class="text-sm text-muted-foreground">
                                    • {{ detail }}
                                </li>
                            </ul>
                        </CardContent>
                        <div class="p-4 pt-0 mt-auto"> <!-- Reduced padding -->
                            <Button :href="card.action.href" class="w-full" variant="default">
                                {{ card.action.text }}
                            </Button>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- Knowledge Base Section -->
            <div class="mb-12 max-w-5xl mx-auto">
                <h2 class="text-3xl font-bold tracking-tight mb-6 text-center pt-4">Knowledge Base</h2>
                <div class="space-y-4">
                    <Collapsible v-for="item in props.knowledgeBase" :key="item.id" class="border rounded-lg">
                        <CollapsibleTrigger class="flex w-full items-center justify-between p-4 text-left">
                            <h3 class="font-semibold">{{ item.title }}</h3>
                            <ChevronDown class="h-4 w-4 text-muted-foreground transition-transform duration-200" />
                        </CollapsibleTrigger>
                        <CollapsibleContent class="px-4 pb-4 text-sm text-muted-foreground text-left">
                             <TiptapContent :content="parseContent(item.body)" />
                        </CollapsibleContent>
                    </Collapsible>
                </div>
            </div>
        </div>
    </div>
</template>
