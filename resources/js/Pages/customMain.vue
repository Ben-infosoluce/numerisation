<template>
    <div class="flex flex-col h-screen dark:bg-gray-800 ">
        <!-- Full-width white header -->
        <!-- <Toaster position="top-right" /> -->
        <header class="bg-white shadow-md w-full z-50">
            <div class="flex mx-auto px-4 lg:py-4 md:py-4 sm:py-4 py-3 text-start justify-between">
                <div>
                    <!-- <Link :href="route('home')"> -->
                    <img src="/public/assets/images/logo_dgttc.jpg" alt="" style=" width: 140px;height: 40px;" />
                    <!-- </Link> -->
                    <!-- <h1 class="text-xl font-semibold text-gray-800 ">Your App Name</h1> -->
                </div>


                <div class="flex items-center cursor-pointer">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Avatar>
                                <AvatarFallback>{{
                                    getInitials()
                                    }}</AvatarFallback>
                            </Avatar>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="w-56">
                            <DropdownMenuLabel>Mon compte</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuGroup>
                                <DropdownMenuItem>
                                    <User class="mr-2 h-4 w-4" />
                                    <span>Profile</span>
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="openPasswordModal">
                                    <Settings class="mr-2 h-4 w-4" />
                                    <span>Mot de passe</span>
                                </DropdownMenuItem>
                            </DropdownMenuGroup>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem>
                                <LogOut class="mr-2 h-4 w-4" />
                                <span @click="logout">Deconnexion</span>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <button data-drawer-target="sidebar-multi-level-sidebar"
                        data-drawer-toggle="sidebar-multi-level-sidebar" aria-controls="sidebar-multi-level-sidebar"
                        type="button"
                        class="inline-flex items-center p-2 mt-2 ms-3 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path clip-rule="evenodd" fill-rule="evenodd"
                                d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
        </header>


        <div class="flex items-center space-x-2">
            <AlertDialog :open="openPassword">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Modifier votre mot de passe
                        </AlertDialogTitle>

                        <AlertDialogDescription>
                            <Input v-model="oldPassword" class="my-8" placeholder="Ancien mot de passe"
                                type="password" />
                            <Input v-model="newPassword" class="my-8" placeholder="Nouveau mot de passe"
                                type="password" />
                            <Input v-model="confirmPassword" class="my-8"
                                placeholder="Confirmer le nouveau mot de passe" type="password" />
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter class="flex flex-row gap-2">
                        <AlertDialogCancel @click="openPassword = false">
                            Annuler
                        </AlertDialogCancel>
                        <div>
                            <Button @click="handlePassword">
                                Valider
                                <span v-if="loading" class="ml-2 animate-spin">⏳</span>
                            </Button>
                        </div>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
        <div class="mt-4 flex-1 overflow-y-auto p-6">
            <slot></slot>
        </div>
    </div>
</template>

<script setup>
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
    DropdownMenuGroup,
} from "@/components/ui/dropdown-menu";
import { LogOut, Settings, User, LockKeyhole } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import Menu from "./Menu.vue";
import axios from "axios";
// import { LockKeyhole } from "lucide-vue-next";
import { usePage } from "@inertiajs/vue3";
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
import { Toaster, toast } from "vue-sonner";

import { useCaisseStore } from "@/stores/mainStore";
import { storeToRefs } from "pinia";
import { ref, computed, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
// import Input from "@/components/ui/input/Input.vue";

const store = useCaisseStore();
const { isOpen, error, loading } = storeToRefs(store);
const { close } = store;
const fondDeCaisse = ref("");
const fondDeCaisseAttendu = ref(0);
const paiements = ref([]);
const currentCaisse = ref(null);
const montantRecuCheck = ref(true);
const montantSaisieCaisse = ref("");
const openPassword = ref(false);
const oldPassword = ref("");
const newPassword = ref("");
const confirmPassword = ref("");

const openPasswordModal = () => {
    openPassword.value = true;
};

const handlePassword = async () => {
    if (!oldPassword.value || !newPassword.value || !confirmPassword.value) {
        toast.error("Veuillez remplir tous les champs");
        return;
    }

    loading.value = true;

    try {
        const userId = usePage().props.auth_user?.data.id;

        await axios.post(`/users/change-password/${userId}`, {
            old_password: oldPassword.value,
            new_password: newPassword.value,
            new_password_confirmation: confirmPassword.value,
        });

        toast.success("Mot de passe modifié avec succès");
        openPassword.value = false;

        // Reset champs (bonne UX)
        oldPassword.value = "";
        newPassword.value = "";
        confirmPassword.value = "";
    } catch (error) {
        // Gestion erreurs Laravel (422)
        if (error.response?.status === 422) {
            const errors = error.response.data.errors;
            const firstError = Object.values(errors)[0]?.[0];
            toast.error(firstError);
        } else {
            toast.error(
                "Une erreur est survenue lors de la modification du mot de passe"
            );
        }
    } finally {
        loading.value = false;
    }
};

const handleClose = async () => {
    handleValidate();
    // await close(fondDeCaisse.value);
};

function logout() {
    axios
        .post("/logout")
        .then(() => {
            console.log("Déconnexion réussie");
            router.visit("/");
            // window.location.href = '/';
        })
        .catch((error) => {
            console.error("Erreur lors de la déconnexion :", error);
        });
}

function getInitials() {
    const user = usePage().props.auth_user?.data;
    // Vérifier si l'objet user est défini et contient les propriétés nom et prenom
    if (user && user.nom && user.prenom) {
        // Extraire la première lettre du nom et du prénom et les convertir en majuscules
        const firstInitial = user.nom.charAt(0).toUpperCase();
        const lastInitial = user.prenom.charAt(0).toUpperCase();
        // Retourner les initiales combinées
        return `${firstInitial}${lastInitial}`;
    }
    return "";
}

</script>

<script>
export default {
    name: "SidebarWithHeader",
    // Add any necessary component logic here
};
</script>

<style scoped>
/* Add any component-specific styles here */
</style>
