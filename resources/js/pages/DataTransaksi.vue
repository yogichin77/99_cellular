<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardFooter, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { Minus, Plus, Search, Trash2, Pencil } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, ref, watch } from 'vue';
import { Skeleton } from '@/components/ui/skeleton';

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Datatransaksi',
    href: 'datatransaksi',
  },
];

// State untuk data transaksi
const transaksis = ref<any[]>([]);
const isLoading = ref(false);
const searchTerm = ref('');
const statusFilter = ref('all');
const dateRange = ref({
  start: '',
  end: ''
});

// State untuk form edit
const selectedTransaksi = ref<any>(null);
const additionalPayment = ref(0);
const semuaProduk = ref<any[]>([]);
const produkBaru = ref({
  id: '',
  jumlah: 1
});
const isEditDialogOpen = ref(false);
const isLoadingDetail = ref(false);

// Fetch data transaksi
const fetchTransaksis = async () => {
  try {
    isLoading.value = true;
    const response = await axios.get('/api/transaksi', {
      params: {
        search: searchTerm.value,
        status: statusFilter.value === 'all' ? null : statusFilter.value,
        start_date: dateRange.value.start,
        end_date: dateRange.value.end
      }
    });
    transaksis.value = response.data.data;
  } catch (error) {
    console.error('Gagal memuat transaksi:', error);
    Swal.fire('Error', 'Gagal memuat data transaksi', 'error');
  } finally {
    isLoading.value = false;
  }
};

// Fetch semua produk
const fetchSemuaProduk = async () => {
  try {
    const response = await axios.get('/api/produk');
    semuaProduk.value = response.data.data;
  } catch (error) {
    console.error('Gagal memuat produk:', error);
  }
};

// Format tanggal dan mata uang
const formatDate = (dateString: string) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString('id-ID');
};

const formatDateTime = (dateString: string) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID') + ' ' + date.toLocaleTimeString('id-ID');
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(amount || 0);
};

// Filter transaksi berdasarkan search term
const filteredTransactions = computed(() => {
  return transaksis.value.filter(transaksi => {
    const matchesSearch = searchTerm.value === '' ||
      transaksi.id.toString().includes(searchTerm.value) ||
      (transaksi.pelanggan?.nama_pelanggan && transaksi.pelanggan.nama_pelanggan.toLowerCase().includes(searchTerm.value.toLowerCase())) ||
      (transaksi.pelanggan?.nama_toko && transaksi.pelanggan.nama_toko.toLowerCase().includes(searchTerm.value.toLowerCase()));

    const matchesStatus = statusFilter.value === 'all' ||
      transaksi.status_pembayaran === statusFilter.value;

    const matchesDate = (!dateRange.value.start && !dateRange.value.end) ||
      (new Date(transaksi.created_at) >= new Date(dateRange.value.start)) &&
      (new Date(transaksi.created_at) <= new Date(dateRange.value.end))

    return matchesSearch && matchesStatus && matchesDate;
  });
});

// Hitung sub total berdasarkan item di frontend
const computedSubTotalHarga = computed(() => {
  if (!selectedTransaksi.value || !selectedTransaksi.value.detailtransaksis) return 0;
  return selectedTransaksi.value.detailtransaksis.reduce((total: number, item: any) => {
    const hargaSatuan = parseFloat(item.harga_satuan) || 0;
    return total + (item.jumlah * hargaSatuan);
  }, 0);
});

const totalTagihan = computed(() => {
  const diskon = parseFloat(selectedTransaksi.value?.diskon || 0);
  return computedSubTotalHarga.value - diskon;
});

const sisaTagihan = computed(() => {
  if (!selectedTransaksi.value) return 0;
  const totalBayarAwal = parseFloat(selectedTransaksi.value.total_bayar) || 0;
  const totalBayarSaatIni = totalBayarAwal + additionalPayment.value;
  return Math.max(0, totalTagihan.value - totalBayarSaatIni);
});

// Validasi tambahan pembayaran
const validateAdditionalPayment = () => {
  if (additionalPayment.value < 0) {
    additionalPayment.value = 0;
  }
};

