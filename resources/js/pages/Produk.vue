<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { Check, Image as ImageIcon, Pencil, PlusCircle, Search, Trash2, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, ref } from 'vue';

// Shadcn UI Components
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Produk', href: '/produk' }];

// State
const produk = ref<any[]>([]);
const kategoris = ref<{ id: number, nama_kategori: string }[]>([]);
const mereks = ref<{ id: number, nama_merek: string }[]>([]);
const editingId = ref<number | null>(null);
const searchTerm = ref('');
const isLoading = ref(false);
const isSubmitting = ref(false);
const isDragging = ref(false);

// Form state
const form = ref({
  nama_produk: '',
  id_kategori: null as number | null,
  id_merek: null as number | null,
  harga_modal: 0,
  harga_jual: 0,
  jumlah_stok: 0,
  gambar_produk: null as File | string | null,
  previewImage: ''
});

// Fetch all data
const fetchproduk = async () => {
  try {
    isLoading.value = true;
    const [produkRes, kategoriRes, merekRes] = await Promise.all([
      axios.get('/api/produk'),
      axios.get('/api/kategori'),
      axios.get('/api/merek')
    ]);

    produk.value = produkRes.data.data;
    kategoris.value = kategoriRes.data.data;
    mereks.value = merekRes.data.data;
  } catch (error) {
    console.error('Error fetching data:', error);
    showError('Gagal memuat data');
  } finally {
    isLoading.value = false;
  }
};

// Drag and drop handlers
const handleDragOver = (e: DragEvent) => {
  e.preventDefault();
  isDragging.value = true;
};

const handleDragLeave = (e: DragEvent) => {
  e.preventDefault();
  isDragging.value = false;
};

const handleDrop = (e: DragEvent) => {
  e.preventDefault();
  isDragging.value = false;

  const files = e.dataTransfer?.files;
  if (files && files[0]) {
    handleFileUpload({ target: { files } } as unknown as Event);
  }
};

// File upload handler
const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file && file.type.startsWith('image/')) {
    form.value.gambar_produk = file;

    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
      form.value.previewImage = e.target?.result as string;
    };
    reader.readAsDataURL(file);
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

// Submit form
const submitForm = async () => {
  try {
    isSubmitting.value = true;
    console.log({
      id_kategori: form.value.id_kategori,
      id_merek: form.value.id_merek
    });
    const formData = new FormData();
    formData.append('nama_produk', form.value.nama_produk);
    formData.append('id_kategori', String(form.value.id_kategori));
    formData.append('id_merek', String(form.value.id_merek));
    formData.append('harga_modal', String(form.value.harga_modal));
    formData.append('harga_jual', String(form.value.harga_jual));
    formData.append('jumlah_stok', String(form.value.jumlah_stok));

    if (form.value.gambar_produk instanceof File) {
      formData.append('gambar_produk', form.value.gambar_produk);
    }

    if (editingId.value) {
      await axios.post(`/api/produk/${editingId.value}?_method=PUT`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      showSuccess('Produk berhasil diperbarui');
    } else {
      await axios.post('/api/produk', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      showSuccess('Produk berhasil ditambahkan');
    }

    resetForm();
    await fetchproduk();
  } catch (error) {
    handleError(error);
  } finally {
    isSubmitting.value = false;
  }
};

// Edit produk
const editProduk = (item: any) => {
  form.value = {
    nama_produk: item.nama_produk,
    id_kategori: item.id_kategori,
    id_merek: item.id_merek,
    harga_modal: item.harga_modal,
    harga_jual: item.harga_jual,
    jumlah_stok: item.jumlah_stok,
    gambar_produk: item.gambar_produk || null,
    previewImage: item.gambar_produk ? `/storage/${item.gambar_produk}` : ''
  };
  editingId.value = item.id;
};

// Delete produk
const deleteProduk = async (id: number) => {
  const result = await Swal.fire({
    title: 'Yakin menghapus produk?',
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
      await axios.delete(`/api/produk/${id}`);
      await fetchproduk();
      showSuccess('Produk berhasil dihapus');
    } catch (error) {
      handleError(error);
    }
  }
};

// Reset form
const resetForm = () => {
  form.value = {
    nama_produk: '',
    id_kategori: null,
    id_merek: null,
    harga_modal: 0,
    harga_jual: 0,
    jumlah_stok: 0,
    gambar_produk: null,
    previewImage: ''
  };
  editingId.value = null;
};

// Handle error
const handleError = (error: any) => {
  console.error('Error:', error);
  let errorMessage = 'Gagal menyimpan produk';

  if (error.response?.data?.errors) {
    errorMessage = Object.entries(error.response.data.errors)
      .map(([field, messages]) => {
        const fieldName = field.replace(/_/g, ' ');
        return `<b>${fieldName}</b>: ${(messages as string[]).join(', ')}`;
      })
      .join('<br>');
  }

  showError(errorMessage);
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
    html: message,
    confirmButtonColor: '#3b82f6',
  });
};

onMounted(fetchproduk);
</script>

