<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
    modelValue: {
        type: File,
        default: null,
    },
    accept: {
        type: String,
        default: 'application/pdf,image/*',
    },
    maxSizeMB: {
        type: Number,
        default: 5,
    },
})

const emit = defineEmits(['update:modelValue'])

const file = ref(props.modelValue)
const fileInput = ref(null)
const isDragging = ref(false)

watch(
    () => props.modelValue,
    (val) => {
        file.value = val
    }
)

const maxSizeBytes = props.maxSizeMB * 1024 * 1024

const triggerFileInput = () => fileInput.value.click()

const validateFile = (selectedFile) => {
    const isValidType =
        selectedFile.type === 'application/pdf' ||
        selectedFile.type.startsWith('image/')

    if (!isValidType) return alert('PDF ou images uniquement')

    if (selectedFile.size > maxSizeBytes)
        return alert(`Max ${props.maxSizeMB} MB`)

    return true
}

const setFile = (selectedFile) => {
    if (!validateFile(selectedFile)) return
    file.value = selectedFile
    emit('update:modelValue', selectedFile)
}

const removeFile = () => {
    file.value = null
    fileInput.value.value = ''
    emit('update:modelValue', null)
}

const onFileChange = (e) => {
    const f = e.target.files[0]
    if (f) setFile(f)
}

const onDrop = (e) => {
    isDragging.value = false
    const f = e.dataTransfer.files[0]
    if (f) setFile(f)
}

const isPdf = computed(() => file.value?.type === 'application/pdf')
const formattedSize = computed(() =>
    file.value ? `${(file.value.size / 1024 / 1024).toFixed(2)} MB` : ''
)
</script>

<template>
    <div class="space-y-4">
        <!-- Zone d’upload -->
        <div class="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer
             hover:border-gray-400 transition" :class="{ 'border-blue-500 bg-blue-50': isDragging }"
            @click="triggerFileInput" @dragover.prevent="onDragOver" @dragleave.prevent="onDragLeave"
            @drop.prevent="onDrop">
            <div class="flex flex-col items-center gap-2 text-gray-600">
                <div class="w-10 h-10 flex items-center justify-center rounded-full border">
                    ⬆️
                </div>

                <p class="font-semibold text-gray-800">Upload file</p>
                <p class="text-sm text-gray-500">
                    Drag & drop or click to browse (max. {{ maxSizeMB }} MB)
                </p>
            </div>

            <input ref="fileInput" type="file" class="hidden" accept="application/pdf,image/*" @change="onFileChange" />
        </div>

        <!-- Card du fichier sélectionné -->
        <div v-if="file" class="border rounded-lg p-4 flex items-center justify-between shadow-sm bg-white">
            <div class="flex items-center gap-3">
                <span class="text-2xl">
                    {{ isPdf ? '📄' : '🖼️' }}
                </span>

                <div>
                    <p class="font-medium text-gray-800">{{ file.name }}</p>
                    <p class="text-xs text-gray-500">{{ formattedSize }}</p>
                </div>
            </div>

            <button class="text-red-500 text-sm hover:underline" @click="removeFile">
                Remove
            </button>
        </div>
    </div>
</template>