<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { PlusCircle, Pencil, Trash2, X, Check, User, Phone, MapPin, Store, Search } from 'lucide-vue-next';

// Import Idb_Pelanggan functions
import { clearOfflinePelanggans, getOfflinePelanggans, saveOfflinePelanggans, deleteOfflinePelanggan } from '@/lib/Idb_Pelanggan';

// Shadcn UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pelanggan', href: '/pelanggan' }
];

// State
const pelanggan = ref<any[]>([]);
const form = ref({
    nama_pelanggan: '',
    alamat: '',
    no_handphone: '',
    nama_toko: '',
});
const editingId = ref<number | null>(null);
const isLoading = ref(false);
const isSubmitting = ref(false);
const searchQuery = ref('');
const isOnline = ref(navigator.onLine); // Deteksi status online/offline
const offlineQueue = ref<any[]>([]); // Queue untuk operasi yang tertunda
const showDialog = ref(false); // State untuk mengontrol visibilitas dialog

// Computed property untuk memfilter pelanggan
const filteredPelanggan = computed(() => {
    const query = searchQuery.value.toLowerCase().trim();
    if (!query) {
        return pelanggan.value;
    }
    return pelanggan.value.filter(item =>
        item.nama_pelanggan.toLowerCase().includes(query) ||
        item.no_handphone.toLowerCase().includes(query) ||
        (item.nama_toko && item.nama_toko.toLowerCase().includes(query)) ||
        (item.alamat && item.alamat.toLowerCase().includes(query))
    );
});

// --- Network Status Detection ---
const updateOnlineStatus = () => {
    isOnline.value = navigator.onLine;
    if (isOnline.value) {
        console.log('App is online. Attempting to sync offline queue...');
        syncOfflineQueue(); // Coba sinkronkan saat kembali online
    } else {
        console.log('App is offline.');
        showError('Anda sedang offline. Perubahan akan disimpan dan disinkronkan saat online kembali.');
    }
};

// Event listeners for online/offline status
onMounted(() => {
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
});

// Watch for changes in online status to trigger sync
watch(isOnline, (newValue) => {
    if (newValue === true) {
        syncOfflineQueue();
    }
});

// --- Core Data Fetching & Syncing ---
const fetchpelanggan = async () => {
    try {
        isLoading.value = true;
        if (isOnline.value) {
            const response = await axios.get('/api/pelanggan');
            pelanggan.value = response.data.data;
            // Bersihkan IndexedDB dan simpan data terbaru dari server
            await clearOfflinePelanggans();
            for (const item of response.data.data) {
                await saveOfflinePelanggans(item);
            }
            console.log('Pelanggan fetched from API and saved to IndexedDB.');
        } else {
            pelanggan.value = await getOfflinePelanggans(); // Ambil dari IndexedDB jika offline
            console.log('Pelanggan loaded from IndexedDB (offline mode).');
        }
    } catch (error) {
        console.error('Error fetching customers:', error);
        showError('Gagal memuat data pelanggan dari server. Memuat data tersimpan.');
        pelanggan.value = await getOfflinePelanggans(); // Coba ambil dari IndexedDB bahkan jika ada error API
    } finally {
        isLoading.value = false;
    }
};

