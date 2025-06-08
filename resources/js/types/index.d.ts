import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;


export interface TransaksiResponse {
  id: number;
  sub_total: number;
  diskon: number;
  total_bayar: number;
  total_kurang: number;
  metode_pembayaran: 'cash' | 'kredit';
  jatuh_tempo?: string;
  created_at: string;
  updated_at: string;
  user?: {
    id: number;
    name: string;
  };
  pelanggan?: {
    id: number;
    nama_pelanggan: string;
    nama_toko: string;
  };
  detailtransaksis?: Array<{
    id: number;
    jumlah: number;
    harga_satuan: number;
    total_harga: number;
    produk: {
      id: number;
      nama_produk: string;
    };
  }>;
}

