<template>
    <div>
        <div class="flex flex-col space-y-4 mx-8  sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
            <!-- Titre -->
            <h4 class="text-3xl font-bold tracking-tight">
                Dashboard
            </h4>
        </div>
        <div class="    rounded-lg dark:border-gray-700">
            <main class="flex flex-1 flex-col gap-4 p-4 md:gap-8 md:p-8">
                <div class="grid gap-4 md:grid-cols-2 md:gap-8 lg:grid-cols-3">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total Enrolement</CardTitle>
                            <DollarSign class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats?.Total ?? '...' }}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Dossier Scanner</CardTitle>
                            <Users class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats?.['Dossier numérisé'] ?? '...' }}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">En attente de traitement</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats?.['En attente de traitement'] ?? '...' }}</div>
                        </CardContent>
                    </Card>
                </div>
            </main>
        </div>
        <div>
        </div>
        <div class="p-6 space-y-8">
            <!-- Sélecteur de période -->
            <div class="bg-white  rounded p-4">
                <h2 class="text-xl font-semibold my-8">Filtrer par période</h2>
                <Tabs default-value="today" v-model="periode">
                    <TabsList>
                        <TabsTrigger value="today">Aujourd'hui</TabsTrigger>
                        <TabsTrigger value="week">Cette semaine</TabsTrigger>
                        <TabsTrigger value="month">Ce mois</TabsTrigger>
                        <TabsTrigger value="year">Cette année</TabsTrigger>
                    </TabsList>
                </Tabs>
            </div>

            <!-- Grille responsive : 1 colonne sur mobile, 3 colonnes sur tablette/desktop -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 w-full">

                <!-- Carte 1 -->
                <div class="bg-white shadow rounded p-4 flex flex-col">
                    <h2 class="text-xl font-semibold mb-2">Total enrolement par Site</h2>
                    <!-- Conteneur relatif important pour le resize des charts -->
                    <div class="relative flex-grow" style="min-height: 300px;">
                        <div ref="totalSiteChart" class="w-full h-full"></div>
                    </div>
                </div>

                <!-- Carte 2 -->
                <div class="bg-white shadow rounded p-4 flex flex-col">
                    <h2 class="text-xl font-semibold mb-2">Dossier Scanner par Site</h2>
                    <div class="relative flex-grow" style="min-height: 300px;">
                        <div ref="
                            dossierSiteChart" class="w-full h-full"></div>
                    </div>
                </div>

                <!-- Carte 3 -->
                <div class="bg-white shadow rounded p-4 flex flex-col">
                    <h2 class="text-xl font-semibold mb-2">En attente de traitement par Site</h2>
                    <div class="relative flex-grow" style="min-height: 300px;">
                        <div ref="attenteChart" class="w-full h-full"></div>
                    </div>
                </div>
            </div>

        </div>

        <stat />
    </div>

</template>

<script setup>
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import axios from 'axios'
import { ref, watch, onMounted, nextTick } from 'vue'
import { DollarSign, Users } from 'lucide-vue-next'
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import * as echarts from "echarts";
import Stat from "@/Pages/Admin/components/stat.vue";

// 📊 Stats cards
const stats = ref(null)
const data = ref([])
const dateRange = ref({ from: null, to: null })

// 📊 Charts refs
const totalSiteChart = ref(null)
const dossierSiteChart = ref(null)
const attenteChart = ref(null)

// ⏱️ période
const periode = ref("today")

// 📊 DATA charts
const newStats = ref({
    totalSites: [],
    dossiersNumerises: [],
    attenteSites: [],
})

// ⏳ loading
const loading = ref(false)

/**
 * 🔁 Agrégation (cards)
 */
function aggregate(data) {
    return data.reduce((acc, item) => {
        for (const key in item) {
            if (key !== 'name') {
                acc[key] = (acc[key] || 0) + item[key]
            }
        }
        return acc
    }, {})
}

/**
 * 📥 STATS GLOBAL (cards)
 */
async function fetchStats() {
    try {
        const response = await axios.get('/admin/dashboard/stats')
        stats.value = aggregate(response.data)
        data.value = response.data
    } catch (err) {
        console.error('Erreur stats', err)
    }
}

/**
 * 📥 STATS CHARTS
 */
const fetchNewStats = async () => {
    try {
        loading.value = true

        const response = await axios.get(
            `/get/admin/global/stats?periode=${periode.value}`
        )

        // ✅ CORRECTION ICI
        newStats.value = {
            totalSites: response.data.total_sites.map(item => ({
                name: item.nom_site,
                value: parseInt(item.total)
            })),
            dossiersNumerises: response.data.dossiers_numerises.map(item => ({
                name: item.nom_site,
                value: parseInt(item.total)
            })),
            attenteSites: response.data.attente_sites.map(item => ({
                name: item.nom_site,
                value: parseInt(item.total)
            }))
        }

    } catch (e) {
        console.error("Erreur charts:", e)
    } finally {
        loading.value = false
    }
}

/**
 * 🧹 destroy chart
 */
function destroyChart(el) {
    if (el && echarts.getInstanceByDom(el)) {
        echarts.getInstanceByDom(el).dispose()
    }
}

/**
 * 📊 init chart
 */
function initChart(el, title, data, isWide = false) {
    if (!el) return

    const chart = echarts.init(el)

    chart.setOption({
        title: { text: title, left: 'center' },
        tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'shadow' }
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '12%',
            containLabel: true
        },
        xAxis: {
            type: "category",
            data: data.map(d => d.name),
            axisLabel: {
                interval: 0,
                rotate: isWide ? 45 : 0
            }
        },
        yAxis: { type: "value" },
        series: [
            {
                type: "bar",
                data: data.map(d => d.value),
                itemStyle: {
                    color: '#4f46e5'
                }
            }
        ]
    })

    window.addEventListener("resize", () => chart.resize())
}

/**
 * 🚀 init all charts
 */
const initCharts = () => {
    destroyChart(totalSiteChart.value)
    destroyChart(dossierSiteChart.value)
    destroyChart(attenteChart.value)

    initChart(totalSiteChart.value, "Total enrôlement par Site", newStats.value.totalSites)
    initChart(dossierSiteChart.value, "Dossiers numérisés par Site", newStats.value.dossiersNumerises, true)
    initChart(attenteChart.value, "En attente par Site", newStats.value.attenteSites)
}

/**
 * 🔄 watch période
 */
watch(periode, async () => {
    await fetchNewStats()
    await nextTick() // 🔥 obligatoire
    initCharts()
})

/**
 * 🚀 mounted
 */
onMounted(async () => {
    await fetchStats()
    await fetchNewStats()
    await nextTick() // 🔥 obligatoire
    initCharts()
})
</script>

<script>

import Main from '/resources/js/Pages/Main.vue';
export default {
    layout: Main,
};

</script>