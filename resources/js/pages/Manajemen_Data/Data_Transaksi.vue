<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { Minus, Pencil, Plus, Search, FileText } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, ref, watch } from 'vue';
import { Skeleton } from '@/components/ui/skeleton';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Data transaksi',
    href: 'datatransaksi',
  },
];


const transaksis = ref<any[]>([]);
const isLoading = ref(false);
const searchTerm = ref('');
const statusFilter = ref('all');
const dateRange = ref({
  start: '',
  end: ''
});

const selectedTransaksi = ref<any>(null);
const additionalPayment = ref(0);
const semuaProduk = ref<any[]>([]);
const produkBaru = ref({
  id: '',
  jumlah: 1
});
const isEditDialogOpen = ref(false);
const isLoadingDetail = ref(false);
const validationErrors = ref<any>({});


const fetchTransaksis = async () => {
  try {
    isLoading.value = true;
    const response = await axios.get('api/transaksi', {
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


const fetchSemuaProduk = async () => {
  try {
    const response = await axios.get('api/produk');
    semuaProduk.value = response.data.data;
  } catch (error) {
    console.error('Gagal memuat produk:', error);
  }
};


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

const cleanCurrencyFormat = (value: any) => {
  if (typeof value === 'string') {

    const cleaned = value.replace(/[^0-9-]/g, '');
    return parseInt(cleaned) || 0;
  }
  return parseInt(value) || 0;
};



const filteredTransactions = computed(() => {
  return transaksis.value.filter(transaksi => {
    const matchesSearch = searchTerm.value === '' ||
      transaksi.id.toString().includes(searchTerm.value) ||
      (transaksi.pelanggan?.nama_pelanggan && transaksi.pelanggan.nama_pelanggan.toLowerCase().includes(searchTerm.value.toLowerCase())) ||
      (transaksi.pelanggan?.nama_toko && transaksi.pelanggan.nama_toko.toLowerCase().includes(searchTerm.value.toLowerCase()));

    const matchesStatus = statusFilter.value === 'all' ||
      transaksi.metode_pembayaran === statusFilter.value;

    const matchesDate = (!dateRange.value.start && !dateRange.value.end) ||
      (new Date(transaksi.created_at) >= new Date(dateRange.value.start)) &&
      (new Date(transaksi.created_at) <= new Date(dateRange.value.end))

    return matchesSearch && matchesStatus && matchesDate;
  });
});

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


const validateAdditionalPayment = () => {
  if (additionalPayment.value < 0) {
    additionalPayment.value = 0;
  }
};


const openEditDialog = async (transaksi: any) => {
  try {
    isLoadingDetail.value = true;
    isEditDialogOpen.value = true;
    validationErrors.value = {};


    const response = await axios.get(`api/transaksi/${transaksi.id}`);
    selectedTransaksi.value = response.data.data;
    additionalPayment.value = 0;


    selectedTransaksi.value.sub_total = cleanCurrencyFormat(selectedTransaksi.value.sub_total);
    selectedTransaksi.value.total_bayar = cleanCurrencyFormat(selectedTransaksi.value.total_bayar);
    selectedTransaksi.value.diskon = cleanCurrencyFormat(selectedTransaksi.value.diskon);


    if (selectedTransaksi.value.detailtransaksis) {
      selectedTransaksi.value.detailtransaksis.forEach((item: any) => {
        item.harga_satuan = cleanCurrencyFormat(item.harga_satuan);
        item.total_harga = cleanCurrencyFormat(item.total_harga);
      });
    }

  } catch (error) {
    console.error('Gagal memuat detail transaksi:', error);
    Swal.fire('Error', 'Gagal memuat detail transaksi', 'error');
    isEditDialogOpen.value = false;
  } finally {
    isLoadingDetail.value = false;
  }
};


const closeEditDialog = () => {
  isEditDialogOpen.value = false;
  selectedTransaksi.value = null;
  additionalPayment.value = 0;
  validationErrors.value = {};
};

const tambahProdukBaru = () => {
  if (!produkBaru.value.id || produkBaru.value.jumlah < 1) {
    Swal.fire('Peringatan', 'Pilih produk dan jumlah yang valid', 'warning');
    return;
  }

  const produk = semuaProduk.value.find(p => p.id == produkBaru.value.id);
  if (!produk) {
    Swal.fire('Peringatan', 'Produk tidak ditemukan.', 'warning');
    return;
  }

  const existingItem = selectedTransaksi.value.detailtransaksis.find(
    (item: any) => item.id_produk == produkBaru.value.id
  );
  const currentQuantityInCart = existingItem ? existingItem.jumlah : 0;
  const requestedQuantity = produkBaru.value.jumlah;

  if (produk.jumlah_stok < (currentQuantityInCart + requestedQuantity)) {
    Swal.fire('Peringatan', `Stok ${produk.nama_produk} tidak mencukupi. Tersedia: ${produk.jumlah_stok}, diminta: ${currentQuantityInCart + requestedQuantity}`, 'warning');
    return;
  }

  const existingItemIndex = selectedTransaksi.value.detailtransaksis.findIndex(
    (item: any) => item.id_produk == produkBaru.value.id
  );

  if (existingItemIndex >= 0) {
    selectedTransaksi.value.detailtransaksis[existingItemIndex].jumlah += produkBaru.value.jumlah;
    selectedTransaksi.value.detailtransaksis[existingItemIndex].total_harga =
      selectedTransaksi.value.detailtransaksis[existingItemIndex].jumlah *
      selectedTransaksi.value.detailtransaksis[existingItemIndex].harga_satuan;
  } else {
    selectedTransaksi.value.detailtransaksis.push({
      id_produk: produk.id,
      produk: produk,
      jumlah: produkBaru.value.jumlah,
      harga_satuan: parseFloat(produk.harga_jual),
      total_harga: produkBaru.value.jumlah * parseFloat(produk.harga_jual),
    });
  }


  produkBaru.value = {
    id: '',
    jumlah: 1
  };
};


const updateHargaProduk = (index: number) => {
  const item = selectedTransaksi.value.detailtransaksis[index];
  if (item.jumlah < 1) item.jumlah = 1;

  const produkTerkait = semuaProduk.value.find(p => p.id === item.id_produk);
  if (produkTerkait && item.jumlah > produkTerkait.jumlah_stok) {
    Swal.fire('Peringatan', `Jumlah melebihi stok tersedia (${produkTerkait.jumlah_stok}) untuk ${produkTerkait.nama_produk}.`, 'warning');
    item.jumlah = produkTerkait.jumlah_stok;
  }

  item.total_harga = item.jumlah * parseFloat(item.harga_satuan || 0);
};


const tambahJumlah = (index: number) => {
  const item = selectedTransaksi.value.detailtransaksis[index];
  const produkTerkait = semuaProduk.value.find(p => p.id === item.id_produk);

  if (produkTerkait && item.jumlah < produkTerkait.jumlah_stok) {
    selectedTransaksi.value.detailtransaksis[index].jumlah++;
    updateHargaProduk(index);
  } else if (produkTerkait) {
    Swal.fire('Peringatan', `Jumlah melebihi stok tersedia (${produkTerkait.jumlah_stok}) untuk ${produkTerkait.nama_produk}.`, 'warning');
  }
};


const kurangiJumlah = (index: number) => {
  if (selectedTransaksi.value.detailtransaksis[index].jumlah > 1) {
    selectedTransaksi.value.detailtransaksis[index].jumlah--;
    updateHargaProduk(index);
  }
};

const hapusProduk = (index: number) => {
  selectedTransaksi.value.detailtransaksis.splice(index, 1);
};


const updateTransaction = async () => {
  try {

    validationErrors.value = {};


    if (!selectedTransaksi.value) return;

    if (selectedTransaksi.value.metode_pembayaran === 'kredit' && !selectedTransaksi.value.jatuh_tempo) {
      Swal.fire('Peringatan', 'Harap tentukan jatuh tempo untuk transaksi kredit', 'warning');
      return;
    }


    if (selectedTransaksi.value.detailtransaksis.some((item: any) => item.jumlah <= 0)) {
      Swal.fire('Peringatan', 'Jumlah produk tidak boleh nol atau negatif.', 'warning');
      return;
    }
    if (selectedTransaksi.value.detailtransaksis.length === 0) {
      Swal.fire('Peringatan', 'Transaksi harus memiliki setidaknya satu produk.', 'warning');
      return;
    }

    const payload = {
      sub_total: computedSubTotalHarga.value,
      total_bayar: parseFloat(selectedTransaksi.value.total_bayar || 0) + additionalPayment.value,
      metode_pembayaran: selectedTransaksi.value.metode_pembayaran,
      jatuh_tempo: selectedTransaksi.value.metode_pembayaran === 'kredit'
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
    const response = await axios.put(`api/transaksi/${selectedTransaksi.value.id}`, payload);
    const updatedTransaction = response.data.data;
    let successMessage = `Transaksi #${updatedTransaction.id} berhasil diperbarui.`;
    if (selectedTransaksi.value.metode_pembayaran === 'kredit' && updatedTransaction.metode_pembayaran === 'cash') {
      successMessage += ` Status pembayaran telah berubah menjadi **Lunas (Cash)**.`;
    } else if (additionalPayment.value > 0) {
      successMessage += ` Pembayaran tambahan sebesar ${formatCurrency(additionalPayment.value)} telah ditambahkan. Sisa tagihan: ${formatCurrency(updatedTransaction.total_kurang)}.`;
    }
    Swal.fire('Sukses', successMessage, 'success');
    fetchTransaksis();
    closeEditDialog();
  } catch (error: any) {
    console.error('Gagal memperbarui transaksi:', error);
    if (error.response?.status === 422) {
      validationErrors.value = error.response.data.errors;
      Swal.fire('Validasi Gagal', 'Mohon periksa kembali input Anda.', 'warning');
    } else {
      let errorMessage = 'Gagal memperbarui transaksi';
      if (error.response?.data?.message) {
        errorMessage = error.response.data.message;
        if (error.response.data.error) {
          errorMessage += ": " + error.response.data.error;
        }
      }
      Swal.fire('Error', errorMessage, 'error');
    }
  }
};

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
      await axios.delete(`api/transaksi/${id}`);
      Swal.fire('Dihapus!', 'Transaksi berhasil dihapus.', 'success');
      fetchTransaksis();
    }
  } catch (error) {
    console.error('Gagal menghapus transaksi:', error);
    Swal.fire('Error', 'Gagal menghapus transaksi', 'error');
  }
};

const exportPdf = async () => {
  try {
    Swal.fire({
      title: 'Mempersiapkan Laporan...',
      text: 'Mohon tunggu, laporan sedang dibuat.',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    const params = {
      search: searchTerm.value,
      // Perbaikan di sini: Jika statusFilter.value adalah null, ubah menjadi string kosong.
      status: statusFilter.value === 'all' || statusFilter.value === null ? '' : statusFilter.value,
      start_date: dateRange.value.start || '', // Juga pastikan ini string, jika bisa null/undefined
      end_date: dateRange.value.end || ''     // Juga pastikan ini string, jika bisa null/undefined
    };

    // Menggunakan window.location.href untuk mengunduh file
    const queryString = new URLSearchParams(params).toString();
    window.location.href = `api/reports/sales/export-pdf?${queryString}`;

    Swal.close();
  } catch (error) {
    console.error('Gagal mengekspor PDF:', error);
    Swal.fire('Error', 'Gagal mengekspor laporan penjualan ke PDF.', 'error');
  }
};

watch([searchTerm, statusFilter, dateRange], () => {
  fetchTransaksis();
}, { deep: true });

onMounted(() => {
  fetchTransaksis();
  fetchSemuaProduk();
});
</script>

<template>

  <Head title="Data Transaksi" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="w-full mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6">
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
              <SelectItem value="Cash">Cash</SelectItem>
              <SelectItem value="Kredit">Kredit</SelectItem>
            </SelectContent>
          </Select>
          <div class="flex gap-2 w-full">
            <Input type="date" v-model="dateRange.start" class="w-1/2" />
            <Input type="date" v-model="dateRange.end" class="w-1/2" />
          </div>
          <Button @click="fetchTransaksis" class="w-full md:w-auto">Refresh</Button>
          <Button @click="exportPdf" class="w-full md:w-auto bg-green-600 hover:bg-green-700">
            <FileText class="h-4 w-4 mr-2" /> Export PDF
          </Button>
        </div>
      </div>

      <Card class="mt-6">
        <CardHeader>
          <CardTitle>Daftar Transaksi</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="isLoading" class="overflow-x-auto max-h-[60vh] overflow-y-auto border rounded-md">
            <Table>
              <TableHeader class="sticky top-0 bg-white z-10">
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
                <TableRow v-for="i in 5" :key="i">
                  <TableCell>
                    <Skeleton class="h-4 w-12" />
                  </TableCell>
                  <TableCell>
                    <Skeleton class="h-4 w-32" />
                  </TableCell>
                  <TableCell>
                    <Skeleton class="h-4 w-40" />
                    <Skeleton class="h-3 w-24 mt-1" />
                  </TableCell>
                  <TableCell>
                    <Skeleton class="h-4 w-28" />
                  </TableCell>
                  <TableCell>
                    <Skeleton class="h-4 w-24" />
                  </TableCell>
                  <TableCell>
                    <Skeleton class="h-4 w-20" />
                  </TableCell>
                  <TableCell>
                    <Skeleton class="h-4 w-24" />
                  </TableCell>
                  <TableCell>
                    <Skeleton class="h-4 w-24" />
                  </TableCell>
                  <TableCell>
                    <Skeleton class="h-6 w-16" />
                  </TableCell>
                  <TableCell>
                    <Skeleton class="h-4 w-24" />
                  </TableCell>
                  <TableCell class="flex justify-end gap-2">
                    <Skeleton class="h-8 w-8 rounded-md" />
                    <Skeleton class="h-8 w-8 rounded-md" />
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
          <div v-else-if="filteredTransactions.length === 0" class="text-center py-8 text-gray-500">
            Tidak ada transaksi yang ditemukan.
          </div>
          <div v-else class="overflow-x-auto max-h-[60vh] overflow-y-auto border rounded-md">
            <Table>
              <TableHeader class="sticky top-0 z-10">
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
                <TableRow v-for="transaksi in filteredTransactions" :key="transaksi.id" class="group">
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
                  <TableCell>{{ formatCurrency(transaksi.sub_total) }}</TableCell>
                  <TableCell>{{ formatCurrency(transaksi.diskon) }}</TableCell>
                  <TableCell>{{ formatCurrency(transaksi.total_bayar) }}</TableCell>
                  <TableCell>{{ formatCurrency(transaksi.total_kurang) }}</TableCell>
                  <TableCell>
                    <Badge :variant="transaksi.metode_pembayaran === 'Cash' ? 'default' : 'destructive'">
                      {{ transaksi.metode_pembayaran === 'Cash' ? 'Cash' : 'Kredit' }}
                    </Badge>
                  </TableCell>
                  <TableCell>{{ formatDate(transaksi.jatuh_tempo) }}</TableCell>
                  <TableCell class="text-right space-x-2">
                    <Button @click="openEditDialog(transaksi)" variant="ghost" size="sm"
                      class="h-8 px-2 text-primary hover:text-primary sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                      <Pencil class="h-4 w-4" />
                    </Button>
                    <Button @click="deleteTransaction(transaksi.id)" variant="ghost" size="sm"
                      class="h-8 px-2 text-destructive hover:text-destructive sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

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

          <div v-if="isLoadingDetail" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-4">
              <Skeleton class="h-6 w-1/2" />
              <Skeleton class="h-8 w-full" />
              <Skeleton class="h-8 w-full" />
              <Skeleton class="h-8 w-full" />
              <Skeleton class="h-8 w-full" />
              <Skeleton class="h-8 w-full" />
            </div>
            <div class="space-y-4">
              <Skeleton class="h-6 w-1/2" />
              <div class="border rounded-md p-4 space-y-2">
                <Skeleton class="h-8 w-full" v-for="i in 3" :key="i" />
              </div>
              <Skeleton class="h-6 w-1/2" />
              <div class="flex flex-col sm:flex-row gap-2">
                <Skeleton class="h-10 flex-1" />
                <Skeleton class="h-10 w-24" />
                <Skeleton class="h-10 w-full sm:w-auto" />
              </div>
            </div>
          </div>

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
                <Input type="number" v-model.number="selectedTransaksi.diskon" min="0" class="w-full"
                  :class="{ 'border-red-500': validationErrors.diskon }" />
                <p v-if="validationErrors.diskon" class="text-red-500 text-sm mt-1">
                  {{ validationErrors.diskon[0] }}
                </p>
              </div>

              <div>
                <Label>Tambahan Pembayaran (Rp)</Label>
                <Input type="number" v-model.number="additionalPayment" @input="validateAdditionalPayment"
                  class="w-full" :class="{ 'border-red-500': validationErrors.total_bayar }" />
                <p v-if="validationErrors.total_bayar" class="text-red-500 text-sm mt-1">
                  {{ validationErrors.total_bayar[0] }}
                </p>
              </div>

              <div>
                <Label>Status Pembayaran</Label>
                <Select v-model="selectedTransaksi.metode_pembayaran">
                  <SelectTrigger class="w-full" :class="{ 'border-red-500': validationErrors.metode_pembayaran }">
                    <SelectValue placeholder="Pilih Status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="cash">Cash</SelectItem>
                    <SelectItem value="kredit">Kredit</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="validationErrors.metode_pembayaran" class="text-red-500 text-sm mt-1">
                  {{ validationErrors.metode_pembayaran[0] }}
                </p>
              </div>

              <div v-if="selectedTransaksi.metode_pembayaran === 'kredit'">
                <Label>Jatuh Tempo</Label>
                <Input type="date" v-model="selectedTransaksi.jatuh_tempo" :min="new Date().toISOString().split('T')[0]"
                  class="w-full" :class="{ 'border-red-500': validationErrors.jatuh_tempo }" />
                <p v-if="validationErrors.jatuh_tempo" class="text-red-500 text-sm mt-1">
                  {{ validationErrors.jatuh_tempo[0] }}
                </p>
              </div>
            </div>

            <div class="space-y-4">
              <h3 class="text-lg font-semibold">Detail Produk</h3>

              <div v-if="selectedTransaksi.detailtransaksis && selectedTransaksi.detailtransaksis.length > 0">
                <div class="border rounded-md overflow-x-auto max-h-[300px] overflow-y-auto">
                  <Table>
                    <TableHeader class="sticky top-0 z-10">
                      <TableRow>
                        <TableHead class="w-[40%]">Produk</TableHead>
                        <TableHead>Jumlah</TableHead>
                        <TableHead>Harga</TableHead>
                        <TableHead class="text-right">Total</TableHead>
                        <TableHead>Aksi</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      <TableRow v-for="(item, index) in selectedTransaksi.detailtransaksis"
                        :key="item.id || item.id_produj">
                        <TableCell>
                          {{ item.produk?.nama_produk || 'Produk Tidak Ditemukan' }}
                        </TableCell>
                        <TableCell>
                          <div class="flex items-center gap-1">
                            <Button size="icon" variant="outline" @click="kurangiJumlah(index)" class="h-8 w-8">
                              <Minus class="w-4 h-4" />
                            </Button>
                            <Input type="number" v-model.number="item.jumlah" min="1" class="w-16 text-center"
                              @change="updateHargaProduk(index)"
                              :class="{ 'border-red-500': validationErrors[`items.${index}.jumlah`] }" />
                            <Button size="icon" variant="outline" @click="tambahJumlah(index)" class="h-8 w-8">
                              <Plus class="w-4 h-4" />
                            </Button>
                          </div>
                          <p v-if="validationErrors[`items.${index}.jumlah`]" class="text-red-500 text-xs mt-1">
                            {{ validationErrors[`items.${index}.jumlah`][0] }}
                          </p>
                        </TableCell>
                        <TableCell>
                          {{ formatCurrency(item.harga_satuan) }}
                          <p v-if="validationErrors[`items.${index}.harga_satuan`]" class="text-red-500 text-xs mt-1">
                            {{ validationErrors[`items.${index}.harga_satuan`][0] }}
                          </p>
                        </TableCell>
                        <TableCell class="text-right">
                          {{ formatCurrency(item.total_harga) }}
                        </TableCell>
                        <TableCell>
                          <Button variant="ghost" size="sm" @click="hapusProduk(index)"
                            class="text-destructive hover:text-destructive">
                            <Trash2 class="w-4 h-4" />
                          </Button>
                        </TableCell>
                      </TableRow>
                    </TableBody>
                  </Table>
                </div>
                <p v-if="validationErrors.items" class="text-red-500 text-sm mt-2">
                  {{ validationErrors.items[0] }}
                </p>
              </div>

              <div v-else class="text-gray-500 py-4 text-center">
                Tidak ada produk dalam transaksi ini.
                <p v-if="validationErrors.items && validationErrors.items[0].includes('required')"
                  class="text-red-500 text-sm mt-2">
                  {{ validationErrors.items[0] }}
                </p>
              </div>

              <div class="mt-4">
                <h4 class="text-md font-medium mb-2">Tambah Produk Baru</h4>
                <div class="flex flex-col sm:flex-row gap-2">
                  <div class="flex-1">
                    <Label>Produk</Label>
                    <Select v-model="produkBaru.id">
                      <SelectTrigger :class="{ 'border-red-500': validationErrors['produkBaru.id'] }">
                        <SelectValue placeholder="Pilih Produk" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem v-for="produk in semuaProduk" :key="produk.id" :value="produk.id">
                          {{ produk.nama_produk }} (Stok: {{ produk.jumlah_stok }})
                        </SelectItem>
                      </SelectContent>
                    </Select>
                    <p v-if="validationErrors['produkBaru.id']" class="text-red-500 text-sm mt-1">
                      {{ validationErrors['produkBaru.id'][0] }}
                    </p>
                  </div>
                  <div>
                    <Label>Jumlah</Label>
                    <Input type="number" v-model.number="produkBaru.jumlah" min="1" class="w-24"
                      :class="{ 'border-red-500': validationErrors['produkBaru.jumlah'] }" />
                    <p v-if="validationErrors['produkBaru.jumlah']" class="text-red-500 text-sm mt-1">
                      {{ validationErrors['produkBaru.jumlah'][0] }}
                    </p>
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