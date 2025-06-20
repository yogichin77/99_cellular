<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-4xl max-h-[90vh] overflow-y-auto">
      <div class="printable-area" ref="printableContent">
        <!-- Header Profesional -->
        <div class="mb-6 border-b pb-4 border-blue-200">
          <div class="flex items-center justify-center mb-3">
            <div class="bg-blue-800 text-white font-bold text-xl px-4 py-2 rounded-lg">99 CELLULAR</div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-center">
            <div class="text-sm text-gray-600 dark:text-gray-400">
              <span class="font-medium">Alamat:</span> Jl. Oevang Oeray No. 123, Sintang
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              <span class="font-medium">Telp:</span> 0812-3456-7890
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              <span class="font-medium">Email:</span> info@99cellular.com
            </div>
          </div>
        </div>

        <DialogHeader class="mb-4">
          <DialogTitle class="text-xl font-bold text-blue-800">Detail Transaksi #{{ transaction?.id }}</DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400 mt-1">
            Tanggal: {{ formatDateTime(transaction?.created_at || '') }}
          </DialogDescription>
        </DialogHeader>

        <div class="py-4" v-if="transaction">
          <!-- Informasi Transaksi Terstruktur -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
              <div class="flex items-center mb-2">
                <span class="font-medium w-32 text-gray-700 dark:text-gray-300">Kasir:</span>
                <span class="text-gray-900 dark:text-gray-100">{{ transaction.user?.name || '-' }}</span>
              </div>
              <div class="flex items-center mb-2">
                <span class="font-medium w-32 text-gray-700 dark:text-gray-300">Metode Pembayaran:</span>
                <Badge :class="statusClass(transaction.metode_pembayaran)" class="px-2 py-1 rounded-sm text-xs">
                  {{ transaction.metode_pembayaran?.toUpperCase() }}
                </Badge>
              </div>
            </div>
            <div>
              <div v-if="transaction.pelanggan" class="mb-2">
                <div class="font-medium text-gray-700 dark:text-gray-300">Pelanggan</div>
                <div class="text-gray-900 dark:text-gray-100">{{ transaction.pelanggan.nama_pelanggan }}</div>
                <div v-if="transaction.pelanggan.nama_toko" class="text-sm text-gray-600 dark:text-gray-400">
                  {{ transaction.pelanggan.nama_toko }}
                </div>
              </div>
              <div v-if="transaction.metode_pembayaran === 'kredit' && transaction.jatuh_tempo" class="text-sm">
                <span class="font-medium text-gray-700 dark:text-gray-300">Jatuh Tempo:</span>
                <span class="text-gray-900 dark:text-gray-100">{{ formatDate(transaction.jatuh_tempo) }}</span>
              </div>
            </div>
          </div>

          <!-- Tabel Produk Minimalis -->
          <div class="mb-6">
            <h3 class="text-md font-bold mb-3 text-blue-700">Daftar Produk</h3>
            <div class="overflow-x-auto">
              <table class="w-full min-w-full">
                <thead>
                  <tr class="border-b border-blue-200">
                    <th class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Produk</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Harga</th>
                    <th class="px-3 py-2 text-center font-medium text-gray-700 dark:text-gray-300">Qty</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in transaction.detailtransaksis" :key="item.id"
                    class="border-b border-blue-50 dark:border-gray-700">
                    <td class="px-3 py-3 text-gray-900 dark:text-gray-100">{{ item.produk.nama_produk }}</td>
                    <td class="px-3 py-3 text-left text-gray-900 dark:text-gray-100">{{ formatCurrency(item.harga_satuan) }}</td>
                    <td class="px-3 py-3 text-center text-gray-900 dark:text-gray-100">{{ item.jumlah }}</td>
                    <td class="px-3 py-3 text-left text-gray-900 dark:text-gray-100 font-medium">{{ formatCurrency(item.total_harga) }}</td>
                  </tr>
                  <tr v-if="!transaction.detailtransaksis || transaction.detailtransaksis.length === 0">
                    <td colspan="4" class="text-center py-4 text-gray-500 dark:text-gray-400">Tidak ada produk dalam transaksi ini.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Ringkasan Pembayaran -->
          <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6">
            <div class="flex flex-col items-end space-y-2">
              <div class="flex justify-between w-full max-w-[220px]">
                <p class="text-gray-700 dark:text-gray-300">Subtotal:</p>
                <span class="text-gray-900 dark:text-gray-100">{{ formatCurrency(transaction.sub_total) }}</span>
              </div>
              <div class="flex justify-between w-full max-w-[220px]">
                <p class="text-gray-700 dark:text-gray-300">Diskon:</p>
                <span class="text-red-600 dark:text-red-400">-{{ formatCurrency(transaction.diskon) }}</span>
              </div>
              <div class="flex justify-between w-full max-w-[220px] border-t border-blue-200 pt-2 mt-1">
                <p class="font-bold text-gray-900 dark:text-gray-100">Total:</p>
                <span class="font-bold text-blue-700 dark:text-blue-300">{{ formatCurrency(transaction.total_bayar) }}</span>
              </div>
              
              <!-- Pembayaran Cash -->
              <div v-if="transaction.metode_pembayaran === 'cash'" class="w-full max-w-[220px] mt-2 pt-2 border-t border-blue-200">
                <div class="flex justify-between mb-1">
                  <p class="text-gray-700 dark:text-gray-300">Dibayar:</p>
                  <span class="text-gray-900 dark:text-gray-100">{{ formatCurrency(transaction.total_bayar) }}</span>
                </div>
                <div class="flex justify-between">
                  <p class="font-bold text-gray-900 dark:text-gray-100">Kembalian:</p>
                  <span class="font-bold text-green-700 dark:text-green-300">{{ formatCurrency(transaction.total_bayar - (transaction.sub_total - transaction.diskon)) }}</span>
                </div>
              </div>
              
              <!-- Pembayaran Kredit -->
              <div v-else-if="transaction.metode_pembayaran === 'kredit' && parseFloat(String(transaction.total_kurang)) > 0"
                class="flex justify-between w-full max-w-[220px] mt-2 pt-2 border-t border-blue-200">
                <p class="font-bold text-gray-900 dark:text-gray-100">Sisa Tagihan:</p>
                <span class="font-bold text-red-700 dark:text-red-300">{{ formatCurrency(transaction.total_kurang) }}</span>
              </div>
            </div>
          </div>

          <!-- Footer Resmi -->
          <div class="text-center mt-8 text-gray-600 dark:text-gray-400 text-sm">
            <p class="font-medium mb-2">Terima kasih atas kepercayaan Anda berbelanja di 99 Cellular</p>
            <p class="text-xs italic">Barang yang sudah dibeli tidak dapat ditukar/dikembalikan kecuali ada perjanjian Sebelumnya</p>
          </div>
        </div>

        <div v-else class="py-8 text-center text-gray-500 dark:text-gray-400">
          Memuat detail transaksi atau data tidak tersedia.
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-2 justify-end mt-4 print:hidden">
        <Button variant="outline" @click="$emit('update:open', false)">Tutup</Button>
        <Button @click="handlePrint" class="bg-blue-700 hover:bg-blue-800">
          <Printer class="w-4 h-4 mr-2" /> Cetak Struk
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import type { TransaksiResponse } from '@/types';
import { Printer } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
  transaction: TransaksiResponse | null;
  open: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
}>();

