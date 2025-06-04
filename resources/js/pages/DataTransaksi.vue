<script setup lang="ts">
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
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label'; // Import Label component
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { Minus, Plus, Search, Trash2 } from 'lucide-vue-next'; // Import Plus and Minus icons
import Swal from 'sweetalert2';
import { computed, onMounted, ref, watch } from 'vue';

// Breadcrumbs for navigation
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Data Transaksi', href: '/datatransaksi' }];

// Reactive state variables


const isLoading = ref(false);
const searchTerm = ref('');
const statusFilter = ref<string>('all');
const dateRange = ref({ start: '', end: '' });
const selectedTransaksi = ref<TransaksiResponse | null>(null);
const semuaProduk = ref<Produk[]>([]);
const allPelanggans = ref<{ id_pelanggan: number; nama_pelanggan: string; nama_toko: string }[]>([]);
const allUsers = ref<{ id: number; name: string }[]>([]);
const sortConfig = ref({ key: 'id', direction: 'desc' });
const additionalPayment = ref(0);
const isEditModalOpen = ref(false);
const produks = ref<Produk[]>([]);
const transaksis = ref<TransaksiResponse[]>([]);
const items = ref<Item[]>([]);
const diskon = ref(0);
const totalBayar = ref(0);
const selectedPelanggan = ref<number | null>(null);
const statusPembayaran = ref<'cash' | 'kredit' | null>(null);
const jatuhTempo = ref<string>('');

const searchPelanggan = ref("");
const openModalTambahPelanggan = ref(false);
const loadingTambahPelanggan = ref(false);
const showDetailModal = ref(false);
const showPrintModal = ref(false);
const currentTransaction = ref<TransaksiResponse | null>(null);

interface Produk {
  id: number;
  nama_produk: string;
  harga_jual: number;
  jumlah_stok: number;
}


interface detailtransaksis {
  id?: number;
  id_produk: number;
  jumlah: number;
  harga_satuan: number;
  total_harga: number;
  produk: Produk;
}


interface TransaksiResponse {
  id: number;
  sub_total_bayar: number;
  diskon: number;
  total_bayar: number;
  total_kurang: number;
  status_pembayaran: 'cash' | 'kredit';
  jatuh_tempo?: string | null;
  created_at: string;
  updated_at: string;
  id_user?: number;
  id_pelanggan?: number | null;
  user?: {
    id: number;
    name: string;
  };
  pelanggan?: {
    id: number;
    nama_pelanggan: string;
    nama_toko: string;
  };
  detailtransaksis: Array<{  // Pastikan nama sesuai dengan relasi
    id?: number;
    id_produk: number;
    jumlah: number;
    harga_satuan: number;
    total_harga: number;
    produk: Produk;
  }>;// Match backend relationship name
}

const fetchTransaksis = async () => {
  try {
    isLoading.value = true;
    const { data } = await axios.get('/api/transaksi');
    transaksis.value = (data.data || data).map((t: any) => ({
      ...t,
      detailtransaksis: t.detailtransaksis || [],
      sub_total_harga: t.sub_total_harga, // Gunakan field yang benar
      created_at: t.created_at,
      jatuh_tempo: t.jatuh_tempo || null,
      id_user: t.user?.id,
      id_pelanggan: t.pelanggan?.id
    })).sort((a: TransaksiResponse, b: TransaksiResponse) =>
      new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
    );
  } catch (error: unknown) {
    let errorMessage = 'Gagal memuat data transaksi';
    if (axios.isAxiosError(error)) {
      errorMessage = error.response?.data?.message || error.message;
    } else if (error instanceof Error) {
      errorMessage = error.message;
    }
    console.error('Error fetching transaksi:', error);
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: errorMessage,
    });
  } finally {
    isLoading.value = false;
  }
};

/**
 * Fetches all products from the API.
 */
const fetchProduk = async () => {
  try {
    const { data } = await axios.get('/api/produk');
    semuaProduk.value = data.data;
  } catch (error: unknown) {
    let errorMessage = 'Gagal memuat data produk';
    if (axios.isAxiosError(error)) {
      errorMessage = error.response?.data?.message || error.message;
    } else if (error instanceof Error) {
      errorMessage = error.message;
    }
    console.error('Error fetching produk:', error);
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: errorMessage,
    });
  }
};

