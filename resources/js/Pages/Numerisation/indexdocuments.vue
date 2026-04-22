<template>
    <div class="">
        <div
            class="sticky top-[-20px] z-40 bg-[#ffffff] dark:bg-gray-900 flex flex-col space-y-4 px-8  py-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
            <h4 class="text-2xl font-bold tracking-tight">
                Numérisation
            </h4>
            <div class="flex items-center space-x-2">
                <Button variant="outline" @click="retunBack()" class="inline-flex items-center">
                    <MoveLeft class="w-4 h-4 mr-2" /> Retour
                </Button>
            </div>
        </div>

        <!-- Informations générales -->
        <Card class="mt-6 p-6 bg-white dark:bg-gray-900 mx-8 ">
            <div class="flex flex-col space-y-4 mx-8 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                <div>
                    <h5 class="text-lg font-semibold">Dossier : {{ props.dossier.num_chrono }}</h5>
                </div>
                <div>
                    <strong>Date de création :</strong> {{ formatDate(props.dossier.date_creation) }}
                </div>
            </div>
        </Card>

        <!-- Galerie de PDF -->
        <Card class="mt-6 p-6 bg-white dark:bg-gray-900 mx-8 ">
            <div>
                <div v-for="(doc, index) in cleanedDocuments" :key="index" class="p-4 space-y-4">
                    <h5 class="font-semibold text-gray-600 dark:text-gray-300 text-center mt-4 mb-8">
                        Liste des documents
                    </h5>

                    <!-- Grille de liens vers les PDF -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 mt-8">
                        <template v-for="(value, key) in doc" :key="key">
                            <div v-if="!keysToExcludeView.includes(key)"
                                class="relative cursor-pointer flex flex-col items-center m-6">
                                <!-- Icône PDF -->
                                <div
                                    class="w-[160px] h-[160px] flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded shadow-md hover:scale-105 transition duration-300">
                                    <ReceiptText class="w-20 h-20 text-red-600" />
                                </div>

                                <!-- Lien vers le PDF -->
                                <a :href="`/storage/${value}`" target="_blank"
                                    class="mt-2 text-center text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ formatLabel(key) }}
                                </a>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </Card>
    </div>

    <Toaster richColors position="top-right" />
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { MoveLeft, ReceiptText } from 'lucide-vue-next'

const props = defineProps({
    dossier: {
        type: Object,
        required: true
    },
    documents: {
        type: Array,
        required: true
    }
})

const keysToExclude = ['id_dossier', 'type_document', 'created_at', 'updated_at']
const keysToExcludeView = ['id', 'created_at', 'updated_at']

const cleanedDocuments = computed(() =>
    props.documents.map(doc => {
        const cleanedDoc = {}

        for (const key in doc) {
            const value = doc[key]

            // Exclure les clés spécifiques
            if (keysToExclude.includes(key)) continue

            // Exclure null, undefined, vide
            if (!value) continue

            // Exclure les valeurs numériques (id, id_dossier, etc.)
            if (typeof value === 'number') continue

            // Exclure "piece-identite"
            if (value === 'piece-identite') continue

            // Garder seulement les fichiers PDF
            if (typeof value === 'string' && value.startsWith('numerisations/') && value.endsWith('.pdf')) {
                cleanedDoc[key] = value
            }
        }

        return cleanedDoc
    })
)

const retunBack = () => {
    const params = new URLSearchParams(window.location.search)
    if (params.toString()) {
        router.visit('/numerisation?' + params.toString())
    } else {
        window.history.back()
    }
}

function formatDate(date) {
    if (!date) return '—'
    const options = { year: 'numeric', month: 'long', day: 'numeric' }
    return new Date(date).toLocaleDateString('fr-FR', options)
}

function formatLabel(key) {
    return key
        .replace(/_/g, ' ')
        .replace(/\b(de|la|le|et|en|du|des|aux|au|dans|sur|avec|à)\b/g, match => match.toLowerCase())
        .replace(/\b\w/g, l => l.toUpperCase())
}
</script>

<script>
import { Card, CardContent } from '@/components/ui/card'

import CustomMainMain from '/resources/js/Pages/customMain.vue';
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import {
    Table,
    TableHeader,
    TableBody,
    TableRow,
    TableHead,
    TableCell,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { MoreVertical } from 'lucide-vue-next'
import { useForm } from 'vee-validate';
import { toast, Toaster } from 'vue-sonner';



export default {
    layout: CustomMainMain,
    components: {
        Card,
        Table,
        TableHeader,
        TableBody,
        TableRow,
        TableHead,
        TableCell,
        Button,
        Badge
    }
}
</script>
