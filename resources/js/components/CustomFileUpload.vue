<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    dropText: {
        type: String,
        default: 'Upload PDF',
    },
    previewText: {
        type: String,
        default: 'Fichier sélectionné',
    },
    previewPlaceholder: {
        type: String,
        default: 'Aucun fichier sélectionné',
    },
    accept: {
        type: String,
        default: 'application/pdf',
    },
})

const emit = defineEmits(['input', 'file-selected'])

const file = ref(null)
const fileInput = ref(null)
const isDragging = ref(false)

const triggerFileInput = () => fileInput.value.click()

const validateFile = (selectedFile) => {
    if (selectedFile.type !== 'application/pdf') {
        alert('Seuls les fichiers PDF sont autorisés')
        return false
    }
    return true
}

const setFile = (selectedFile) => {
    if (!validateFile(selectedFile)) return

    file.value = selectedFile

    // compatibilité CustomFileUpload
    emit('input', { target: { files: [selectedFile] } })
    emit('file-selected', selectedFile)
}

const onFileChange = (e) => {
    const selectedFile = e.target.files[0]
    if (selectedFile) setFile(selectedFile)
}

const onDrop = (e) => {
    isDragging.value = false
    const droppedFile = e.dataTransfer.files[0]
    if (droppedFile) setFile(droppedFile)
}

const removeFile = () => {
    file.value = null
    fileInput.value.value = ''
    emit('input', { target: { files: [] } })
}

const formattedSize = computed(() =>
    file.value
        ? `${(file.value.size / 1024 / 1024).toFixed(2)} MB`
        : ''
)
</script>


<template>
    <div class="space-y-3">
        <!-- ZONE SELECTION (visible UNIQUEMENT si aucun fichier) -->
        <div v-if="!file" class="border-2 border-dashed rounded-xl p-6 text-center cursor-pointer
             transition hover:border-gray-400" @click="triggerFileInput" @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false" @drop.prevent="onDrop">
            <p class="font-semibold text-gray-800">{{ dropText }}</p>
            <p class="text-sm text-gray-500">
                Drag & drop ou cliquez pour parcourir
            </p>

            <input ref="fileInput" type="file" class="hidden" accept="application/pdf" @change="onFileChange" />
        </div>

        <!-- PREVIEW (remplace la zone drag & drop) -->
        <div v-else class="border-2 border-dashed border-green-500 rounded-xl p-4
             bg-green-50 flex items-center justify-between">
            <div class="flex items-center gap-3 overflow-hidden">
                <!-- Icône PDF -->
                <span class="text-xl shrink-0">📄</span>

                <!-- Nom fichier -->
                <div class="overflow-hidden">
                    <p class="text-sm font-medium text-gray-800 truncate">
                        {{ file.name }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ formattedSize }}
                    </p>
                </div>
            </div>

            <!-- Supprimer -->
            <button type="button" class="text-red-500 hover:text-red-700 transition" @click.stop="removeFile"
                title="Supprimer">
                🗑️
            </button>
        </div>
    </div>
</template>
