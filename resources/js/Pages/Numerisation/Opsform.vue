<template>
    <div class="flex flex-col space-y-4 mx-8 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
        <h4 class="text-2xl font-bold tracking-tight">Nouvelle Numérisation</h4>
        <Link :href="route('show.numerisation.list')">
        <Button>
            <MoveLeft class="w-4 h-4 mr-2" /> Retour
        </Button>
        </Link>
    </div>
    <div class="rounded-lg dark:border-gray-700">
        <main class="flex flex-1 flex-col gap-4 p-4 md:gap-8 md:p-8">
            <Card class="h-full flex flex-col gap-4 p-10">
                <ScrollArea class="h-full w-full">
                    <!-- En-tête -->
                    <div
                        class="flex flex-col space-y-4 py-2 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                        <h2>
                            <strong>Service :</strong>
                            {{ dossier.r_dossier_services.nom_service }}
                        </h2>
                        <h2>
                            Statut du dossier :
                            <Badge class="mx-2" :variant="dossier?.statut === 1
                                ? 'warning'
                                : dossier?.statut === 2
                                    ? 'success'
                                    : dossier?.statut === 3
                                        ? 'error'
                                        : 'secondary'
                                ">
                                {{
                                    dossier?.statut === 1
                                        ? "En attente"
                                        : dossier?.statut === 2
                                            ? "Validé"
                                            : dossier?.statut === 3
                                                ? "Refusé"
                                                : "Inconnu"
                                }}
                            </Badge>
                        </h2>
                    </div>
                    <!-- Motif de rejet -->
                    <div v-if="dossier.motif_rejet" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-1 mb-1">
                        <p class="border border-red-300 rounded-md p-3">
                            <strong>Motif de rejet :</strong>
                            {{ dossier.motif_rejet }}
                        </p>
                    </div>
                    <!-- Type de service -->
                    <h2>
                        <strong>Type de Service :</strong>
                        <template v-if="
                            dossier.detail && Array.isArray(dossier.detail)
                        ">
                            {{ dossier.detail.join(", ") }}
                        </template>
                        <template v-else-if="
                            dossier.detail &&
                            typeof dossier.detail === 'string'
                        ">
                            {{ JSON.parse(dossier.detail).join(", ") }}
                        </template>
                    </h2>
                    <!-- Titre des pièces justificatives -->
                    <h3 class="text-xl font-semibold mt-2 mb-2 text-center">
                        Pièces justificatifs (joindre les documents du dossier)
                    </h3>
                    <!-- Informations véhicule -->
                    <div>
                        <h1 class="font-semibold mt-4 mb-5">
                            Numérisation Globale
                        </h1>
                        <div class="w-full">
                            <!-- Fichier Global (10 pages) -->
                            <div class="space-y-4">
                                <p class="text-lg font-medium">Fichier de numérisation global (Scannez les 12 pages ensemble) *</p>
                                <CustomFileUpload 
                                    dropText="Déposez le fichier PDF contenant les 12 pages ici" 
                                    previewText="Fichier global sélectionné"
                                    accept="application/pdf" 
                                    @input="form.global_scan = $event.target.files[0]" 
                                    previewPlaceholder="Aucun fichier sélectionné" 
                                    :dossier="dossier"
                                    @file-selected="handleFile" 
                                    modal-title="Aperçu du scan global" 
                                />
                                <p class="text-sm text-gray-500 italic">
                                    Note : Le fichier doit contenir les 12 pièces dans l'ordre (1. Recensement, 2. Permis, 3. Bon à enlever, 4. Déclaration D3, 5. Carte Grise, 6. Carte Pro, 7. Identification, 8. Pièce ID Propriétaire, 9. Quittance Douanes, 10. RTI, 11. Assurance, 12. Visite Technique).
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Bouton Enregistrer -->
                    <div class="w-full flex flex-row justify-between mt-10">
                        <div></div>
                        <div>
                            <Button @click="submitForm" class="bg-green-600 text-white py-4 px-4 rounded-lg">
                                <Check class="w-5 h-5" /> Enregistrers
                            </Button>
                        </div>
                    </div>
                </ScrollArea>
            </Card>
        </main>
        <!-- Overlay Loader -->
        <div v-if="isLoading" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl p-6 shadow-lg flex flex-col items-center">
                <span class="loader mb-3"></span>
                <p class="text-lg font-medium">
                    Traitement et enregistrement en cours...
                </p>
            </div>
        </div>
    </div>
    <Toaster richColors position="top-right" />
</template>

<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { LogIn, RefreshCcw, ArrowRight, Check } from "lucide-vue-next";

const props = defineProps({
    dossier: Object,
    client: Object,
});

const isLoading = ref(false);
const form = useForm({
    id_dossier: props.dossier.id,
    global_scan: null,
    // Timestamps
    created_at: Date.now(),
    updated_at: Date.now(),
});

const submitForm = async () => {
    try {
        if (!form.global_scan) {
            toast.error("Le fichier de numérisation global est obligatoire.");
            return;
        }

        // Préparation des données pour l'envoi
        isLoading.value = true;
        const formData = new FormData();
        Object.entries(form).forEach(([key, value]) => {
            if (value !== null && value !== undefined) {
                formData.append(key, value);
            }
        });

        // Envoi des données au serveur
        await axios.post("/numerisation/ops/save", formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        // Réinitialisation et redirection après succès
        toast.success("Documents enregistrés avec succès !");
        form.reset();
        router.visit("/numerisation/list");
    } catch (error) {
        console.error(error);
        toast.error("Erreur lors de l'envoi des documents");
    } finally {
        isLoading.value = false;
    }
};
</script>

<script>
import { Card } from "@/components/ui/card";
import customMain from "/resources/js/Pages/customMain.vue";
import { Button } from "@/components/ui/button";
import { router, useForm } from "@inertiajs/vue3";
import { MoveLeft } from "lucide-vue-next";
import { Toaster, toast } from "vue-sonner";
import { Badge } from "@/components/ui/badge";
import CustomFileUpload from "@/components/CustomFileUpload.vue";
import { ScrollArea } from "@/components/ui/scroll-area";
export default {
    layout: customMain,
};
</script>

<style scoped>
/* Petit spinner CSS */
.loader {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}
</style>
