import { openDB, type IDBPDatabase } from 'idb';

const DB_NAME = 'kasir_db';
const DB_VERSION = 1;
const PRODUCT_STORE = 'produks';
const PELANGGAN_STORE = 'pelanggans';
const TRANSAKSI_STORE = 'transaksis';

let db: IDBPDatabase;

async function getDb(): Promise<IDBPDatabase> {
    if (!db) {
        db = await openDB(DB_NAME, DB_VERSION, {
            upgrade(db) {
                if (!db.objectStoreNames.contains(PRODUCT_STORE)) {
                    db.createObjectStore(PRODUCT_STORE, { keyPath: 'id' });
                }
                if (!db.objectStoreNames.contains(PELANGGAN_STORE)) {
                    db.createObjectStore(PELANGGAN_STORE, { keyPath: 'id' });
                }
                if (!db.objectStoreNames.contains(TRANSAKSI_STORE)) {
                    // For transactions, autoIncrement is usually preferred if you don't have a unique key.
                    // If 'id' is coming from the server, then 'id' as keyPath would be better.
                    // Assuming you want an auto-generated ID here based on your existing code.
                    db.createObjectStore(TRANSAKSI_STORE, { autoIncrement: true });
                }
            },
        });
    }
    return db;
}

// --- Product Functions ---
export async function saveOfflineProduks(produks: any[]): Promise<void> {
    const db = await getDb();
    const tx = db.transaction(PRODUCT_STORE, 'readwrite');
    const store = tx.objectStore(PRODUCT_STORE);

    // Clear old data to ensure fresh sync
    await store.clear();

    // Iterate through each product and put it into the store
    for (const produk of produks) {
        // This is where you should add your console.log to inspect the 'produk' object
        // right before it's stored, if you still face DataCloneError after this fix.
        console.log('Attempting to put produk:', produk);
        await store.put(produk);
    }

    // Wait for the transaction to complete
    await tx.done;
}

export async function getAllOfflineProduks(): Promise<any[]> {
    const db = await getDb();
    return db.getAll(PRODUCT_STORE);
}

// --- Pelanggan Functions ---
export async function saveOfflinePelanggans(pelanggans: any[]): Promise<void> {
    const db = await getDb();
    const tx = db.transaction(PELANGGAN_STORE, 'readwrite');
    const store = tx.objectStore(PELANGGAN_STORE);
    await store.clear(); // Clear old data to ensure fresh sync
    for (const pelanggan of pelanggans) {
        await store.put(pelanggan);
    }
    await tx.done;
}

export async function getAllOfflinePelanggans(): Promise<any[]> {
    const db = await getDb();
    return db.getAll(PELANGGAN_STORE);
}

// --- Transaksi Functions (ensure existing logic aligns with getDb and constants) ---
interface OfflineTransaksiPayload {
    sub_total_bayar: number;
    diskon: number;
    total_bayar: number;
    status_pembayaran: 'cash' | 'kredit';
    jatuh_tempo?: string | null;
    id_pelanggan: number | null;
    id_user: number;
    items: Array<{
        id_produk: number;
        jumlah: number;
        total_harga: number;
    }>;
    // Add original fields for saving to IndexedDB
    subtotal: number;
    total_kurang: number;
}

export async function saveOfflineTransaksi(transaksi: OfflineTransaksiPayload): Promise<void> {
    const db = await getDb();
    const tx = db.transaction(TRANSAKSI_STORE, 'readwrite');
    const store = tx.objectStore(TRANSAKSI_STORE);
    await store.add(transaksi);
    await tx.done;
}

export async function getAllOfflineTransaksis(): Promise<OfflineTransaksiPayload[]> {
    const db = await getDb();
    return db.getAll(TRANSAKSI_STORE);
}

export async function clearOfflineTransaksis(): Promise<void> {
    const db = await getDb();
    const tx = db.transaction(TRANSAKSI_STORE, 'readwrite');
    await tx.objectStore(TRANSAKSI_STORE).clear();
    await tx.done;
}