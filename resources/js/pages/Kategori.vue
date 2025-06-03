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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kategori', href: '/kategori' }
];

// State
const kategori = ref<any[]>([]);
const form = ref({
    nama_kategori: '',
});
const editingId = ref<number | null>(null);
const isLoading = ref(false);

// Fetch data
const fetchkategori = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get('/api/kategori');
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
        if (editingId.value) {
            await axios.put(`/api/kategori/${editingId.value}`, form.value);
            Swal.fire('Berhasil!', 'Kategori berhasil diperbarui', 'success');
        } else {
            await axios.post('/api/kategori', form.value);
            Swal.fire('Berhasil!', 'Kategori berhasil ditambahkan', 'success');
        }
        resetForm();
        await fetchkategori();
    } catch (error) {
        console.error('Error saving category:', error);
        Swal.fire('Error', 'Gagal menyimpan kategori', 'error');
    } finally {
        isLoading.value = false;
    }
};

// Edit category
const editkategori = (item: any) => {
    form.value = { nama_kategori: item.nama_kategori };
    editingId.value = item.id;
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
            await axios.delete(`/api/kategori/${id}`);
            await fetchkategori();
            Swal.fire('Berhasil!', 'Kategori telah dihapus', 'success');
        } catch (error) {
            console.error('Error deleting category:', error);
            Swal.fire('Error', 'Gagal menghapus kategori karena terkait pada produk', 'error');
        } finally {
            isLoading.value = false;
        }
    }
};

// Reset form
const resetForm = () => {
    form.value = { nama_kategori: '' };
    editingId.value = null;
};

onMounted(fetchkategori);
</script>

<template>
    <Head title="Kategori" />
    <AppLayout :breadcrumbs="breadcrumbs">
    <div class="w-full mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Form Card -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <PlusCircle class="w-5 h-5" />
                        {{ editingId ? 'Edit Kategori' : 'Tambah Kategori Baru' }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="nama_kategori">
                                Nama Kategori <span class="text-destructive">*</span>
                            </Label>
                            <Input 
                                v-model="form.nama_kategori" 
                                id="nama_kategori" 
                                placeholder="Masukkan nama kategori" 
                                required 
                            />
                        </div>
                        <div class="flex gap-3 pt-2">
                            <Button type="submit" :disabled="isLoading">
                                <Check class="w-4 h-4 mr-2" />
                                {{ editingId ? 'Update' : 'Simpan' }}
                                <span v-if="isLoading" class="ml-2">
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
                        Daftar Kategori
                        <Badge variant="outline" class="px-2 py-1">
                            {{ kategori.length }} item
                        </Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-[80%]">Nama Kategori</TableHead>
                                    <TableHead class="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow 
                                    v-for="item in kategori" 
                                    :key="item.id"
                                    class="group hover:bg-muted/50 transition-colors"
                                >
                                    <TableCell class="font-medium">
                                        {{ item.nama_kategori }}
                                    </TableCell>
                                    <TableCell class="text-right space-x-2">
                                        <Button 
                                            @click="editkategori(item)" 
                                            variant="ghost" 
                                            size="sm"
                                            class="h-8 px-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                        <Button 
                                            @click="deletekategori(item.id)" 
                                            variant="ghost" 
                                            size="sm"
                                            class="h-8 px-2 text-destructive hover:text-destructive opacity-0 group-hover:opacity-100 transition-opacity"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="kategori.length === 0 && !isLoading">
                                    <TableCell colspan="2" class="text-center text-muted-foreground py-8">
                                        Tidak ada data kategori
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="isLoading">
                                    <TableCell colspan="2" class="text-center text-muted-foreground py-8">
                                        Memuat data...
                                    </TableCell>
                                </TableRow>
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