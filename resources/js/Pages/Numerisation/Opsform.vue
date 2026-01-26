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
                            Informations Véhicule
                        </h1>
                        <div class="w-full grid gap-4 md:gap-8 lg:grid-cols-3 xl:grid-cols-3">
                            <!-- Formulaire de recensement (obligatoire) -->
                            <div class="space-y-2">
                                <p>Formulaire de recensement *</p>
                                <CustomFileUpload dropText="Formulaire de recensement" previewText="Fichier sélectionné"
                                    :accept="acceptedImageTypes" @input="
                                        form.formulaire_recensement =
                                        $event.target.files[0]
                                        " previewPlaceholder="Aucun fichier sélectionné" :dossier="dossier"
                                    @file-selected="handleFile" modal-title="Détails du formulaire de recensement" />
                            </div>

                            <!-- Fiche de Réception à Titre Isolé (RTI) (obligatoire) -->
                            <div class="space-y-2">
                                <p>Fiche de Réception à Titre Isolé (RTI) *</p>
                                <CustomFileUpload dropText="Fiche RTI" previewText="Fichier sélectionné"
                                    :accept="acceptedImageTypes" @input="
                                        form.fiche_rti = $event.target.files[0]
                                        " previewPlaceholder="Aucun fichier sélectionné" :dossier="dossier"
                                    @file-selected="handleFile" modal-title="Détails de la fiche RTI" />
                            </div>

                            <!-- Fiche CIVIO (obligatoire) -->
                            <div class="space-y-2">
                                <p>Fiche CIVIO *</p>
                                <CustomFileUpload dropText="Fiche CIVIO" previewText="Fichier sélectionné"
                                    :accept="acceptedImageTypes" @input="
                                        form.fiche_civio =
                                        $event.target.files[0]
                                        " previewPlaceholder="Aucun fichier sélectionné" :dossier="dossier"
                                    @file-selected="handleFile" modal-title="Détails de la fiche CIVIO" />
                            </div>

                            <!-- Carte Nationale d’Identité (CNI) (obligatoire) -->
                            <div class="space-y-2">
                                <p>Carte Nationale d’Identité (CNI) *</p>
                                <CustomFileUpload dropText="CNI" previewText="Fichier sélectionné"
                                    :accept="acceptedImageTypes" @input="form.cni = $event.target.files[0]"
                                    previewPlaceholder="Aucun fichier sélectionné" :dossier="dossier"
                                    @file-selected="handleFile" modal-title="Détails de la CNI" />
                            </div>

                            <!-- Carte professionnelle (obligatoire) -->
                            <div class="space-y-2">
                                <p>Carte professionnelle *</p>
                                <CustomFileUpload dropText="Carte professionnelle" previewText="Fichier sélectionné"
                                    :accept="acceptedImageTypes" @input="
                                        form.carte_professionnelle =
                                        $event.target.files[0]
                                        " previewPlaceholder="Aucun fichier sélectionné" :dossier="dossier"
                                    @file-selected="handleFile" modal-title="Détails de la carte professionnelle" />
                            </div>

                            <!-- Permis de conduire civil (obligatoire) -->
                            <div class="space-y-2">
                                <p>Permis de conduire civil *</p>
                                <CustomFileUpload dropText="Permis de conduire" previewText="Fichier sélectionné"
                                    :accept="acceptedImageTypes" @input="
                                        form.permis_conduire =
                                        $event.target.files[0]
                                        " previewPlaceholder="Aucun fichier sélectionné" :dossier="dossier"
                                    @file-selected="handleFile" modal-title="Détails du permis de conduire" />
                            </div>

                            <!-- Documents douaniers (obligatoire) -->
                            <div class="space-y-2">
                                <p>
                                    Documents douaniers (quittance, bon à
                                    enlever, déclaration) *
                                </p>
                                <CustomFileUpload dropText="Documents douaniers" previewText="Fichier sélectionné"
                                    :accept="acceptedImageTypes" @input="
                                        form.documents_douane =
                                        $event.target.files[0]
                                        " previewPlaceholder="Aucun fichier sélectionné" :dossier="dossier"
                                    @file-selected="handleFile" modal-title="Détails des documents douaniers" />
                            </div>

                            <!-- Fiche de demande de carte grise (obligatoire) -->
                            <div class="space-y-2">
                                <p>Fiche de demande de carte grise *</p>
                                <CustomFileUpload dropText="Fiche de demande de carte grise"
                                    previewText="Fichier sélectionné" :accept="acceptedImageTypes" @input="
                                        form.fiche_demande_carte_grise =
                                        $event.target.files[0]
                                        " previewPlaceholder="Aucun fichier sélectionné" :dossier="dossier"
                                    @file-selected="handleFile"
                                    modal-title="Détails de la fiche de demande de carte grise" />
                            </div>

                            <!-- Assurance en cours de validité (obligatoire) -->
                            <div class="space-y-2">
                                <p>Assurance en cours de validité *</p>
                                <CustomFileUpload dropText="Assurance en cours de validité"
                                    previewText="Fichier sélectionné" :accept="acceptedImageTypes" @input="
                                        form.assurance = $event.target.files[0]
                                        " previewPlaceholder="Aucun fichier sélectionné" :dossier="dossier"
                                    @file-selected="handleFile" modal-title="Détails de l'assurance" />
                            </div>
                        </div>
                    </div>

                    <!-- Bouton Enregistrer -->
                    <div class="w-full flex flex-row justify-between">
                        <div></div>
                        <div>
                            <Button @click="submitForm" class="bg-amber-800 text-white py-4 px-4 rounded-lg">
                                Enregistrer
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

const acceptedImageTypes = ".jpeg,.png,.jpg,.webp";
const props = defineProps({
    dossier: Object,
    client: Object,
});

const isLoading = ref(false);
const form = useForm({
    id_dossier: props.dossier.id,
    // Nouveaux champs obligatoires
    formulaire_recensement: null, // Formulaire de recensement
    fiche_rti: null, // Fiche de Réception à Titre Isolé (RTI)
    fiche_civio: null, // Fiche CIVIO
    cni: null, // Carte Nationale d’Identité (CNI)
    carte_professionnelle: null, // Carte professionnelle
    permis_conduire: null, // Permis de conduire civil
    documents_douane: null, // Documents émis par la douane (quittance, bon à enlever, déclaration)
    fiche_demande_carte_grise: null, // Fiche de demande de carte grise
    assurance: null, // Assurance en cours de validité
    // Timestamps
    created_at: Date.now(),
    updated_at: Date.now(),
});

const submitForm = async () => {
    try {
        // Liste de tous les champs obligatoires
        const requiredFields = [
            {
                value: form.formulaire_recensement,
                name: "Formulaire de recensement",
            },
            {
                value: form.fiche_rti,
                name: "Fiche de Réception à Titre Isolé (RTI)",
            },
            { value: form.fiche_civio, name: "Fiche CIVIO" },
            { value: form.cni, name: "Carte Nationale d’Identité (CNI)" },
            {
                value: form.carte_professionnelle,
                name: "Carte professionnelle",
            },
            { value: form.permis_conduire, name: "Permis de conduire civil" },
            {
                value: form.documents_douane,
                name: "Documents émis par la douane",
            },
            {
                value: form.fiche_demande_carte_grise,
                name: "Fiche de demande de carte grise",
            },
            { value: form.assurance, name: "Assurance en cours de validité" },
        ];

        // Vérification des champs obligatoires
        for (const field of requiredFields) {
            if (!field.value) {
                toast.error(`${field.name} est obligatoire.`);
                return;
            }
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
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
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