// --- Offline Queue Processing ---
const syncOfflineQueue = async () => {
    if (!isOnline.value || offlineQueue.value.length === 0) {
        return; // Jangan sync jika offline atau queue kosong
    }

    console.log('Attempting to sync offline operations...', offlineQueue.value);
    const successfulOperations: number[] = []; // Index dari operasi yang berhasil

    for (let i = 0; i < offlineQueue.value.length; i++) {
        const operation = offlineQueue.value[i];
        try {
            if (operation.type === 'create') {
                const response = await axios.post('/api/pelanggan', operation.data);
                showSuccess(`Pelanggan "${operation.data.nama_pelanggan}" berhasil ditambahkan (disinkronkan).`);
                // Perbarui ID di IndexedDB untuk entri offline yang baru dibuat
                if (operation.offlineId) {
                    // Hapus entri lama dengan offlineId
                    await deleteOfflinePelanggan(operation.offlineId);
                    // Tambahkan entri baru dengan ID dari server
                    await saveOfflinePelanggans(response.data.data);
                    console.log(`Updated offline entry ID from ${operation.offlineId} to ${response.data.data.id}`);
                }
            } else if (operation.type === 'update') {
                await axios.put(`/api/pelanggan/${operation.id}`, operation.data);
                showSuccess(`Pelanggan "${operation.data.nama_pelanggan}" berhasil diperbarui (disinkronkan).`);
                // Setelah update di server, update juga di IndexedDB untuk memastikan konsistensi
                await saveOfflinePelanggans({ ...operation.data, id: operation.id });
            } else if (operation.type === 'delete') {
                await axios.delete(`/api/pelanggan/${operation.id}`);
                showSuccess(`Pelanggan (ID: ${operation.id}) berhasil dihapus (disinkronkan).`);
                // Hapus juga dari IndexedDB
                await deleteOfflinePelanggan(operation.id);
            }
            successfulOperations.push(i); // Tandai sebagai berhasil
        } catch (error: any) {
            console.error(`Failed to sync operation ${operation.type} for ID ${operation.id || operation.offlineId || operation.data?.nama_pelanggan}:`, error);
            showError(`Gagal menyinkronkan data: ${error.message}. Akan dicoba lagi.`);
            // Jika gagal, biarkan di queue untuk dicoba lagi nanti
        }
    }

    // Hapus operasi yang berhasil dari queue
    offlineQueue.value = offlineQueue.value.filter((_, index) => !successfulOperations.includes(index));
    localStorage.setItem('offline_queue', JSON.stringify(offlineQueue.value)); // Tetap simpan queue di LocalStorage

    // Ambil ulang data pelanggan setelah sinkronisasi (untuk memastikan konsistensi)
    if (successfulOperations.length > 0) {
        await fetchpelanggan();
    }
};

// --- Modified submitForm for Offline Handling ---
const submitForm = async () => {
    try {
        isSubmitting.value = true;
        const dataToSave = { ...form.value }; // Salin data form

        if (editingId.value) {
            // Operasi UPDATE
            if (isOnline.value) {
                await axios.put(`/api/pelanggan/${editingId.value}`, dataToSave);
                showSuccess('Pelanggan berhasil diperbarui');
            } else {
                // Tambahkan ke queue jika offline
                offlineQueue.value.push({ type: 'update', id: editingId.value, data: dataToSave });
                localStorage.setItem('offline_queue', JSON.stringify(offlineQueue.value));
                showSuccess('Anda offline. Perubahan pelanggan disimpan lokal dan akan disinkronkan.');
                // Update tampilan lokal dan IndexedDB secara langsung
                await saveOfflinePelanggans({ ...dataToSave, id: editingId.value });
            }
        } else {
            // Operasi CREATE
            if (isOnline.value) {
                const response = await axios.post('/api/pelanggan', dataToSave);
                showSuccess('Pelanggan berhasil ditambahkan');
                // Data pelanggan akan di-fetch ulang dari server, jadi tidak perlu update pelanggan.value secara manual di sini
            } else {
                // Generate temporary ID for new offline entry
                const tempId = `offline-${Date.now()}-${Math.floor(Math.random() * 1000)}`; // ID sementara unik
                offlineQueue.value.push({ type: 'create', data: dataToSave, offlineId: tempId });
                localStorage.setItem('offline_queue', JSON.stringify(offlineQueue.value));
                showSuccess('Anda offline. Pelanggan baru disimpan lokal dan akan disinkronkan.');
                // Tambahkan ke daftar pelanggan lokal dengan ID sementara
                const newCustomer = { ...dataToSave, id: tempId, is_offline_new: true };
                pelanggan.value.push(newCustomer);
                // Simpan juga ke IndexedDB dengan ID sementara
                await saveOfflinePelanggans(newCustomer);
            }
        }
        resetForm();
        showDialog.value = false; // Tutup dialog setelah submit
        await fetchpelanggan(); // Selalu fetch data setelah operasi (baik online maupun offline)
    } catch (error: any) {
        console.error('Failed to save customer:', error);
        showError('Gagal menyimpan data pelanggan');
    } finally {
        isSubmitting.value = false;
    }
};

// --- Modified editpelanggan ---
const editpelanggan = (item: any) => {
    form.value = {
        nama_pelanggan: item.nama_pelanggan || '',
        no_handphone: item.no_handphone || '',
        alamat: item.alamat || '',
        nama_toko: item.nama_toko || '',
    };
    editingId.value = item.id;
    showDialog.value = true; // Buka dialog saat mengedit
};

