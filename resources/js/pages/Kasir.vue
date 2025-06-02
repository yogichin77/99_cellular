<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import DetailTransaksi from '@/pages/DetailTransaksi.vue';
import StrukTransaksi from '@/pages/StrukTransaksi.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { CheckCircle, Eye, History, Package, Plus, Printer, Search, ShoppingCart, Trash2, User } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { saveOfflineTransaksi } from '@/lib/indexedDb';
import { getAllOfflineTransaksis, clearOfflineTransaksis } from '@/lib/indexedDb';
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Kasir', href: '/kasir' }];

const user = usePage().props.user as { id: number, name: string, role: string };

interface Produk {
    id_produk: number;
    nama_produk: string;
    harga_jual: number;
    jumlah_stok: number;
    harga_beli?: number;
    qty: number;
};

interface Pelanggan {
    id_pelanggan: number;
    nama_pelanggan: string;
    nama_toko: string;
    alamat?: string;
    no_hanphone?: string;
};

interface ItemTransaksi {
    id_produk: number;
    nama_produk: string;
    harga_satuan: number;
    jumlah: number;
    total_harga: number;
    stok: number;
    harga_beli?: number;
};

interface TransaksiResponse {
    id: number;
    sub_total_harga: number;
    diskon: number;
    total_bayar: number;
    total_kurang: number;
    status_pembayaran: 'cash' | 'kredit';
    jatuh_tempo?: string;
    created_at: string;
    updated_at: string;
    user?: {
        id: number;
        name: string;
    };
    pelanggan?: {
        id_pelanggan: number;
        nama_pelanggan: string;
        nama_toko: string;
    };
    detail_transaksis?: Array<{
        id: number;
        jumlah: number;
        harga_satuan: number;
        total_harga: number;
        produk: {
            id_produk: number;
            nama_produk: string;
        };
    }>;
};

window.addEventListener('online', async () => {
    const offlineData = await getAllOfflineTransaksis();

    for (const transaksi of offlineData) {
        try {
            await axios.post('/api/transaksi', transaksi);
        } catch (e) {
            console.error('Gagal sinkronisasi transaksi offline:', e);
        }
    }

    await clearOfflineTransaksis();
});



// State Management
const pelanggans = ref<Pelanggan[]>([]);
const produks = ref<Produk[]>([]);
const transaksis = ref<TransaksiResponse[]>([]);
const items = ref<ItemTransaksi[]>([]);
const searchProduct = ref('');
const diskon = ref(0);
const totalBayar = ref(0);
const selectedPelanggan = ref<number | null>(null);
const statusPembayaran = ref<'cash' | 'kredit' | null>(null);
const jatuhTempo = ref<string>('');
const isLoading = ref(false);
const searchPelanggan = ref("");
const openModalTambahPelanggan = ref(false);
const loadingTambahPelanggan = ref(false);
const showDetailModal = ref(false);
const showPrintModal = ref(false);
const currentTransaction = ref<TransaksiResponse | null>(null);
const formPelanggan = ref({
    nama_pelanggan: '',
    alamat: '',
    no_handphone: '',
    nama_toko: '',
});


const showTransactionDetail = async (transaksi: TransaksiResponse) => {
    try {
        const { data } = await axios.get(`/api/transaksi/${transaksi.id}`, {
            params: {
                include: 'user,detail_transaksis.produk'
            }
        });
        currentTransaction.value = data.data;
        showDetailModal.value = true;
    } catch (error) {
        console.error('Gagal memuat detail transaksi:', error);
    }
};

const filteredPelanggan = computed(() => {
    if (!searchPelanggan.value) return pelanggans.value;
    return pelanggans.value.filter((p) =>
        p.nama_pelanggan.toLowerCase().includes(searchPelanggan.value.toLowerCase()) ||
        (p.nama_toko && p.nama_toko.toLowerCase().includes(searchPelanggan.value.toLowerCase()))
    );
});

const handlePrint = async (transaksi: TransaksiResponse) => {
    try {
        // Fetch ulang data transaksi dengan relasi yang diperlukan
        const { data } = await axios.get(`/api/transaksi/${transaksi.id}`, {
            params: {
                include: 'user,detail_transaksis.produk,pelanggan' // Tambahkan relasi
            }
        });
        currentTransaction.value = data.data;
        showPrintModal.value = true;
        await nextTick();
    } catch (error) {
        console.error('Gagal memuat data struk:', error);
        Swal.fire('Error', 'Gagal memuat data untuk dicetak', 'error');
    }
};


