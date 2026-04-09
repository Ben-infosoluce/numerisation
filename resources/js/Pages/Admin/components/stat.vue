<template>
    <div class="p-6 space-y-6 bg-gray-50 ">

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
                <h2 class="text-2xl font-bold">{{ stats.Total }}</h2>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm">
                <p class="text-sm text-gray-500">Dossiers numérisés</p>
                <h2 class="text-2xl font-bold">{{ stats.numerises }}</h2>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm">
                <p class="text-sm text-gray-500">En attente</p>
                <h2 class="text-2xl font-bold">{{ stats.attente }}</h2>
            </div>
        </div>

        <!-- GRAPH -->
        <div class="bg-white p-6 rounded-xl shadow-sm">
            <h2 class="text-lg font-semibold mb-4">Dossiers numérisés par site</h2>
            <div class="w-full h-[400px]" ref="dossierChart"></div>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from "vue";
import axios from "axios";
import * as echarts from "echarts";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";

// 🔹 Refs
const dossierChart = ref(null);
const periode = ref("today");

// 🔹 Data
const stats = ref({
    Total: 0,
    numerises: 0,
    attente: 0
});

const dossierData = ref([]);

// 🔹 Chart instance
let chartInstance = null;

// 🔹 FETCH DATA
const fetchData = async () => {
    try {
        const res = await axios.get(`/get/admin/global/stats?periode=${periode.value}`);

        // KPI
        stats.value = {
            Total: res.data.total_enrolement || 0,
            numerises: res.data.dossiers_numerises || 0,
            attente: res.data.en_attente || 0
        };

        // Graph
        dossierData.value = res.data.numerises_par_site.map(s => ({
            name: s.nom_site,
            value: s.total
        }));

    } catch (err) {
        console.error("Erreur fetchData:", err);
    }
};

// 🔹 INIT CHART
const initChart = () => {
    if (!dossierChart.value) return;

    // destroy ancien chart
    if (chartInstance) {
        chartInstance.dispose();
    }

    chartInstance = echarts.init(dossierChart.value);

    chartInstance.setOption({
        title: {
            text: `Total: ${dossierData.value.reduce((sum, d) => sum + d.value, 0)}`,
            left: "center"
        },
        tooltip: {
            trigger: "axis",
            axisPointer: { type: "shadow" }
        },
        xAxis: {
            type: "category",
            data: dossierData.value.map(d => d.name),
            axisLabel: { rotate: 45 }
        },
        yAxis: {
            type: "value"
        },
        series: [{
            name: "Dossiers numérisés",
            type: "bar",
            data: dossierData.value.map(d => d.value),
            itemStyle: {
                color: "#3b82f6"
            },
            label: {
                show: true,
                position: "top",
                fontWeight: "bold"
            }
        }]
    });

    window.addEventListener("resize", () => {
        chartInstance.resize();
    });
};

// 🔹 WATCH période
watch(periode, async () => {
    await fetchData();
    await nextTick();
    initChart();
});

// 🔹 MOUNT
onMounted(async () => {
    await fetchData();
    await nextTick();
    initChart();
});
</script>