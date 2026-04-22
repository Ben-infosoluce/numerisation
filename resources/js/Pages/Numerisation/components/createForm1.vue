<template>
    <!-- Header sticky -->
    <div
        class="sticky top-[-20px] z-10 bg-[#ffffff] dark:bg-gray-900 flex flex-col space-y-4 px-8 py-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
        <h4 class="text-2xl font-bold tracking-tight">Détails du dossiers</h4>
        <div class="flex items-center space-x-2">
            <Button @click="returnBack()">
                <MoveLeft class="w-4 h-4 mr-2" /> Retour
            </Button>
        </div>
    </div>

    <!-- Contenu scrollable -->
    <div class="rounded-lg dark:border-gray-700">
        <main class="flex flex-1 flex-col gap-4 p-4 md:gap-8 md:p-8">
            <div class="grid gap-4 md:gap-8 lg:grid-cols-2 xl:grid-cols-3">
                <Card class="xl:col-span-2">
                    <ScrollArea class="h-full w-full rounded-md border">
                        <!-- Si pas Post-immatriculation -->
                        <div class="m-8" v-if="dossier.id_service != 3">
                            <!-- Service & statut du dossier -->
                            <div
                                class="flex flex-col space-y-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                                <h2>
                                    <strong>Service : </strong>{{
                                        dossier.r_dossier_services.nom_service
                                    }}
                                    <samp>
                                        /{{
                                            dossier_lier
                                                ? dossier_lier
                                                    .r_dossier_services
                                                    .nom_service
                                                : ""
                                        }}</samp>
                                </h2>
                                <h2>
                                    Statut du dossier :
                                    <Badge :variant="dossier?.statut === 1
                                        ? 'warning'
                                        : dossier?.statut === 2
                                            ? 'success'
                                            : dossier?.statut === 3
                                                ? 'error'
                                                : dossier?.statut === 4
                                                    ? 'warning'
                                                    : 'secondary'
                                        ">
                                        {{
                                            dossier?.statut === 2
                                                ? "Validé"
                                                : dossier?.statut === 3
                                                    ? "Refusé"
                                                    : "En attente de validation"
                                        }}
                                    </Badge>
                                </h2>
                            </div>

                            <!-- Type de Service & motif de rejet -->
                            <div
                                class="flex flex-col space-y-4 py-2 sm:flex-row sm:items-center justify-between sm:space-y-0">
                                <div>
                                    <h2>
                                        <strong>Type de Service : </strong>
                                        <template v-if="
                                            dossier.detail &&
                                            Array.isArray(dossier.detail)
                                        ">
                                            {{ dossier.detail.join(", ") }}
                                            <samp>{{
                                                dossier_lier
                                                    ? dossier_lier.detail.join(
                                                        ", ",
                                                    )
                                                    : ""
                                            }}
                                            </samp>
                                        </template>
                                        <template v-else-if="
                                            dossier.detail &&
                                            typeof dossier.detail ===
                                            'string'
                                        ">
                                            {{
                                                JSON.parse(dossier.detail).join(
                                                    ", ",
                                                )
                                            }}
                                        </template>
                                    </h2>
                                </div>
                            </div>

                            <!-- Informations du véhicule -->
                            <h3 class="mt-3">
                                <strong> Informations du véhicule</strong>
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 mb-6">
                                <p>
                                    Châssis (VIN) :
                                    {{ dossier.r_dossier_vehicule.vin }}
                                </p>
                                <p>
                                    Marque du véhicule :
                                    {{ dossier.r_dossier_vehicule.marque }}
                                </p>
                                <p>
                                    Modèle du véhicule :
                                    {{ dossier.r_dossier_vehicule.modele }}
                                </p>
                                <p>
                                    Genre :
                                    {{
                                        dossier.r_dossier_vehicule
                                            .genre_vehicule
                                    }}
                                </p>
                                <p>
                                    Poids Total en charge :
                                    {{
                                        dossier.r_dossier_vehicule
                                            .poids_total_charge
                                    }}
                                </p>
                                <p>
                                    Poids Utile :
                                    {{ dossier.r_dossier_vehicule.poids_utile }}
                                </p>
                                <p>
                                    Sources d’énergie :
                                    {{
                                        dossier.r_dossier_vehicule
                                            .source_energie
                                    }}
                                </p>
                                <p>
                                    Couleur :
                                    {{ dossier.r_dossier_vehicule.couleur }}
                                </p>
                                <p>
                                    Carrosserie :
                                    {{ dossier.r_dossier_vehicule.carrosserie }}
                                </p>
                                <p>
                                    Type technique :
                                    {{
                                        dossier.r_dossier_vehicule
                                            .type_technique
                                    }}
                                </p>
                                <p>
                                    Poids à Vide :
                                    {{ dossier.r_dossier_vehicule.poids_vide }}
                                </p>
                                <p>
                                    Puissance administrative :
                                    {{
                                        dossier.r_dossier_vehicule
                                            .puissance_administrative
                                    }}
                                </p>
                                <p>
                                    Places Assises :
                                    {{
                                        dossier.r_dossier_vehicule
                                            .places_assises
                                    }}
                                </p>
                                <p>
                                    Nbre d’Essieux :
                                    {{
                                        dossier.r_dossier_vehicule
                                            .nombre_essieux
                                    }}
                                </p>
                                <p>
                                    Usage :
                                    {{
                                        dossier.r_dossier_vehicule
                                            .usage_vehicule
                                    }}
                                </p>
                                <p v-if="
                                    dossier.r_dossier_vehicule
                                        .num_immatriculation
                                ">
                                    N° d'immatriculation :
                                    {{
                                        dossier.r_dossier_vehicule
                                            .num_immatriculation
                                    }}
                                </p>
                                <p v-if="
                                    dossier.r_dossier_vehicule
                                        .num_immatriculation_precedant
                                ">
                                    N° d'immatriculation precedant :
                                    {{
                                        dossier.r_dossier_vehicule
                                            .num_immatriculation_precedant
                                    }}
                                </p>
                                <p v-if="dossier.reserverNumero">
                                    N° réservé : {{ dossier.reserverNumero }}
                                </p>
                                <p v-if="dossier.numeroDiplomatique">
                                    N° d'immatriculation diplomatique :
                                    {{ dossier.numeroDiplomatique }}
                                </p>
                            </div>
                            <hr />
                            <!-- Informations du propriétaire -->
                            <h3 class="mt-6">
                                <strong>
                                    Informations du propriétaire :
                                </strong>
                                {{ dossier.r_dossier_client.civilite }} ,
                                {{ dossier.r_dossier_client.nom }}
                                {{ dossier.r_dossier_client.prenom }}
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 mb-6">
                                <p>
                                    Adresse :
                                    {{ dossier.r_dossier_client.adresse }}
                                </p>
                                <p>
                                    Email : {{ dossier.r_dossier_client.email }}
                                </p>
                                <p>
                                    Telephone :
                                    {{ dossier.r_dossier_client.telephone }}
                                </p>
                                <p>
                                    Date de naissance :
                                    {{
                                        dossier.r_dossier_client.date_naissance
                                    }}
                                </p>
                                <p>
                                    Ville de naissance :
                                    {{
                                        dossier.r_dossier_client.ville_naissance
                                    }}
                                </p>
                            </div>


                        </div>


                    </ScrollArea>
                </Card>

                <Card>
                    <div class="m-8">
                        <h3 class="mt-6 text-center">DOCUMENTATION</h3>
                        <div class="max-w-xl mx-auto bg-white rounded-lg shadow p-4 mt-6">
                            <div class="space-y-5"></div>
                        </div>
                    </div>
                </Card>
            </div>
            <div v-if="dossier.statut_numerisation == 1 && (dossier.statut == 1 || dossier.statut == 4) "
                class="sticky top-[-20px] z-10 bg-[#ffffff] dark:bg-gray-900 flex flex-col space-y-4 px-8 py-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                <div></div>
                <div
                    class="sticky top-[-20px] z-10 bg-[#ffffff] dark:bg-gray-900 flex flex-col space-y-4 px-8 py-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                    <div></div>
                    <div class="flex items-center space-x-2">
                        <Link v-if="
                            props.dossier.r_dossier_services
                                .nom_service ===
                            'Immatriculation spéciale'
                        " :href="route('show.ops.form.numerisation', {
                            dossier: dossier.id,
                            service:
                                dossier.r_dossier_services
                                    .nom_service,
                            detail: dossier.detail,
                            physique_morale:
                                dossier.r_dossier_vehicule
                                    .physique_morale,
                        })
                            ">
                        <Button class="bg-[#068A06]">
                            <CreditCard class="w-4 h-4 mr-2" />
                            Ouvrir la numérisation
                        </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { ref, computed, onMounted } from "vue";