function showSuccess(message: string) {
    Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: message,
        timer: 1500,
        showConfirmButton: false,
    });
}

async function tambahPelanggan() {
    loadingTambahPelanggan.value = true;

    try {
        const response = await axios.post('/api/pelanggan', formPelanggan.value);
        showSuccess(response.data.message || 'Pelanggan berhasil ditambahkan');
        const newPelanggan = response.data.data;
        pelanggans.value.push(newPelanggan);
        formPelanggan.value = { nama_pelanggan: '', nama_toko: '', alamat: '', no_handphone: '' };
        openModalTambahPelanggan.value = false;
        selectedPelanggan.value = newPelanggan.id_pelanggan;

    } catch (error) {
        alert("Gagal tambah pelanggan baru. Silakan coba lagi.");
    } finally {
        loadingTambahPelanggan.value = false;
    }
}

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);

const formatDateTime = (dateString: string) => {
    const options: Intl.DateTimeFormatOptions = {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    };
    return new Date(dateString).toLocaleString('id-ID', options);
};

const subtotal = computed(() =>
    items.value.reduce((sum, item) => sum + item.total_harga, 0)
);

const totalSetelahDiskon = computed(() =>
    Math.max(0, subtotal.value - diskon.value)
);

const kembalian = computed(() =>
    totalBayar.value - totalSetelahDiskon.value
);

const totalKurang = computed(() =>
    totalSetelahDiskon.value - totalBayar.value
);

const filteredProduks = computed(() =>
    produks.value.filter(produk =>
        produk.nama_produk.toLowerCase().includes(searchProduct.value.toLowerCase())
    )
);

const minDueDate = computed(() => {
    const today = new Date();
    today.setDate(today.getDate() + 1);
    return today.toISOString().split('T')[0];
});

// Lifecycle Hooks
onMounted(async () => {
    isLoading.value = true;
    try {
        await Promise.all([fetchPelanggan(), fetchProduk(), fetchTransaksis()]);
    } catch (error) {
        console.error('Failed to initialize:', error);
        Swal.fire('Error', 'Gagal memuat data awal', 'error');
    } finally {
        isLoading.value = false;
    }
});

// API Functions
const fetchPelanggan = async () => {
    try {
        const { data } = await axios.get('/api/pelanggan');
        pelanggans.value = data.data;
    } catch (error) {
        console.error('Error fetching pelanggans:', error);
        throw error;
    }
};

const fetchProduk = async () => {
    try {
        const { data } = await axios.get('/api/produk');
        produks.value = data.data.map((p: Produk) => ({ ...p, qty: 1 }));
    } catch (error) {
        console.error('Error fetching produk:', error);
        throw error;
    }
};

const fetchTransaksis = async () => {
    try {
        const { data } = await axios.get('/api/transaksi');
        // Urutkan berdasarkan tanggal terbaru
        transaksis.value = (data.data || data)
            .map((t: TransaksiResponse) => ({
                ...t,
                created_at: t.created_at // Simpan format asli untuk sorting
            }))
            .sort((a: TransaksiResponse, b: TransaksiResponse) =>
                new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
            );
    } catch (error) {
        console.error('Error fetching transaksi:', error);
        Swal.fire('Error', 'Gagal memuat riwayat transaksi', 'error');
    }
};

// Transaction Functions
const addItem = (produk: Produk) => {
    if (!produk.qty || produk.qty < 1) {
        Swal.fire('Peringatan', 'Masukkan jumlah yang valid', 'warning');
        return;
    }

    const stokTersedia = produk.jumlah_stok;
    const existingItemIndex = items.value.findIndex(item => item.id_produk === produk.id_produk);

    if (existingItemIndex >= 0) {
        const newQty = items.value[existingItemIndex].jumlah + produk.qty;
        if (newQty > stokTersedia) {
            Swal.fire('Stok Tidak Cukup', `Stok tersisa hanya ${stokTersedia}`, 'warning');
            return;
        }
        items.value[existingItemIndex].jumlah = newQty;
        items.value[existingItemIndex].total_harga = newQty * items.value[existingItemIndex].harga_satuan;
    } else {
        if (produk.qty > stokTersedia) {
            Swal.fire('Stok Tidak Cukup', `Stok tersisa hanya ${stokTersedia}`, 'warning');
            return;
        }
        items.value.push({
            id_produk: produk.id_produk,
            nama_produk: produk.nama_produk,
            harga_satuan: produk.harga_jual,
            jumlah: produk.qty,
            total_harga: produk.harga_jual * produk.qty,
            stok: produk.jumlah_stok,
            harga_beli: produk.harga_beli
        });
    }
    produk.qty = 1;
};

