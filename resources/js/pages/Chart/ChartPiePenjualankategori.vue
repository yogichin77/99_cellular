<script setup lang="ts">
import { ref } from 'vue'
import { Pie } from 'vue-chartjs'
import type { ChartOptions } from 'chart.js'

import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  ArcElement
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, ArcElement)

const chartData = ref({
  labels: ['Handphone', 'Sparepart', 'Aksesoris'],
  datasets: [
    {
      backgroundColor: ['#f87171', '#34d399', '#60a5fa'],
      data: [300, 500, 100],
    },
  ],
})

const chartOptions = ref<ChartOptions<'pie'>>({
  responsive: true,
  maintainAspectRatio: false,
  layout: {
    padding: {
      top: 20,
      bottom: -10,
      left: 10,
      right: 10,
    },
  },
  plugins: {
    legend: {
      position: 'bottom', // Sekarang valid karena typenya dikenali
      labels: {
        padding: 20,

      }
    },
  },
})
</script>

<template>
  <section class="p-6 bg-white rounded-2xl h-78 shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
        Penjualan per Kategori
      </h3>
      <span
        class="px-3 py-1 text-sm bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full">
        Tahun 2025
      </span>
    </div>
    <div class="w-full h-52">
      <Pie :data="chartData" :options="chartOptions" />
    </div>
  </section>
</template>
