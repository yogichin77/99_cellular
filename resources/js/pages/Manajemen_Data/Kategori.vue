<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { PlusCircle, Pencil, Trash2, X, Check } from 'lucide-vue-next';

// Shadcn UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea/';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
// Import the Skeleton component if you have it in Shadcn UI
import { Skeleton } from '@/components/ui/skeleton'; // Assuming this path for Shadcn UI Skeleton

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kategori', href: '/kategori' }
];

// State
const kategori = ref<any[]>([]);
const form = ref({
    nama_kategori: '',
    deskripsi_kategori: '',
});
const editingId = ref<number | null>(null);
const isLoading = ref(false);
const isFormDialogOpen = ref(false);

// Fetch data
const fetchkategori = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get('api/kategori');
        kategori.value = response.data.data;
    } catch (error) {
        console.error('Error fetching categories:', error);
        Swal.fire('Error', 'Gagal memuat data kategori', 'error');
    } finally {
        isLoading.value = false;
    }
};

// Submit form (create/update)
const submitForm = async () => {
    try {
        isLoading.value = true;

        const formData = new FormData();
        formData.append('nama_kategori', form.value.nama_kategori);
        formData.append('deskripsi_kategori', form.value.deskripsi_kategori);

        if (editingId.value) {
            formData.append('_method', 'PUT');
            await axios.post(`api/kategori/${editingId.value}`, formData);
            Swal.fire('Berhasil!', 'Kategori berhasil diperbarui', 'success');
        } else {
            await axios.post('api/kategori', formData);
            Swal.fire('Berhasil!', 'Kategori berhasil ditambahkan', 'success');
        }
        resetForm();
        isFormDialogOpen.value = false;
        await fetchkategori();
    } catch (error: any) {
        console.error('Error saving category:', error);
        if (axios.isAxiosError(error) && error.response) {
            if (error.response.status === 422) {
                const errors = error.response.data.errors;
                let errorMessage = '';
                for (const key in errors) {
                    errorMessage += errors[key].join(', ') + '\n';
                }
                Swal.fire('Validasi Gagal', errorMessage, 'error');
            } else {
                Swal.fire('Error', 'Gagal menyimpan kategori. ' + (error.response.data.message || ''), 'error');
            }
        } else {
            Swal.fire('Error', 'Gagal menyimpan kategori.', 'error');
        }
    } finally {
        isLoading.value = false;
    }
};

// Edit category
const editkategori = (item: any) => {
    form.value = {
        nama_kategori: item.nama_kategori,
        deskripsi_kategori: item.deskripsi_kategori,
    };
    editingId.value = item.id;
    isFormDialogOpen.value = true;
};

// Delete category
const deletekategori = async (id: number) => {
    const result = await Swal.fire({
        title: 'Yakin menghapus kategori?',
        text: 'Data tidak bisa dikembalikan setelah dihapus',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#ef4444',
    });

    if (result.isConfirmed) {
        try {
            isLoading.value = true;
            await axios.delete(`api/kategori/${id}`);
            await fetchkategori();
            Swal.fire('Berhasil!', 'Kategori telah dihapus', 'success');
        } catch (error) {
            console.error('Error deleting category:', error);
            Swal.fire('Error', 'Gagal menghapus kategori. Mungkin terkait pada produk lain.', 'error');
        } finally {
            isLoading.value = false;
        }
    }
};

// Reset form
const resetForm = () => {
    form.value = {
        nama_kategori: '',
        deskripsi_kategori: '',
    };
    editingId.value = null;
};

// Function to truncate text
const truncateText = (text: string, maxLength: number) => {
    if (text && text.length > maxLength) {
        return text.substring(0, maxLength) + '...';
    }
    return text;
};

onMounted(fetchkategori);
</script>

