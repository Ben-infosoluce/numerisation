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

            <!-- Bouton de téléchargement groupé -->
            <div v-if="filteredZips.length > 0" class="mb-4">
                <Button @click="downloadSelected" :disabled="selectedZips.length === 0" class="sm">
                    <Download class="mr-1" /> Télécharger la sélection
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
                        <TableCell>
                            <Button variant="outline" size="sm" @click="downloadSingle(zip)">
                                <Download class="mr-1" />Télécharger
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
    <Toaster richColors position="top-center" />
</template>


<script setup>
import { ref, onMounted, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Checkbox } from '@/components/ui/checkbox';
import { Download, ChevronRight, ChevronLeft } from 'lucide-vue-next';
import { Toaster, toast } from 'vue-sonner';
import DateRangePicker from '@/components/ui/DateRangePicker.vue';

const zips = ref([]);
const filteredZips = ref([]);
const selectedZips = ref([]);
const loading = ref(true);
const error = ref(null);
const dateRange = ref({ start: null, end: null });
const currentPage = ref(1);
const itemsPerPage = 10;
const allSelected = ref(false);

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