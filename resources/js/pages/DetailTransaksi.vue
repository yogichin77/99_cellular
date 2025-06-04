<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-4xl">
      <DialogHeader>
        <DialogTitle class="text-xl">Detail Transaksi #{{ transaction?.id }}</DialogTitle>
      </DialogHeader>
      
      <!-- Transaction Info -->
      <div class="space-y-4" v-if="transaction">
        <!-- General Info -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <Label class="font-medium">Tanggal Transaksi:</Label>
            <p>{{ formatDateTime(transaction.created_at) }}</p>
          </div>
          <div>
            <Label class="font-medium">Kasir:</Label>
            <p>{{ transaction.user?.name || '-' }}</p>
          </div>
          <div>
            <Label class="font-medium">Status Pembayaran:</Label>
            <Badge :class="statusClass(transaction.status_pembayaran)">
              {{ transaction.status_pembayaran?.toUpperCase() }}
            </Badge>
          </div>
          <div v-if="transaction.status_pembayaran === 'kredit'">
            <Label class="font-medium">Jatuh Tempo:</Label>
            <p>{{ transaction.jatuh_tempo ? formatDate(transaction.jatuh_tempo) : '-' }}</p>
          </div>
        </div>

        <!-- Customer Info -->
        <div v-if="transaction.pelanggan" class="space-y-2">
          <h3 class="font-medium">Info Pelanggan</h3>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <Label>Nama:</Label>
              <p>{{ transaction.pelanggan.nama_pelanggan }}</p>
            </div>
            <div>
              <Label>Toko:</Label>
              <p>{{ transaction.pelanggan.nama_toko || '-' }}</p>
            </div>
          </div>
        </div>

        <!-- Items Table -->
        <div class="border rounded-lg overflow-hidden">
          <Table>
            <TableHeader class="bg-muted">
              <TableRow>
                <TableHead>Produk</TableHead>
                <TableHead class="text-right">Harga Satuan</TableHead>
                <TableHead class="text-center">Jumlah</TableHead>
                <TableHead class="text-right">Total</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in transaction.detailtransaksis" :key="item.id">
                <TableCell>{{ item.produk.nama_produk }}</TableCell>
                <TableCell class="text-right">{{ formatCurrency(item.harga_satuan) }}</TableCell>
                <TableCell class="text-center">{{ item.jumlah }}</TableCell>
                <TableCell class="text-right">{{ formatCurrency(item.total_harga) }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- Payment Summary -->
        <div class="space-y-2">
          <div class="flex justify-between">
            <Label>Subtotal:</Label>
            <span>{{ formatCurrency(transaction.sub_total_bayar) }}</span>
          </div>
          <div class="flex justify-between">
            <Label>Diskon:</Label>
            <span>-{{ formatCurrency(transaction.diskon) }}</span>
          </div>
          <div class="flex justify-between font-semibold">
            <Label>Total Pembayaran:</Label>
            <span>{{ formatCurrency(transaction.total_bayar) }}</span>
          </div>
          <div v-if="transaction.status_pembayaran === 'kredit'" class="flex justify-between text-red-600">
            <Label>Total kredit:</Label>
            <span>{{ formatCurrency(transaction.total_kurang) }}</span>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { TransaksiResponse } from '@/types';

const props = defineProps<{
  transaction: TransaksiResponse | null;
  open: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
}>();

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value);
};

const formatDateTime = (dateString: string) => {
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

const statusClass = (status: 'cash' | 'kredit') => {
  return {
    'cash': 'bg-green-100 text-green-800',
    'kredit': 'bg-red-100 text-red-800',
  }[status];
};
</script>