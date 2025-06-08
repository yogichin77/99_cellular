<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Search, Megaphone, AlertTriangle } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';// Pastikan Link diimpor jika ini menggunakan Inertia.js


// Import Shadcn UI components
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';

const produk = ref([]);
const isLoading = ref(false);
const searchTerm = ref('');

// Function to fetch product data
const fetchProduk = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('api/produk/publicindex');
        produk.value = response.data.data;
    } catch (error) {
        console.error('Error fetching products:', error);
    } finally {
        isLoading.value = false;
    }
};

// Computed property to filter products based on search term
const filteredProduk = computed(() => {
    if (!searchTerm.value.trim()) {
        return produk.value;
    }
    const lowerCaseSearchTerm = searchTerm.value.toLowerCase();

    return produk.value.filter(
        (item) =>
            item.nama_produk.toLowerCase().includes(lowerCaseSearchTerm) ||
            (item.kategori && item.kategori.nama_kategori.toLowerCase().includes(lowerCaseSearchTerm)) ||
            (item.merek && item.merek.nama_merek.toLowerCase().includes(lowerCaseSearchTerm)) ||
            (item.barcode && item.barcode.toLowerCase().includes(lowerCaseSearchTerm))
    );
});

// Format currency
const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

// Truncate text
const truncateText = (text: string | null | undefined, maxLength: number) => {
    if (text && text.length > maxLength) {
        return text.substring(0, maxLength) + '...';
    }
    return text || '';
};

const produkTersedia = computed(() =>
    filteredProduk.value.filter((item) => item.jumlah_stok > 0)
);

// Produk tidak tersedia
const produkTidakTersedia = computed(() =>
    filteredProduk.value.filter((item) => item.jumlah_stok === 0)
);

onMounted(fetchProduk);
</script>

