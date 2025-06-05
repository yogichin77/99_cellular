// src/lib/Idb_Pelanggan.ts
import { openDB } from 'idb';

const DB_NAME = 'PelangganOfflineDB'; // Nama database khusus untuk pelanggan
const STORE_NAME = 'pelanggan';     // Nama object store untuk pelanggan
const DB_VERSION = 1;               // Versi database

export const getPelangganDb = async () => {
    return await openDB(DB_NAME, DB_VERSION, {
        upgrade(db) {
            // Pastikan object store 'pelanggan' dibuat
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                // keyPath adalah 'id' karena data pelanggan memiliki properti 'id'
                // autoIncrement diatur false karena ID akan berasal dari server atau sementara
                db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: false });
            }
        },
    });
};

export const saveOfflinePelanggans = async (data: any) => {
    const db = await getPelangganDb();
    // Menggunakan .put() agar bisa update jika ID sudah ada (misalnya saat sync)
    await db.put(STORE_NAME, data);
};

export const getOfflinePelanggans = async () => {
    const db = await getPelangganDb();
    return await db.getAll(STORE_NAME);
};

export const clearOfflinePelanggans = async () => {
    const db = await getPelangganDb();
    await db.clear(STORE_NAME);
};

export const deleteOfflinePelanggan = async (id: number | string) => {
    const db = await getPelangganDb();
    await db.delete(STORE_NAME, id);
};