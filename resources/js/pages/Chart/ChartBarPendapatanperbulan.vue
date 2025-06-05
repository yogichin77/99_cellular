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
import { ref, onMounted, computed, watch, nextTick, onBeforeUnmount } from 'vue'
import axios from 'axios';
import Swal from 'sweetalert2';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const revenueChartRef = ref<HTMLCanvasElement | null>(null)
const chartInstance = ref<ChartJS | null>(null);
const monthlyRevenueData = ref<any[]>([]);
const isLoadingRevenue = ref(true);

const fetchMonthlyRevenue = async () => {
    isLoadingRevenue.value = true;
    try {
        const currentYear = new Date().getFullYear();
        const response = await axios.get(`/api/transaksi/monthly-revenue`, {
            params: {
                year: currentYear
            }
        });
        const fetchedData = response.data.data;
        const processedData = Array.from({ length: 12 }, (_, i) => {
            const monthNum = i + 1;
            const existingData = fetchedData.find((item: any) => item.month_num === monthNum);
            if (existingData) {
                return {
                    month_num: monthNum,
                    month: existingData.month,
                    amount: parseFloat(existingData.amount), // Pastikan ini number
                    raw_amount: parseFloat(existingData.raw_amount) // Pastikan ini number
                };
            } else {
                const monthName = new Date(currentYear, i, 1).toLocaleString('id-ID', { month: 'short' });
                return {
                    month_num: monthNum,
                    month: monthName,
                    amount: 0,
                    raw_amount: 0
                };
            }
        });
        monthlyRevenueData.value = processedData;
    } catch (error) {
        console.error('Failed to fetch monthly revenue:', error);
        Swal.fire('Error', 'Gagal memuat data pendapatan bulanan.', 'error');
    } finally {
        isLoadingRevenue.value = false;
    }
};

const chartLabels = computed(() => monthlyRevenueData.value.map((item) => item.month));
const chartAmounts = computed(() => monthlyRevenueData.value.map((item) => item.amount));

const totalRevenue = computed(() => {
    const sum = monthlyRevenueData.value.reduce((sum, item) => sum + item.raw_amount, 0);
    console.log('Total Revenue Computed:', sum); // Untuk debugging
    return sum;
});

const highestMonth = computed(() => {
    if (monthlyRevenueData.value.length === 0) return { month: '-', amount: 0, raw_amount: 0 };
    return monthlyRevenueData.value.reduce((prev, current) => (prev.raw_amount > current.raw_amount ? prev : current));
});

// Computed property untuk mengontrol v-if pada canvas
const showCanvas = computed(() => !isLoadingRevenue.value && totalRevenue.value > 0);

// Fungsi untuk membuat/memperbarui grafik
const createOrUpdateChart = () => {
    console.log('Attempting to create or update chart...');
    if (revenueChartRef.value) {
        console.log('revenueChartRef.value is present:', revenueChartRef.value);
        const ctx = revenueChartRef.value.getContext('2d');
        if (ctx) {
            console.log('Canvas context (ctx) is obtained.');
            if (chartInstance.value) {
                console.log('Updating existing chart instance.');
                chartInstance.value.data.labels = chartLabels.value;
                chartInstance.value.data.datasets[0].data = chartAmounts.value;
                chartInstance.value.update();
            } else {
                console.log('Creating new chart instance.');
                chartInstance.value = new ChartJS(ctx, {
                    type: 'bar',
                    data: {
                        labels: chartLabels.value,
                        datasets: [
                            {
                                label: 'Pendapatan (juta Rp)',
                                data: chartAmounts.value,
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
                                        const value = parseFloat(context.raw as any) * 1000000;
                                        return `Rp ${value.toLocaleString('id-ID')}`;
                                    },
                                },
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function (value) {
                                        return `Rp ${(Number(value) * 1000000).toLocaleString('id-ID')}`;
                                    },
                                },
                            },
                        },
                    },
                });
            }
        } else {
            console.error('Failed to get 2D context from canvas!');
        }
    } else {
        console.warn('revenueChartRef.value is null/undefined. Canvas not yet mounted or v-if condition not met. This is expected if showCanvas is false.');
    }
};

onMounted(async () => {
    await fetchMonthlyRevenue();
});

// Watcher untuk memantau perubahan pada 'showCanvas' dan 'monthlyRevenueData'
watch([showCanvas, monthlyRevenueData], ([newShowCanvas, newMonthlyRevenueData]) => {
    // Hanya coba membuat/memperbarui chart jika showCanvas bernilai true
    // DAN jika data sudah dimuat dan ada isinya
    if (newShowCanvas && newMonthlyRevenueData.length > 0) {
        nextTick(() => {
            createOrUpdateChart();
        });
    } else if (!newShowCanvas && chartInstance.value) {
        // Jika canvas tidak lagi ditampilkan, hancurkan chart instance
        chartInstance.value.destroy();
        chartInstance.value = null;
    }
}, { deep: true, immediate: true }); // immediate: true agar watcher dijalankan saat komponen pertama kali mounted

onBeforeUnmount(() => {
    if (chartInstance.value) {
        console.log('Destroying chart instance...');
        chartInstance.value.destroy();
        chartInstance.value = null;
    }
});
</script>

<template>
    <section class="p-6 bg-white rounded-2xl shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
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

        <div class="h-80 relative">
            <div v-if="isLoadingRevenue"
                class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-70 dark:bg-gray-800 dark:bg-opacity-70 rounded-lg">
                <p class="text-gray-600 dark:text-gray-400">Memuat data grafik...</p>
            </div>
            <div v-else-if="!showCanvas && !isLoadingRevenue"
                class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-70 dark:bg-gray-800 dark:bg-opacity-70 rounded-lg">
                <p class="text-gray-600 dark:text-gray-400">Tidak ada data pendapatan untuk tahun ini.</p>
            </div>
            <canvas v-if="showCanvas" ref="revenueChartRef"></canvas>
        </div>

        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Pendapatan (Tahun Ini)</p>
                        <p class="font-semibold text-blue-600 dark:text-blue-400">
                            Rp {{ totalRevenue.toLocaleString('id-ID') }}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Bulan Tertinggi</p>
                    <p class="text-lg font-bold text-green-600 dark:text-green-400">
                        {{ highestMonth.month }}:
                        Rp {{ highestMonth.raw_amount.toLocaleString('id-ID') }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>