/**
 * Fetches all customers from the API.
 */
const fetchPelanggans = async () => {
  try {
    const { data } = await axios.get('/api/pelanggan'); // Assuming you have a /api/pelanggan endpoint
    allPelanggans.value = data.data;
  } catch (error: unknown) {
    console.error('Error fetching pelanggans:', error);
    // Handle error appropriately
  }
};

/**
 * Fetches all users from the API.
 */
const fetchUsers = async () => {
  try {
    const { data } = await axios.get('/api/users'); // Assuming you have a /api/users endpoint
    allUsers.value = data.data;
  } catch (error: unknown) {
    console.error('Error fetching users:', error);
    // Handle error appropriately
  }
};

/**
 * Selects a transaction for editing and loads its data into the form.
 * @param transaksi The transaction object to be edited.
 */
const selectTransactionForEdit = async (transaksi: TransaksiResponse) => {
  try {
    await Promise.all([fetchProduk(), fetchPelanggans(), fetchUsers()]);

    // Normalize data structure
    const clonedTransaksi: TransaksiResponse = {
      ...transaksi,
      detailtransaksis: (transaksi.detailtransaksis || transaksi.detailtransaksis || []).map(item => {
        const masterProduk = semuaProduk.value.find(p => p.id === item.id_produk);
        return {
          ...item,
          produk: masterProduk || {
            id: item.id_produk,
            nama_produk: item.produk?.nama_produk || `Produk (ID: ${item.id_produk})`,
            harga_jual: item.harga_satuan,
            jumlah_stok: 0
          }
        };
      }),
      jatuh_tempo: transaksi.jatuh_tempo
        ? new Date(transaksi.jatuh_tempo).toISOString().split('T')[0]
        : null,
      id_user: transaksi.user?.id || transaksi.id_user,
      id_pelanggan: transaksi.pelanggan?.id || transaksi.id_pelanggan
    };

    selectedTransaksi.value = clonedTransaksi;
    additionalPayment.value = 0;
    isEditModalOpen.value = true;

  } catch (error: unknown) {
    let errorMessage = 'Tidak dapat memuat data produk atau transaksi.';
    if (axios.isAxiosError(error)) {
      errorMessage = error.response?.data?.message || error.message;
    } else if (error instanceof Error) {
      errorMessage = error.message;
    }
    console.error('Gagal memuat produk/transaksi:', error);
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: errorMessage,
    });
  }
};

/**
 * Computes the sub total (sum of all item total_harga) for the selected transaction.
 */
const computedSubTotalHarga = computed(() => {
  if (!selectedTransaksi.value) return 0;
  return selectedTransaksi.value.detailtransaksis.reduce(
    (sum: number, item: detailtransaksis) => sum + (item.total_harga || (item.jumlah * item.harga_satuan)), 0
  );
});

/**
 * Computes the total bill for the selected transaction after discount.
 * This is the amount the customer *should* pay based on current items.
 */
const totalTagihan = computed(() => {
  if (!selectedTransaksi.value) return 0;
  return computedSubTotalHarga.value - (selectedTransaksi.value.diskon || 0);
});

/**
 * Computes the currently paid amount (original total_bayar + additional payment).
 */
const currentPaidAmount = computed(() => {
  if (!selectedTransaksi.value) return 0;
  return selectedTransaksi.value.total_bayar + additionalPayment.value;
});

/**
 * Computes the remaining balance (sisa tagihan) for the selected transaction.
 */
const sisaTagihan = computed(() => {
  if (!selectedTransaksi.value) return 0;
  return Math.max(0, totalTagihan.value - currentPaidAmount.value);
});

// Watch for changes in sisaTagihan to update status_pembayaran
watch(sisaTagihan, (newSisa) => {
  if (selectedTransaksi.value) {
    if (newSisa <= 0) {
      selectedTransaksi.value.status_pembayaran = 'cash';
    } else {
      // Only set to kredit if it was previously cash and now has remaining,
      // or if it's already kredit. Don't force 'kredit' if it was cash and just became zero.
      if (selectedTransaksi.value.status_pembayaran === 'cash' && newSisa > 0) {
        selectedTransaksi.value.status_pembayaran = 'kredit';
      }
    }
  }
});