// --- Modified deletepelanggan for Offline Handling ---
const deletepelanggan = async (id: number) => {
    const result = await Swal.fire({
        title: 'Yakin menghapus pelanggan?',
        text: 'Data tidak bisa dikembalikan setelah dihapus',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
    });

    if (result.isConfirmed) {
        try {
            if (isOnline.value) {
                await axios.delete(`/api/pelanggan/${id}`);
                showSuccess('Pelanggan berhasil dihapus');
                // Hapus dari IndexedDB
                await deleteOfflinePelanggan(id);
            } else {
                // Tambahkan ke queue jika offline
                offlineQueue.value.push({ type: 'delete', id: id });
                localStorage.setItem('offline_queue', JSON.stringify(offlineQueue.value));
                showSuccess('Anda offline. Permintaan penghapusan disimpan lokal dan akan disinkronkan.');
                // Hapus dari IndexedDB dan tampilan lokal secara langsung
                await deleteOfflinePelanggan(id);
            }
            await fetchpelanggan(); // Ambil ulang data setelah operasi
        } catch (error) {
            console.error('Failed to delete customer:', error);
            showError('Gagal menghapus pelanggan');
        }
    }
};

// Reset form and close dialog
const resetForm = () => {
    form.value = {
        nama_pelanggan: '',
        alamat: '',
        no_handphone: '',
        nama_toko: '',
    };
    editingId.value = null;
    // showDialog.value = false; // This will be handled by dialog's v-model on close
};

// Function to open dialog for adding new customer
const openAddCustomerDialog = () => {
    resetForm(); // Pastikan form bersih saat menambah baru
    showDialog.value = true;
};

// Show notifications
const showSuccess = (message: string) => {
    Swal.fire({
        title: 'Berhasil!',
        text: message,
        icon: 'success',
        confirmButtonColor: '#3b82f6',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });
};

const showError = (message: string) => {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message,
        confirmButtonColor: '#3b82f6',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
    });
};

// Initialize on mount
onMounted(async () => {
    offlineQueue.value = localStorage.getItem('offline_queue') ? JSON.parse(localStorage.getItem('offline_queue')!) : [];

    pelanggan.value = await getOfflinePelanggans();
    console.log('Initial customers loaded from IndexedDB:', pelanggan.value);

    await fetchpelanggan();
    updateOnlineStatus();
});
</script>

