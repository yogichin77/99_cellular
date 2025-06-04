<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
<DialogContent class="max-w-full print:w-full print:max-w-full">
      <div class="print-struk p-4 text-sm" ref="strukContent">
        <!-- Header Toko -->
        <div class="text-center mb-4 border-b pb-2">
          <h1 class="text-lg font-bold">99 Cellular</h1>
          <p class="text-xs">Jl.Oevang Oeray Sintang</p>
          <p class="text-xs">Telp: (021) 12345678</p>
        </div>

        <!-- Info Transaksi -->
        <div class="mb-3 space-y-1 text-xs">
          <div class="flex justify-between">
            <span class="font-medium">No. Transaksi:</span>
            <span>#{{ transaction?.id }}</span>
          </div>
          <div class="flex justify-between">
            <span class="font-medium">Tanggal:</span>
            <span>{{ formatDateTime(transaction?.created_at) }}</span>
          </div>
          <div class="flex justify-between" v-if="transaction?.user">
            <span class="font-medium">Kasir:</span>
            <span>{{ transaction.user.name }}</span>
          </div>
        </div>

        <!-- Info Pelanggan -->
        <div v-if="transaction?.pelanggan" class="mb-3 p-2 rounded">
          <div class="flex justify-between">
            <span class="font-medium">Pelanggan:</span>
            <span>{{ transaction.pelanggan.nama_pelanggan }}</span>
          </div>
          <div class="flex justify-between">
            <span class="font-medium">Toko:</span>
            <span>{{ transaction.pelanggan.nama_toko || '-' }}</span>
          </div>
        </div>

        <!-- Daftar Produk -->
        <div class="mb-4">
          <div class="grid grid-cols-12 gap-1 font-medium mb-1 text-xs">
            <div class="col-span-6">Produk</div>
            <div class="col-span-2 text-right">Harga</div>
            <div class="col-span-2 text-center">Qty</div>
            <div class="col-span-2 text-right">Total</div>
          </div>

          <template v-if="transaction?.detailtransaksis?.length">
            <div v-for="item in transaction.detailtransaksis" :key="item.id"
              class="grid grid-cols-12 gap-1 py-1 text-xs border-b border-dashed">
              <div class="col-span-6">{{ item.produk.nama_produk }}</div>
              <div class="col-span-2 text-right">{{ formatCurrency(item.harga_satuan) }}</div>
              <div class="col-span-2 text-center">{{ item.jumlah }}</div>
              <div class="col-span-2 text-right">{{ formatCurrency(item.total_harga) }}</div>
            </div>
          </template>
        </div>

        <!-- Ringkasan Pembayaran -->
        <div class="space-y-2 text-xs">
          <div class="flex justify-between">
            <span class="font-medium">Sub total:</span>
            <span>{{ formatCurrency(transaction?.sub_total_bayar) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="font-medium">Diskon:</span>
            <span>-{{ formatCurrency(transaction?.diskon) }}</span>
          </div>
          <div class="flex justify-between font-bold border-t pt-2">
            <span>Total Pembayaran:</span>
            <span>{{ formatCurrency(transaction?.total_bayar) }}</span>
          </div>
          <div v-if="transaction?.status_pembayaran === 'kredit'" class="flex justify-between text-red-600">
            <span>Total kredit:</span>
            <span>{{ formatCurrency(transaction?.total_kurang) }}</span>
          </div>
          <div v-if="transaction?.status_pembayaran === 'kredit'">
            <Label class="font-medium">Jatuh Tempo:</Label>
            <p>{{ transaction.jatuh_tempo ? formatDate(transaction.jatuh_tempo) : '-' }}</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4 text-[10px] border-t pt-2">
          <p>Terima kasih telah berbelanja</p>
          <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</p>
        </div>
      </div>

      <!-- Tombol Cetak -->
      <div class="flex gap-2 justify-end mt-4 print:hidden">
        <Button variant="outline" @click="$emit('update:open', false)">Tutup</Button>
        <Button @click="handlePrint">
          <Printer class="w-4 h-4 mr-2" /> Cetak Struk
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import type { TransaksiResponse } from '@/types';
import { Printer } from 'lucide-vue-next';
import { watch } from 'vue';

const props = defineProps<{
  transaction: TransaksiResponse | null;
  open: boolean;
}>();

const formatCurrency = (value?: number) => {
  if (!value) return 'Rp0';
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value);
};

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

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const handlePrint = () => {
  // Hapus event listener sebelumnya
  window.removeEventListener('afterprint', afterPrintHandler);

  // Tambahkan event listener baru
  window.addEventListener('afterprint', afterPrintHandler);
  window.print();
};
const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
}>();
const afterPrintHandler = () => {
  // Reset state setelah cetak
  window.removeEventListener('afterprint', afterPrintHandler);
  emit('update:open', false);
};
// Debugging data
watch(() => props.transaction, (newVal) => {
  console.log('Struk Data:', JSON.parse(JSON.stringify(newVal)));
}, { deep: true });
</script>

<style>
@media print {
  @page {
    size: A4;
    margin: 0;
  }

  body {
    margin: 0;
    padding: 0;
  }

  .print-struk {

    width: 100%;
    box-sizing: border-box;
    font-size: 13px;
    font-family: monospace;
  }
}
</style>