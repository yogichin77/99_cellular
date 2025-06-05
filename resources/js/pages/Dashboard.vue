<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import ChartBarPenjualanTahunan from './Chart/ChartBarPenjualanTahunan.vue';
import ChartPiePenjualankategori from './Chart/ChartPiePenjualankategori.vue';
import ChartBarStatusTransaksi from './Chart/ChartBarStatusTransaksi.vue';
import ChartBarPendapatanperbulan from './Chart/ChartBarPendapatanperbulan.vue';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: 'dashboard',
  },
];
</script>

<template>
  <Head title="Dashboard" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto px-4 py-8 md:px-6 lg:px-8 flex flex-col gap-8">
      
      <header class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Analitik Penjualan</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Ringkasan performa penjualan dan transaksi bisnis Anda.</p>
      </header>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 flex flex-col justify-between">
          <ChartBarPenjualanTahunan class="w-full min-h-[250px]" />
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 flex flex-col justify-between">
          <ChartPiePenjualankategori class="w-full min-h-[250px]" />
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 flex flex-col justify-between">
          <ChartBarStatusTransaksi class="w-full min-h-[250px]" />
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
        <ChartBarPendapatanperbulan class="w-full min-h-[300px]" />
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Anda bisa menambahkan gaya kustom di sini jika diperlukan, 
   tapi Tailwind CSS sudah menangani sebagian besar */
</style>