<template>
    <Head title="Kategori" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-end">
                <Dialog v-model:open="isFormDialogOpen" @update:open="val => { if (!val) resetForm() }">
                    <DialogTrigger as-child>
                        <Button @click="resetForm">
                            <PlusCircle class="w-4 h-4 mr-2" />
                            Tambah Kategori Baru
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[500px] overflow-y-auto max-h-[90vh]">
                        <DialogHeader>
                            <DialogTitle>{{ editingId ? 'Edit Kategori' : 'Tambah Kategori Baru' }}</DialogTitle>
                            <DialogDescription>
                                Lengkapi detail kategori di bawah ini. Klik simpan saat Anda selesai.
                            </DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submitForm" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="nama_kategori">
                                    Nama Kategori <span class="text-destructive">*</span>
                                </Label>
                                <Input v-model="form.nama_kategori" id="nama_kategori"
                                    placeholder="Masukkan nama kategori" required :disabled="isLoading" />
                            </div>

                            <div class="space-y-2">
                                <Label for="deskripsi_kategori">
                                    Deskripsi Kategori <span class="text-destructive">*</span>
                                </Label>
                                <Textarea v-model="form.deskripsi_kategori" id="deskripsi_kategori"
                                    placeholder="Masukkan deskripsi kategori" rows="4" required :disabled="isLoading" />
                            </div>
                        </form>
                        <DialogFooter class="mt-4">
                            <Button v-if="editingId" @click="isFormDialogOpen = false; resetForm()" type="button"
                                variant="outline" :disabled="isLoading">
                                <X class="w-4 h-4 mr-2" />
                                Batal
                            </Button>
                            <Button @click="submitForm" :disabled="isLoading">
                                <Check class="w-4 h-4 mr-2" />
                                {{ editingId ? 'Update' : 'Simpan' }}
                                <span v-if="isLoading" class="ml-2">
                                    <span class="loading-dots">
                                        <span>.</span><span>.</span><span>.</span>
                                    </span>
                                </span>
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        Daftar Kategori
                        <Badge variant="outline" class="px-2 py-1">
                            {{ kategori.length }} item
                        </Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border relative overflow-x-auto">
                        <Table>
                            <TableHeader class="sticky top-0 bg-background z-10">
                                <TableRow>
                                    <TableHead class="w-[30%]">Nama Kategori</TableHead>
                                    <TableHead class="w-[50%]">Deskripsi Kategori</TableHead>
                                    <TableHead class="text-right w-[20%]">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="isLoading">
                                    <TableRow v-for="i in 5" :key="i">
                                        <TableCell class="py-3">
                                            <Skeleton class="h-4 w-[200px]" />
                                        </TableCell>
                                        <TableCell class="py-3">
                                            <Skeleton class="h-4 w-[300px]" />
                                        </TableCell>
                                        <TableCell class="flex justify-end gap-2 py-3">
                                            <Skeleton class="h-8 w-8" />
                                            <Skeleton class="h-8 w-8" />
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <template v-else-if="kategori.length > 0">
                                    <TableRow v-for="item in kategori" :key="item.id"
                                        class="group hover:bg-muted/50 transition-colors">
                                        <TableCell class="font-medium">
                                            {{ item.nama_kategori }}
                                        </TableCell>
                                        <TableCell
                                            class="text-muted-foreground max-w-[200px] overflow-hidden text-ellipsis whitespace-nowrap">
                                            {{ truncateText(item.deskripsi_kategori, 100) }}
                                        </TableCell>
                                        <TableCell class="text-right space-x-2">
                                            <Button @click="editkategori(item)" variant="ghost" title="Edit"
                                                size="sm"
                                                class="h-8 px-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                            <Button @click="deletekategori(item.id)" variant="ghost" size="sm"
                                                title="Hapus"
                                                class="h-8 px-2 text-destructive hover:text-destructive opacity-0 group-hover:opacity-100 transition-opacity">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <template v-else>
                                    <TableRow>
                                        <TableCell colspan="3" class="text-center text-muted-foreground py-8">
                                            Tidak ada data kategori
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