/**
 * Closes the edit form and resets the selected transaction.
 */
const closeEditForm = () => {
  selectedTransaksi.value = null;
  isEditModalOpen.value = false;
  additionalPayment.value = 0; // Reset additional payment
};

/**
 * Handles the update of a transaction.
 * Sends the updated transaction data to the backend API.
 */
const updateTransaction = async () => {
  if (!selectedTransaksi.value) return;

  try {
    const payload = {
      // Gunakan field yang sesuai dengan backend
      sub_total_harga: computedSubTotalHarga.value,
      total_bayar: currentPaidAmount.value,
      total_kurang: sisaTagihan.value,
      status_pembayaran: selectedTransaksi.value.status_pembayaran,
      jatuh_tempo: selectedTransaksi.value.status_pembayaran === 'kredit'
        ? selectedTransaksi.value.jatuh_tempo
        : null,
      diskon: selectedTransaksi.value.diskon || 0,
      id_pelanggan: selectedTransaksi.value.id_pelanggan || null,
      id_user: selectedTransaksi.value.id_user,
      items: selectedTransaksi.value.detailtransaksis.map(item => ({
        id_detail_transaksi: item.id || null, // ID null untuk item baru
        id_produk: item.id_produk,
        jumlah: item.jumlah,
        harga_satuan: item.harga_satuan
      }))
    };
    await axios.put(`/api/transaksi/{id}`, payload);

    Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: 'Transaksi berhasil diperbarui',
      timer: 1500,
      showConfirmButton: false,
    });

    closeEditForm();
    await fetchTransaksis();

  } catch (error: unknown) {
    let errorMessage = 'Terjadi kesalahan saat memperbarui transaksi';
    if (axios.isAxiosError(error)) {
      if (error.response && error.response.status === 422) {
        const errors = error.response.data.errors;
        let validationMessages = '';
        for (const key in errors) {
          if (Object.prototype.hasOwnProperty.call(errors, key)) {
            validationMessages += `${errors[key].join(', ')}\n`;
          }
        }
        errorMessage = `Validasi Gagal:\n${validationMessages}`;
      } else {
        errorMessage = error.response?.data?.message || error.message;
      }
    } else if (error instanceof Error) {
      errorMessage = error.message;
    }

    console.error('Gagal update transaksi:', error);
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: errorMessage,
      html: errorMessage.includes('\n') ? `<pre style="text-align: left;">${errorMessage}</pre>` : errorMessage,
    });
  }
};

/**
 * Handles the deletion of a transaction.
 * @param id The ID of the transaction to delete.
 */
const deleteTransaction = async (id: number) => {
  // Cari transaksi yang akan dihapus
  const transaksi = transaksis.value.find(t => t.id === id);

  // Validasi: Cek apakah pelanggan masih memiliki kredit aktif
  if (transaksi && transaksi.status_pembayaran === 'kredit' && transaksi.total_kurang > 0) {
    Swal.fire({
      icon: 'error',
      title: 'Tidak Dapat Dihapus',
      html: `Pelanggan masih memiliki sisa tagihan sebesar <b>${formatCurrency(transaksi.total_kurang)}</b>. 
             Transaksi dengan status kredit dan sisa tagihan tidak dapat dihapus.`,
    });
    return;
  }

  // Lanjutkan dengan konfirmasi penghapusan jika validasi lolos
  Swal.fire({
    title: 'Apakah Anda yakin?',
    text: "Anda tidak akan dapat mengembalikan ini!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Ya, hapus!',
    cancelButtonText: 'Batal'
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await axios.delete(`/api/transaksi/${id}`);
        Swal.fire(
          'Dihapus!',
          'Transaksi telah berhasil dihapus.',
          'success'
        );
        if (selectedTransaksi.value?.id === id) {
          closeEditForm();
        }
        await fetchTransaksis();
      } catch (error: unknown) {
        let errorMessage = 'Gagal menghapus transaksi.';
        if (axios.isAxiosError(error)) {
          errorMessage = error.response?.data?.message || error.message;
        } else if (error instanceof Error) {
          errorMessage = error.message;
        }
        console.error('Error deleting transaksi:', error);
        Swal.fire({
          icon: 'error',
          title: 'Gagal',
          text: errorMessage,
        });
      }
    }
  });
};
/**
 * Formats a number as Indonesian Rupiah currency.
 * @param value The number to format.
 * @returns Formatted currency string.
 */