// import { returnBack } from "/resources/js/composable/fonction.js";
import {
    MoveRight,
    MoveLeft,
    HandCoins,
    CreditCard,
    Eye,
    CircleAlert,
} from "lucide-vue-next";
import { Card } from "@/components/ui/card";
import { ScrollArea } from "@/components/ui/scroll-area";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import InfoRow from "./InfoRow.vue";
import axios from "axios";
import { Toaster, toast } from "vue-sonner";
import { router } from "@inertiajs/vue3";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
const props = defineProps({
    vin: String,
    dossier: Object,
    dossier_lier: Object,
    client: Object,
    old: Object,
    new: Object,
});
console.log("dossier_lier:", props.dossier_lier);

onMounted(() => {
    if (props.dossier.r_dossier_vehicule.entreprise_id) {
        fetchEntreprise(props.dossier.r_dossier_vehicule.entreprise_id);
    }
});

const returnBack = () => {
    const params = new URLSearchParams(window.location.search)
    if (params.toString()) {
        router.visit('/numerisation?' + params.toString())
    } else {
        window.history.back()
    }
}
const motifRejet = ref("");
const entreprise = ref(null);


// ✅ Fonction pour charger les données
async function fetchEntreprise(id) {
    try {
        const response = await axios.get(`/entreprises/${id}`);
        entreprise.value = response.data.data; // On stocke uniquement la partie data
        console.log("Données de l'entreprise :", entreprise.value);
    } catch (error) {
        console.error("Erreur :", error.response?.data || error);
        entreprise.value = null;
    }
}

;



</script>

<script>
import CustomMainMain from "/resources/js/Pages/customMain.vue";


export default {
    layout: CustomMainMain,
};
</script>

<style scoped>
/* Optionnel pour responsive / animation */
</style>