const removeItem = (index: number) => {
    items.value.splice(index, 1);
};

const resetForm = () => {
    items.value = [];
    diskon.value = 0;
    totalBayar.value = 0;
    selectedPelanggan.value = null;
    statusPembayaran.value = null;
    jatuhTempo.value = '';
    produks.value.forEach(p => p.qty = 1);
};

watch(selectedPelanggan, (newVal) => {
    if (newVal === null && statusPembayaran.value === 'kredit') {
        statusPembayaran.value = null;
        jatuhTempo.value = '';
    }
});

const validateTransaction = () => {
    if (items.value.length === 0) {
        Swal.fire('Error', 'Tambahkan minimal 1 produk', 'error');
        return false;
    }

    if (!statusPembayaran.value) {
        Swal.fire('Error', 'Pilih status pembayaran', 'error');
        return false;
    }
    if (statusPembayaran.value === 'kredit' && selectedPelanggan.value === null) {
        Swal.fire('Error', 'Pelanggan umum tidak dapat menggunakan status pembayaran kredit', 'error');
        return false;
    }
    const totalTagihan = totalSetelahDiskon.value;

    if (statusPembayaran.value === 'kredit') {
        if (!jatuhTempo.value) {
            Swal.fire('Error', 'Isi tanggal jatuh tempo', 'error');
            return false;
        }
        if (totalBayar.value >= totalTagihan) {
            Swal.fire('Error', 'Untuk kredit, jumlah bayar harus kurang dari total', 'error');
            return false;
        }
    } else {
        if (totalBayar.value < totalTagihan) {
            Swal.fire('Error', 'Jumlah bayar tidak mencukupi', 'error');
            return false;
        }
    }
    return true;
};

const prosesTransaksi = async () => {
    if (!validateTransaction()) return;
    isLoading.value = true;
    try {
        const payload = {
            sub_total_harga: subtotal.value,
            total_bayar: totalBayar.value,
            status_pembayaran: statusPembayaran.value,
            jatuh_tempo: statusPembayaran.value === 'kredit' ? jatuhTempo.value : null,
            diskon: diskon.value,
            id_pelanggan: selectedPelanggan.value,
            id_user: user.id,
            items: items.value.map(item => ({
                id_produk: item.id_produk,
                jumlah: item.jumlah
            }))
        };

        // Jika offline, simpan ke IndexedDB
        if (!navigator.onLine) {
            await saveOfflineTransaksi(payload);
            Swal.fire({
                title: 'Offline Mode',
                text: 'Transaksi disimpan secara offline dan akan dikirim saat online.',
                icon: 'info',
                confirmButtonText: 'OK'
            });
            resetForm();
            return;
        }

        // Jika online, langsung kirim ke API
        const { data } = await axios.post('/api/transaksi', payload);

        currentTransaction.value = {
            ...data.data,
            detail_transaksis: data.data.detail_transaksis.map((detail: any) => ({
                id: detail.id,
                jumlah: detail.jumlah,
                harga_satuan: detail.harga_satuan,
                total_harga: detail.total_harga,
                produk: {
                    id_produk: detail.produk?.id_produk,
                    nama_produk: detail.produk?.nama_produk
                }
            }))
        };

        await Swal.fire({
            title: 'Transaksi Berhasil!',
            html: `No. Transaksi: <b>#${data.data.id}</b>`,
            icon: 'success',
            timer: 1000,
            showConfirmButton: false,
            allowOutsideClick: false,
            didClose: () => handlePrint(data.data)
        });

        resetForm();
        await fetchProduk();
        await fetchTransaksis();


    } catch (error: any) {
        // Handle error response dari API
        const errorMessage = error.response?.data?.message ||
            error.response?.data?.errors?.join('\n') ||
            'Terjadi kesalahan saat memproses transaksi';

        Swal.fire({
            title: 'Error!',
            text: errorMessage,
            icon: 'error',
            confirmButtonText: 'OK'
        });
    } finally {
        isLoading.value = false;
    }
};
</script>