const formatCurrency = (value: number) =>
  new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value);

/**
 * Formats a date string into a localized date and time string.
 * @param dateString The date string to format.
 * @returns Formatted date and time string or '-'.
 */
const formatDateTime = (dateString?: string) => {
  if (!dateString) return '-';
  const options: Intl.DateTimeFormatOptions = {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  };
  return new Date(dateString).toLocaleString('id-ID', options);
};

/**
 * Formats a date string into a localized date string (DD/MM/YYYY).
 * @param dateString The date string to format.
 * @returns Formatted date string.
 */
const formatDate = (dateString?: string | null) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

// Sorting function
const sortTransactions = (key: string) => {
  if (sortConfig.value.key === key) {
    sortConfig.value.direction = sortConfig.value.direction === 'asc' ? 'desc' : 'asc';
  } else {
    sortConfig.value.key = key;
    sortConfig.value.direction = 'asc';
  }
};

// Gunakan Promise.all untuk paralel request
onMounted(async () => {
  isLoading.value = true;
  try {
    await Promise.all([
      fetchTransaksis(),
      fetchProduk(),
      fetchPelanggans(),
      fetchUsers()
    ]);
  } finally {
    isLoading.value = false;
  }
});

/**
 * Computed property to filter transactions based on search term, status, and date range.
 */
const filteredTransactions = computed(() => {
  let result = [...transaksis.value];

  // Filter by status
  if (statusFilter.value !== 'all') {
    result = result.filter(t => t.status_pembayaran === statusFilter.value);
  }

  // Filter by date range
  if (dateRange.value.start && dateRange.value.end) {
    const startDate = new Date(dateRange.value.start);
    const endDate = new Date(dateRange.value.end);
    endDate.setHours(23, 59, 59, 999); // Set to end of the day

    result = result.filter(t => {
      const transDate = new Date(t.created_at);
      return transDate >= startDate && transDate <= endDate;
    });
  }

  // Filter by search term
  const term = searchTerm.value.toLowerCase().trim();
  if (term) {
    result = result.filter(transaction =>
      transaction.id.toString().includes(term) ||
      (transaction.pelanggan?.nama_pelanggan?.toLowerCase() || '').includes(term) ||
      (transaction.pelanggan?.nama_toko?.toLowerCase() || '').includes(term) ||
      (transaction.user?.name?.toLowerCase() || '').includes(term) // Also search by user name
    );
  }

  return result;
});

// Reactive variable for adding new products to the transaction
const produkBaru = ref<{ id: number | null; jumlah: number }>({
  id: null,
  jumlah: 1
});

/**
 * Decrements the quantity of a product in the selected transaction's detail.
 * @param index The index of the detail item in the array.
 */
const kurangiJumlah = (index: number) => {
  if (!selectedTransaksi.value?.detailtransaksis?.[index]) return;

  const item = selectedTransaksi.value.detailtransaksis[index];
  if (item.jumlah > 1) {
    item.jumlah--;
    item.total_harga = item.jumlah * item.harga_satuan;
  }
};

/**
 * Increments the quantity of a product in the selected transaction's detail.
 * Includes stock validation.
 * @param index 
 */