<template>

  <Head title="Produk" />
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
        <CardContent c>
          <div class="relative max-w-md">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Search class="h-5 w-5 text-muted-foreground" />
            </div>
            <Input v-model.trim="searchTerm" type="search" placeholder="Cari produk..." class="pl-10" />
          </div>
        </CardContent>
      </Card>

      <!-- Form Section -->
      <Card class="mb-4">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <PlusCircle class="w-5 h-5" />
            {{ editingId ? 'Edit Produk' : 'Tambah Produk Baru' }}
          </CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-4">
              <!-- Nama Produk -->
              <div class="space-y-2">
                <Label for="nama_produk">
                  Nama Produk <span class="text-destructive">*</span>
                </Label>
                <Input v-model.trim="form.nama_produk" id="nama_produk" placeholder="Nama produk" required
                  :disabled="isSubmitting" />
              </div>

              <!-- Kategori dan Merek -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label for="id_kategori">
                    Kategori <span class="text-destructive">*</span>
                  </Label>
                  <Select v-model="form.id_kategori" required :disabled="isSubmitting">
                    <SelectTrigger>
                      <SelectValue placeholder="Pilih Kategori" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="kategori in kategoris" :key="kategori.id" :value="kategori.id">
                        {{ kategori.nama_kategori }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div class="space-y-2">
                  <Label for="id_merek">
                    Merek <span class="text-destructive">*</span>
                  </Label>
                  <Select v-model="form.id_merek" required :disabled="isSubmitting">
                    <SelectTrigger>
                      <SelectValue placeholder="Pilih Merek" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="merek in mereks" :key="merek.id" :value="merek.id">
                        {{ merek.nama_merek }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <!-- Harga Inputs -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label for="harga_modal">
                    Harga Modal <span class="text-destructive">*</span>
                  </Label>
                  <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">Rp</span>
                    <Input v-model.number="form.harga_modal" id="harga_modal" type="number" min="0" step="1000" required
                      class="pl-10" :disabled="isSubmitting" />
                  </div>
                </div>

                <div class="space-y-2">
                  <Label for="harga_jual">
                    Harga Jual <span class="text-destructive">*</span>
                  </Label>
                  <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">Rp</span>
                    <Input v-model.number="form.harga_jual" id="harga_jual" type="number" min="0" step="1000" required
                      class="pl-10" :disabled="isSubmitting" />
                  </div>
                </div>
              </div>

              <!-- Stok -->
              <div class="space-y-2">
                <Label for="jumlah_stok">
                  Jumlah Stok <span class="text-destructive">*</span>
                </Label>
                <Input v-model.number="form.jumlah_stok" id="jumlah_stok" type="number" min="0" required
                  :disabled="isSubmitting" />
              </div>
            </div>

            <!-- Right Column - Image Upload -->
            <div class="space-y-4">
              <div class="space-y-2">
                <Label>Gambar Produk</Label>
                <div @dragover="handleDragOver" @dragleave="handleDragLeave" @drop="handleDrop"
                  :class="{ 'border-primary bg-primary/5': isDragging }"
                  class="border-2 border-dashed rounded-lg p-4 transition-colors cursor-pointer">
                  <label class="flex flex-col items-center justify-center space-y-2">
                    <div class="relative w-full">
                      <!-- Image Preview -->
                      <div v-if="form.previewImage" class="flex justify-center mb-4">
                        <img :src="form.previewImage" alt="Preview Gambar Produk"
                          class="h-48 w-48 rounded-lg object-cover border">
                      </div>
                      <div v-else class="flex flex-col items-center justify-center py-8">
                        <ImageIcon class="w-12 h-12 text-muted-foreground mb-2" />
                        <p class="text-sm text-muted-foreground text-center">
                          <span class="font-medium text-primary">Klik untuk upload</span> atau drag and drop
                        </p>
                        <p class="text-xs text-muted-foreground mt-1">
                          PNG, JPG (Maks. 2MB)
                        </p>
                      </div>
                    </div>
                    <input type="file" @change="handleFileUpload" accept="image/*" class="hidden" id="file-upload">
                  </label>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="md:col-span-2 flex justify-end gap-3 pt-4">
              <Button v-if="editingId" @click="resetForm" type="button" variant="outline" :disabled="isSubmitting">
                <X class="w-4 h-4 mr-2" />
                Batal
              </Button>
              <Button type="submit" :disabled="isSubmitting">
                <Check class="w-4 h-4 mr-2" />
                {{ editingId ? 'Update Produk' : 'Tambah Produk' }}
                <span v-if="isSubmitting" class="ml-2">
                  <span class="loading-dots">
                    <span>.</span><span>.</span><span>.</span>
                  </span>
                </span>
              </Button>
            </div>
          </form>
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
                  <TableHead class="text-right">Harga Modal</TableHead>
                  <TableHead class="text-right">Harga Jual</TableHead>
                  <TableHead class="text-right">Stok</TableHead>
                  <TableHead class="text-right">Aksi</TableHead>
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
                    <TableCell>
                      <Skeleton class="h-4 w-[40px] ml-auto" />
                    </TableCell>
                    <TableCell class="flex justify-end gap-2">
                      <Skeleton class="h-8 w-8" />
                      <Skeleton class="h-8 w-8" />
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
                    <TableCell class="text-right text-destructive">
                      {{ formatCurrency(item.harga_modal) }}
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
                    <TableCell class="text-right space-x-2">
                      <Button @click="editProduk(item)" variant="ghost" size="sm"
                        class="h-8 px-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <Pencil class="h-4 w-4" />
                      </Button>
                      <Button @click="deleteProduk(item.id)" variant="ghost" size="sm"
                        class="h-8 px-2 text-destructive hover:text-destructive opacity-0 group-hover:opacity-100 transition-opacity">
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </TableCell>
                  </TableRow>
                  <TableRow v-if="filteredProduk.length === 0 && !isLoading">
                    <TableCell colspan="8" class="text-center text-muted-foreground py-8">
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