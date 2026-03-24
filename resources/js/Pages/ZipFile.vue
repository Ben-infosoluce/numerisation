<template>
    <div class="max-w-7xl mx-auto mb-4 p-6 bg-white rounded-lg shadow-md">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-8 flex items-center justify-center gap-2">
            <i class="fa-solid fa-file-zipper"></i> Listes des dossiers numérisés
        </h1>

        <div class="mb-4 mt-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <!-- Filtre par date avec DateRangePicker -->
            <div class="mb-6 flex flex-col sm:flex-row gap-4 items-center">
                <DateRangePicker v-model="dateRange" @update:start="onStartDateChange" @update:end="onEndDateChange" />
            </div>

            <!-- Actions -->
            <div class="mb-4 flex gap-2">
                <Button v-if="filteredZips.length > 0" @click="downloadSelected" :disabled="selectedZips.length === 0" class="sm">
                    <Download class="mr-1" /> Télécharger la sélection
                </Button>
                <Button @click="showUploadModal = true" class="sm bg-blue-600 hover:bg-blue-700 text-white">
                    <Upload class="w-4 h-4 mr-1" /> Importer un fichier
                </Button>
            </div>
        </div>

        <!-- Chargement/Erreur -->
        <div v-if="loading" class="text-center py-4 text-gray-500">
            Chargement des fichiers...
        </div>
        <div v-else-if="error" class="text-center py-4 text-red-500">
            {{ error }}
        </div>
        <div v-else-if="filteredZips.length === 0" class="text-center py-4 text-gray-500">
            Aucun fichier ZIP disponible.
        </div>

        <!-- Tableau des fichiers -->
        <div v-else>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[50px]">
                            <Checkbox :checked="allSelected" @update:checked="toggleSelectAll" />
                        </TableHead>
                        <TableHead>Nom du fichier</TableHead>
                        <TableHead>Taille</TableHead>
                        <TableHead>Date de création</TableHead>
                        <TableHead>Emplacement</TableHead>
                        <TableHead>Action</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="(zip, index) in paginatedZips" :key="index">
                        <TableCell>
                            <Checkbox :checked="selectedZips.some(item => item.url === zip.url)"
                                @update:checked="(checked) => toggleSelect(zip, checked)" />
                        </TableCell>
                        <TableCell>{{ zip.name }}</TableCell>
                        <TableCell>{{ zip.size }}</TableCell>
                        <TableCell>{{ zip.date }}</TableCell>
                        <TableCell>{{ 'Camp BAE Yopougon' }}</TableCell>
                        <TableCell class="flex gap-2">
                            <Button variant="outline" size="sm" @click="downloadSingle(zip)" title="Télécharger">
                                <Download class="w-4 h-4" />
                            </Button>
                            <Button variant="outline" size="sm" @click="renameZip(zip)" title="Renommer">
                                <Edit class="w-4 h-4 text-blue-500" />
                            </Button>
                            <Button variant="outline" size="sm" @click="deleteZip(zip)" title="Supprimer">
                                <Trash2 class="w-4 h-4 text-red-500" />
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <!-- Pagination -->
            <div class="flex justify-center items-center gap-4 mt-6">
                <Button variant="outline" @click="prevPage" :disabled="currentPage === 1" class="sm cursor-pointer">
                    <ChevronLeft class="mr-1 sm" /> Précédent
                </Button>
                <span class="text-gray-700">
                    Page {{ currentPage }} sur {{ totalPages }}
                </span>
                <Button variant="outline" @click="nextPage" :disabled="currentPage === totalPages"
                    class="sm cursor-pointer">
                    Suivant
                    <ChevronRight class="mr-1 sm" />
                </Button>
            </div>
        </div>
    </div>

    <!-- Modal Upload -->
    <div v-if="showUploadModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Importer un fichier</h2>
                <button @click="closeUploadModal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>

            <!-- Zone drag & drop -->
            <div
                class="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition"
                :class="isDraggingUpload ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-blue-400'"
                @click="triggerUploadInput"
                @dragover.prevent="isDraggingUpload = true"
                @dragleave.prevent="isDraggingUpload = false"
                @drop.prevent="onUploadDrop"
            >
                <Upload class="w-10 h-10 mx-auto mb-3 text-blue-400" />
                <p class="font-medium text-gray-700">Déposez votre fichier ici</p>
                <p class="text-sm text-gray-400 mt-1">ou cliquez pour parcourir</p>
                <p class="text-xs text-gray-400 mt-2">Formats acceptés : ZIP, PDF, XLS, XLSX</p>
                <input ref="uploadInput" type="file" class="hidden" accept=".zip,.pdf,.xls,.xlsx,application/zip,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" @change="onUploadFileChange" />
            </div>

            <!-- Fichier sélectionné -->
            <div v-if="uploadFile" class="mt-4 flex items-center justify-between border border-green-400 bg-green-50 rounded-lg px-4 py-2">
                <div class="flex items-center gap-2 overflow-hidden">
                    <span class="text-xl">📄</span>
                    <div class="overflow-hidden">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ uploadFile.name }}</p>
                        <p class="text-xs text-gray-500">{{ (uploadFile.size / 1024 / 1024).toFixed(2) }} MB</p>
                    </div>
                </div>
                <button @click="uploadFile = null" class="text-red-500 hover:text-red-700 text-lg" title="Retirer">🗑️</button>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex justify-end gap-3">
                <Button variant="outline" @click="closeUploadModal">Annuler</Button>
                <Button :disabled="!uploadFile || isUploading" @click="submitUpload" class="bg-blue-600 hover:bg-blue-700 text-white">
                    <span v-if="isUploading">Envoi en cours...</span>
                    <span v-else><Upload class="w-4 h-4 inline mr-1" />Envoyer</span>
                </Button>
            </div>
        </div>
    </div>

    <Toaster richColors position="top-center" />
