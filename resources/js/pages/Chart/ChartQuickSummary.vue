<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

// Data reaktif untuk ringkasan
const totalSalesThisYear = ref<number>(0)
const totalTransactionsThisMonth = ref<number>(0)
const currentYear = ref<number>(new Date().getFullYear())
const currentMonth = ref<string>(new Date().toLocaleString('id-ID', { month: 'long' })) // Nama bulan

// Fungsi untuk mengambil data ringkasan dari API
const fetchQuickSummary = async () => {
  try {
    const response = await axios.get('/api/dashboard/quick-summary')
    const apiData = response.data.data

    totalSalesThisYear.value = apiData.total_sales_this_year
    totalTransactionsThisMonth.value = apiData.total_transactions_this_month
    currentYear.value = apiData.year
    currentMonth.value = apiData.month

  } catch (error) {
    console.error('Error fetching quick summary data:', error)
    // Anda bisa menampilkan pesan error di UI jika diperlukan
  }
}

// Helper untuk format mata uang
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0, // Tidak menampilkan desimal
    maximumFractionDigits: 0
  }).format(value)
}

// Panggil fungsi saat komponen dimuat
onMounted(() => {
  fetchQuickSummary()
})
</script>

<template>
  <div class="p-6 bg-white rounded-2xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 h-full flex flex-col justify-between">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
      Ringkasan Cepat
    </h3>
    <div class="space-y-4">
      <div class="flex flex-col">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Penjualan Tahun Ini ({{ currentYear }})</p>
        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
          {{ formatCurrency(totalSalesThisYear) }}
        </p>
      </div>

      <div class="flex flex-col">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaksi Bulan Ini ({{ currentMonth }})</p>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">
          {{ totalTransactionsThisMonth }}
        </p>
      </div>
    </div>
    </div>
</template>