const tambahJumlah = (index: number) => {
  if (!selectedTransaksi.value?.detailtransaksis?.[index]) return;
  const totalInTransaction = selectedTransaksi.value.detailtransaksis
    .filter(d => d.id_produk === item.id_produk)
    .reduce((sum, d) => sum + d.jumlah, 0);
  const item = selectedTransaksi.value.detailtransaksis[index];
  const produkMaster = semuaProduk.value.find(p => p.id === item.produk.id);
  
  if (!produkMaster) {
    Swal.fire({ icon: 'error', title: 'Error', text: 'Produk tidak ditemukan di daftar master.' });
    return;
  }


  const currentProductInTransactionCount = selectedTransaksi.value.detailtransaksis.reduce((sum, detail) => {
    if (detail.id === item.produk.id) {
      return sum + detail.jumlah;
    }
    return sum;
  }, 0);

  if (currentProductInTransactionCount + 1 > produkMaster.jumlah_stok) {
    Swal.fire({
      icon: 'warning',
      title: 'Stok Terbatas',
      text: `Stok ${produkMaster.nama_produk} hanya tersedia ${produkMaster.jumlah_stok} unit.`,
    });
    return;
  }

  item.jumlah++;
  item.total_harga = item.jumlah * item.harga_satuan;
};

/**
 * Removes a product from the selected transaction's detail.
 * @param index The index of the detail item to remove.
 */
const hapusProduk = (index: number) => {
  if (selectedTransaksi.value?.detailtransaksis) {
    selectedTransaksi.value.detailtransaksis.splice(index, 1);
  }
};

/**
 * Adds a new product to the selected transaction's detail.
 * Includes stock validation and handles existing products.
 */
const tambahProdukBaru = () => {
  if (!selectedTransaksi.value) return;

  const produkId = produkBaru.value.id;
  if (!produkId) {
    Swal.fire({ icon: 'warning', title: 'Peringatan', text: 'Pilih produk terlebih dahulu.' });
    return;
  }
  if (produkBaru.value.jumlah < 1) {
    Swal.fire({ icon: 'warning', title: 'Peringatan', text: 'Jumlah produk tidak boleh kurang dari 1.' });
    return;
  }

  const produkMaster = semuaProduk.value.find(p => p.id === produkId);
  if (!produkMaster) {
    Swal.fire({ icon: 'error', title: 'Error', text: 'Produk tidak ditemukan di daftar master.' });
    return;
  }

  // Calculate total quantity of this product already in the transaction
  const totalExistingJumlah = selectedTransaksi.value.detailtransaksis
    .filter(d => d.id === produkId)
    .reduce((sum, d) => sum + d.jumlah, 0);

  // Available stock from master product minus what's already in the transaction
  const availableStockForNewAddition = produkMaster.jumlah_stok - totalExistingJumlah;

  if (produkBaru.value.jumlah > availableStockForNewAddition) {
    Swal.fire({
      icon: 'error',
      title: 'Stok Tidak Cukup',
      text: `Stok ${produkMaster.nama_produk} yang tersedia untuk penambahan baru hanya ${availableStockForNewAddition} unit. Anda mencoba menambahkan ${produkBaru.value.jumlah}.`,
    });
    return;
  }

  // Check if the product already exists in the transaction details
  const existingDetail = selectedTransaksi.value.detailtransaksis.find(
    item => item.id === produkId
  );

  if (existingDetail) {
    // Product already exists, increment its quantity
    existingDetail.jumlah += produkBaru.value.jumlah;
    existingDetail.total_harga = existingDetail.jumlah * existingDetail.harga_satuan;
  } else {
    // Product is new, add it as a new detail item
    selectedTransaksi.value.detailtransaksis.push({
      id_produk: produkMaster.id, // Gunakan id_produk
      jumlah: produkBaru.value.jumlah,
      harga_satuan: produkMaster.harga_jual,
      total_harga: produkBaru.value.jumlah * produkMaster.harga_jual,
      produk: {
        ...produkMaster // Simpan salinan data produk
      }
    });
  }

  // Reset the new product form
  produkBaru.value = { id: null, jumlah: 1 };
};

/**
 * Validates the additional payment amount.
 */
const validateAdditionalPayment = () => {
  if (!selectedTransaksi.value) return;

  if (additionalPayment.value < 0) {
    additionalPayment.value = 0;
  }

  // Max payment is the remaining balance
  const maxPaymentPossible = totalTagihan.value - selectedTransaksi.value.total_bayar;
  if (additionalPayment.value > maxPaymentPossible) {
    Swal.fire({
      icon: 'warning',
      title: 'Peringatan',
      text: `Jumlah pembayaran tidak boleh melebihi sisa tagihan ${formatCurrency(maxPaymentPossible)}.`,
    });
    additionalPayment.value = Math.max(0, maxPaymentPossible); // Cap the payment
  }
};

