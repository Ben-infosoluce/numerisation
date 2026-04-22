<template>
    <div class="p-6 space-y-6 bg-gray-50 ">

        <!-- HEADER -->
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold">Dashboard</h1>

            <div class="flex items-center gap-4">
                <DateRangePicker v-model="form.dateRange"
                            @update:start="val => { form.date_start = val; onFilterChange(); }"
                            @update:end="val => { form.date_end = val; onFilterChange(); }" />

                <Tabs v-model="periode">
                    <TabsList>
                        <TabsTrigger value="today">Aujourd'hui</TabsTrigger>
                        <TabsTrigger value="week">Semaine</TabsTrigger>
                        <TabsTrigger value="month">Mois</TabsTrigger>
                        <TabsTrigger value="year">Année</TabsTrigger>
                    </TabsList>
                </Tabs>
            </div>
        </div>

        <!-- KPI CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500">Total enrôlement</p>
                <h2 class="text-2xl font-bold">{{ stats.Total }}</h2>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500">Dossiers numérisés</p>
                <h2 class="text-2xl font-bold text-green-600">{{ stats.numerises }}</h2>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500">En attente</p>
                <h2 class="text-2xl font-bold text-orange-500">{{ stats.attente }}</h2>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500">Dossiers immatriculés</p>
                <h2 class="text-2xl font-bold text-blue-600">{{ stats.immatricules }}</h2>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- GRAPH: NUMÉRISÉS -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-lg font-semibold mb-4 text-green-700">Dossiers numérisés par site</h2>
                <div class="w-full h-[400px]" ref="dossierChart"></div>
            </div>

            <!-- GRAPH: IMMATRICULÉS -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-lg font-semibold mb-4 text-blue-700">Dossiers immatriculés par site</h2>
                <div class="w-full h-[400px]" ref="immatriculeChart"></div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, nextTick } from "vue";
import axios from "axios";
import * as echarts from "echarts";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import DateRangePicker from "@/components/ui/DateRangePicker.vue";

// 🔹 Refs
const dossierChart = ref(null);
const immatriculeChart = ref(null);
const periode = ref("today");

// 🔹 Data
const stats = ref({
    Total: 0,
    numerises: 0,
    attente: 0,
    immatricules: 0
});

const dossierData = ref([]);
const immatriculeData = ref([]);

const form = reactive({
    dateRange: null,
    date_start: null,
    date_end: null
});

// 🔹 Chart instances
let dossierChartInstance = null;
let immatriculeChartInstance = null;

// 🔹 FETCH DATA
const fetchData = async () => {
    try {
        let url = `/get/admin/global/stats?periode=${periode.value}`;
        if (form.date_start && form.date_end) {
            url += `&date_start=${form.date_start}&date_end=${form.date_end}`;
        }
        const res = await axios.get(url);

        // KPI
        stats.value = {
            Total: res.data.total_enrolement || 0,
            numerises: res.data.dossiers_numerises || 0,
            attente: res.data.en_attente || 0,
            immatricules: res.data.dossiers_immatricules || 0
        };

        // Graph 1: Numérisés
        dossierData.value = res.data.numerises_par_site.map(s => ({
            name: s.nom_site,
            value: s.total
        }));

        // Graph 2: Immatriculés
        immatriculeData.value = res.data.immatricules_par_site.map(s => ({
            name: s.nom_site,
            value: s.total
        }));

    } catch (err) {
        console.error("Erreur fetchData:", err);
    }
};

// 🔹 INIT CHARTS
const initCharts = () => {
    initDossierChart();
    initImmatriculeChart();
};

const initDossierChart = () => {
    if (!dossierChart.value) return;
    if (dossierChartInstance) dossierChartInstance.dispose();
    
    dossierChartInstance = echarts.init(dossierChart.value);
    dossierChartInstance.setOption({
        title: { text: `Total: ${dossierData.value.reduce((sum, d) => sum + d.value, 0)}`, left: "center" },
        tooltip: { trigger: "axis", axisPointer: { type: "shadow" } },
        xAxis: { type: "category", data: dossierData.value.map(d => d.name), axisLabel: { rotate: 45 } },
        yAxis: { type: "value" },
        series: [{
            name: "Dossiers numérisés",
            type: "bar",
            data: dossierData.value.map(d => d.value),
            itemStyle: { color: "#10b981" }, // Green
            label: { show: true, position: "top", fontWeight: "bold" }
        }]
    });
};

const initImmatriculeChart = () => {
    if (!immatriculeChart.value) return;
    if (immatriculeChartInstance) immatriculeChartInstance.dispose();
    
    immatriculeChartInstance = echarts.init(immatriculeChart.value);
    immatriculeChartInstance.setOption({
        title: { text: `Total: ${immatriculeData.value.reduce((sum, d) => sum + d.value, 0)}`, left: "center" },
        tooltip: { trigger: "axis", axisPointer: { type: "shadow" } },
        xAxis: { type: "category", data: immatriculeData.value.map(d => d.name), axisLabel: { rotate: 45 } },
        yAxis: { type: "value" },
        series: [{
            name: "Dossiers immatriculés",
            type: "bar",
            data: immatriculeData.value.map(d => d.value),
            itemStyle: { color: "#3b82f6" }, // Blue
            label: { show: true, position: "top", fontWeight: "bold" }
        }]
    });
};

window.addEventListener("resize", () => {
    if (dossierChartInstance) dossierChartInstance.resize();
    if (immatriculeChartInstance) immatriculeChartInstance.resize();
});

const onFilterChange = async () => {
    await fetchData();
    await nextTick();
    initCharts();
};

// 🔹 WATCH période
watch(periode, async () => {
    if (periode.value) {
        form.dateRange = null;
        form.date_start = null;
        form.date_end = null;
    }
    await fetchData();
    await nextTick();
    initCharts();
});

// 🔹 MOUNT
onMounted(async () => {
    await fetchData();
    await nextTick();
    initChart();
});
</script>