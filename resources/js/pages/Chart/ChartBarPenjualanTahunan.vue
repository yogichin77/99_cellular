<template>
  <section
    class="p-6 bg-white rounded-2xl h-78 shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
        Penjualan Tahunan
      </h3>
      <span
        class="px-3 py-1 text-sm bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full">
        2025
      </span>
    </div>
    <div class="h-full">
      <canvas ref="canvas" class="w-full h-full" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Chart } from 'chart.js/auto'

const canvas = ref<HTMLCanvasElement | null>(null)

onMounted(() => {
  if (!canvas.value) return

  new Chart(canvas.value, {
    type: 'bar',
    data: {
      labels: ['2020', '2021', '2022', '2024', '2025'],
      datasets: [
        {
          label: 'Penjualan',
          data: [50, 70, 100, 90, 20],
          backgroundColor: '#3b82f6',
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: {
        padding: {
          bottom: 20,
        },
      },
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        x: {
          offset: true,
          border: {
            display: true, // ✅ menggantikan drawBorder
            color: '#d1d5db', // abu-abu border
          },
          grid: {
            drawOnChartArea: false,
            drawTicks: true,
          },
          ticks: {
            padding: 4,
            color: '#374151',
            font: {
              size: 12,
            },
          },
        },
        y: {
          beginAtZero: true,
          border: {
            display: true, // ✅ menggantikan drawBorder
            color: '#d1d5db',
          },
          ticks: {
            padding: 4,
          },
          grid: {
            drawTicks: true,
          },
        },
      },
    },
  })
})
</script>

<style scoped>
canvas {
  display: block;
}
</style>