/**
 * Updates the total price of a product item when its quantity or unit price changes.
 * Includes stock validation.
 * @param index 
 */
const updateHargaProduk = (index: number) => {
  if (!selectedTransaksi.value?.detailtransaksis?.[index]) return;

  const item = selectedTransaksi.value.detailtransaksis[index];
  const produkMaster = semuaProduk.value.find(p => p.id === item.produk.id);
  if (!produkMaster) return;

  if (item.jumlah < 1) item.jumlah = 1;


  const totalQuantityForThisProduct = selectedTransaksi.value.detailtransaksis.reduce((sum, detail) => {
    if (detail.id === item.produk.id) {
      return sum + detail.jumlah;
    }
    return sum;
  }, 0);

  if (totalQuantityForThisProduct > produkMaster.jumlah_stok) {
    Swal.fire({
      icon: 'warning',
      title: 'Stok Terbatas',
      text: `Jumlah total ${item.produk.nama_produk} yang Anda masukkan (${totalQuantityForThisProduct}) melebihi stok tersedia (${produkMaster.jumlah_stok}).`,
    });
    const diff = totalQuantityForThisProduct - produkMaster.jumlah_stok;
    item.jumlah = Math.max(1, item.jumlah - diff);
  }

  item.total_harga = item.jumlah * item.harga_satuan;
};

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
              <SelectItem value="cash">cash</SelectItem>
              <SelectItem value="kredit">kredit</SelectItem>
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
                  <TableCell>{{ transaksi.pelanggan?.nama_pelanggan || '-' }} <br />
                    <span class="text-xs text-gray-500">({{ transaksi.pelanggan?.nama_toko || '-' }})</span>
                  </TableCell>
                  <TableCell>{{ transaksi.user?.name || '-' }}</TableCell>
                  <TableCell>{{ formatCurrency(transaksi.sub_total_bayar) }}</TableCell>
                  <TableCell>{{ formatCurrency(transaksi.diskon) }}</TableCell>
                  <TableCell>{{ formatCurrency(transaksi.total_bayar) }}</TableCell>
                  <TableCell>{{ formatCurrency(transaksi.total_kurang) }}</TableCell>
                  <TableCell>
                    <Badge :variant="transaksi.status_pembayaran === 'cash' ? 'success' : 'destructive'">
                      {{ transaksi.status_pembayaran === 'cash' ? 'cash' : 'kredit' }}
                    </Badge>
                  </TableCell>
                  <TableCell>{{ formatDate(transaksi.jatuh_tempo) }}</TableCell>
                  <TableCell>
                    <div class="flex gap-2">
                      <Button variant="outline" size="sm" @click="selectTransactionForEdit(transaksi)">Edit</Button>
                      <Button variant="destructive" size="sm" @click="deleteTransaction(transaksi.id)">
                        <Trash2 class="w-4 h-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      <Dialog v-model:open="isEditModalOpen">
        <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Edit Transaksi #{{ selectedTransaksi?.id }}</DialogTitle>
            <DialogDescription>
              Ubah detail transaksi, produk, pembayaran, dan status.
            </DialogDescription>
          </DialogHeader>

          <div v-if="selectedTransaksi" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-4">
              <h3 class="text-lg font-semibold">Detail Utama</h3>

              <div>
                <Label for="pelanggan">Pelanggan</Label>
                <Select v-model="selectedTransaksi.pelanggan!.id">
                  <SelectTrigger>
                    <SelectValue placeholder="Pilih Pelanggan" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem :value="null">-- Tanpa Pelanggan --</SelectItem>
                    <SelectItem v-for="pelanggan in allPelanggans" :key="pelanggan.id_pelanggan"
                      :value="pelanggan.id_pelanggan">
                      {{ pelanggan.nama_pelanggan }} ({{ pelanggan.nama_toko }})
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Label for="user">Kasir</Label>
                <Select v-model="selectedTransaksi.user!.id">
                  <SelectTrigger>
                    <SelectValue placeholder="Pilih User" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="user in allUsers" :key="user.id" :value="user.id">
                      {{ user.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Label for="diskon">Diskon (Rp)</Label>
                <Input id="diskon" type="number" v-model.number="selectedTransaksi.diskon" min="0" />
              </div>

              <div>
                <Label for="total_bayar">Pembayaran Saat Ini (Rp)</Label>
                <Input id="total_bayar" type="number" :value="selectedTransaksi.total_bayar" disabled />
              </div>

              <div>
                <Label for="additional_payment">Tambahan Pembayaran (Rp)</Label>
                <Input id="additional_payment" type="number" v-model.number="additionalPayment" min="0"
                  @input="validateAdditionalPayment" />
              </div>

              <div>
                <Label for="status_pembayaran">Status Pembayaran</Label>
                <Select v-model="selectedTransaksi.status_pembayaran">
                  <SelectTrigger>
                    <SelectValue placeholder="Pilih Status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="cash">cash</SelectItem>
                    <SelectItem value="kredit">kredit</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div v-if="selectedTransaksi.status_pembayaran === 'kredit'">
                <Label for="jatuh_tempo">Jatuh Tempo</Label>
                <Input id="jatuh_tempo" type="date" v-model="selectedTransaksi.jatuh_tempo" />
              </div>

              <div class="mt-4 p-3 bg-gray-100 dark:bg-gray-800 rounded-md">
                <p class="font-semibold text-lg">Sub Total: {{ formatCurrency(computedSubTotalHarga) }}</p>
                <p class="font-semibold text-lg">Total Tagihan (Setelah Diskon): {{ formatCurrency(totalTagihan) }}</p>
                <p class="font-bold text-xl text-red-600 dark:text-red-400">Sisa Tagihan: {{ formatCurrency(sisaTagihan)
                  }}</p>
              </div>
            </div>

            <div class="space-y-4">
              <h3 class="text-lg font-semibold">Daftar Produk</h3>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-2 items-end border-b pb-4">
                <div class="col-span-2">
                  <Label for="new_produk">Tambah Produk Baru</Label>
                  <Select v-model="produkBaru.id">
                    <SelectTrigger>
                      <SelectValue placeholder="Pilih Produk" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="produk in semuaProduk" :key="produk.id" :value="produk.id">
                        {{ produk.nama_produk }} (Stok: {{ produk.jumlah_stok }})
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div>
                  <Label for="new_jumlah">Jumlah</Label>
                  <Input id="new_jumlah" type="number" v-model.number="produkBaru.jumlah" min="1" />
                </div>
                <div class="col-span-3 text-right">
                  <Button @click="tambahProdukBaru" class="w-full">Tambah Produk</Button>
                </div>
              </div>

              <div v-if="selectedTransaksi.detailtransaksis.length > 0" class="space-y-3 max-h-60 overflow-y-auto">
                <div v-for="(item, index) in selectedTransaksi.detailtransaksis" :key="item.id || `new-${index}`"
                  class="border p-2 rounded-md flex items-center gap-3">
                  <div class="flex-grow">
                    <p class="font-semibold">{{ item.produk.nama_produk }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Harga Satuan: {{
                      formatCurrency(item.harga_satuan) }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total: {{ formatCurrency(item.total_harga) }}
                    </p>
                  </div>
                  <div class="flex items-center gap-1">
                    <Button variant="outline" size="icon" @click="kurangiJumlah(index)">
                      <Minus class="w-4 h-4" />
                    </Button>
                    <Input type="number" v-model.number="item.jumlah" min="1" class="w-16 text-center"
                      @input="updateHargaProduk(index)" />
                    <Button variant="outline" size="icon" @click="tambahJumlah(index)">
                      <Plus class="w-4 h-4" />
                    </Button>
                  </div>
                  <Button variant="destructive" size="icon" @click="hapusProduk(index)">
                    <Trash2 class="w-4 h-4" />
                  </Button>
                </div>
              </div>
              <div v-else class="text-center text-gray-500 py-4">Belum ada produk dalam transaksi ini.</div>
            </div>
          </div>

          <DialogFooter class="mt-6 flex flex-col sm:flex-row gap-2">
            <Button variant="outline" @click="closeEditForm">Batal</Button>
            <Button @click="updateTransaction">Simpan Perubahan</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