// Pilih transaksi untuk diedit
const openEditDialog = async (transaksi: any) => {
  try {
    isLoadingDetail.value = true;
    isEditDialogOpen.value = true;
    
    // Fetch detail lengkap transaksi
    const response = await axios.get(`/api/transaksi/${transaksi.id}`);
    selectedTransaksi.value = response.data.data;
    additionalPayment.value = 0;

    // Pastikan nilai-nilai numerik diinisialisasi dengan angka yang benar
    selectedTransaksi.value.sub_total_bayar = parseFloat(selectedTransaksi.value.sub_total_bayar) || 0;
    selectedTransaksi.value.total_bayar = parseFloat(selectedTransaksi.value.total_bayar) || 0;
    selectedTransaksi.value.diskon = parseFloat(selectedTransaksi.value.diskon) || 0;

    // Pastikan setiap detail transaksi memiliki harga_satuan sebagai float
    if (selectedTransaksi.value.detailtransaksis) {
      selectedTransaksi.value.detailtransaksis.forEach((item: any) => {
        item.harga_satuan = parseFloat(item.harga_satuan) || 0;
        item.total_harga = parseFloat(item.total_harga) || 0;
      });
    }

  } catch (error) {
    console.error('Gagal memuat detail transaksi:', error);
    Swal.fire('Error', 'Gagal memuat detail transaksi', 'error');
  } finally {
    isLoadingDetail.value = false;
  }
};

// Tutup form edit
const closeEditDialog = () => {
  isEditDialogOpen.value = false;
  selectedTransaksi.value = null;
  additionalPayment.value = 0;
};

// Tambah produk baru ke transaksi
const tambahProdukBaru = () => {
  if (!produkBaru.value.id || produkBaru.value.jumlah < 1) {
    Swal.fire('Peringatan', 'Pilih produk dan jumlah yang valid', 'warning');
    return;
  }

  const produk = semuaProduk.value.find(p => p.id == produkBaru.value.id);
  if (!produk) return;

  // Cek apakah produk sudah ada di transaksi
  const existingItemIndex = selectedTransaksi.value.detailtransaksis.findIndex(
    (item: any) => item.id_produk == produkBaru.value.id
  );

  if (existingItemIndex >= 0) {
    // Jika sudah ada, tambahkan jumlahnya
    selectedTransaksi.value.detailtransaksis[existingItemIndex].jumlah += produkBaru.value.jumlah;
    selectedTransaksi.value.detailtransaksis[existingItemIndex].total_harga =
      selectedTransaksi.value.detailtransaksis[existingItemIndex].jumlah *
      selectedTransaksi.value.detailtransaksis[existingItemIndex].harga_satuan;
  } else {
    // Jika belum ada, tambahkan item baru
    selectedTransaksi.value.detailtransaksis.push({
      id_produk: produk.id,
      produk: produk,
      jumlah: produkBaru.value.jumlah,
      harga_satuan: parseFloat(produk.harga_jual),
      total_harga: produkBaru.value.jumlah * parseFloat(produk.harga_jual),
    });
  }

  // Reset form produk baru
  produkBaru.value = {
    id: '',
    jumlah: 1
  };
};

// Update harga produk ketika jumlah diubah
const updateHargaProduk = (index: number) => {
  const item = selectedTransaksi.value.detailtransaksis[index];
  if (item.jumlah < 1) item.jumlah = 1;
  item.total_harga = item.jumlah * parseFloat(item.harga_satuan || 0);
};

// Tambah jumlah produk
const tambahJumlah = (index: number) => {
  selectedTransaksi.value.detailtransaksis[index].jumlah++;
  updateHargaProduk(index);
};

// Kurangi jumlah produk
const kurangiJumlah = (index: number) => {
  if (selectedTransaksi.value.detailtransaksis[index].jumlah > 1) {
    selectedTransaksi.value.detailtransaksis[index].jumlah--;
    updateHargaProduk(index);
  }
};

// Hapus produk dari transaksi
const hapusProduk = (index: number) => {
  selectedTransaksi.value.detailtransaksis.splice(index, 1);
};

