<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import type { PageProps as InertiaPageProps } from '@inertiajs/core'

interface User {
  id: number
  name: string
  email: string
}

interface PageProps extends InertiaPageProps {
  auth: {
    user: User | null
  }
}

const page = usePage<PageProps>()
</script>

<template>

  <Head title="Welcome">
    <link rel="preconnect" href="https://rsms.me/" />
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
  </Head>

  <div
    class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] flex flex-col relative overflow-hidden">
    <div class="ocean absolute inset-0 z-0">
      <div class="wave"></div>
      <div class="wave"></div>
      <div class="wave"></div>
    </div>

    <header
      class="w-full border-b border-gray-200 dark:border-gray-800 px-6 py-4 flex justify-between items-center relative z-10">
      <h1 class="text-lg font-semibold">99 CELLULAR</h1>
      <nav class="flex gap-3 text-sm">
        <Link v-if="page.props.auth.user" :href="route('/dashboard')"
          class="rounded-md border border-gray-300 px-4 py-2 hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-800 transition">
        Dashboard
        </Link>
        <template v-else>
          <Link :href="route('login')" class="rounded-md px-4 py-2 hover:underline transition">
          Log in
          </Link>

          <Link :href="route('register')"
            class="rounded-md border border-gray-300 px-4 py-2 hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-800 transition">
          Register
          </Link>
        </template>
      </nav>
    </header>

    <main class="flex-grow flex items-center justify-center px-6 py-12 lg:py-24 relative z-10">
      <div class="text-center max-w-2xl bg-white/70 dark:bg-black/60 backdrop-blur-sm p-8 rounded-xl shadow-lg">
        
        <h2 class="text-2xl lg:text-4xl font-bold mb-4">Selamat Datang di Sistem Penjualan</h2>
        <Link :href="page.props.auth.user ? route('dashboard') : route('login')"
          class="inline-block bg-gray-600 text-white px-6 py-3 rounded-md hover:bg-gray-700 transition">
        {{ page.props.auth.user ? 'Masuk ke Dashboard' : 'Mulai Sekarang' }}
        </Link>
      </div>
    </main>

    <footer class="text-center text-sm text-gray-500 dark:text-gray-600 py-6 relative z-10">
      © {{ new Date().getFullYear() }} 99 CELLULAR. All rights reserved.
    </footer>
  </div>
</template>

<style scoped>
/* Animasi Gelombang Air */
.ocean {
  height: 100%;
  width: 100%;
  position: absolute;
  bottom: 0;
  left: 0;
  /* overflow: hidden; Dihapus karena parent min-h-screen sudah menangani */
}

.wave {
  /* Menggunakan warna dasar biru yang sedikit lebih terang dan transparan */
  background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120' preserveAspectRatio='none'%3E%3Cpath d='M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z' fill='%2366b3ff'%3E%3C/path%3E%3C/svg%3E"); /* Biru muda */
  position: absolute;
  width: 200%; /* Memastikan lebar cukup untuk pergeseran */
  height: 120px; /* Tinggi gelombang tetap sesuai SVG */
  bottom: 0; /* Lapisan paling depan di posisi dasar */
  
  /* PERUBAHAN PENTING UNTUK SMOOTHNESS */
  animation: wave 20s ease-in-out infinite alternate; /* Durasi lebih panjang, timing function ease-in-out, dan bolak-balik */
  transform: translate3d(0, 0, 0); /* Memaksa akselerasi hardware */
  opacity: 0.7; /* Opacity lebih tinggi untuk gelombang depan */
  will-change: transform; /* Memberi tahu browser untuk mengoptimalkan transformasi */
}

.wave:nth-of-type(2) {
  /* Warna biru medium */
  background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120' preserveAspectRatio='none'%3E%3Cpath d='M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z' fill='%233a90e2'%3E%3C/path%3E%3C/svg%3E"); 
  animation-duration: 25s; /* Durasi lebih panjang lagi */
  animation-delay: -7s; /* Delay berbeda agar tidak sinkron */
  opacity: 0.5; /* Opacity lebih rendah untuk kedalaman */
  bottom: 10px; /* Offset vertikal untuk efek paralaks */
}

.wave:nth-of-type(3) {
  /* Warna biru gelap */
  background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120' preserveAspectRatio='none'%3E%3Cpath d='M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z' fill='%231f6ecf'%3E%3C/path%3E%3C/svg%3E"); 
  animation-duration: 30s; /* Durasi terpanjang */
  animation-delay: -15s; /* Delay terpanjang */
  opacity: 0.3; /* Opacity terendah untuk latar belakang */
  bottom: 20px; /* Offset vertikal terjauh */
}

@keyframes wave {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-50%); /* Geser sejauh 50% dari lebar 200% */
  }
}

/* Penyesuaian Dark mode */
.dark .wave {
  opacity: 0.4; /* Opacity yang sedikit lebih tinggi di dark mode agar tetap terlihat */
  /* Jika ingin warna gelombang berbeda di dark mode, Anda perlu mengganti fill URL SVG di sini */
}
</style>