import { ref } from "vue";

export function formatBytes(bytes, decimals = 2) {
    if (!bytes) return "0 Bytes";

    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(decimals)) + " " + sizes[i];
}

export function useFileUpload({ maxSize, initialFiles = [] }) {
    const files = ref([]);
    const errors = ref([]);

    const inputRef = ref(null);
    const dropzoneRef = ref(null);

    // init files
    if (initialFiles.length) {
        files.value = initialFiles.map((f) => ({
            ...f,
            file: f.file || f,
        }));
    }

    const openFileDialog = () => {
        inputRef.value?.click();
    };

    const removeFile = (id) => {
        files.value = files.value.filter((f) => f.id !== id);
    };

    const onChange = (event) => {
        errors.value = [];
        const file = event.target.files[0];
        if (!file) return;

        if (file.size > maxSize) {
            errors.value.push("File is too large");
            return;
        }

        files.value = [
            {
                id: `${file.name}-${Date.now()}`,
                file,
            },
        ];
    };

    return {
        files,
        errors,
        openFileDialog,
        removeFile,
        inputRef,
        dropzoneRef,
        onChange,
    };
}
