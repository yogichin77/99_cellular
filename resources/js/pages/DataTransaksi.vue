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
const transactions = ref<TransaksiResponse[]>([]);
const isLoading = ref(false);
const searchTerm = ref('');
const statusFilter = ref<string>('all');
const dateRange = ref({ start: '', end: '' });
const selectedTransaksi = ref<TransaksiResponse | null>(null);
const semuaProduk = ref<Produk[]>([]);
const allPelanggans = ref<{ id_pelanggan: number; nama_pelanggan: string; nama_toko: string }[]>([]);
const allUsers = ref<{ id: number; name: string }[]>([]);
const sortConfig = ref({ key: 'id', direction: 'desc' });
// Reactive variables for the update form
const additionalPayment = ref(0); // Renamed from tambahanBayar for clarity
const isEditModalOpen = ref(false); // Controls visibility of the edit modal

// Interface for Product data
interface Produk {
  id_produk: number;
  nama_produk: string;
  harga_jual: number;
  jumlah_stok: number;
}

// Interface for Transaction Detail data
interface DetailTransaksi {
  id?: number; // Optional ID for existing detail items (corresponds to id_detail_transaksi in backend validation)
  id_produk: number; // Added to match backend payload structure
  jumlah: number;
  harga_satuan: number;
  total_harga: number;
  produk: Produk; // Nested product object for display and stock checks
}

// Interface for Transaction Response data from API
interface TransaksiResponse {
  id: number;
  sub_total_harga: number;
  diskon: number;
  total_bayar: number;
  total_kurang: number;
  status_pembayaran: 'cash' | 'kredit';
  jatuh_tempo?: string | null; // Can be null
  created_at: string;
  updated_at: string;
  user?: {
    id: number;
    name: string;
  };
  pelanggan?: {
    id_pelanggan: number;
    nama_pelanggan: string;
    nama_toko: string;
  };
  detail_transaksis: DetailTransaksi[];
}

/**
 * Fetches all transactions from the API.
 */
