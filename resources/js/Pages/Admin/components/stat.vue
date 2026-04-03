<template>
    <div class="p-6 space-y-6 bg-gray-50 min-h-screen">

        <!-- HEADER -->
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold">Dashboard</h1>

            <Tabs v-model="periode">
                <TabsList>
                    <TabsTrigger value="today">Aujourd'hui</TabsTrigger>
                    <TabsTrigger value="week">Semaine</TabsTrigger>
                    <TabsTrigger value="month">Mois</TabsTrigger>
                    <TabsTrigger value="year">Année</TabsTrigger>
                </TabsList>
            </Tabs>
        </div>

        <!-- KPI CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-xl shadow-sm">
                <p class="text-sm text-gray-500">Total enrôlement</p>
                <h2 class="text-2xl font-bold">{{ stats.Total ?? 0 }}</h2>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm">
                <p class="text-sm text-gray-500">Dossiers numérisés</p>
                <h2 class="text-2xl font-bold">{{ stats['Dossier numérisé'] ?? 0 }}</h2>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm">
                <p class="text-sm text-gray-500">En attente</p>
                <h2 class="text-2xl font-bold">{{ stats['En attente de traitement'] ?? 0 }}</h2>
            </div>
        </div>

        <!-- GRAPH PAR SITE -->
        <div class="bg-white p-6 rounded-xl shadow-sm">
            <h2 class="text-lg font-semibold mb-4">Performance par site</h2>
            <div class="w-full h-[400px]" ref="siteChart"></div>
        </div>

        <!-- Dossiers numérisés par site -->
        <div class="bg-white p-6 rounded-xl shadow-sm">
            <h2 class="text-lg font-semibold mb-4">Dossiers numérisés par site</h2>
            <div class="w-full h-[350px]" ref="dossierChart"></div>
        </div>

        <!-- En attente par site -->
        <div class="bg-white p-6 rounded-xl shadow-sm">
            <h2 class="text-lg font-semibold mb-4">En attente par site</h2>
            <div class="w-full h-[350px]" ref="attenteChart"></div>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from "vue";
import axios from "axios";
import * as echarts from "echarts";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";

// ✅ Refs charts
const siteChart = ref(null);
const dossierChart = ref(null);
const attenteChart = ref(null);

// ✅ Période
const periode = ref("today");

// ✅ Stats
const stats = ref({});

// ✅ Données par site
const siteData = ref([]);
const dossierData = ref([]);
const attenteData = ref([]);

// 🔹 FETCH DATA
const fetchData = async () => {
    try {
        const res = await axios.get(`/get/admin/global/stats?periode=${periode.value}`);

        // KPI
        stats.value = {
            Total: res.data.total_sites.reduce((a, b) => a + b.total, 0),
            'Dossier numérisé': res.data.dossiers_numerises.reduce((a, b) => a + b.total, 0),
            'En attente de traitement': res.data.attente_sites.reduce((a, b) => a + b.total, 0),
        };

        // DATA charts
        siteData.value = res.data.total_sites.map(s => ({ name: s.nom_site, value: s.total }));
        dossierData.value = res.data.dossiers_numerises.map(s => ({ name: s.nom_site, value: s.total }));
        attenteData.value = res.data.attente_sites.map(s => ({ name: s.nom_site, value: s.total }));

    } catch (err) {
        console.error("Erreur fetchData:", err);
    }
};

// 🔹 INIT CHART FUNCTION
const initChart = (el, title, data, rotate = false, color = "#4f46e5") => {
    if (!el) return;
    const chart = echarts.init(el);
    chart.setOption({
        title: { text: title, left: "center" },
        tooltip: { trigger: "axis", axisPointer: { type: "shadow" } },
        xAxis: {
            type: "category",
            data: data.map(d => d.name),
            axisLabel: { rotate: rotate ? 45 : 0 }
        },
        yAxis: { type: "value" },
        series: [{
            type: "bar",
            data: data.map(d => d.value),
            itemStyle: { color }
        }]
    });
    window.addEventListener("resize", () => chart.resize());
};

// 🔹 INIT ALL CHARTS
const initCharts = () => {
    initChart(siteChart.value, "Total enrôlement par site", siteData.value, true, "#10b981");
    initChart(dossierChart.value, "Dossiers numérisés par site", dossierData.value, true, "#3b82f6");
    initChart(attenteChart.value, "En attente par site", attenteData.value, true, "#f59e0b");
};

// 🔹 Watch période
watch(periode, async () => {
    await fetchData();
    await nextTick();
    initCharts();
});

// 🔹 Mounted
onMounted(async () => {
    await fetchData();
    await nextTick();
    initCharts();
});
</script>