<template>

    <Head title="Kasir" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full mx-auto py-4 px-2 sm:px-4 lg:px-6 xl:px-8">
            <div class="flex flex-col xl:flex-row gap-4">
                <!-- Main Content (Left Side) -->
                <div class="flex-1 flex flex-col gap-4">
                    <!-- Product List Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-2">
                                    <Package class="w-5 h-5 text-primary" />
                                    <span class="text-base sm:text-lg">Daftar Produk</span>
                                </div>
                                <div class="w-full sm:w-64 relative">
                                    <Input v-model="searchProduct" placeholder="Cari produk..."
                                        class="pl-10 w-full text-sm sm:text-base" />
                                    <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                </div>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                class="border rounded-lg overflow-hidden max-h-[400px] sm:max-h-[500px] overflow-y-auto">
                                <div class="overflow-x-auto">
                                    <Table class="min-w-[600px] w-full">
                                        <TableHeader class="bg-muted">
                                            <TableRow>
                                                <TableHead class="w-[80px] sm:w-[100px]">Nama
                                                    Produk</TableHead>
                                                <TableHead class="text-left w-[80px] sm:w-[100px]">Harga</TableHead>
                                                <TableHead class="text-center w-[60px] sm:w-[80px]">Stok</TableHead>
                                                <TableHead class="w-[80px] sm:w-[100px]">Jumlah</TableHead>
                                                <TableHead class="text-right w-[80px] sm:w-[100px]">Aksi</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="produk in filteredProduks" :key="produk.id_produk">
                                                <TableCell class="font-medium truncate max-w-[120px] sm:max-w-[200px]">
                                                    {{ produk.nama_produk }}
                                                </TableCell>
                                                <TableCell class="text-left whitespace-nowrap">
                                                    {{ formatCurrency(produk.harga_jual) }}
                                                </TableCell>
                                                <TableCell class="text-center whitespace-nowrap">
                                                    <Badge variant="outline" class="text-xs sm:text-sm">
                                                        {{ produk.jumlah_stok }}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div class="flex justify-center sm:justify-start">
                                                        <Input type="number" min="1" :max="produk.jumlah_stok"
                                                            v-model.number="produk.qty" @keyup.enter="addItem(produk)"
                                                            class="text-center h-8 sm:h-9 w-16 sm:w-20 text-sm sm:text-base" />
                                                    </div>
                                                </TableCell>
                                                <TableCell class="text-right whitespace-nowrap">
                                                    <Button @click="addItem(produk)" size="sm"
                                                        class="h-7 sm:h-8 w-full sm:w-auto text-xs sm:text-sm"
                                                        :disabled="!produk.qty || produk.qty < 1">
                                                        <Plus class="w-3 h-3 sm:w-4 sm:h-4 mr-1" />
                                                        <span class="hidden xs:inline">Tambah</span>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                            <TableRow v-if="filteredProduks.length === 0">
                                                <TableCell colspan="5" class="text-center py-6 text-muted-foreground">
                                                    Tidak ada produk ditemukan.
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Active Transaction Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <ShoppingCart class="w-5 h-5 text-primary" />
                                <span class="text-base sm:text-lg">Transaksi Aktif</span>
                                <Badge variant="outline" class="ml-2 text-xs sm:text-sm">
                                    {{ items.length }} Item
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 sm:space-y-6">
                            <!-- Customer Selection -->
                            <div class="space-y-2">
                                <div class="flex flex-col xs:flex-row gap-2 justify-between">
                                    <Label class="flex items-center gap-2 text-xs sm:text-sm font-medium">
                                        <User class="w-3 h-3 sm:w-4 sm:h-4" /> Pilih Pelanggan
                                    </Label>
                                    <Button size="sm" @click="openModalTambahPelanggan = true" variant="outline"
                                        class="w-full xs:w-auto text-xs sm:text-sm">
                                        <Plus class="w-3 h-3 sm:w-4 sm:h-4 mr-1" />
                                        Tambah Pelanggan
                                    </Button>
                                </div>
                                <Select v-model="selectedPelanggan">
                                    <SelectTrigger class="w-full text-xs sm:text-sm">
                                        <SelectValue placeholder="Cari atau pilih pelanggan..." />
                                    </SelectTrigger>
                                    <SelectContent class="max-h-60">
                                        <Input v-model="searchPelanggan" placeholder="Cari pelanggan..."
                                            class="mb-2 sticky top-0 bg-background text-xs sm:text-sm" />
                                        <SelectItem :value="null" class="text-xs sm:text-sm">
                                            <span class="text-muted-foreground">Umum</span>
                                        </SelectItem>

                                        <SelectItem v-for="pelanggan in filteredPelanggan" :key="pelanggan.id_pelanggan"
                                            :value="pelanggan.id_pelanggan" class="text-xs sm:text-sm">
                                            <div class="flex flex-col">
                                                <span class="font-medium">{{ pelanggan.nama_pelanggan }}</span>
                                                <span class="text-xs text-muted-foreground">
                                                    {{ pelanggan.nama_toko || 'Tidak ada nama toko' }}
                                                </span>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <Dialog v-model:open="openModalTambahPelanggan">
                                <DialogContent class="sm:max-w-[500px]">
                                    <DialogHeader>
                                        <DialogTitle class="border-b pb-4">Tambah Pelanggan Baru</DialogTitle>
                                    </DialogHeader>

                                    <form @submit.prevent="tambahPelanggan" class="space-y-4">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="space-y-2">
                                                <Label for="nama_pelanggan">Nama Pelanggan <span
                                                        class="text-red-500">*</span></Label>
                                                <Input id="nama_pelanggan" v-model="formPelanggan.nama_pelanggan"
                                                    required class="text-sm sm:text-base" />
                                            </div>
                                            <div class="space-y-2">
                                                <Label for="nama_toko">Nama Toko</Label>
                                                <Input id="nama_toko" v-model="formPelanggan.nama_toko"
                                                    class="text-sm sm:text-base" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="alamat_pelanggan">Alamat</Label>
                                            <Input id="alamat_pelanggan" v-model="formPelanggan.alamat"
                                                class="text-sm sm:text-base" />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="no_handphone">Nomor Telepon</Label>
                                            <Input id="no_handphone" v-model="formPelanggan.no_handphone" type="tel"
                                                class="text-sm sm:text-base" />
                                        </div>

                                        <div class="flex justify-end gap-2 mt-6">
                                            <Button type="button" variant="outline" class="text-sm sm:text-base"
                                                @click="openModalTambahPelanggan = false">
                                                Batal
                                            </Button>
                                            <Button type="submit" :disabled="loadingTambahPelanggan"
                                                class="text-sm sm:text-base">
                                                <span v-if="!loadingTambahPelanggan">Simpan</span>
                                                <span v-else>Menyimpan...</span>
                                            </Button>
                                        </div>
                                    </form>
                                </DialogContent>
                            </Dialog>

                            <!-- Items Table -->
                            <div class="w-full overflow-x-auto rounded-lg border">
                                <table class="w-full text-xs sm:text-sm">
                                    <thead class="bg-muted text-muted-foreground">
                                        <tr>
                                            <th class="min-w-[100px] sm:min-w-[150px] px-3 py-2">Produk</th>
                                            <th class="min-w-[80px] sm:min-w-[100px] px-3 py-2 text-right">Harga</th>
                                            <th class="min-w-[60px] sm:min-w-[80px] px-3 py-2 text-center">Qty</th>
                                            <th class="min-w-[80px] sm:min-w-[100px] px-3 py-2 text-right">Total</th>
                                            <th class="min-w-[60px] sm:min-w-[80px] px-3 py-2 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in items" :key="item.id_produk" class="border-t">
                                            <td class="px-3 py-2 whitespace-nowrap font-medium">
                                                {{ item.nama_produk }}
                                            </td>
                                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                                {{ formatCurrency(item.harga_satuan) }}
                                            </td>
                                            <td class="px-3 py-2 text-center whitespace-nowrap">
                                                {{ item.jumlah }}
                                            </td>
                                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                                {{ formatCurrency(item.total_harga) }}
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <Button variant="ghost" size="icon" @click="removeItem(index)"
                                                    class="h-7 w-7">
                                                    <Trash2 class="w-3 h-3 sm:w-4 sm:h-4 text-red-500" />
                                                </Button>
                                            </td>
                                        </tr>
                                        <tr v-if="items.length === 0">
                                            <td colspan="5" class="px-4 py-6 text-center text-muted-foreground">
                                                Belum ada item ditambahkan
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Payment Section -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                                <!-- Payment Form -->
                                <div class="space-y-4 sm:space-y-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                        <!-- Diskon -->
                                        <div class="space-y-1 sm:space-y-2">
                                            <Label for="diskon" class="text-xs sm:text-sm">Diskon (Rp)</Label>
                                            <Input id="diskon" type="number" min="0" v-model.number="diskon"
                                                class="w-full text-sm sm:text-base" />
                                        </div>

                                        <!-- Status Pembayaran -->
                                        <div class="space-y-1 sm:space-y-2">
                                            <Label for="status_pembayaran" class="text-xs sm:text-sm">Status</Label>
                                            <Select v-model="statusPembayaran">
                                                <SelectTrigger id="status_pembayaran" class="w-full text-xs sm:text-sm">
                                                    <SelectValue placeholder="Pilih Status" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="cash" class="text-xs sm:text-sm">
                                                        <span class="text-green-600">cash</span>
                                                    </SelectItem>
                                                    <SelectItem value="kredit" class="text-xs sm:text-sm"
                                                        :disabled="selectedPelanggan === null">
                                                        <span class="text-yellow-600">kredit</span>
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>

                                    <!-- Jatuh Tempo (jika kredit) -->
                                    <div v-if="statusPembayaran === 'kredit'" class="space-y-1 sm:space-y-2">
                                        <Label for="jatuhTempo" class="text-xs sm:text-sm">Jatuh Tempo</Label>
                                        <Input id="jatuhTempo" type="date" v-model="jatuhTempo" :min="minDueDate"
                                            class="w-full text-sm sm:text-base" />
                                    </div>
                                </div>

                                <!-- Payment Summary -->
                                <Card class="shadow-sm">
                                    <CardHeader class="bg-muted/40 py-3">
                                        <CardTitle class="text-base sm:text-lg font-semibold">Ringkasan Pembayaran
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent class="pt-4 space-y-3 sm:space-y-4">
                                        <div class="space-y-2 sm:space-y-3">
                                            <div class="flex justify-between items-center">
                                                <span class="text-xs sm:text-sm">Subtotal:</span>
                                                <span class="font-medium text-sm sm:text-base">{{
                                                    formatCurrency(subtotal) }}</span>
                                            </div>

                                            <div class="flex justify-between items-center">
                                                <span class="text-xs sm:text-sm">Diskon:</span>
                                                <span class="text-red-500 font-medium text-sm sm:text-base">-{{
                                                    formatCurrency(diskon) }}</span>
                                            </div>

                                            <Separator class="my-1 sm:my-2" />

                                            <div class="flex justify-between items-center">
                                                <span class="font-semibold text-sm sm:text-base">Total Tagihan:</span>
                                                <span class="text-primary font-bold text-base sm:text-lg">
                                                    {{ formatCurrency(totalSetelahDiskon) }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="space-y-2 sm:space-y-3 pt-3 sm:pt-4">
                                            <div class="flex flex-col gap-1 sm:gap-2">
                                                <Label class="text-center font-medium text-sm sm:text-base">Jumlah
                                                    Bayar</Label>
                                                <Input type="number" v-model.number="totalBayar"
                                                    class="h-10 sm:h-12 text-sm sm:text-lg font-bold w-full" />
                                            </div>

                                            <Separator class="my-2 sm:my-3" />

                                            <div class="flex justify-between items-center text-sm sm:text-base">
                                                <span>Kembalian:</span>
                                                <span :class="{
                                                    'text-green-600': kembalian >= 0,
                                                    'text-red-600': kembalian < 0
                                                }">
                                                    {{ formatCurrency(kembalian) }}
                                                </span>
                                            </div>
                                            <div v-if="statusPembayaran === 'kredit'"
                                                class="flex justify-between items-center text-sm sm:text-base">
                                                <span>Sisa kredit:</span>
                                                <span class="text-red-600">
                                                    {{ formatCurrency(totalKurang) }}
                                                </span>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            <!-- Submit Button -->
                            <Button class="w-full h-10 sm:h-12 text-sm sm:text-lg" @click="prosesTransaksi"
                                :disabled="isLoading || items.length === 0">
                                <CheckCircle class="w-4 h-4 sm:w-5 sm:h-5 mr-2" />
                                {{ isLoading ? 'Memproses...' : 'Simpan Transaksi' }}
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <!-- Transaction History (Right Side) -->
                <Card class="w-full xl:max-w-[400px] 2xl:max-w-[500px]">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <History class="w-5 h-5 text-emerald-500" />
                            <span class="text-base sm:text-lg">Riwayat Transaksi</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="w-full py-2 px-1 sm:px-2">
                            <div class="overflow-x-auto">
                                <Table class="min-w-[600px] sm:min-w-full">
                                    <TableHeader class="bg-muted">
                                        <TableRow>
                                            <TableHead
                                                class="w-[70px] sm:w-[80px] whitespace-nowrap text-xs sm:text-sm">ID
                                            </TableHead>
                                            <TableHead
                                                class="min-w-[100px] sm:min-w-[120px] whitespace-nowrap text-xs sm:text-sm">
                                                Tanggal</TableHead>
                                            <TableHead
                                                class="min-w-[80px] sm:min-w-[100px] whitespace-nowrap text-xs sm:text-sm">
                                                Pelanggan</TableHead>
                                            <TableHead
                                                class="text-left min-w-[80px] sm:min-w-[100px] whitespace-nowrap text-xs sm:text-sm">
                                                Total</TableHead>
                                            <TableHead class="whitespace-nowrap text-xs sm:text-sm">Status</TableHead>
                                            <TableHead
                                                class="w-[80px] sm:w-[90px] text-right whitespace-nowrap text-xs sm:text-sm">
                                                Aksi</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="transaksi in transaksis" :key="transaksi.id"
                                            class="group hover:bg-muted/50 transition-colors">
                                            <TableCell
                                                class="font-medium py-1 sm:py-2 align-middle whitespace-nowrap text-xs sm:text-sm">
                                                #{{ transaksi.id }}
                                            </TableCell>
                                            <TableCell
                                                class="py-1 sm:py-2 align-middle whitespace-nowrap text-xs sm:text-sm">
                                                <div class="text-muted-foreground">
                                                    {{ formatDateTime(transaksi.created_at) }}
                                                </div>
                                            </TableCell>
                                            <TableCell
                                                class="py-1 sm:py-2 align-middle whitespace-nowrap text-xs sm:text-sm">
                                                <div class="flex flex-col">
                                                    <span class="font-medium truncate max-w-[100px]">
                                                        {{ transaksi.pelanggan?.nama_pelanggan || 'Umum' }}
                                                    </span>
                                                    <span class="text-xs text-muted-foreground truncate max-w-[100px]">
                                                        {{ transaksi.pelanggan?.nama_toko || '' }}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell
                                                class="py-1 sm:py-2 align-middle whitespace-nowrap text-xs sm:text-sm">
                                                <div class="font-semibold">
                                                    {{ formatCurrency(transaksi.sub_total_harga) }}
                                                </div>
                                            </TableCell>
                                            <TableCell class="py-1 sm:py-2 align-middle whitespace-nowrap">
                                                <Badge
                                                    class="transition-colors h-5 px-2 py-0.5 min-w-[70px] sm:px-2 sm:py-1 text-xs sm:text-sm"
                                                    :class="{
                                                        'bg-green-600 text-white hover:bg-green-700': transaksi.status_pembayaran === 'cash',
                                                        'bg-red-600 text-white hover:bg-red-700': transaksi.status_pembayaran === 'kredit',
                                                    }">
                                                    {{ transaksi.status_pembayaran?.toUpperCase() }}
                                                </Badge>
                                            </TableCell>
                                            <TableCell class="py-1 sm:py-2 align-middle">
                                                <div class="flex gap-1 justify-end">
                                                    <Button variant="ghost" size="icon"
                                                        class="h-7 w-7 sm:h-8 sm:w-8 p-0 hover:bg-blue-50"
                                                        @click="showTransactionDetail(transaksi)" title="Lihat Detail">
                                                        <Eye class="w-3 h-3 sm:w-4 sm:h-4 text-blue-600" />
                                                    </Button>
                                                    <Button variant="ghost" size="icon"
                                                        class="h-7 w-7 sm:h-8 sm:w-8 p-0 hover:bg-gray-100"
                                                        @click="handlePrint(transaksi)" title="Cetak Ulang">
                                                        <Printer class="w-3 h-3 sm:w-4 sm:h-4 text-gray-600" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>

                                        <TableRow v-if="transaksis.length === 0">
                                            <TableCell colspan="6"
                                                class="text-center py-6 text-muted-foreground text-xs sm:text-sm">
                                                Belum ada riwayat transaksi
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
    <DetailTransaksi v-model:open="showDetailModal" :transaction="currentTransaction" />
    <StrukTransaksi v-model:open="showPrintModal" :transaction="currentTransaction" />
</template>