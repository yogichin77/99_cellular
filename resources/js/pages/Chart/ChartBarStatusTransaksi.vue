<script setup lang="ts">
import { ref } from 'vue'
import { Bar } from 'vue-chartjs'
import {
  Chart,
  BarElement,
  CategoryScale,
  LinearScale,
  Title,
  Tooltip,
  Legend,
  ChartOptions,
} from 'chart.js'

// Registrasi chart.js komponen
Chart.register(BarElement, CategoryScale, LinearScale, Title, Tooltip, Legend)

type TransaksiItem = {
  name: string
  value: number
  color: 'green' | 'yellow' | 'red'
}

const transaksiData: TransaksiItem[] = [
  { name: 'Cash', value: 40, color: 'green' },
  { name: 'Kredit', value: 300, color: 'yellow' },
  { name: 'Cancel', value: 150, color: 'red' },
]

const colorMap: Record<TransaksiItem['color'], string> = {
  green: 'rgba(34, 197, 94, 0.7)',
  yellow: 'rgba(234, 179, 8, 0.7)',
  red: 'rgba(239, 68, 68, 0.7)',
}

const chartData = ref({
  labels: transaksiData.map(item => item.name),
  datasets: [
    {
      label: 'Jumlah Transaksi',
      data: transaksiData.map(item => item.value),
      backgroundColor: transaksiData.map(item => colorMap[item.color]),
      borderRadius: 6,
      barThickness: 20,
    },
  ],
})

// ✅ Plugin kustom untuk menampilkan label di dalam bar
const barLabelPlugin = {
  id: 'barLabelPlugin',
  afterDatasetsDraw(chart: any) {
    const ctx = chart.ctx
    chart.data.datasets.forEach((dataset: any, i: number) => {
      const meta = chart.getDatasetMeta(i)
      meta.data.forEach((bar: any, index: number) => {
        const value = dataset.data[index]
        ctx.fillStyle = '#fff'
        ctx.font = 'bold 12px sans-serif'
        ctx.textAlign = 'right'
        ctx.fillText(`${value}`, bar.base + bar.width - 10, bar.y + bar.height / 2 + 4)
      })
    })
  },
}

// ✅ Properti chartOptions
const chartOptions: ChartOptions<'bar'> = {
  indexAxis: 'y',
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      enabled: true,
      callbacks: {
        label(context) {
          return `${context.parsed.x} transaksi`
        },
      },
    },
    title: { display: false },
  },
  scales: {
    x: {
      beginAtZero: true,
      max: 500,
      ticks: { stepSize: 100 },
      grid: { display: false },
    },
    y: { grid: { display: false } },
  },
  
}
</script>

<template>
  <section
    class="p-6 bg-white rounded-2xl shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700 max-w-xl mx-auto"
    style="height: 295px;"
  >
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Status Transaksi</h2>
      <span class="text-sm text-gray-500 dark:text-gray-400">Update Terakhir: Hari Ini</span>
    </div>
    <!-- ✅ Pasangkan plugin langsung di komponen Bar -->
    <Bar :data="chartData" :options="chartOptions" :plugins="[barLabelPlugin]" />
  </section>
</template>
