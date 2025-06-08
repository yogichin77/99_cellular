<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { Bar } from 'vue-chartjs' // Ubah dari Pie menjadi Bar
import type { ChartOptions } from 'chart.js'
import axios from 'axios'

import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement, // Tambahkan BarElement
  CategoryScale, // Tambahkan CategoryScale (untuk sumbu X)
  LinearScale // Tambahkan LinearScale (untuk sumbu Y)
} from 'chart.js'

// Daftarkan komponen yang diperlukan untuk Bar Chart
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

// Data reaktif untuk chart
const chartData = ref({
  labels: [],
  datasets: [
    {
      label: 'Total Penjualan', // Label untuk Bar Chart
      backgroundColor: [], // Warna akan diisi dari API
      data: [], // Data penjualan akan diisi dari API
    },
  ],
})

// Tahun yang akan digunakan untuk filter data API
const selectedYear = ref(new Date().getFullYear()) // Default ke tahun saat ini

const chartOptions = ref<ChartOptions<'bar'>>({ // Ubah type dari 'pie' menjadi 'bar'
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
      display: false, // Biasanya legend tidak terlalu relevan untuk single-dataset bar chart
    },
    tooltip: { // Konfigurasi tooltip untuk menampilkan nilai asli
      callbacks: {
        label: function(context) {
          let label = context.dataset.label || '';
          if (label) {
            label += ': ';
          }
          if (context.parsed.y !== null) { // Untuk bar chart, nilai ada di context.parsed.y
            const value = context.parsed.y;
            // Format sebagai mata uang (misal: Rp.)
            const formattedValue = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
            return `${label}${formattedValue}`;
          }
          return label;
        }
      }
    }
  },
  scales: { // Tambahkan konfigurasi scales untuk sumbu X dan Y
    x: {
      title: {
        display: true,
        color: '#6B7280' // Warna teks sumbu X
      },
      ticks: {
        color: '#6B7280' // Warna label sumbu X
      },
      grid: {
        display: false // Hilangkan grid vertikal
      }
    },
    y: {
      beginAtZero: true, // Mulai sumbu Y dari nol
      title: {
        display: true,
        text: 'Total Penjualan (IDR)',
        color: '#6B7280' // Warna teks sumbu Y
      },
      ticks: {
        color: '#6B7280', // Warna label sumbu Y
        callback: function(value: string | number) { // Format label sumbu Y sebagai mata uang
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value as number);
        }
      },
      grid: {
        color: 'rgba(200, 200, 200, 0.2)' // Warna grid horizontal
      }
    }
  }
})

// Fungsi untuk mengambil data dari API
const fetchSalesByCategory = async () => {
  try {
    const response = await axios.get(`api/dashboard/sales-by-category?year=${selectedYear.value}`)
    const apiData = response.data.data

    const labels = apiData.map((item: any) => item.name)
    const dataValues = apiData.map((item: any) => item.value)

    const backgroundColors = generateRandomColors(labels.length)

    chartData.value = {
      labels: labels,
      datasets: [
        {
          label: 'Total Penjualan',
          backgroundColor: backgroundColors,
          data: dataValues,
        },
      ],
    }
  } catch (error) {
    console.error('Error fetching sales by category data:', error)
    // Handle error, mungkin tampilkan pesan ke user
  }
}

// Fungsi helper untuk menghasilkan warna acak
const generateRandomColors = (numColors: number) => {
  const colors = []
  for (let i = 0; i < numColors; i++) {
    const r = Math.floor(Math.random() * 200) + 50 // Warna agak lebih cerah
    const g = Math.floor(Math.random() * 200) + 50
    const b = Math.floor(Math.random() * 200) + 50
    colors.push(`rgba(${r},${g},${b},0.8)`)
  }
  return colors
}

// Panggil fungsi saat komponen dimuat
onMounted(() => {
  fetchSalesByCategory()
})

// Watcher untuk memanggil API lagi jika tahun berubah
watch(selectedYear, () => {
  fetchSalesByCategory()
})

</script>

<template>
  <section class="p-6 bg-white rounded-2xl h-78 shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
        Penjualan per Kategori
      </h3>
      <div class="flex items-center space-x-2">
        <label for="year-select" class="text-sm text-gray-600 dark:text-gray-400">Tahun:</label>
        <select id="year-select" v-model="selectedYear"
                class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option v-for="yearOption in [2023, 2024, 2025, 2026]" :key="yearOption" :value="yearOption">
            {{ yearOption }}
          </option>
        </select>
      </div>
    </div>
    <div class="w-full h-52">
      <Bar :data="chartData" :options="chartOptions" />
    </div>
  </section>
</template>