<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { Check, Pencil, PlusCircle, Trash2, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { onMounted, ref } from 'vue';

// Shadcn UI Components
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea/';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog'; // Import Dialog components

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Merek', href: '/merek' }
];

// State
const merek = ref<any[]>([]);
const form = ref({
    nama_merek: '',
    deskripsi_merek: '',
});
const editingId = ref<number | null>(null);
const isLoading = ref(false); // For initial data fetch and general loading indication
const isSubmitting = ref(false); // For form submission loading indication
const isFormDialogOpen = ref(false); // State to control the dialog visibility

// Fetch data
const fetchmerek = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get('api/merek');
        merek.value = response.data.data;
    } catch (error) {
        console.error('Error fetching brands:', error);
        Swal.fire({
            title: 'Error',
            text: 'Gagal memuat data merek',
            icon: 'error',
            confirmButtonColor: '#3b82f6',
        });
    } finally {
        isLoading.value = false;
    }
};

// Submit form (create/update)
const submitForm = async () => {
    try {
        isSubmitting.value = true;
        if (editingId.value) {
            // Change here: Add 'api/' prefix for PUT requests
            await axios.put(`api/merek/${editingId.value}`, form.value);
            Swal.fire({
                title: 'Berhasil!',
                text: 'Merek berhasil diperbarui',
                icon: 'success',
                confirmButtonColor: '#3b82f6',
            });
        } else {
            // This already has '/api/merek', so no change needed
            await axios.post('api/merek', form.value);
            Swal.fire({
                title: 'Berhasil!',
                text: 'Merek berhasil ditambahkan',
                icon: 'success',
                confirmButtonColor: '#3b82f6',
            });
        }
        isFormDialogOpen.value = false; // Close dialog on success
        resetForm(); // Reset form after closing
        await fetchmerek();
    } catch (error: any) { // Catch error as 'any' to access response properties
        console.error('Failed to save brand:', error);
        let errorMessage = 'Gagal menyimpan data merek';
        if (error.response && error.response.data && error.response.data.errors) {
            // Handle validation errors from Laravel
            const errors = error.response.data.errors;
            errorMessage = Object.values(errors).flat().join('\n');
        }
        Swal.fire({
            title: 'Error',
            text: errorMessage,
            icon: 'error',
            confirmButtonColor: '#3b82f6',
        });
    } finally {
        isSubmitting.value = false;
    }
};

// Edit brand
const editmerek = (item: any) => {
    form.value = {
        nama_merek: item.nama_merek,
        deskripsi_merek: item.deskripsi_merek,
    };
    editingId.value = item.id;
    isFormDialogOpen.value = true; // Open the dialog for editing
};

// Delete brand
const deletemerek = async (id: number) => {
    const result = await Swal.fire({
        title: 'Yakin menghapus merek?',
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
            isLoading.value = true; // Show loading while deleting
            await axios.delete(`api/merek/${id}`);
            await fetchmerek();
            Swal.fire({
                title: 'Berhasil!',
                text: 'Merek telah dihapus',
                icon: 'success',
                confirmButtonColor: '#3b82f6',
            });
        } catch (error) {
            console.error('Failed to delete brand:', error);
            Swal.fire({
                title: 'Error',
                text: 'Gagal menghapus merek karena terkait pada produk',
                icon: 'error',
                confirmButtonColor: '#3b82f6',
            });
        } finally {
            isLoading.value = false; // Hide loading after deleting
        }
    }
};

// Reset form
const resetForm = () => {
    form.value = {
        nama_merek: '',
        deskripsi_merek: '',
    };
    editingId.value = null;
    // Don't set isFormDialogOpen to false here, let Dialog's v-model handle closing
};

onMounted(fetchmerek);

const truncateText = (text: string, maxLength: number) => {
    if (text && text.length > maxLength) {
        return text.substring(0, maxLength) + '...';
    }
    return text;
};
</script>

<template>
    <Head title="Merek" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-end">
                <Dialog v-model:open="isFormDialogOpen" @update:open="val => { if (!val) resetForm() }">
                    <DialogTrigger as-child>
                        <Button @click="resetForm">
                            <PlusCircle class="w-4 h-4 mr-2" />
                            Tambah Merek Baru
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[500px] overflow-y-auto max-h-[90vh]">
                        <DialogHeader>
                            <DialogTitle>{{ editingId ? 'Edit Merek' : 'Tambah Merek Baru' }}</DialogTitle>
                            <DialogDescription>
                                Lengkapi detail merek di bawah ini. Klik simpan saat Anda selesai.
                            </DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submitForm" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="nama_merek">
                                    Nama Merek <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    v-model="form.nama_merek"
                                    id="nama_merek"
                                    placeholder="Masukkan nama merek"
                                    required
                                    :disabled="isSubmitting"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="deskripsi_merek">
                                    Deskripsi Merek <span class="text-destructive">*</span>
                                </Label>
                                <Textarea
                                    v-model="form.deskripsi_merek"
                                    id="deskripsi_merek"
                                    placeholder="Masukkan deskripsi merek"
                                    rows="4"
                                    required
                                    :disabled="isSubmitting"
                                />
                            </div>
                        </form>
                        <DialogFooter class="mt-4">
                            <Button v-if="editingId" @click="isFormDialogOpen = false; resetForm()" type="button" variant="outline" :disabled="isSubmitting">
                                <X class="w-4 h-4 mr-2" />
                                Batal
                            </Button>
                            <Button @click="submitForm" :disabled="isSubmitting">
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
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        Daftar Merek
                        <Badge variant="outline" class="px-2 py-1">
                            {{ merek.length }} item
                        </Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border relative overflow-x-auto">
                        <Table>
                            <TableHeader class="sticky top-0 bg-background z-10">
                                <TableRow>
                                    <TableHead class="w-[30%]">Nama Merek</TableHead>
                                    <TableHead class="w-[50%]">Deskripsi Merek</TableHead>
                                    <TableHead class="text-right">Aksi</TableHead>
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
                                <template v-else>
                                    <TableRow v-for="item in merek" :key="item.id"
                                        class="group hover:bg-muted/50 transition-colors">
                                        <TableCell class="font-medium">
                                            {{ item.nama_merek }}
                                        </TableCell>
                                        <TableCell
                                            class="text-muted-foreground max-w-[200px] overflow-hidden text-ellipsis whitespace-nowrap">
                                            {{ truncateText(item.deskripsi_merek, 100) }}
                                        </TableCell>
                                        <TableCell class="text-right space-x-2">
                                            <Button @click="editmerek(item)" variant="ghost" size="sm" title="Edit"
                                                class="h-8 px-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                            <Button @click="deletemerek(item.id)" variant="ghost" size="sm"
                                                title="Hapus"
                                                class="h-8 px-2 text-destructive hover:text-destructive opacity-0 group-hover:opacity-100 transition-opacity">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="merek.length === 0">
                                        <TableCell colspan="3" class="text-center text-muted-foreground py-8">
                                            Tidak ada data merek
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