<template>
    <div class="container mx-auto py-8">
        <div class="sticky top-0 z-10 bg-white dark:bg-gray-950 py-3 -mt-8 mb-4 border-b dark:border-gray-800"
            style="margin-left: -1rem; margin-right: -1rem; padding-left: 1rem; padding-right: 1rem;">
            <div class="flex items-center gap-2 md:gap-4">
                <Link :href="route('home')" class="flex items-center flex-shrink-0">
                    <img :src="`/icons/ShopLogo.png`" alt="99 Cellular Logo"
                        class="h-10 w-auto md:h-12 rounded-lg cursor-pointer" />
                </Link>

                <Card class="shadow-md flex-grow w-full">
                    <CardHeader class="py-2 pb-1">
                        <CardTitle class="text-base font-semibold flex items-center gap-1">
                            <Search class="h-4 w-4 text-primary" /> Cari Produk
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="pt-0 pb-2">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
                                <Search class="h-4 w-4 text-gray-500" />
                            </div>
                            <Input v-model="searchTerm" type="search" placeholder="Cari produk..."
                                class="pl-8 text-sm h-9 md:h-10" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <div class="mt-4 text-center text-gray-700 dark:text-gray-200 text-base font-medium">
            Silakan cari produk yang Anda butuhkan.
        </div>

        <Card class="mt-4 bg-yellow-100 dark:bg-yellow-900 border-yellow-400 dark:border-yellow-700 shadow-sm">
            <CardHeader class="flex items-center gap-2 py-2">
                <Megaphone class="h-5 w-5 text-yellow-800 dark:text-yellow-300" />
                <CardTitle class="text-base text-yellow-800 dark:text-yellow-200">
                    Promo Hari Ini
                </CardTitle>
            </CardHeader>
            <CardContent class="text-sm text-yellow-900 dark:text-yellow-100">
                📢 Dapatkan diskon 20% untuk produk kategori Aksesoris! Berlaku hingga 23:59 malam ini.
            </CardContent>
        </Card>

        <div v-if="isLoading" class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-8">
            <div v-for="i in 8" :key="i" class="animate-pulse">
                <Card>
                    <Skeleton class="w-full aspect-square bg-gray-200 dark:bg-gray-700" />
                    <CardContent class="p-4">
                        <Skeleton class="h-4 w-3/4 mb-2" />
                        <Skeleton class="h-4 w-1/2 mb-2" />
                        <div class="flex space-x-2">
                            <Skeleton class="h-5 w-1/3 rounded-full" />
                            <Skeleton class="h-5 w-1/4 rounded-full" />
                        </div>
                        <Skeleton class="h-6 w-1/2 mt-3" />
                        <Skeleton class="h-4 w-1/4 mt-1" />
                        <Skeleton class="h-3 w-full mt-2" />
                        <Skeleton class="h-3 w-5/6 mt-1" />
                    </CardContent>
                </Card>
            </div>
        </div>

        <div v-if="produkTersedia.length > 0 || produkTidakTersedia.length > 0">
            <div v-if="produkTersedia.length > 0"
                class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-8">
                <Card v-for="item in produkTersedia" :key="item.id" class="flex flex-col">
                    <div class="relative w-full overflow-hidden bg-gray-100 dark:bg-gray-800" style="padding-top: 100%;">
                        <img :src="item.gambar_produk ? `/storage/${item.gambar_produk}` : '/placeholder-product.jpg'"
                            :alt="item.nama_produk" class="absolute inset-0 w-full h-full object-contain p-2" />
                    </div>
                    <CardContent class="p-4 flex-grow flex flex-col justify-between">
                        <div>
                            <h2 class="text-lg font-semibold truncate">{{ item.nama_produk }}</h2>
                            <div class="flex flex-wrap gap-1 mb-2">
                                <Badge variant="secondary" class="whitespace-nowrap">{{ item.kategori?.nama_kategori || '-' }}</Badge>
                                <Badge variant="outline" class="whitespace-nowrap">{{ item.merek?.nama_merek || '-' }}</Badge>
                            </div>
                            <p class="text-sm text-muted-foreground mb-2">{{ truncateText(item.deskripsi_produk, 100) }}</p>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency(item.harga_jual) }}</p>
                            <p class="text-sm">
                                Stok:
                                <Badge variant="default">{{ item.jumlah_stok }}</Badge>
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div v-if="produkTidakTersedia.length > 0" class="mt-12">
                <div class="flex items-center gap-2 mb-4 text-red-600 dark:text-red-400 text-lg font-semibold">
                    <AlertTriangle class="w-5 h-5" />
                    Produk Tidak Tersedia
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <Card v-for="item in produkTidakTersedia" :key="item.id" class="opacity-50 flex flex-col">
                        <div class="relative w-full overflow-hidden bg-gray-100 dark:bg-gray-800" style="padding-top: 100%;">
                            <img :src="item.gambar_produk ? `/storage/${item.gambar_produk}` : '/placeholder-product.jpg'"
                                :alt="item.nama_produk" class="absolute inset-0 w-full h-full object-contain p-2 grayscale" />
                        </div>
                        <CardContent class="p-4 flex-grow flex flex-col justify-between">
                            <div>
                                <h2 class="text-lg font-semibold truncate">{{ item.nama_produk }}</h2>
                                <div class="flex flex-wrap gap-1 mb-2">
                                    <Badge variant="secondary" class="whitespace-nowrap">{{ item.kategori?.nama_kategori || '-' }}</Badge>
                                    <Badge variant="outline" class="whitespace-nowrap">{{ item.merek?.nama_merek || '-' }}</Badge>
                                </div>
                                <p class="text-sm text-muted-foreground mb-2">{{ truncateText(item.deskripsi_produk, 100) }}</p>
                            </div>
                            <div>
                                <p class="text-xl font-bold text-gray-500 line-through dark:text-gray-400">{{ formatCurrency(item.harga_jual) }}</p>
                                <p class="text-sm">
                                    Stok:
                                    <Badge variant="destructive">0</Badge>
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <div v-else-if="!isLoading" class="text-center py-8 text-muted-foreground">
            <p class="text-lg">Tidak ada produk ditemukan.</p>
            <p class="text-sm">Coba ubah kata kunci pencarian Anda.</p>
        </div>
    </div>
</template>

<style scoped>
/* Tambahkan styling jika dibutuhkan */
</style>