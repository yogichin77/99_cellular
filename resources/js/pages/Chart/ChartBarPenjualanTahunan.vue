<template>
  <section
    class="p-6 bg-white rounded-2xl h-78 shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
        Penjualan Tahunan
      </h3>
      <span
        class="px-3 py-1 text-sm bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full">
        {{ currentYear }} </span>
    </div>
    <div class="h-full relative">
        <div v-if="isLoadingAnnualRevenue"
             class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-70 dark:bg-gray-800 dark:bg-opacity-70 rounded-lg">
            <p class="text-gray-600 dark:text-gray-400">Memuat data grafik...</p>
        </div>
        <div v-else-if="annualRevenueData.length === 0 || totalAnnualRevenue === 0"
             class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-70 dark:bg-gray-800 dark:bg-opacity-70 rounded-lg">
            <p class="text-gray-600 dark:text-gray-400">Tidak ada data penjualan untuk tahun ini.</p>
        </div>
        <canvas v-if="showCanvas" ref="canvas" class="w-full h-full" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch, nextTick, onBeforeUnmount } from 'vue'
import { Chart } from 'chart.js/auto' // Menggunakan 'chart.js/auto' untuk pendaftaran otomatis
import axios from 'axios'; // Import axios
import Swal from 'sweetalert2'; // Import Swal for error handling

const canvas = ref<HTMLCanvasElement | null>(null)
const chartInstance = ref<Chart | null>(null); // Ref untuk instance Chart.js
const annualRevenueData = ref<any[]>([]); // State untuk data penjualan tahunan
const isLoadingAnnualRevenue = ref(true); // State loading

const currentYear = new Date().getFullYear(); // Ambil tahun saat ini

const fetchAnnualRevenue = async () => {
    isLoadingAnnualRevenue.value = true;
    try {
        const response = await axios.get(`/api/transaksi/annual-revenue`, {
            params: {
                start_year: currentYear - 4, // Misalnya, 5 tahun terakhir termasuk tahun ini
                end_year: currentYear
            }
        });
        annualRevenueData.value = response.data.data;
    } catch (error) {
        console.error('Failed to fetch annual revenue:', error);
        Swal.fire('Error', 'Gagal memuat data penjualan tahunan.', 'error');
    } finally {
        isLoadingAnnualRevenue.value = false;
    }
};

// Computed properties untuk data grafik
const chartLabels = computed(() => annualRevenueData.value.map((item) => item.year.toString()));
const chartAmounts = computed(() => annualRevenueData.value.map((item) => item.amount));

const totalAnnualRevenue = computed(() => {
    return annualRevenueData.value.reduce((sum, item) => sum + item.raw_amount, 0);
});

// Computed property untuk mengontrol v-if pada canvas
const showCanvas = computed(() => !isLoadingAnnualRevenue.value && totalAnnualRevenue.value > 0);


// Fungsi untuk membuat/memperbarui grafik
const createOrUpdateChart = () => {
    if (canvas.value) {
        const ctx = canvas.value.getContext('2d');
        if (ctx) {
            if (chartInstance.value) {
                // Perbarui data jika instans sudah ada
                chartInstance.value.data.labels = chartLabels.value;
                chartInstance.value.data.datasets[0].data = chartAmounts.value;
                chartInstance.value.update();
            } else {
                // Buat instans baru jika belum ada
                chartInstance.value = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: chartLabels.value,
                        datasets: [
                            {
                                label: 'Penjualan (juta Rp)', // Sesuaikan label
                                data: chartAmounts.value,
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
                            tooltip: { // Tambahkan tooltip untuk format rupiah
                                callbacks: {
                                    label: function (context) {
                                        const value = parseFloat(context.raw as any) * 1000000;
                                        return `Rp ${value.toLocaleString('id-ID')}`;
                                    },
                                },
                            },
                        },
                        scales: {
                            x: {
                                offset: true,
                                border: {
                                    display: true,
                                    color: '#d1d5db',
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
                                    display: true,
                                    color: '#d1d5db',
                                },
                                ticks: {
                                    padding: 4,
                                    callback: function (value) { // Tambahkan callback untuk format rupiah
                                        return `Rp ${(Number(value) * 1000000).toLocaleString('id-ID')}`;
                                    },
                                },
                                grid: {
                                    drawTicks: true,
                                },
                            },
                        },
                    },
                });
            }
        }
    }
};

onMounted(async () => {
    await fetchAnnualRevenue();
});

// Watcher untuk memantau perubahan pada 'showCanvas' dan 'annualRevenueData'
watch([showCanvas, annualRevenueData], ([newShowCanvas, newAnnualRevenueData]) => {
    if (newShowCanvas && newAnnualRevenueData.length > 0) {
        nextTick(() => {
            createOrUpdateChart();
        });
    } else if (!newShowCanvas && chartInstance.value) {
        chartInstance.value.destroy();
        chartInstance.value = null;
    }
}, { deep: true, immediate: true });

onBeforeUnmount(() => {
    if (chartInstance.value) {
        chartInstance.value.destroy();
        chartInstance.value = null;
    }
});
</script>

<style scoped>
canvas {
  display: block;
}
</style>