import { openDB } from 'idb';

const DB_NAME = 'TransaksiOfflineDB';
const STORE_NAME = 'transaksi';

export const getDb = async () => {
    return await openDB(DB_NAME, 1, {
        upgrade(db) {
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
            }
        },
    });
};

export const saveOfflineTransaksi = async (data: any) => {
    const db = await getDb();
    await db.add(STORE_NAME, data);
};

export const getAllOfflineTransaksis = async () => {
    const db = await getDb();
    return await db.getAll(STORE_NAME);
};

export const clearOfflineTransaksis = async () => {
    const db = await getDb();
    await db.clear(STORE_NAME);
};