<template>
    <Head title="Pelanggan" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">
            <div :class="[
                'p-3 rounded-lg text-sm font-medium text-center',
                isOnline ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-yellow-100 text-yellow-700 border border-yellow-200'
            ]" v-if="offlineQueue.length > 0 || !isOnline">
                <span v-if="isOnline">
                    Anda sedang online. Mengirim {{ offlineQueue.length }} operasi yang tertunda...
                </span>
                <span v-else>
                    Anda sedang offline. Data akan disinkronkan saat online kembali. ({{ offlineQueue.length }} operasi tertunda)
                </span>
            </div>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="flex items-center gap-2">
                        Daftar Pelanggan
                        <Badge variant="outline" class="px-2 py-1">
                            {{ filteredPelanggan.length }} data
                        </Badge>
                    </CardTitle>
                    <div class="flex items-center gap-4">
                        <div class="relative w-full max-w-sm">
                            <Input
                                v-model="searchQuery"
                                placeholder="Cari pelanggan..."
                                class="pl-8"
                            />
                            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        </div>
                        <Button @click="openAddCustomerDialog">
                            <PlusCircle class="w-4 h-4 mr-2" />
                            Tambah Pelanggan
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <div class="max-h-[500px] overflow-y-auto">
                            <Table>
                                <TableHeader class="sticky top-0 bg-white z-10">
                                    <TableRow>
                                        <TableHead>Nama Pelanggan</TableHead>
                                        <TableHead>No. HP</TableHead>
                                        <TableHead>Nama Toko</TableHead>
                                        <TableHead>Alamat</TableHead>
                                        <TableHead class="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <template v-if="isLoading">
                                        <TableRow v-for="i in 3" :key="i">
                                            <TableCell>
                                                <Skeleton class="h-4 w-[120px]" />
                                            </TableCell>
                                            <TableCell>
                                                <Skeleton class="h-4 w-[100px]" />
                                            </TableCell>
                                            <TableCell>
                                                <Skeleton class="h-4 w-[150px]" />
                                            </TableCell>
                                            <TableCell>
                                                <Skeleton class="h-4 w-[200px]" />
                                            </TableCell>
                                            <TableCell class="flex justify-end gap-2">
                                                <Skeleton class="h-8 w-8" />
                                                <Skeleton class="h-8 w-8" />
                                            </TableCell>
                                        </TableRow>
                                    </template>
                                    <template v-else>
                                        <TableRow v-for="item in filteredPelanggan" :key="item.id"
                                            class="group hover:bg-muted/50 transition-colors">
                                            <TableCell class="font-medium">
                                                {{ item.nama_pelanggan }}
                                                <Badge v-if="typeof item.id === 'string' && item.id.startsWith('offline-')" variant="secondary" class="ml-2">Offline New</Badge>
                                            </TableCell>
                                            <TableCell>
                                                {{ item.no_handphone }}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {{ item.nama_toko || '-' }}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {{ item.alamat || '-' }}
                                                </Badge>
                                            </TableCell>
                                            <TableCell class="text-right space-x-2">
                                                <Button @click="editpelanggan(item)" variant="ghost" size="sm" title="Edit"
                                                    class="h-8 px-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <Pencil class="h-4 w-4" />
                                                </Button>
                                                <Button @click="deletepelanggan(item.id)" variant="ghost"
                                                    size="sm"
                                                    title="Hapus"
                                                    class="h-8 px-2 text-destructive hover:text-destructive opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-if="filteredPelanggan.length === 0 && !isLoading">
                                            <TableCell :colspan="5" class="text-center text-muted-foreground py-8">
                                                Tidak ada data pelanggan yang cocok dengan pencarian Anda.
                                            </TableCell>
                                        </TableRow>
                                    </template>
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="showDialog" @update:open="val => { if (!val) resetForm() }">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>{{ editingId ? 'Edit Pelanggan' : 'Tambah Pelanggan Baru' }}</DialogTitle>
                    <DialogDescription>
                        Lengkapi detail pelanggan di bawah ini. Klik simpan setelah selesai.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitForm" class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="nama_pelanggan">
                            <User class="w-4 h-4 inline mr-1" />
                            Nama Pelanggan <span class="text-destructive">*</span>
                        </Label>
                        <Input v-model.trim="form.nama_pelanggan" id="nama_pelanggan"
                            placeholder="Nama lengkap pelanggan" required :disabled="isSubmitting" />
                    </div>

                    <div class="space-y-2">
                        <Label for="no_handphone">
                            <Phone class="w-4 h-4 inline mr-1" />
                            No. Handphone <span class="text-destructive">*</span>
                        </Label>
                        <Input v-model.trim="form.no_handphone" id="no_handphone"
                            placeholder="Nomor telepon aktif" required :disabled="isSubmitting" />
                    </div>

                    <div class="space-y-2">
                        <Label for="nama_toko">
                            <Store class="w-4 h-4 inline mr-1" />
                            Nama Toko <span class="text-destructive">*</span>
                        </Label>
                        <Input v-model.trim="form.nama_toko" id="nama_toko" placeholder="Nama toko/usaha"
                            required :disabled="isSubmitting" />
                    </div>

                    <div class="space-y-2">
                        <Label for="alamat">
                            <MapPin class="w-4 h-4 inline mr-1" />
                            Alamat <span class="text-destructive">*</span>
                        </Label>
                        <Input v-model.trim="form.alamat" id="alamat" placeholder="Alamat lengkap" required
                            :disabled="isSubmitting" />
                    </div>
                </form>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="showDialog = false; resetForm()" :disabled="isSubmitting">
                        <X class="w-4 h-4 mr-2" />
                        Batal
                    </Button>
                    <Button type="submit" @click="submitForm" :disabled="isSubmitting">
                        <Check class="w-4 h-4 mr-2" />
                        {{ editingId ? 'Update' : 'Simpan' }}
                        <span v-if="isSubmitting" class="ml-2">
                            <span class="loading-dots">
                                <span>.</span><span>.</span><span>.</span>
                            </span>
                        </span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
/* (Gaya CSS tetap sama) */
.loading-dots {
    display: inline-flex;
    gap: 2px;
}

.loading-dots span {
    animation: blink 1.4s infinite both;
    animation-delay: calc(var(--index) * 0.2s);
}

.loading-dots span:nth-child(1) {
    --index: 1;
}

.loading-dots span:nth-child(2) {
    --index: 2;
}

.loading-dots span:nth-child(3) {
    --index: 3;
}

@keyframes blink {

    0%,
    100% {
        opacity: 0.2;
    }

    50% {
        opacity: 1;
    }
}

@media (max-width: 640px) {
    .group-hover\:opacity-100 {
        opacity: 1 !important;
    }
}
</style>