<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { PlusCircle, Pencil, Trash2, X, Check, User, Phone, MapPin, Store } from 'lucide-vue-next';

// Shadcn UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';

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

// Fetch data
const fetchpelanggan = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get('/api/pelanggan');
        pelanggan.value = response.data.data;
    } catch (error) {
        console.error('Error fetching customers:', error);
        showError('Gagal memuat data pelanggan');
    } finally {
        isLoading.value = false;
    }
};

// Submit form (create/update)
const submitForm = async () => {
    try {
        isSubmitting.value = true;
        if (editingId.value) {
            await axios.put(`/api/pelanggan/${editingId.value}`, form.value);
            showSuccess('Pelanggan berhasil diperbarui');
        } else {
            await axios.post('/api/pelanggan', form.value);
            showSuccess('Pelanggan berhasil ditambahkan');
        }
        resetForm();
        await fetchpelanggan();
    } catch (error) {
        console.error('Failed to save customer:', error);
        showError('Gagal menyimpan data pelanggan');
    } finally {
        isSubmitting.value = false;
    }
};

// Edit customer
const editpelanggan = (item: any) => {
    form.value = {
        nama_pelanggan: item.nama_pelanggan || '',
        no_handphone: item.no_handphone || '',
        alamat: item.alamat || '',
        nama_toko: item.nama_toko || '',
    };
    editingId.value = item.id_pelanggan;
};

// Delete customer
const deletepelanggan = async (id_pelanggan: number) => {
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
            await axios.delete(`/api/pelanggan/${id_pelanggan}`);
            await fetchpelanggan();
            showSuccess('Pelanggan berhasil dihapus');
        } catch (error) {
            console.error('Failed to delete customer:', error);
            showError('Gagal menghapus pelanggan');
        }
    }
};

// Reset form
const resetForm = () => {
    form.value = {
        nama_pelanggan: '',
        alamat: '',
        no_handphone: '',
        nama_toko: '',
    };
    editingId.value = null;
};

// Show notifications
const showSuccess = (message: string) => {
    Swal.fire({
        title: 'Berhasil!',
        text: message,
        icon: 'success',
        confirmButtonColor: '#3b82f6',
    });
};

const showError = (message: string) => {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message,
        confirmButtonColor: '#3b82f6',
    });
};

onMounted(fetchpelanggan);
</script>

<template>

    <Head title="Pelanggan" />
    <AppLayout :breadcrumbs="breadcrumbs">
       <div class="w-full mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Form Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <PlusCircle class="w-5 h-5" />
                        {{ editingId ? 'Edit Pelanggan' : 'Tambah Pelanggan Baru' }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Nama Pelanggan -->
                            <div class="space-y-2">
                                <Label for="nama_pelanggan">
                                    <User class="w-4 h-4 inline mr-1" />
                                    Nama Pelanggan <span class="text-destructive">*</span>
                                </Label>
                                <Input v-model.trim="form.nama_pelanggan" id="nama_pelanggan"
                                    placeholder="Nama lengkap pelanggan" required :disabled="isSubmitting" />
                            </div>

                            <!-- No Handphone -->
                            <div class="space-y-2">
                                <Label for="no_handphone">
                                    <Phone class="w-4 h-4 inline mr-1" />
                                    No. Handphone <span class="text-destructive">*</span>
                                </Label>
                                <Input v-model.trim="form.no_handphone" id="no_handphone"
                                    placeholder="Nomor telepon aktif" required :disabled="isSubmitting" />
                            </div>

                            <!-- Nama Toko -->
                            <div class="space-y-2">
                                <Label for="nama_toko">
                                    <Store class="w-4 h-4 inline mr-1" />
                                    Nama Toko <span class="text-destructive">*</span>
                                </Label>
                                <Input v-model.trim="form.nama_toko" id="nama_toko" placeholder="Nama toko/usaha"
                                    required :disabled="isSubmitting" />
                            </div>

                            <!-- Alamat -->
                            <div class="space-y-2 md:col-span-2">
                                <Label for="alamat">
                                    <MapPin class="w-4 h-4 inline mr-1" />
                                    Alamat <span class="text-destructive">*</span>
                                </Label>
                                <Input v-model.trim="form.alamat" id="alamat" placeholder="Alamat lengkap" required
                                    :disabled="isSubmitting" />
                            </div>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <Button type="submit" :disabled="isSubmitting">
                                <Check class="w-4 h-4 mr-2" />
                                {{ editingId ? 'Update' : 'Simpan' }}
                                <span v-if="isSubmitting" class="ml-2">
                                    <span class="loading-dots">
                                        <span>.</span><span>.</span><span>.</span>
                                    </span>
                                </span>
                            </Button>
                            <Button v-if="editingId" @click="resetForm" type="button" variant="outline"
                                :disabled="isSubmitting">
                                <X class="w-4 h-4 mr-2" />
                                Batal
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Table Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        Daftar Pelanggan
                        <Badge variant="outline" class="px-2 py-1">
                            {{ pelanggan.length }} data
                        </Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
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
                                        <TableCell class="flex justify-end gap-2">
                                            <Skeleton class="h-8 w-8" />
                                            <Skeleton class="h-8 w-8" />
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <template v-else>
                                    <TableRow v-for="item in pelanggan" :key="item.id_pelanggan"
                                        class="group hover:bg-muted/50 transition-colors">
                                        <TableCell class="font-medium">
                                            {{ item.nama_pelanggan }}
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
                                            <Button @click="editpelanggan(item)" variant="ghost" size="sm"
                                                class="h-8 px-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                            <Button @click="deletepelanggan(item.id_pelanggan)" variant="ghost"
                                                size="sm"
                                                class="h-8 px-2 text-destructive hover:text-destructive opacity-0 group-hover:opacity-100 transition-opacity">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="pelanggan.length === 0 && !isLoading">
                                        <TableCell colspan="4" class="text-center text-muted-foreground py-8">
                                            Tidak ada data pelanggan
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