<template>
    <div class="max-w-7xl mx-auto mb-4 p-6 bg-white rounded-lg shadow-md">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-8 flex items-center justify-center gap-2">
            <i class="fa-solid fa-file-zipper"></i> Listes des dossiers numérisés
        </h1>

        <!-- Fil d'Ariane / Titre du dossier -->
        <div v-if="currentFolder" class="mb-6 flex items-center gap-2 text-gray-600">
            <button @click="backToFolders" class="hover:text-blue-600 transition flex items-center gap-1">
                <i class="fa-solid fa-folder-open"></i> Dossiers
            </button>
            <ChevronRight class="w-4 h-4" />
            <span class="font-semibold text-gray-800">{{ currentFolder }}</span>
        </div>

        <div class="mb-4 mt-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <!-- Filtre par date avec DateRangePicker (uniquement si dans un dossier) -->
            <div v-if="currentFolder" class="mb-6 flex flex-col sm:flex-row gap-4 items-center">
                <DateRangePicker v-model="dateRange" @update:start="onStartDateChange" @update:end="onEndDateChange" />
            </div>

            <!-- Actions (uniquement si dans un dossier) -->
            <div v-if="currentFolder" class="mb-4 flex gap-2">
                <Button v-if="filteredZips.length > 0" @click="downloadSelected" :disabled="selectedZips.length === 0" class="sm">
                    <Download class="mr-1" /> Télécharger la sélection
                </Button>
                <Button v-if="filteredZips.length > 0" @click="deleteSelected" :disabled="selectedZips.length === 0" class="sm bg-red-600 hover:bg-red-700 text-white">
                    <Trash2 class="w-4 h-4 mr-1" /> Supprimer la sélection
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

        <!-- Vue des dossiers -->
        <div v-else-if="!currentFolder" class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div v-for="folder in folders" :key="folder.name" 
                @click="selectFolder(folder.name)"
                class="group p-8 bg-gray-50 hover:bg-blue-50 border-2 border-gray-100 hover:border-blue-200 rounded-2xl cursor-pointer transition-all duration-300 transform hover:-translate-y-1 text-center"
            >
                <div class="bg-blue-100 text-blue-600 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-folder text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">{{ folder.name }}</h3>
                <p class="text-gray-500 text-sm">
                    {{ folder.file_count || 0 }} fichier(s) numérisé(s)
                </p>
                <div class="mt-4 text-blue-600 font-medium flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    Ouvrir <ChevronRight class="w-4 h-4" />
                </div>
            </div>
        </div>

        <!-- Tableau des fichiers -->
        <div v-else>
            <div v-if="filteredZips.length === 0" class="text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
                <i class="fa-solid fa-file-circle-xmark text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">Aucun fichier ZIP disponible dans ce dossier.</p>
            </div>
            <Table v-else>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[50px]">
                            <Checkbox :checked="allSelected" @update:checked="toggleSelectAll" />
                        </TableHead>
                        <TableHead>Nom du fichier</TableHead>
                        <TableHead>Taille</TableHead>
                        <TableHead>Date de création</TableHead>
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
            <div v-if="totalPages > 1" class="flex justify-center items-center gap-4 mt-6">
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
                <h2 class="text-lg font-semibold text-gray-800">Importer un fichier dans {{ currentFolder }}</h2>
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
const folders = ref([]);
const currentFolder = ref(null);
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
    formData.append('folder', currentFolder.value);

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

const downloadSingle = (zip) => {
    window.open(`/archives/zips/download/${zip.name}?folder=${currentFolder.value}`, '_blank');
};

const downloadSelected = async () => {
    if (selectedZips.value.length === 0) return;
    
    try {
        const response = await axios.post('/archives/zips/bulk-download', {
            files: selectedZips.value.map(z => z.name),
            folder: currentFolder.value
        }, {
            responseType: 'blob'
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `téléchargement_groupé_${new Date().getTime()}.zip`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
        
        toast.success('Téléchargement lancé');
    } catch (err) {
        toast.error('Erreur lors du téléchargement groupé');
        console.error(err);
    }
};

const deleteSelected = async () => {
    if (selectedZips.value.length === 0) return;
    if (!window.confirm(`Êtes-vous sûr de vouloir supprimer les ${selectedZips.value.length} fichiers sélectionnés ?`)) return;

    try {
        const response = await axios.delete('/archives/zips/bulk-delete', {
            data: {
                files: selectedZips.value.map(z => z.name),
                folder: currentFolder.value
            }
        });

        toast.success(response.data.message || 'Fichiers supprimés avec succès');
        selectedZips.value = [];
        allSelected.value = false;
        fetchZips();
    } catch (err) {
        toast.error(err.response?.data?.message || 'Erreur lors de la suppression groupée');
        console.error(err);
    }
};

const getRelativePath = (url) => {
    try {
        const urlObj = new URL(url);
        return urlObj.pathname;
    } catch (e) {
        return url;
    }
};

const renameZip = async (zip) => {
    const newName = window.prompt("Entrez le nouveau nom pour le fichier ZIP (sans extension):", zip.name.replace('.zip', ''));
    if (!newName || newName.trim() === '' || newName === zip.name.replace('.zip', '')) return;

    try {
        const response = await axios.post('/archives/zips/rename', {
            old_name: zip.name,
            new_name: newName.trim() + '.zip',
            folder: currentFolder.value
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
        const response = await axios.delete(`/archives/zips/delete/${zip.name}`, {
            params: { folder: currentFolder.value }
        });
        toast.success(response.data.message || 'Fichier supprimé avec succès');
        fetchZips();
    } catch (err) {
        toast.error(err.response?.data?.message || 'Erreur lors de la suppression');
        console.error(err);
    }
};

const fetchZips = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (currentFolder.value) params.append('folder', currentFolder.value);
        if (dateRange.value.start) params.append('start_date', dateRange.value.start);
        if (dateRange.value.end) params.append('end_date', dateRange.value.end);

        const response = await axios.get(`/archives/zips?${params.toString()}`);
        const data = response.data;

        if (!currentFolder.value) {
            folders.value = data;
        } else {
            zips.value = data.map(zip => ({
                ...zip,
                url: getRelativePath(zip.url)
            }));
            filteredZips.value = [...zips.value];
            applyDateFilter();
        }
    } catch (err) {
        error.value = "Erreur lors de la récupération des données";
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const selectFolder = (folderName) => {
    currentFolder.value = folderName;
    fetchZips();
};

const backToFolders = () => {
    currentFolder.value = null;
    fetchZips();
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

const onStartDateChange = () => applyDateFilter();
const onEndDateChange = () => applyDateFilter();

const toggleSelectNum = (zip, checked) => {
    if (checked) {
        if (!selectedZips.value.some(item => item.url === zip.url)) {
            selectedZips.value.push(zip);
        }
    } else {
        selectedZips.value = selectedZips.value.filter(item => item.url !== zip.url);
    }
    allSelected.value = selectedZips.value.length === paginatedZips.value.length && paginatedZips.value.length > 0;
};

const toggleSelect = (zip, checked) => {
    if (checked) {
        if (!selectedZips.value.some(z => z.name === zip.name)) {
            selectedZips.value.push(zip);
        }
    } else {
        selectedZips.value = selectedZips.value.filter(z => z.name !== zip.name);
    }
    allSelected.value = selectedZips.value.length === paginatedZips.value.length && paginatedZips.value.length > 0;
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