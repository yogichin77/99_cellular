<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import { computed, onMounted, ref } from 'vue';
import { PlusCircle, Pencil, Trash2, X, Check, Search, CheckCircle, XCircle } from 'lucide-vue-next';

// Shadcn UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'User', href: '/user' }];

// State
const users = ref<any[]>([]);
const editingId = ref<number | null>(null);
const searchTerm = ref('');
const isLoading = ref(false);
const isSubmitting = ref(false);

// Form state
const form = ref({
    name: '',
    email: '',
    role: '',
    password: '',
    password_confirmation: ''
});

// Fetch all users
const fetchUsers = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get('/api/user');
        users.value = response.data.data;
    } catch (error) {
        console.error('Error fetching data:', error);
        showError('Gagal memuat data');
    } finally {
        isLoading.value = false;
    }
};

// Filter users
const filteredUsers = computed(() => {
    if (!searchTerm.value.trim()) return users.value;

    return users.value.filter(item =>
        item.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
        item.email.toLowerCase().includes(searchTerm.value.toLowerCase())
    );
});

// Submit form
const submitForm = async () => {
    try {
        isSubmitting.value = true;

        if (editingId.value) {
            // Update user
            let data: any = {
                name: form.value.name,
                email: form.value.email,
                role: form.value.role
            };

            // Jika password diisi, tambahkan password dan konfirmasi
            if (form.value.password) {
                data.password = form.value.password;
                data.password_confirmation = form.value.password_confirmation;
            }

            await axios.put(`/api/user/${editingId.value}`, data);
            showSuccess('User berhasil diperbarui');
        } else {
            // Create user
            await axios.post('/api/user', {
                ...form.value
            });
            showSuccess('User berhasil ditambahkan');
        }

        resetForm();
        await fetchUsers();
    } catch (error) {
        handleError(error);
    } finally {
        isSubmitting.value = false;
    }
};

// Edit user
const editUser = (item: any) => {
    form.value = {
        name: item.name,
        email: item.email,
        role: item.role,
        password: '',
        password_confirmation: ''
    };
    editingId.value = item.id;
};

// Delete user
const deleteUser = async (id: number) => {
    const result = await Swal.fire({
        title: 'Yakin menghapus user?',
        text: 'Data tidak bisa dikembalikan setelah dihapus',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
    });

    if (result.isConfirmed) {
        try {
            await axios.delete(`/api/user/${id}`);
            await fetchUsers();
            showSuccess('User berhasil dihapus');
        } catch (error) {
            handleError(error);
        }
    }
};

// Toggle verification status
const toggleVerification = async (id: number) => {
    try {
        const user = users.value.find(u => u.id === id);
        if (!user) return;

        const newStatus = !user.email_verified_at;
        const message = newStatus
            ? 'Yakin verifikasi akun ini?'
            : 'Yakin membatalkan verifikasi akun ini?';

        const result = await Swal.fire({
            title: 'Konfirmasi',
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#3b82f6',
        });

        if (result.isConfirmed) {
            await axios.put(`/api/user/${id}`, {
                email_verified_at: newStatus ? new Date().toISOString() : null
            });

            showSuccess(newStatus
                ? 'Akun berhasil diverifikasi'
                : 'Verifikasi akun berhasil dibatalkan');

            await fetchUsers();
        }
    } catch (error) {
        handleError(error);
    }
};

// Reset form
const resetForm = () => {
    form.value = {
        name: '',
        email: '',
        role: '',
        password: '',
        password_confirmation: '',
    };
    editingId.value = null;
};

// Handle error
const handleError = (error: any) => {
    console.error('Error:', error);
    let errorMessage = 'Gagal menyimpan user';

    if (error.response?.data?.errors) {
        errorMessage = Object.entries(error.response.data.errors)
            .map(([field, messages]) => {
                const fieldName = field.replace(/_/g, ' ');
                return `<b>${fieldName}</b>: ${(messages as string[]).join(', ')}`;
            })
            .join('<br>');
    }

    showError(errorMessage);
};

// Show notifications
const showSuccess = (message: string) => {
    Swal.fire({
        title: 'Berhasil!',
        text: message,
        icon: 'success',
        confirmButtonColor: '#3b82f6',
    });
};

const showError = (message: string) => {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        html: message,
        confirmButtonColor: '#3b82f6',
    });
};

onMounted(fetchUsers);
</script>