const fetchTransaksis = async () => {
  try {
    isLoading.value = true;
    const { data } = await axios.get('/api/transaksi');

    transactions.value = (data.data || data)
      .map((t: TransaksiResponse) => ({
        ...t,
        created_at: t.created_at,
        jatuh_tempo: t.jatuh_tempo || null, // Ensure jatuh_tempo is null if not set
      }))
      .sort((a: TransaksiResponse, b: TransaksiResponse) =>
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
    // Pastikan semua data master (produk, pelanggan, user) sudah diambil
    await Promise.all([fetchProduk(), fetchPelanggans(), fetchUsers()]);

    // Lakukan deep clone pada objek transaksi untuk diedit
    // Ini penting agar perubahan di form edit tidak langsung mempengaruhi data di tabel utama
    const clonedTransaksi: TransaksiResponse = JSON.parse(JSON.stringify(transaksi));

    // Proses detail_transaksis untuk memastikan data produknya lengkap dan konsisten
    clonedTransaksi.detail_transaksis = (clonedTransaksi.detail_transaksis || []).map(item => {
      const masterProduk = semuaProduk.value.find(p => p.id_produk === item.id_produk);

      // Pastikan item.produk memiliki data yang lengkap.
      // Jika masterProduk ditemukan, gunakan data master untuk stok dan harga jual terbaru.
      // Jika tidak, gunakan data yang ada dari transaksi lama.
      return {
        ...item,
        produk: masterProduk ? {
          id_produk: masterProduk.id_produk,
          nama_produk: masterProduk.nama_produk,
          harga_jual: masterProduk.harga_jual, // Menggunakan harga jual terbaru dari master
          jumlah_stok: masterProduk.jumlah_stok // Menggunakan stok terbaru dari master
        } : {
          // Fallback jika produk tidak ditemukan di daftar master (mungkin sudah dihapus/tidak aktif)
          id_produk: item.id_produk,
          nama_produk: item.produk?.nama_produk || 'Produk Tidak Ditemukan (ID: ' + item.id_produk + ')',
          harga_jual: item.harga_satuan, // Tetap gunakan harga historis dari transaksi
          jumlah_stok: 0 // Stok tidak diketahui jika produk master tidak ada
        },
        // Pastikan harga_satuan di item detail tetap harga transaksi (historis)
        // Jika Anda ingin mengupdate harga detail menjadi harga jual terbaru,
        // ubah `item.harga_satuan` menjadi `masterProduk.harga_jual` di sini.
        // Namun, umumnya harga transaksi adalah historis, jadi biarkan saja.
        // item.harga_satuan = item.harga_satuan; // Ini tidak perlu diubah, biarkan data aslinya
        total_harga: item.jumlah * item.harga_satuan // Hitung ulang total_harga berdasarkan harga satuan historis
      };
    });

    // Set jatuh_tempo ke string format YYYY-MM-DD untuk input date
    clonedTransaksi.jatuh_tempo = transaksi.jatuh_tempo
      ? new Date(transaksi.jatuh_tempo).toISOString().split('T')[0]
      : null;

    selectedTransaksi.value = clonedTransaksi;
    additionalPayment.value = 0; // Reset additional payment
    isEditModalOpen.value = true; // Open the dialog

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
  return selectedTransaksi.value.detail_transaksis.reduce(
    (sum: number, item: DetailTransaksi) => sum + (item.total_harga || (item.jumlah * item.harga_satuan)), 0
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
    const id = selectedTransaksi.value.id;

    // Recalculate sub_total_harga based on the current items in the form
    const sub_total_harga = computedSubTotalHarga.value; // Use the computed property

    // Determine total_kurang based on the new logic
    const new_total_kurang = sisaTagihan.value; // Use the computed property for remaining balance

    // Frontend validation: if status is 'cash', remaining balance must be zero
    if (selectedTransaksi.value.status_pembayaran === 'cash' && new_total_kurang > 0) {
      throw new Error('Transaksi tidak dapat berstatus cash jika masih ada sisa tagihan.');
    }

    // Construct the payload for the API request
    const payload = {
      sub_total_harga: sub_total_harga,
      total_bayar: currentPaidAmount.value, // Send the accumulated total_bayar
      total_kurang: new_total_kurang, // Send the calculated remaining balance
      status_pembayaran: selectedTransaksi.value.status_pembayaran,
      jatuh_tempo: selectedTransaksi.value.status_pembayaran === 'kredit' && selectedTransaksi.value.jatuh_tempo
        ? selectedTransaksi.value.jatuh_tempo
        : null,
      diskon: selectedTransaksi.value.diskon || 0,
      id_pelanggan: selectedTransaksi.value.pelanggan?.id_pelanggan || null,
      id_user: selectedTransaksi.value.user?.id,
      items: selectedTransaksi.value.detail_transaksis.map(item => ({
        id_detail_transaksi: item.id || null, // Pass id_detail_transaksi for existing items
        id_produk: item.produk.id_produk,
        jumlah: item.jumlah,
        harga_satuan: item.harga_satuan, // Ensure harga_satuan is sent
      }))
    };

    // Critical check for id_user as it's required by backend.
    // In a real application, this should come from authenticated user context.
    if (!payload.id_user) {
      // You should replace this with actual authenticated user ID logic.
      // For demonstration, you might need to select a user from a dropdown or get from session.
      // For now, if missing, default to the ID of the first available user.
      payload.id_user = allUsers.value.length > 0 ? allUsers.value[0].id : 1; // Fallback to 1 if no users
      console.warn("id_user not found for transaction, using ID:", payload.id_user);
    }

    // Send the PUT request to update the transaction
    await axios.put(`/api/transaksi/${id}`, payload);

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
  const transaksi = transactions.value.find(t => t.id === id);

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

// Fetch data when the component is mounted
onMounted(() => {
  fetchTransaksis();
  fetchProduk();
  fetchPelanggans();
  fetchUsers(); // Fetch users to populate the user dropdown
});

/**
 * Computed property to filter transactions based on search term, status, and date range.
 */
const filteredTransactions = computed(() => {
  let result = [...transactions.value];

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
const produkBaru = ref<{ id_produk: number | null; jumlah: number }>({
  id_produk: null,
  jumlah: 1
});

/**
 * Decrements the quantity of a product in the selected transaction's detail.
 * @param index The index of the detail item in the array.
 */
const kurangiJumlah = (index: number) => {
  if (!selectedTransaksi.value?.detail_transaksis?.[index]) return;

  const item = selectedTransaksi.value.detail_transaksis[index];
  if (item.jumlah > 1) {
    item.jumlah--;
    item.total_harga = item.jumlah * item.harga_satuan;
  }
};

/**
 * Increments the quantity of a product in the selected transaction's detail.
 * Includes stock validation.
 * @param index The index of the detail item in the array.
 */
const tambahJumlah = (index: number) => {
  if (!selectedTransaksi.value?.detail_transaksis?.[index]) return;

  const item = selectedTransaksi.value.detail_transaksis[index];
  const produkMaster = semuaProduk.value.find(p => p.id_produk === item.produk.id_produk);
  if (!produkMaster) {
    Swal.fire({ icon: 'error', title: 'Error', text: 'Produk tidak ditemukan di daftar master.' });
    return;
  }

  // Calculate the total quantity of this product in the transaction (including the current item)
  const currentProductInTransactionCount = selectedTransaksi.value.detail_transaksis.reduce((sum, detail) => {
    if (detail.id_produk === item.produk.id_produk) {
      return sum + detail.jumlah;
    }
    return sum;
  }, 0);

  // Available stock for this specific product (master stock - what's already included in transaction)
  // We need to check if incrementing the current item's quantity would exceed the master stock
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
  if (selectedTransaksi.value?.detail_transaksis) {
    selectedTransaksi.value.detail_transaksis.splice(index, 1);
  }
};

/**
 * Adds a new product to the selected transaction's detail.
 * Includes stock validation and handles existing products.
 */
const tambahProdukBaru = () => {
  if (!selectedTransaksi.value) return;

  const produkId = produkBaru.value.id_produk;
  if (!produkId) {
    Swal.fire({ icon: 'warning', title: 'Peringatan', text: 'Pilih produk terlebih dahulu.' });
    return;
  }
  if (produkBaru.value.jumlah < 1) {
    Swal.fire({ icon: 'warning', title: 'Peringatan', text: 'Jumlah produk tidak boleh kurang dari 1.' });
    return;
  }

  const produkMaster = semuaProduk.value.find(p => p.id_produk === produkId);
  if (!produkMaster) {
    Swal.fire({ icon: 'error', title: 'Error', text: 'Produk tidak ditemukan di daftar master.' });
    return;
  }

  // Calculate total quantity of this product already in the transaction
  const totalExistingJumlah = selectedTransaksi.value.detail_transaksis
    .filter(d => d.id_produk === produkId)
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
  const existingDetail = selectedTransaksi.value.detail_transaksis.find(
    item => item.id_produk === produkId
  );

  if (existingDetail) {
    // Product already exists, increment its quantity
    existingDetail.jumlah += produkBaru.value.jumlah;
    existingDetail.total_harga = existingDetail.jumlah * existingDetail.harga_satuan;
  } else {
    // Product is new, add it as a new detail item
    selectedTransaksi.value.detail_transaksis.push({
      id: undefined, // ID will be generated by the backend for new items
      id_produk: produkMaster.id_produk, // Important: send id_produk
      jumlah: produkBaru.value.jumlah,
      harga_satuan: produkMaster.harga_jual, // Use the current selling price from master product
      total_harga: produkBaru.value.jumlah * produkMaster.harga_jual,
      produk: { // Populate the nested product object for display
        id_produk: produkMaster.id_produk,
        nama_produk: produkMaster.nama_produk,
        harga_jual: produkMaster.harga_jual,
        jumlah_stok: produkMaster.jumlah_stok
      }
    });
  }

  // Reset the new product form
  produkBaru.value = { id_produk: null, jumlah: 1 };
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
 * @param index The index of the detail item.
 */
const updateHargaProduk = (index: number) => {
  if (!selectedTransaksi.value?.detail_transaksis?.[index]) return;

  const item = selectedTransaksi.value.detail_transaksis[index];
  const produkMaster = semuaProduk.value.find(p => p.id_produk === item.produk.id_produk);
  if (!produkMaster) return;

  if (item.jumlah < 1) item.jumlah = 1;

  // Recalculate total quantity for this product across all detail items
  const totalQuantityForThisProduct = selectedTransaksi.value.detail_transaksis.reduce((sum, detail) => {
    if (detail.id_produk === item.produk.id_produk) {
      return sum + detail.jumlah;
    }
    return sum;
  }, 0);

  // Validate against master stock
  if (totalQuantityForThisProduct > produkMaster.jumlah_stok) {
    Swal.fire({
      icon: 'warning',
      title: 'Stok Terbatas',
      text: `Jumlah total ${item.produk.nama_produk} yang Anda masukkan (${totalQuantityForThisProduct}) melebihi stok tersedia (${produkMaster.jumlah_stok}).`,
    });
    // Adjust the current item's quantity to match the remaining stock
    const diff = totalQuantityForThisProduct - produkMaster.jumlah_stok;
    item.jumlah = Math.max(1, item.jumlah - diff); // Ensure quantity is at least 1
  }

  item.total_harga = item.jumlah * item.harga_satuan;
};





// --- UI Rendering ---
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
                  <TableCell>{{ formatCurrency(transaksi.sub_total_harga) }}</TableCell>
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
                <Select v-model="selectedTransaksi.pelanggan!.id_pelanggan">
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
                  <Select v-model="produkBaru.id_produk">
                    <SelectTrigger>
                      <SelectValue placeholder="Pilih Produk" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="produk in semuaProduk" :key="produk.id_produk" :value="produk.id_produk">
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

              <div v-if="selectedTransaksi.detail_transaksis.length > 0" class="space-y-3 max-h-60 overflow-y-auto">
                <div v-for="(item, index) in selectedTransaksi.detail_transaksis" :key="item.id || `new-${index}`"
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

<style scoped>
/* Add any specific styles here if needed */
</style>