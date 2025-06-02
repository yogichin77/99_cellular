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
    <div class="container mx-auto px-4 py-6 flex flex-col gap-8">
      
      <!-- Header Section -->
      <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <!-- Optional header content -->
      </header>

      <!-- Top charts grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <section class="w-full">
          <ChartBarPenjualanTahunan class="w-full min-h-[200px]" />
        </section>

        <section class="w-full">
          <ChartPiePenjualankategori class="w-full min-h-[200px]" />
        </section>

       <section class="w-full">
        <ChartBarStatusTransaksi class="w-full min-h-[200px]" />
      </section>
      </div>

      <!-- Bottom charts -->
      <section class="w-full">
        <ChartBarPendapatanperbulan class="w-full min-h-[250px]" />
      </section>
    </div>
  </AppLayout>
</template>