// Update transaksi
const updateTransaction = async () => {
  try {
    // Validasi data
    if (!selectedTransaksi.value) return;

    if (selectedTransaksi.value.status_pembayaran === 'kredit' && !selectedTransaksi.value.jatuh_tempo) {
      Swal.fire('Peringatan', 'Harap tentukan jatuh tempo untuk transaksi kredit', 'warning');
      return;
    }

    // Siapkan data untuk dikirim
    const payload = {
      sub_total_bayar: computedSubTotalHarga.value,
      total_bayar: parseFloat(selectedTransaksi.value.total_bayar || 0) + additionalPayment.value,
      status_pembayaran: selectedTransaksi.value.status_pembayaran,
      jatuh_tempo: selectedTransaksi.value.status_pembayaran === 'kredit'
        ? selectedTransaksi.value.jatuh_tempo
        : null,
      diskon: parseFloat(selectedTransaksi.value.diskon || 0),
      id_pelanggan: selectedTransaksi.value.id_pelanggan,
      id_user: selectedTransaksi.value.id_user,
      items: selectedTransaksi.value.detailtransaksis.map((item: any) => ({
        id_detail_transaksi: item.id || null,
        id_produk: item.id_produk,
        jumlah: item.jumlah,
        harga_satuan: parseFloat(item.harga_satuan || 0)
      }))
    };

    // Kirim request update
    await axios.put(`/api/transaksi/${selectedTransaksi.value.id}`, payload);

    // Tampilkan notifikasi sukses
    Swal.fire('Sukses', 'Transaksi berhasil diperbarui', 'success');

    // Refresh data transaksi
    fetchTransaksis();

    // Tutup dialog
    closeEditDialog();
  } catch (error: any) {
    console.error('Gagal memperbarui transaksi:', error);
    let errorMessage = 'Gagal memperbarui transaksi';
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
      if (error.response.data.error) {
        errorMessage += ": " + error.response.data.error;
      }
    }
    Swal.fire('Error', errorMessage, 'error');
  }
};

// Hapus transaksi
const deleteTransaction = async (id: number) => {
  try {
    const result = await Swal.fire({
      title: 'Apakah Anda yakin?',
      text: "Transaksi yang dihapus tidak dapat dikembalikan!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Ya, hapus!',
      cancelButtonText: 'Batal'
    });

    if (result.isConfirmed) {
      await axios.delete(`/api/transaksi/${id}`);
      Swal.fire('Dihapus!', 'Transaksi berhasil dihapus.', 'success');
      fetchTransaksis();
    }
  } catch (error) {
    console.error('Gagal menghapus transaksi:', error);
    Swal.fire('Error', 'Gagal menghapus transaksi', 'error');
  }
};

// Watch perubahan filter
watch([searchTerm, statusFilter, dateRange], () => {
  fetchTransaksis();
}, { deep: true });

// Lifecycle hooks
onMounted(() => {
  fetchTransaksis();
  fetchSemuaProduk();
});
</script>

