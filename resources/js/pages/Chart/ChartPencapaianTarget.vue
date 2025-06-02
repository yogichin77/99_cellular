<template>
  <div
    class="flex flex-col items-center justify-center h-full gap-4 p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md max-w-xs mx-auto"
  >
    <div class="flex items-center gap-3">
      <div class="text-4xl font-extrabold text-blue-600 dark:text-blue-400 leading-none">
        85%
      </div>
      <svg
        class="w-7 h-7 text-green-500"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        viewBox="0 0 24 24"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2l4-4"></path>
      </svg>
    </div>

    <p
      class="text-sm text-center text-gray-600 dark:text-gray-400 font-semibold tracking-wide"
    >
      Pencapaian Target
    </p>

    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
      Penjualan Bulan Ini
    </p>

    <!-- Progress bar -->
    <div
      class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mt-2 overflow-hidden"
      aria-label="Progress bar pencapaian target"
    >
      <div
        class="bg-green-500 h-3 rounded-full transition-all duration-500"
        :style="{ width: '85%' }"
      ></div>
    </div>

    <button
      class="mt-4 px-5 py-1.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full font-semibold hover:bg-green-200 dark:hover:bg-green-800 transition"
      aria-label="Status pencapaian target"
    >
      On Track
    </button>
  </div>
</template>