</template>


<script setup>
import { ref, onMounted, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Checkbox } from '@/components/ui/checkbox';
import { Download, ChevronRight, ChevronLeft, Trash2, Edit, Upload } from 'lucide-vue-next';
import { Toaster, toast } from 'vue-sonner';
import DateRangePicker from '@/components/ui/DateRangePicker.vue';
import axios from 'axios';

const zips = ref([]);
const filteredZips = ref([]);
const selectedZips = ref([]);
const loading = ref(true);
const error = ref(null);
const dateRange = ref({ start: null, end: null });
const currentPage = ref(1);
const itemsPerPage = 10;
const allSelected = ref(false);

// Upload
const showUploadModal = ref(false);
const uploadFile = ref(null);
const isDraggingUpload = ref(false);
const isUploading = ref(false);
const uploadInput = ref(null);

const triggerUploadInput = () => uploadInput.value?.click();

const onUploadFileChange = (e) => {
    const f = e.target.files[0];
    if (f) uploadFile.value = f;
};

const onUploadDrop = (e) => {
    isDraggingUpload.value = false;
    const f = e.dataTransfer.files[0];
    if (f) uploadFile.value = f;
};

const closeUploadModal = () => {
    showUploadModal.value = false;
    uploadFile.value = null;
    isDraggingUpload.value = false;
    if (uploadInput.value) uploadInput.value.value = '';
};