<template>

  <Head title="Data Transaksi" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="w-full mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <h2 class="text-2xl font-bold tracking-tight">Data Transaksi</h2>

      <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div class="relative w-full md:w-1/3">
          <Input type="text" placeholder="Cari transaksi (ID, pelanggan, toko)..." v-model="searchTerm" class="pl-10" />
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
        </div>
        <div class="flex flex-col md:flex-row gap-4 w-full md:w-2/3 items-center">
          <Select v-model="statusFilter">
            <SelectTrigger class="w-full md:w-[180px]">
              <SelectValue placeholder="Filter Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Semua Status</SelectItem>
              <SelectItem value="cash">Cash</SelectItem>
              <SelectItem value="kredit">Kredit</SelectItem>
            </SelectContent>
          </Select>
          <div class="flex gap-2 w-full">
            <Input type="date" v-model="dateRange.start" class="w-1/2" />
            <Input type="date" v-model="dateRange.end" class="w-1/2" />
          </div>
          <Button @click="fetchTransaksis" class="w-full md:w-auto">Refresh</Button>
        </div>
      </div>

      <Card class="mt-6">
        <CardHeader>
          <CardTitle>Daftar Transaksi</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="isLoading" class="text-center py-8">Memuat data...</div>
          <div v-else-if="filteredTransactions.length === 0" class="text-center py-8 text-gray-500">
            Tidak ada transaksi yang ditemukan.
          </div>
          <div v-else class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>ID</TableHead>
                  <TableHead>Tanggal</TableHead>
                  <TableHead>Pelanggan</TableHead>
                  <TableHead>Kasir</TableHead>
                  <TableHead>Sub Total</TableHead>
                  <TableHead>Diskon</TableHead>
                  <TableHead>Total Bayar</TableHead>
                  <TableHead>Sisa Tagihan</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Jatuh Tempo</TableHead>
                  <TableHead>Aksi</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="transaksi in filteredTransactions" :key="transaksi.id">
                  <TableCell>{{ transaksi.id }}</TableCell>
                  <TableCell>{{ formatDateTime(transaksi.created_at) }}</TableCell>
                  <TableCell>
                    {{ transaksi.pelanggan?.nama_pelanggan || '-' }} 
                    <br />
                    <span class="text-xs text-gray-500">
                      ({{ transaksi.pelanggan?.nama_toko || '-' }})
                    </span>
                  </TableCell>
                  <TableCell>{{ transaksi.user?.name || '-' }}</TableCell>
                  <TableCell>{{ formatCurrency(transaksi.sub_total_bayar) }}</TableCell>
                  <TableCell>{{ formatCurrency(transaksi.diskon) }}</TableCell>
                  <TableCell>{{ formatCurrency(transaksi.total_bayar) }}</TableCell>
                  <TableCell>{{ formatCurrency(transaksi.total_kurang) }}</TableCell>
                  <TableCell>
                    <Badge :variant="transaksi.status_pembayaran === 'cash' ? 'default' : 'destructive'">
                      {{ transaksi.status_pembayaran === 'cash' ? 'Cash' : 'Kredit' }}
                    </Badge>
                  </TableCell>
                  <TableCell>{{ formatDate(transaksi.jatuh_tempo) }}</TableCell>
                  <TableCell class="text-right space-x-2">
                    <Button @click="openEditDialog(transaksi)" variant="ghost" size="sm" class="h-8 px-2">
                      <Pencil class="h-4 w-4" />
                    </Button>
                    <Button @click="deleteTransaction(transaksi.id)" variant="ghost" size="sm"
                      class="h-8 px-2 text-destructive hover:text-destructive">
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      <!-- Dialog Edit Transaksi -->
      <Dialog v-model:open="isEditDialogOpen">
        <DialogContent class="sm:max-w-4xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>
              Edit Transaksi #{{ selectedTransaksi?.id }}
            </DialogTitle>
            <DialogDescription>
              Ubah detail transaksi, produk, pembayaran, dan status.
            </DialogDescription>
          </DialogHeader>

          <!-- Loading State -->
          <div v-if="isLoadingDetail" class="flex justify-center items-center h-64">
            <div class="text-center space-y-4">
              <div class="loading-dots flex justify-center">
                <span class="dot w-2 h-2 bg-primary rounded-full mx-1 animate-bounce"></span>
                <span class="dot w-2 h-2 bg-primary rounded-full mx-1 animate-bounce" style="animation-delay: 0.2s"></span>
                <span class="dot w-2 h-2 bg-primary rounded-full mx-1 animate-bounce" style="animation-delay: 0.4s"></span>
              </div>
              <p class="text-gray-500">Memuat detail transaksi...</p>
            </div>
          </div>

          <!-- Form Edit -->
          <div v-else-if="selectedTransaksi" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-4">
              <h3 class="text-lg font-semibold">Detail Utama</h3>

              <div>
                <Label for="pelanggan">Pelanggan</Label>
                <p class="text-sm text-gray-700">
                  {{ selectedTransaksi.pelanggan?.nama_pelanggan || '-' }}
                  <span v-if="selectedTransaksi.pelanggan?.nama_toko">
                    ({{ selectedTransaksi.pelanggan.nama_toko }})
                  </span>
                </p>
              </div>
              
              <div>
                <Label for="user">Kasir</Label>
                <p class="text-sm text-gray-700">
                  {{ selectedTransaksi.user?.name || '-' }}
                </p>
              </div>

              <div>
                <Label>Diskon (Rp)</Label>
                <Input 
                  type="number" 
                  v-model.number="selectedTransaksi.diskon" 
                  min="0" 
                  class="w-full"
                />
              </div>
              
              <div>
                <Label>Tambahan Pembayaran (Rp)</Label>
                <Input 
                  type="number" 
                  v-model.number="additionalPayment" 
                  @input="validateAdditionalPayment" 
                  class="w-full"
                />
              </div>

              <div>
                <Label>Status Pembayaran</Label>
                <Select v-model="selectedTransaksi.status_pembayaran">
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="Pilih Status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="cash">Cash</SelectItem>
                    <SelectItem value="kredit">Kredit</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              
              <div v-if="selectedTransaksi.status_pembayaran === 'kredit'">
                <Label>Jatuh Tempo</Label>
                <Input 
                  type="date" 
                  v-model="selectedTransaksi.jatuh_tempo"
                  :min="new Date().toISOString().split('T')[0]" 
                  class="w-full"
                />
              </div>
            </div>

            <div class="space-y-4">
              <h3 class="text-lg font-semibold">Detail Produk</h3>
              
              <div v-if="selectedTransaksi.detailtransaksis && selectedTransaksi.detailtransaksis.length > 0">
                <div class="border rounded-md">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead class="w-[40%]">Produk</TableHead>
                        <TableHead>Jumlah</TableHead>
                        <TableHead>Harga</TableHead>
                        <TableHead class="text-right">Total</TableHead>
                        <TableHead>Aksi</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      <TableRow 
                        v-for="(item, index) in selectedTransaksi.detailtransaksis"
                        :key="item.id || item.id_produj"
                      >
                        <TableCell>
                          {{ item.produk?.nama_produk || 'Produk Tidak Ditemukan' }}
                        </TableCell>
                        <TableCell>
                          <div class="flex items-center gap-1">
                            <Button 
                              size="icon" 
                              variant="outline" 
                              @click="kurangiJumlah(index)"
                              class="h-8 w-8"
                            >
                              <Minus class="w-4 h-4" />
                            </Button>
                            <Input 
                              type="number" 
                              v-model.number="item.jumlah" 
                              min="1" 
                              class="w-16 text-center"
                              @change="updateHargaProduk(index)"
                            />
                            <Button 
                              size="icon" 
                              variant="outline" 
                              @click="tambahJumlah(index)"
                              class="h-8 w-8"
                            >
                              <Plus class="w-4 h-4" />
                            </Button>
                          </div>
                        </TableCell>
                        <TableCell>
                          {{ formatCurrency(item.harga_satuan) }}
                        </TableCell>
                        <TableCell class="text-right">
                          {{ formatCurrency(item.total_harga) }}
                        </TableCell>
                        <TableCell>
                          <Button 
                            variant="ghost" 
                            size="sm" 
                            @click="hapusProduk(index)"
                            class="text-destructive hover:text-destructive"
                          >
                            <Trash2 class="w-4 h-4" />
                          </Button>
                        </TableCell>
                      </TableRow>
                    </TableBody>
                  </Table>
                </div>
              </div>
              
              <div v-else class="text-gray-500 py-4 text-center">
                Tidak ada produk dalam transaksi ini.
              </div>

              <div class="mt-4">
                <h4 class="text-md font-medium mb-2">Tambah Produk Baru</h4>
                <div class="flex flex-col sm:flex-row gap-2">
                  <div class="flex-1">
                    <Label>Produk</Label>
                    <Select v-model="produkBaru.id">
                      <SelectTrigger>
                        <SelectValue placeholder="Pilih Produk" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem 
                          v-for="produk in semuaProduk" 
                          :key="produk.id" 
                          :value="produk.id"
                        >
                          {{ produk.nama_produk }} (Stok: {{ produk.jumlah_stok }})
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div>
                    <Label>Jumlah</Label>
                    <Input 
                      type="number" 
                      v-model.number="produkBaru.jumlah" 
                      min="1" 
                      class="w-24"
                    />
                  </div>
                  <div class="flex items-end">
                    <Button @click="tambahProdukBaru" class="w-full sm:w-auto">
                      Tambah
                    </Button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Summary Pembayaran -->
          <DialogFooter v-if="selectedTransaksi" class="pt-4 border-t">
            <div class="w-full space-y-2">
              <div class="flex justify-between">
                <span>Sub Total:</span>
                <span class="font-medium">{{ formatCurrency(computedSubTotalHarga) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Diskon:</span>
                <span class="font-medium text-destructive">
                  -{{ formatCurrency(selectedTransaksi.diskon || 0) }}
                </span>
              </div>
              <div class="flex justify-between pt-2 border-t">
                <span class="font-bold">Total Tagihan:</span>
                <span class="font-bold">{{ formatCurrency(totalTagihan) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Sudah Dibayar:</span>
                <span class="font-medium">
                  {{ formatCurrency(parseFloat(selectedTransaksi.total_bayar || 0) + additionalPayment) }}
                </span>
              </div>
              <div class="flex justify-between pt-2 border-t">
                <span class="font-bold">Sisa Tagihan:</span>
                <span class="font-bold text-destructive">
                  {{ formatCurrency(sisaTagihan) }}
                </span>
              </div>
              
              <div class="flex justify-end gap-2 pt-4">
                <Button variant="outline" @click="closeEditDialog">
                  Batal
                </Button>
                <Button @click="updateTransaction">
                  Simpan Perubahan
                </Button>
              </div>
            </div>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>

<style scoped>
.loading-dots {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.25rem;
}

.dot {
  width: 0.5rem;
  height: 0.5rem;
  background-color: #3b82f6;
  border-radius: 50%;
  animation: bounce 1.5s infinite;
}

.dot:nth-child(2) {
  animation-delay: 0.2s;
}

.dot:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes bounce {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-0.5rem);
  }
}

@media (max-width: 640px) {
  .edit-form-grid {
    grid-template-columns: 1fr;
  }
  
  .dialog-content {
    max-height: 85vh;
  }
}
</style>