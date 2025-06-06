<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Search } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

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
        const response = await axios.get('/api/produk');
        produk.value = response.data.data;
    } catch (error) {
        console.error('Error fetching products:', error);
    } finally {
        isLoading.value = false;
    }
};

// Computed property to filter products based on search term
const filteredProduk = computed(() => {
    if (!searchTerm.value.trim()) { // Menggunakan .trim() untuk memastikan tidak ada spasi kosong
        return produk.value;
    }
    const lowerCaseSearchTerm = searchTerm.value.toLowerCase(); // Menggunakan variabel untuk menghindari pemanggilan berulang

    return produk.value.filter(
        (item) =>
            item.nama_produk.toLowerCase().includes(lowerCaseSearchTerm) ||
            (item.kategori && item.kategori.nama_kategori.toLowerCase().includes(lowerCaseSearchTerm)) ||
            (item.merek && item.merek.nama_merek.toLowerCase().includes(lowerCaseSearchTerm)) ||
            (item.barcode && item.barcode.toLowerCase().includes(lowerCaseSearchTerm)) || // Tambahkan pencarian barcode
            (item.deskripsi_produk && item.deskripsi_produk.toLowerCase().includes(lowerCaseSearchTerm)) // Tambahkan pencarian berdasarkan deskripsi
    );
});

// Function to format currency
const formatCurrency = (value: number) => { // Tambahkan tipe data number
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

// Function to truncate text (newly added)
const truncateText = (text: string | null | undefined, maxLength: number) => { // Tambahkan tipe data string | null | undefined
    if (text && text.length > maxLength) {
        return text.substring(0, maxLength) + '...';
    }
    return text || ''; // Mengembalikan string kosong jika text null/undefined
};

onMounted(fetchProduk);
</script>

<template>
    <div class="container mx-auto py-8">
        <div class="sticky top-0 z-10 bg-white dark:bg-gray-950 py-4 -mt-8 mb-4 border-b dark:border-gray-800"
            style="margin-left: -1rem; margin-right: -1rem; padding-left: 1rem; padding-right: 1rem;">
            <div class="flex flex-col md:flex-row items-center gap-2 md:gap-4">
                <Link :href="route('home')" class="flex items-center gap-1 md:gap-2 flex-shrink-0">
                <img :src="`/icons/ShopLogo.png`" alt="99 Cellular Logo"
                    class="h-30 rounded-2xl w-auto cursor-pointer" />
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
                                class="pl-8 text-sm py-1" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

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

        <div v-else-if="filteredProduk.length > 0"
            class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-8">
            <Card v-for="item in filteredProduk" :key="item.id" class="flex flex-col">
                <div class="relative w-full overflow-hidden bg-gray-100 dark:bg-gray-800" style="padding-top: 100%;">
                    <img :src="item.gambar_produk ? `/storage/${item.gambar_produk}` : '/placeholder-product.jpg'"
                        :alt="item.nama_produk" class="absolute inset-0 w-full h-full object-contain p-2" />
                </div>
                <CardContent class="p-4 flex-grow flex flex-col justify-between">
                    <div>
                        <h2 class="text-lg font-semibold truncate">{{ item.nama_produk }}</h2>
                        <div class="flex flex-wrap gap-1 mb-2">
                            <Badge variant="secondary" class="whitespace-nowrap">{{ item.kategori?.nama_kategori || '-'
                                }}</Badge>
                            <Badge variant="outline" class="whitespace-nowrap">{{ item.merek?.nama_merek || '-' }}
                            </Badge>
                        </div>
                        <p class="text-sm text-muted-foreground mb-2">
                            {{ truncateText(item.deskripsi_produk, 100) }} </p>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-green-600 dark:text-green-400">{{
                                formatCurrency(item.harga_jual) }}</p>
                        <p class="text-sm">
                            Stok:
                            <Badge :variant="item.jumlah_stok > 10 ? 'default' : item.jumlah_stok > 0 ? 'warning' : 'destructive'
                                ">{{ item.jumlah_stok }}</Badge>
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
        <div v-else class="text-center py-8 text-muted-foreground">
            <p class="text-lg">Tidak ada produk ditemukan.</p>
            <p class="text-sm">Coba ubah kata kunci pencarian Anda.</p>
        </div>
    </div>
</template>

<style scoped>
/* Anda bisa menambahkan styling tambahan di sini jika diperlukan */
</style>