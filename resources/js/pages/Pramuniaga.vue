<script setup lang="ts">

import { type BreadcrumbItem } from '@/types';
import axios from 'axios';
import { Search } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

// Shadcn UI Components
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pramuniaga', href: '/pramuniaga' }];

// State
const produk = ref<any[]>([]);
const isLoading = ref(false);
const searchTerm = ref('');

// Fetch all data
const fetchproduk = async () => {
    try {
        isLoading.value = true;
        const produkRes = await axios.get('api/produk');
        produk.value = produkRes.data.data;
    } catch (error) {
        console.error('Error fetching data:', error);
    } finally {
        isLoading.value = false;
    }
};

// Filter produk
const filteredProduk = computed(() => {
    if (!searchTerm.value.trim()) return produk.value;

    return produk.value.filter(item =>
        item.nama_produk.toLowerCase().includes(searchTerm.value.toLowerCase())
    );
});

// Format currency
const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

onMounted(fetchproduk);
</script>

<template>
    <Head title="Pramuniaga" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Search Section -->
            <Card class="mb-4">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Search class="w-5 h-5" />
                        Cari Produk
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="relative max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search class="h-5 w-5 text-muted-foreground" />
                        </div>
                        <Input v-model.trim="searchTerm" type="search" placeholder="Cari produk..." class="pl-10" />
                    </div>
                </CardContent>
            </Card>

            <!-- Table Section -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        Daftar Produk
                        <Badge variant="outline" class="px-2 py-1">
                            {{ produk.length }} item
                        </Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-[100px]">Gambar</TableHead>
                                    <TableHead>Produk</TableHead>
                                    <TableHead>Kategori</TableHead>
                                    <TableHead>Merek</TableHead>
                                    <TableHead class="text-right">Harga Jual</TableHead>
                                    <TableHead class="text-right">Stok</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="isLoading">
                                    <TableRow v-for="i in 5" :key="i">
                                        <TableCell>
                                            <Skeleton class="h-12 w-12 rounded-lg" />
                                        </TableCell>
                                        <TableCell>
                                            <Skeleton class="h-4 w-[120px]" />
                                        </TableCell>
                                        <TableCell>
                                            <Skeleton class="h-4 w-[80px]" />
                                        </TableCell>
                                        <TableCell>
                                            <Skeleton class="h-4 w-[80px]" />
                                        </TableCell>
                                        <TableCell>
                                            <Skeleton class="h-4 w-[80px] ml-auto" />
                                        </TableCell>
                                        <TableCell>
                                            <Skeleton class="h-4 w-[80px] ml-auto" />
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <template v-else>
                                    <TableRow v-for="item in filteredProduk" :key="item.id"
                                        class="group hover:bg-muted/50 transition-colors">
                                        <TableCell>
                                            <img :src="item.gambar_produk ? `/storage/${item.gambar_produk}` : '/placeholder-product.jpg'"
                                                alt="Produk" class="h-12 w-12 rounded-lg object-cover border">
                                        </TableCell>
                                        <TableCell class="font-medium">
                                            {{ item.nama_produk }}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {{ item.kategori?.nama_kategori || '-' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {{ item.merek?.nama_merek || '-' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-right text-green-600 dark:text-green-400">
                                            {{ formatCurrency(item.harga_jual) }}
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <Badge :variant="item.jumlah_stok > 10 ? 'default' :
                                                item.jumlah_stok > 0 ? 'warning' : 'destructive'">
                                                {{ item.jumlah_stok }}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="filteredProduk.length === 0 && !isLoading">
                                        <TableCell colspan="6" class="text-center text-muted-foreground py-8">
                                            Tidak ada produk yang ditemukan
                                        </TableCell>
                                    </TableRow>
                                </template>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
@media (max-width: 640px) {
    .group-hover\:opacity-100 {
        opacity: 1 !important;
    }
}
</style>