const submitUpload = async () => {
    if (!uploadFile.value) return;
    isUploading.value = true;
    const formData = new FormData();
    formData.append('file', uploadFile.value);
    try {
        await axios.post('/archives/zips/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        toast.success('Fichier importé avec succès !');
        closeUploadModal();
        fetchZips();
    } catch (err) {
        toast.error(err.response?.data?.message || "Erreur lors de l'importation");
        console.error(err);
    } finally {
        isUploading.value = false;
    }
};

// Fonction pour simuler un délai de téléchargement
const simulateDownload = (file) => {
    return new Promise((resolve, reject) => {
        try {
            const link = document.createElement('a');
            link.href = file.url;
            link.download = file.name;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';

            setTimeout(() => {
                link.click();
                resolve(file);
            }, 1500);
        } catch (err) {
            reject(err);
        }
    });
};

// Fonction pour télécharger un fichier unique
const downloadSingle = (zip) => {
    toast.promise(
        simulateDownload(zip),
        {
            loading: 'Téléchargement en cours...',
            success: (data) => {
                return `"${data.name}" a été téléchargé avec succès`;
            },
            error: (err) => {
                console.error('Erreur de téléchargement:', err);
                return `Échec du téléchargement de "${zip.name}"`;
            }
        }
    );
};

// Fonction pour télécharger plusieurs fichiers
const downloadMultiple = (files) => {
    const downloadPromises = files.map(file => simulateDownload(file));

    toast.promise(
        Promise.all(downloadPromises),
        {
            loading: `Téléchargement de ${files.length} fichier(s)...`,
            success: (results) => {
                return `${results.length} fichier(s) téléchargé(s) avec succès`;
            },
            error: (err) => {
                console.error('Erreur de téléchargement multiple:', err);
                return 'Échec du téléchargement de certains fichiers';
            }
        }
    );
};

const getRelativePath = (url) => {
    try {
        const urlObj = new URL(url);
        return urlObj.pathname;
    } catch (e) {
        console.error("Erreur lors du traitement de l'URL :", e);
        return url;
    }
};

const renameZip = async (zip) => {
    const newName = window.prompt("Entrez le nouveau nom pour le fichier ZIP (sans extension):", zip.name.replace('.zip', ''));
    if (!newName || newName.trim() === '' || newName === zip.name.replace('.zip', '')) return;

    try {
        const response = await axios.post('/archives/zips/rename', {
            old_name: zip.name,
            new_name: newName.trim() + '.zip'
        });

        toast.success(response.data.message || 'Fichier renommé avec succès');
        fetchZips();
    } catch (err) {
        toast.error(err.response?.data?.message || 'Erreur lors du renommage');
        console.error(err);
    }
};

const deleteZip = async (zip) => {
    if (!window.confirm(`Êtes-vous sûr de vouloir supprimer le fichier "${zip.name}" ?`)) return;

    try {
        const response = await axios.delete(`/archives/zips/delete/${zip.name}`);
        toast.success(response.data.message || 'Fichier supprimé avec succès');
        fetchZips();
    } catch (err) {
        toast.error(err.response?.data?.message || 'Erreur lors de la suppression');
        console.error(err);
    }
};

const fetchZips = async () => {
    try {
        const response = await fetch('/archives/zips');
        if (!response.ok) {
            throw new Error('Erreur lors de la récupération des fichiers');
        }
        const data = await response.json();
        zips.value = data.map(zip => ({
            ...zip,
            url: getRelativePath(zip.url)
        }));
        filteredZips.value = [...zips.value];
    } catch (err) {
        error.value = err.message;
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const onStartDateChange = (start) => {
    applyDateFilter();
};

const onEndDateChange = (end) => {
    applyDateFilter();
};

const applyDateFilter = () => {
    if (!dateRange.value.start && !dateRange.value.end) {
        filteredZips.value = [...zips.value];
        return;
    }

    const start = dateRange.value.start ? new Date(dateRange.value.start).getTime() / 1000 : -Infinity;
    const end = dateRange.value.end ? new Date(dateRange.value.end).getTime() / 1000 + 86400 : Infinity;

    filteredZips.value = zips.value.filter(zip => {
        return zip.timestamp >= start && zip.timestamp <= end;
    });

    currentPage.value = 1;
    allSelected.value = false;
    selectedZips.value = [];
};

const toggleSelect = (zip, checked) => {
    if (checked) {
        if (!selectedZips.value.some(item => item.url === zip.url)) {
            selectedZips.value.push(zip);
        }
    } else {
        selectedZips.value = selectedZips.value.filter(item => item.url !== zip.url);
    }
    allSelected.value = selectedZips.value.length === paginatedZips.value.length;
};

const toggleSelectAll = (checked) => {
    allSelected.value = checked;
    if (checked) {
        selectedZips.value = [...paginatedZips.value];
    } else {
        selectedZips.value = [];
    }
};

const paginatedZips = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredZips.value.slice(start, end);
});

const totalPages = computed(() => {
    return Math.ceil(filteredZips.value.length / itemsPerPage);
});

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
        allSelected.value = false;
    }
};

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
        allSelected.value = false;
    }
};

const downloadSelected = () => {
    if (selectedZips.value.length === 0) {
        toast.error("Veuillez sélectionner au moins un fichier à télécharger.");
        return;
    }

    if (selectedZips.value.length === 1) {
        downloadSingle(selectedZips.value[0]);
    } else {
        downloadMultiple(selectedZips.value);
    }
};

onMounted(() => {
    fetchZips();
});
</script>


<script>
import customMain from "/resources/js/Pages/customMain.vue";
export default {
    layout: customMain,
};
</script>