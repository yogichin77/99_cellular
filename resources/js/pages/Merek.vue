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
import { Skeleton } from '@/components/ui/skeleton';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Merek', href: '/merek' }
];

// State
const merek = ref<any[]>([]);
const form = ref({
    nama_merek: '',
});
const editingId = ref<number | null>(null);
const isLoading = ref(false);
const isSubmitting = ref(false);

// Fetch data
const fetchmerek = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get('/api/merek');
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
            await axios.put(`/api/merek/${editingId.value}`, form.value);
            Swal.fire({
                title: 'Berhasil!',
                text: 'Merek berhasil diperbarui',
                icon: 'success',
                confirmButtonColor: '#3b82f6',
            });
        } else {
            await axios.post('/api/merek', form.value);
            Swal.fire({
                title: 'Berhasil!',
                text: 'Merek berhasil ditambahkan',
                icon: 'success',
                confirmButtonColor: '#3b82f6',
            });
        }
        resetForm();
        await fetchmerek();
    } catch (error) {
        console.error('Failed to save brand:', error);
        Swal.fire({
            title: 'Error',
            text: 'Gagal menyimpan data merek',
            icon: 'error',
            confirmButtonColor: '#3b82f6',
        });
    } finally {
        isSubmitting.value = false;
    }
};

// Edit brand
const editmerek = (item: any) => {
    form.value = { nama_merek: item.nama_merek };
    editingId.value = item.id_merek;
};

// Delete brand
const deletemerek = async (id_merek: number) => {
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
            await axios.delete(`/api/merek/${id_merek}`);
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
                text: 'Gagal menghapus merek',
                icon: 'error',
                confirmButtonColor: '#3b82f6',
            });
        }
    }
};

// Reset form
const resetForm = () => {
    form.value = { nama_merek: '' };
    editingId.value = null;
};

onMounted(fetchmerek);
</script>

<template>
    <Head title="Merek" />
    <AppLayout :breadcrumbs="breadcrumbs">
    <div class="w-full mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Form Card -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <PlusCircle class="w-5 h-5" />
                        {{ editingId ? 'Edit Merek' : 'Tambah Merek Baru' }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
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
                            <Button 
                                v-if="editingId" 
                                @click="resetForm" 
                                type="button" 
                                variant="outline"
                                :disabled="isSubmitting"
                            >
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
                        Daftar Merek
                        <Badge variant="outline" class="px-2 py-1">
                            {{ merek.length }} item
                        </Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-[80%]">Nama Merek</TableHead>
                                    <TableHead class="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="isLoading">
                                    <TableRow v-for="i in 3" :key="i">
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
                                    <TableRow 
                                        v-for="item in merek" 
                                        :key="item.id_merek"
                                        class="group hover:bg-muted/50 transition-colors"
                                    >
                                        <TableCell class="font-medium">
                                            {{ item.nama_merek }}
                                        </TableCell>
                                        <TableCell class="text-right space-x-2">
                                            <Button 
                                                @click="editmerek(item)" 
                                                variant="ghost" 
                                                size="sm"
                                                class="h-8 px-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                            >
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                            <Button 
                                                @click="deletemerek(item.id_merek)" 
                                                variant="ghost" 
                                                size="sm"
                                                class="h-8 px-2 text-destructive hover:text-destructive opacity-0 group-hover:opacity-100 transition-opacity"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="merek.length === 0 && !isLoading">
                                        <TableCell colspan="2" class="text-center text-muted-foreground py-8">
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
    0%, 100% { opacity: 0.2; }
    50% { opacity: 1; }
}

@media (max-width: 640px) {
    .group-hover\:opacity-100 {
        opacity: 1 !important;
    }
}
</style>