<template>

    <Head title="User" />
    <AppLayout :breadcrumbs="breadcrumbs">
    <div class="w-full mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Search Section -->
            <Card class="mb-4">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Search class="w-5 h-5" />
                        Cari User
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="relative max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search class="h-5 w-5 text-muted-foreground" />
                        </div>
                        <Input v-model.trim="searchTerm" type="search" placeholder="Cari user..." class="pl-10" />
                    </div>
                </CardContent>
            </Card>

            <!-- Form Section -->
            <Card class="mb-4">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <PlusCircle class="w-5 h-5" />
                        {{ editingId ? 'Edit User' : 'Tambah User Baru' }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <!-- Nama User -->
                            <div class="space-y-2">
                                <Label for="name">
                                    Nama <span class="text-destructive">*</span>
                                </Label>
                                <Input v-model.trim="form.name" id="name" placeholder="Nama user" required
                                    :disabled="isSubmitting" />
                            </div>

                            <!-- Email -->
                            <div class="space-y-2">
                                <Label for="email">
                                    Email <span class="text-destructive">*</span>
                                </Label>
                                <Input v-model.trim="form.email" id="email" type="email" placeholder="Email user"
                                    required :disabled="isSubmitting" />
                            </div>

                            <!-- Role -->
                            <div class="space-y-2">
                                <Label for="role">
                                    Role <span class="text-destructive">*</span>
                                </Label>
                                <Select v-model="form.role" required :disabled="isSubmitting">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih Role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="admin">Admin</SelectItem>
                                        <SelectItem value="kasir">Kasir</SelectItem>
                                        <SelectItem value="pramuniaga">Pramuniaga</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <!-- Right Column - Password -->
                        <div class="space-y-4">
                            <!-- Password -->
                            <div class="space-y-2">
                                <Label for="password">
                                    Password {{ editingId ? '(Biarkan kosong jika tidak ingin mengubah)' : '' }}
                                    <span v-if="!editingId" class="text-destructive">*</span>
                                </Label>
                                <Input v-model.trim="form.password" id="password" type="password" :required="!editingId"
                                    :placeholder="editingId ? 'Kosongkan jika tidak diubah' : 'Password'"
                                    :disabled="isSubmitting" />
                            </div>

                            <!-- Konfirmasi Password -->
                            <div class="space-y-2">
                                <Label for="password_confirmation">
                                    Konfirmasi Password <span v-if="!editingId" class="text-destructive">*</span>
                                </Label>
                                <Input v-model.trim="form.password_confirmation" id="password_confirmation"
                                    type="password" :required="!editingId"
                                    :placeholder="editingId ? 'Kosongkan jika tidak diubah' : 'Konfirmasi Password'"
                                    :disabled="isSubmitting" />
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="md:col-span-2 flex justify-end gap-3 pt-4">
                            <Button v-if="editingId" @click="resetForm" type="button" variant="outline"
                                :disabled="isSubmitting">
                                <X class="w-4 h-4 mr-2" />
                                Batal
                            </Button>
                            <Button type="submit" :disabled="isSubmitting">
                                <Check class="w-4 h-4 mr-2" />
                                {{ editingId ? 'Update User' : 'Tambah User' }}
                                <span v-if="isSubmitting" class="ml-2">
                                    <span class="loading-dots">
                                        <span>.</span><span>.</span><span>.</span>
                                    </span>
                                </span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Table Section -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        Daftar User
                        <Badge variant="outline" class="px-2 py-1">
                            {{ users.length }} user
                        </Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Status Verifikasi</TableHead>
                                    <TableHead class="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="isLoading">
                                    <TableRow v-for="i in 5" :key="i">
                                        <TableCell>
                                            <Skeleton class="h-4 w-[120px]" />
                                        </TableCell>
                                        <TableCell>
                                            <Skeleton class="h-4 w-[200px]" />
                                        </TableCell>
                                        <TableCell>
                                            <Skeleton class="h-4 w-[80px]" />
                                        </TableCell>
                                        <TableCell>
                                            <Skeleton class="h-4 w-[100px]" />
                                        </TableCell>
                                        <TableCell class="flex justify-end gap-2">
                                            <Skeleton class="h-8 w-8" />
                                            <Skeleton class="h-8 w-8" />
                                            <Skeleton class="h-8 w-8" />
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <template v-else>
                                    <TableRow v-for="item in filteredUsers" :key="item.id"
                                        class="group hover:bg-muted/50 transition-colors">
                                        <TableCell class="font-medium">
                                            {{ item.name }}
                                        </TableCell>
                                        <TableCell>
                                            {{ item.email }}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                :variant="item.role === 'admin' ? 'default' : item.role === 'admin' ? 'secondary' : 'outline'">
                                                {{ item.role }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge v-if="item.email_verified_at" variant="success">
                                                <CheckCircle class="w-4 h-4 mr-1" />
                                                Terverifikasi
                                            </Badge>
                                            <Badge v-else variant="destructive">
                                                <XCircle class="w-4 h-4 mr-1" />
                                                Belum Terverifikasi
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-right space-x-2">
                                            <Button @click="toggleVerification(item.id)" variant="ghost" size="sm"
                                                class="h-8 px-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                                :title="item.email_verified_at ? 'Batalkan Verifikasi' : 'Verifikasi Akun'">
                                                <CheckCircle v-if="!item.email_verified_at"
                                                    class="h-4 w-4 text-green-600" />
                                                <XCircle v-else class="h-4 w-4 text-yellow-600" />
                                            </Button>
                                            <Button @click="editUser(item)" variant="ghost" size="sm"
                                                class="h-8 px-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                            <Button @click="deleteUser(item.id)" variant="ghost" size="sm"
                                                class="h-8 px-2 text-destructive hover:text-destructive opacity-0 group-hover:opacity-100 transition-opacity">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="filteredUsers.length === 0 && !isLoading">
                                        <TableCell colspan="5" class="text-center text-muted-foreground py-8">
                                            Tidak ada user yang ditemukan
                                        </TableCell>
                                    </TableRow>
                                </template>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
.loading-dots {
    display: inline-flex;
    gap: 2px;
}

.loading-dots span {
    animation: blink 1.4s infinite both;
    animation-delay: calc(var(--index) * 0.2s);
}

.loading-dots span:nth-child(1) {
    --index: 1;
}

.loading-dots span:nth-child(2) {
    --index: 2;
}

.loading-dots span:nth-child(3) {
    --index: 3;
}

@keyframes blink {

    0%,
    100% {
        opacity: 0.2;
    }

    50% {
        opacity: 1;
    }
}

@media (max-width: 640px) {
    .group-hover\:opacity-100 {
        opacity: 1 !important;
    }
}
</style>