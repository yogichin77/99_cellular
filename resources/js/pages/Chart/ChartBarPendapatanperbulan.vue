<script setup lang="ts">
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
} from 'chart.js'
import { ref, onMounted } from 'vue'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const revenueChartRef = ref<HTMLCanvasElement | null>(null)

const salesData = [
    { month: 'Jan', amount: 70 },
    { month: 'Feb', amount: 90 },
    { month: 'Mar', amount: 50 },
    { month: 'Apr', amount: 80 },
    { month: 'Mei', amount: 100 },
    { month: 'Jun', amount: 60 },
    { month: 'Jul', amount: 85 },
    { month: 'Agu', amount: 75 },
    { month: 'Sep', amount: 65 },
    { month: 'Okt', amount: 95 },
    { month: 'Nov', amount: 55 },
    { month: 'Des', amount: 85 },
]

onMounted(() => {
    if (revenueChartRef.value) {
        new ChartJS(revenueChartRef.value, {
            type: 'bar',
            data: {
                labels: salesData.map((item) => item.month),
                datasets: [
                    {
                        label: 'Pendapatan (juta Rp)',
                        data: salesData.map((item) => item.amount),
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const value = context.raw as number
                                return `Rp ${(value * 1000000).toLocaleString('id-ID')}`
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return `Rp ${(Number(value) * 1000000).toLocaleString('id-ID')}`
                            },
                        },
                    },
                },
            },
        })
    }
})
</script>

<template>
    <section class="p-6 bg-white rounded-2xl shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2 md:mb-0">
                Pendapatan Bulanan
            </h2>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-blue-600 dark:bg-blue-400"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Pendapatan</span>
                </div>
            </div>
        </div>

        <!-- Chart -->
        <div class="h-80">
            <canvas ref="revenueChartRef"></canvas>
        </div>

        <!-- Footer -->
        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Pendapatan</p>
                        <p class="font-semibold text-blue-600 dark:text-blue-400">
                            Rp {{
                                (salesData.reduce((sum, item) => sum + item.amount, 0) * 1000000).toLocaleString('id-ID')
                            }}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Bulan Tertinggi</p>
                    <p class="text-lg font-bold text-green-600 dark:text-green-400">
                        {{
                            salesData.reduce((prev, current) => (prev.amount > current.amount ? prev : current)).month
                        }}:
                        Rp {{
                            (Math.max(...salesData.map((item) => item.amount)) * 1000000).toLocaleString('id-ID')
                        }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
