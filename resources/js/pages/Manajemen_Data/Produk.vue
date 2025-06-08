<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios, { all } from 'axios';
import { Check, Image as ImageIcon, Pencil, PlusCircle, Search, Trash2, X, FileText } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, ref } from 'vue';

// Shadcn UI Components
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea/';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Produk', href: '/produk' }];

// State
const produk = ref<any[]>([]);
const kategoris = ref<{ id: number, nama_kategori: string }[]>([]);
const mereks = ref<{ id: number, nama_merek: string }[]>([]);
const editingId = ref<number | null>(null);
const searchTerm = ref('');
const minStok = ref<number | null>(null); // State baru untuk filter min stok
const maxStok = ref<number | null>(null); // State baru untuk filter max stok
const isLoading = ref(false);
const isSubmitting = ref(false);
const isDragging = ref(false);
const isDialogOpen = ref(false);

// Form state
const form = ref({
  nama_produk: '',
  id_kategori: null as number | null,
  id_merek: null as number | null,
  harga_modal: 0,
  harga_jual: 0,
  jumlah_stok: 0,
  gambar_produk: null as File | string | null,
  previewImage: '',
  deskripsi_produk: '',
  barcode: '',
});

const fetchproduk = async () => {
  try {
    isLoading.value = true;
    const [produkRes, kategoriRes, merekRes] = await Promise.all([
      axios.get('api/produk'),
      axios.get('api/kategori'),
      axios.get('api/merek')
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

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file && file.type.startsWith('image/')) {
    form.value.gambar_produk = file;

    const reader = new FileReader();
    reader.onload = (e) => {
      form.value.previewImage = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
};

// Filter produk berdasarkan searchTerm dan min/max stok
const displayedProduk = computed(() => {
  const lowerCaseSearchTerm = searchTerm.value.toLowerCase();

  return produk.value.filter(item => {
    const matchesSearch =
      item.nama_produk.toLowerCase().includes(lowerCaseSearchTerm) ||
      (item.barcode && item.barcode.toLowerCase().includes(lowerCaseSearchTerm)) ||
      (item.deskripsi_produk && item.deskripsi_produk.toLowerCase().includes(lowerCaseSearchTerm));

    const matchesMinStok = minStok.value === null || item.jumlah_stok >= minStok.value;
    const matchesMaxStok = maxStok.value === null || item.jumlah_stok <= maxStok.value;

    return matchesSearch && matchesMinStok && matchesMaxStok;
  }).slice();
});

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value);
};

const submitForm = async () => {
  try {
    isSubmitting.value = true;
    const formData = new FormData();
    formData.append('nama_produk', form.value.nama_produk);
    formData.append('id_kategori', String(form.value.id_kategori));
    formData.append('id_merek', String(form.value.id_merek));
    formData.append('harga_modal', String(form.value.harga_modal));
    formData.append('harga_jual', String(form.value.harga_jual));
    formData.append('jumlah_stok', String(form.value.jumlah_stok));
    formData.append('deskripsi_produk', String(form.value.deskripsi_produk));
    formData.append('barcode', form.value.barcode);

    if (form.value.gambar_produk instanceof File) {
      formData.append('gambar_produk', form.value.gambar_produk);
    } else if (typeof form.value.gambar_produk === 'string' && form.value.gambar_produk !== '') {
      // Jika berupa string (path gambar lama), tidak perlu di-append ke formData
    }


    if (editingId.value) {
      formData.append('_method', 'PUT');
      await axios.post(`api/produk/${editingId.value}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      showSuccess('Produk berhasil diperbarui');
    } else {
      await axios.post('api/produk', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      showSuccess('Produk berhasil ditambahkan');
    }

    resetForm();
    await fetchproduk();
    isDialogOpen.value = false;
  } catch (error) {
    handleError(error);
  } finally {
    isSubmitting.value = false;
  }
};

const editProduk = (item: any) => {
  form.value = {
    nama_produk: item.nama_produk,
    id_kategori: item.id_kategori,
    id_merek: item.id_merek,
    harga_modal: item.harga_modal,
    harga_jual: item.harga_jual,
    jumlah_stok: item.jumlah_stok,
    gambar_produk: item.gambar_produk || null,
    deskripsi_produk: item.deskripsi_produk || '',
    previewImage: item.gambar_produk ? `/storage/${item.gambar_produk}` : '',
    barcode: item.barcode || '',
  };
  editingId.value = item.id;
  isDialogOpen.value = true;
};

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
      await axios.delete(`api/produk/${id}`);
      await fetchproduk();
      showSuccess('Produk berhasil dihapus');
    } catch (error) {
      handleError(error);
    }
  }
};

const resetForm = () => {
  form.value = {
    nama_produk: '',
    id_kategori: null,
    id_merek: null,
    harga_modal: 0,
    harga_jual: 0,
    jumlah_stok: 0,
    gambar_produk: null,
    previewImage: '',
    deskripsi_produk: '',
    barcode: '',
  };
  editingId.value = null;
};

const handleError = (error: any) => {
  console.error('Error:', error);
  let errorMessage = 'Gagal menyimpan produk';

  if (axios.isAxiosError(error) && error.response) {
    if (error.response.status === 422) {
      errorMessage = Object.entries(error.response.data.errors)
        .map(([field, messages]) => {
          const fieldName = field.replace(/_/g, ' ');
          return `<b>${fieldName}</b>: ${(messages as string[]).join(', ')}`;
        })
        .join('<br>');
    } else if (error.response.status === 405) {
      errorMessage = 'Metode HTTP tidak diizinkan. Periksa rute API Anda untuk operasi update/create.';
    } else {
      errorMessage = `Gagal menyimpan produk: ${error.response.data.message || 'Terjadi kesalahan tidak dikenal.'}`;
    }
  } else {
    errorMessage = 'Gagal menyimpan produk: Terjadi kesalahan jaringan atau tidak terduga.';
  }

  showError(errorMessage);
};

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

const truncateText = (text: string | null | undefined, maxLength: number) => {
  if (text && text.length > maxLength) {
    return text.substring(0, maxLength) + '...';
  }
  return text || '';
};

const exportStockPdf = async () => {
  try {
    Swal.fire({
      title: 'Membuat Laporan...',
      text: 'Mohon tunggu sebentar',
      didOpen: () => {
        Swal.showLoading();
      },
      allowOutsideClick: false,
      allowEscapeKey: false
    });

    const params: Record<string, string> = {};

    if (searchTerm.value) {
      params.search = searchTerm.value;
    }
    if (minStok.value !== null) { // Kirim hanya jika ada nilai
      params.min_stok = String(minStok.value);
    }
    if (maxStok.value !== null) { // Kirim hanya jika ada nilai
      params.max_stok = String(maxStok.value);
    }

    const queryString = new URLSearchParams(params).toString();
    window.location.href = `api/reports/produk/export-stock-pdf?${queryString}`;

    Swal.close();
  } catch (error) {
    console.error('Error exporting stock PDF:', error);
    showError('Gagal membuat laporan stok produk.');
  }
};
</script>

<template>

  <Head title="Produk" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="w-full mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <Card class="mb-4">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Search class="w-5 h-5" />
            Filter Produk
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <Label for="searchTerm" class="mb-2">Cari Produk</Label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Search class="h-5 w-5 text-muted-foreground" />
                </div>
                <Input v-model.trim="searchTerm" id="searchTerm" type="search" placeholder="Nama, barcode, deskripsi..."
                  class="pl-10" />
              </div>
            </div>
            <div>
              <Label for="minStok" class="mb-2">Stok Minimal</Label>
              <Input v-model.number="minStok" id="minStok" type="number" min="0" placeholder="Min. stok" />
            </div>
            <div>
              <Label for="maxStok" class="mb-2">Stok Maksimal</Label>
              <Input v-model.number="maxStok" id="maxStok" type="number" min="0" placeholder="Max. stok" />
            </div>
          </div>
        </CardContent>
      </Card>


      <div class="mb-4 text-right flex justify-end gap-2">
        <Button @click="exportStockPdf()" variant="outline">
          <FileText class="w-4 h-4 mr-2" />
          Export Laporan Stok PDF
        </Button>

        <Dialog :open="isDialogOpen" @update:open="isDialogOpen = $event">
          <DialogTrigger as-child>
            <Button @click="resetForm(); isDialogOpen = true;">
              <PlusCircle class="w-4 h-4 mr-2" />
              Tambah Produk Baru
            </Button>
          </DialogTrigger>
          <DialogContent class="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{{ editingId ? 'Edit Produk' : 'Tambah Produk Baru' }}</DialogTitle>
              <DialogDescription>
                Lengkapi detail produk. Klik simpan saat Anda selesai.
              </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-2 gap-6 py-4">
              <div class="space-y-4">
                <div class="space-y-2">
                  <Label for="barcode">Barcode</Label>
                  <Input v-model.trim="form.barcode" id="barcode" placeholder="Scan atau masukkan barcode"
                    :disabled="isSubmitting" />
                </div>
                <div class="space-y-2">
                  <Label for="nama_produk">Nama Produk <span class="text-destructive">*</span></Label>
                  <Input v-model.trim="form.nama_produk" id="nama_produk" placeholder="Nama produk" required
                    :disabled="isSubmitting" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-2">
                    <Label for="id_kategori">Kategori <span class="text-destructive">*</span></Label>
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
                    <Label for="id_merek">Merek <span class="text-destructive">*</span></Label>
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-2">
                    <Label for="harga_modal">Harga Modal <span class="text-destructive">*</span></Label>
                    <div class="relative">
                      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">Rp</span>
                      <Input v-model.number="form.harga_modal" id="harga_modal" type="number" min="0" step="1000"
                        required class="pl-10" :disabled="isSubmitting" />
                    </div>
                  </div>
                  <div class="space-y-2">
                    <Label for="harga_jual">Harga Jual <span class="text-destructive">*</span></Label>
                    <div class="relative">
                      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">Rp</span>
                      <Input v-model.number="form.harga_jual" id="harga_jual" type="number" min="0" step="1000" required
                        class="pl-10" :disabled="isSubmitting" />
                    </div>
                  </div>
                </div>
                <div class="space-y-2">
                  <Label for="jumlah_stok">Jumlah Stok <span class="text-destructive">*</span></Label>
                  <Input v-model.number="form.jumlah_stok" id="jumlah_stok" type="number" min="0" required
                    :disabled="isSubmitting" />
                </div>
                <div class="space-y-2">
                  <Label for="deskripsi_produk">Deskripsi Produk <span class="text-destructive">*</span></Label>
                  <Textarea v-model="form.deskripsi_produk" id="deskripsi_produk"
                    placeholder="Masukkan deskripsi produk" rows="4" :disabled="isSubmitting" />
                </div>
              </div>
              <div class="space-y-4">
                <div class="space-y-2">
                  <Label>Gambar Produk</Label>
                  <div @dragover="handleDragOver" @dragleave="handleDragLeave" @drop="handleDrop"
                    :class="{ 'border-primary bg-primary/5': isDragging }"
                    class="border-2 border-dashed rounded-lg p-4 transition-colors cursor-pointer">
                    <label class="flex flex-col items-center justify-center space-y-2">
                      <div class="relative w-full">
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
                      <input type="file" @change="handleFileUpload" accept="image/*" class="hidden" id="file-upload"
                        :disabled="isSubmitting">
                    </label>
                  </div>
                </div>
              </div>
              <DialogFooter class="md:col-span-2 pt-4 flex justify-end gap-3">
                <Button type="button" variant="outline" @click="resetForm(); isDialogOpen = false;"
                  :disabled="isSubmitting">
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
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            Daftar Produk
            <Badge variant="outline" class="px-2 py-1">
              {{ displayedProduk.length }} item
            </Badge>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="rounded-md border relative overflow-x-auto" style="max-height: 70vh;">
            <Table class="min-w-full">
              <TableHeader class="sticky top-0 bg-background z-10">
                <TableRow>
                  <TableHead class="w-[100px]">Gambar</TableHead>
                  <TableHead>Barcode</TableHead>
                  <TableHead>Produk</TableHead>
                  <TableHead>Kategori</TableHead>
                  <TableHead>Merek</TableHead>
                  <TableHead class="text-right">Harga Modal</TableHead>
                  <TableHead class="text-right">Harga Jual</TableHead>
                  <TableHead class="text-right">Stok</TableHead>
                  <TableHead class="w-[200px]">Deskripsi Produk</TableHead>
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
                  <TableRow v-for="item in displayedProduk" :key="item.id"
                    class="group hover:bg-muted/50 transition-colors">
                    <TableCell>
                      <img :src="item.gambar_produk ? `../storage/${item.gambar_produk}` : '/placeholder-product.jpg'"
                        alt="Produk" class="h-12 w-12 rounded-lg object-cover border">
                    </TableCell>
                    <TableCell class="font-medium">
                      {{ item.barcode || '-' }}
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
                    <TableCell
                      class="text-muted-foreground max-w-[200px] overflow-hidden text-ellipsis whitespace-nowrap">
                      {{ truncateText(item.deskripsi_produk, 100) }}
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
                  <TableRow v-if="displayedProduk.length === 0 && !isLoading">
                    <TableCell colspan="10" class="text-center text-muted-foreground py-8">
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

/* Ensure action buttons are always visible on small screens */
@media (max-width: 640px) {
  .group-hover\:opacity-100 {
    opacity: 1 !important;
  }
}

/* Custom styling for sticky table header */
.rounded-md.border {
  border-collapse: separate;
  border-spacing: 0;
}

.rounded-md.border .sticky {
  background-color: var(--background);
  /* Ensure it matches your theme's background */
}

/* Override default table cell padding for sticky header if needed */
.sticky th {
  padding-top: 0.75rem;
  /* Equivalent to py-3 */
  padding-bottom: 0.75rem;
  /* Equivalent to py-3 */
}
</style>