<template>
  <section
    class="p-6 bg-white rounded-2xl shadow-lg dark:bg-gray-800 border border-gray-100 dark:border-gray-700 max-w-xl mx-auto h-78 flex flex-col"
  >
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Status Transaksi</h2>
      <span class="text-sm text-gray-500 dark:text-gray-400">Update Terakhir: Hari Ini</span>
    </div>

    <div class="flex-grow relative min-h-[180px]"> <div v-if="isLoadingStatus"
             class="absolute inset-0 flex flex-col items-center justify-center bg-white bg-opacity-80 dark:bg-gray-800 dark:bg-opacity-80 rounded-lg backdrop-blur-sm">
            <svg class="animate-spin h-8 w-8 text-blue-500 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-gray-600 dark:text-gray-300 text-sm">Memuat data grafik...</p>
        </div>
        <div v-else-if="transactionStatusData.length === 0"
             class="absolute inset-0 flex flex-col items-center justify-center bg-white bg-opacity-80 dark:bg-gray-800 dark:bg-opacity-80 rounded-lg backdrop-blur-sm">
            <svg class="h-10 w-10 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-gray-600 dark:text-gray-300 text-sm">Tidak ada data transaksi untuk tahun ini.</p>
        </div>
        <Bar v-if="showCanvas" :data="chartData" :options="chartOptions" :plugins="[barLabelPlugin]" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick, onBeforeUnmount } from 'vue'
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
import axios from 'axios';
import Swal from 'sweetalert2';

// Registrasi chart.js komponen
Chart.register(BarElement, CategoryScale, LinearScale, Title, Tooltip, Legend)

type TransaksiStatusItem = {
  name: string
  value: number
  color: 'green' | 'red' | 'gray'
}

const transactionStatusData = ref<TransaksiStatusItem[]>([]);
const isLoadingStatus = ref(true);

const colorMap: Record<TransaksiStatusItem['color'], string> = {
  green: 'rgba(34, 197, 94, 0.85)', // Sedikit lebih pekat
  red: 'rgba(239, 68, 68, 0.85)',    // Sedikit lebih pekat
  gray: 'rgba(156, 163, 175, 0.85)', // Warna default untuk metode lain
}

const fetchTransactionStatus = async () => {
    isLoadingStatus.value = true;
    try {
        const currentYear = new Date().getFullYear();
        const response = await axios.get(`/api/transaksi/status`, {
            params: {
                year: currentYear
            }
        });
        transactionStatusData.value = response.data.data;
    } catch (error) {
        console.error('Failed to fetch transaction status:', error);
        Swal.fire('Error', 'Gagal memuat status transaksi.', 'error');
    } finally {
        isLoadingStatus.value = false;
    }
};

const chartLabels = computed(() => transactionStatusData.value.map(item => item.name));
const chartValues = computed(() => transactionStatusData.value.map(item => item.value));
const chartBackgroundColors = computed(() => transactionStatusData.value.map(item => colorMap[item.color]));

const chartData = computed(() => ({
  labels: chartLabels.value,
  datasets: [
    {
      label: 'Jumlah Transaksi',
      data: chartValues.value,
      backgroundColor: chartBackgroundColors.value,
      borderRadius: 8, // Sedikit lebih membulat
      barThickness: 24, // Sedikit lebih tebal
    },
  ],
}));

const maxTransactions = computed(() => {
    if (transactionStatusData.value.length === 0) return 100;
    const maxVal = Math.max(...transactionStatusData.value.map(item => item.value));
    // Bulatkan ke kelipatan 50 atau 100 terdekat, dengan minimal 100
    if (maxVal < 50) return 50;
    return Math.ceil(maxVal / 50) * 50;
});

const barLabelPlugin = {
  id: 'barLabelPlugin',
  afterDatasetsDraw(chart: any) {
    const ctx = chart.ctx
    chart.data.datasets.forEach((dataset: any, i: number) => {
      const meta = chart.getDatasetMeta(i)
      meta.data.forEach((bar: any, index: number) => {
        const value = dataset.data[index]
        ctx.fillStyle = '#fff'
        ctx.font = 'bold 13px sans-serif' // Ukuran font sedikit lebih besar
        ctx.textAlign = 'right'
        ctx.fillText(`${value}`, bar.base + bar.width - 10, bar.y + bar.height / 2 + 4)
      })
    })
  },
}

const chartOptions = computed<ChartOptions<'bar'>>(() => ({
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
      backgroundColor: 'rgba(0, 0, 0, 0.7)', // Background tooltip sedikit lebih gelap
      titleColor: '#fff',
      bodyColor: '#fff',
      borderColor: 'rgba(255, 255, 255, 0.1)',
      borderWidth: 1,
      cornerRadius: 6,
      displayColors: false, // Jangan tampilkan kotak warna di tooltip
    },
    title: { display: false },
  },
  scales: {
    x: {
      beginAtZero: true,
      max: maxTransactions.value,
      ticks: {
        stepSize: maxTransactions.value > 100 ? 100 : 50, // Step size dinamis
        color: '#6b7280', // Warna ticks lebih soft
        font: {
          size: 12,
        },
      },
      grid: {
        display: false, // Menghilangkan garis grid X
        drawBorder: false, // Menghilangkan border sumbu X
      },
      border: {
        display: false // Menghilangkan border sumbu X
      }
    },
    y: {
      grid: {
        display: false, // Menghilangkan garis grid Y
        drawBorder: false, // Menghilangkan border sumbu Y
      },
      ticks: {
        color: '#6b7280', // Warna ticks lebih soft
        font: {
          size: 13, // Ukuran font sedikit lebih besar
        },
      },
      border: {
        display: false // Menghilangkan border sumbu Y
      }
    },
  },
}));

const showCanvas = computed(() => !isLoadingStatus.value && transactionStatusData.value.length > 0);

onMounted(async () => {
    await fetchTransactionStatus();
});

watch([showCanvas, transactionStatusData], () => {
    // Vue-chartjs handles internal updates, no explicit chartInstance destroy/create needed here.
}, { deep: true, immediate: true });

</script>

<style scoped>
/* Anda bisa menambahkan gaya kustom jika diperlukan,
   tapi sebagian besar sudah ditangani oleh Tailwind. */
</style>