const printableContent = ref<HTMLElement | null>(null);

const formatCurrency = (value: any) => {
  const numericValue = parseFloat(value);
  if (isNaN(numericValue)) return 'Rp 0';
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(numericValue);
};

const formatDateTime = (dateString: string) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  }).replace('.', ':');
};

const formatDate = (dateString: string) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
};

const statusClass = (status: 'cash' | 'kredit') => {
  return {
    'cash': 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-200',
    'kredit': 'bg-red-200 text-red-900 dark:bg-red-900/30 dark:text-red-300',
  }[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
};

const handlePrint = () => {
  const contentToPrintElement = printableContent.value;
  if (!contentToPrintElement) {
    alert('Konten untuk dicetak tidak ditemukan.');
    return;
  }

  const printWindow = window.open('', '_blank', 'height=600,width=800');
  if (!printWindow) {
    alert('Gagal membuka jendela cetak. Pastikan pop-up diizinkan.');
    return;
  }

  const printDocument = printWindow.document;

  // Clear existing content in the new window's document
  printDocument.open(); // This clears the document
  printDocument.write(''); // This ensures it's truly empty
  printDocument.close();


  // IMPORTANT: Do NOT create a new <html> element.
  // Instead, append directly to printDocument.head and printDocument.body

  // Set title
  const title = printDocument.createElement('title');
  title.textContent = `Struk Transaksi #${props.transaction?.id || ''}`;
  printDocument.head.appendChild(title); // Append to existing head

  // Add styles
  const style = printDocument.createElement('style');
  style.textContent = `
    /* Modern Print Styles - all your existing print styles here */
    @media print {
      body {
        font-family: 'Segoe UI', 'Roboto', sans-serif;
        margin: 0;
        padding: 10px;
        color: #333;
        font-size: 12px;
        line-height: 1.4;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .printable-area {
        width: 100%;
        max-width: 80mm;
        margin: 0 auto;
      }
      .border-b { border-bottom: 1px solid #e2e8f0; }
      table { width: 100%; border-collapse: collapse; }
      th, td { padding: 6px 8px; text-align: left; }
      th { background-color: #f8fafc; font-weight: 600; border-bottom: 1px solid #cbd5e1; }
      tr { border-bottom: 1px solid #e2e8f0; }
      .bg-blue-50 { background-color: #f0f9ff; }
      .text-right { text-align: right; }
      .text-center { text-align: center; }
      .font-bold { font-weight: 700; }
      .mb-3 { margin-bottom: 12px; }
      .py-3 { padding-top: 12px; padding-bottom: 12px; }
      .bg-blue-800 { background-color: #1e40af !important; color: white !important; }
      .text-blue-700 { color: #1d4ed8 !important; }
      .text-red-600 { color: #dc2626 !important; }
      .text-green-700 { color: #047857 !important; }
      .bg-green-100 { background-color: #dcfce7 !important; }
      .bg-amber-100 { background-color: #fef3c7 !important; }
      .print\\:hidden { display: none !important; }
    }
  `;
  printDocument.head.appendChild(style); // Append to existing head

  // Append the extracted content (cloning to avoid moving the original)
  const clonedContent = contentToPrintElement.cloneNode(true) as HTMLElement;

  // Remove the action buttons from the cloned content if they are inside the printable area
  const actionButtons = clonedContent.querySelector('.print\\:hidden');
  if (actionButtons) {
    actionButtons.remove();
  }

  // Append to existing body
  printDocument.body.appendChild(clonedContent);

  // Add script for auto print and close
  const script = printDocument.createElement('script');
  script.textContent = `
    window.addEventListener('load', () => {
      window.print();
      setTimeout(() => window.close(), 500);
    });
  `;
  printDocument.body.appendChild(script); // Append to existing body

  printDocument.close(); // Important to signal the document is complete
};
</script>