<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class Users extends Component
{
    use WithPagination;

    public string $search = '';

    // Modal state & Form fields
    public bool $showModal = false;
    public ?int $editingUserId = null;

    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $ruc_cedula = '';
    public bool $is_customer = false;
    public string $recovery_pin = '';

    // Spatie Roles and Permissions
    public string $selectedUserRole = '';
    public array $userPermissions = []; // format: ['permission_name' => true/false]

    protected function rules()
    {
        $userId = $this->editingUserId;

        return [
            'name' => 'required|string|max:255',
            'username' => 'required|string|alpha_dash|max:50|unique:users,username,' . $userId,
            'email' => 'required|string|email|max:255|unique:users,email,' . $userId,
            'password' => $userId ? 'nullable|string|min:8|confirmed' : 'required|string|min:8|confirmed',
            'ruc_cedula' => 'nullable|string|max:50',
            'recovery_pin' => 'required|numeric|digits:6',
            'selectedUserRole' => 'nullable|string|exists:roles,name',
        ];
    }

    public function mount(): void
    {
        abort_unless(Gate::allows('manage-users'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->generatePin();
        $this->showModal = true;
    }

    public function openEditModal(int $userId): void
    {
        $this->resetForm();
        $this->editingUserId = $userId;
        $user = User::findOrFail($userId);

        $this->name = $user->name;
        $this->username = $user->username ?? '';
        $this->email = $user->email;
        $this->ruc_cedula = $user->ruc_cedula ?? '';
        $this->is_customer = (bool) $user->is_customer;
        $this->recovery_pin = $user->recovery_pin ?? '';

        $this->selectedUserRole = $user->getRoleNames()->first() ?? '';

        // Load permissions state
        $allPermissions = Permission::all();
        foreach ($allPermissions as $perm) {
            $this->userPermissions[$perm->name] = $user->hasDirectPermission($perm->name);
        }

        $this->showModal = true;
    }

    public function generatePin(): void
    {
        $this->recovery_pin = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function saveUser()
    {
        abort_unless(Gate::allows('manage-users'), 403);
        $this->validate();

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $user->name = $this->name;
            $user->username = $this->username;
            $user->email = $this->email;
            $user->ruc_cedula = $this->ruc_cedula;
            $user->is_customer = $this->is_customer;
            $user->recovery_pin = $this->recovery_pin;

            if (!empty($this->password)) {
                $user->password = Hash::make($this->password);
            }

            $user->save();
            session()->flash('status', __('Usuario actualizado con éxito.'));
        } else {
            $user = User::create([
                'name' => $this->name,
                'username' => $this->username,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'ruc_cedula' => $this->ruc_cedula,
                'is_customer' => $this->is_customer,
                'recovery_pin' => $this->recovery_pin,
            ]);
            session()->flash('status', __('Usuario creado con éxito.'));
        }

        // Sync Spatie Role
        if (empty($this->selectedUserRole)) {
            $user->syncRoles([]);
        } else {
            $user->syncRoles([$this->selectedUserRole]);
        }

        // Sync Direct Permissions
        $checkedPermissions = [];
        foreach ($this->userPermissions as $permissionName => $checked) {
            if ($checked) {
                $checkedPermissions[] = $permissionName;
            }
        }
        $user->syncPermissions($checkedPermissions);

        // Clear Spatie cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->showModal = false;
        $this->resetForm();
    }

    public function deleteUser(int $userId): void
    {
        abort_unless(Gate::allows('manage-users'), 403);

        if (auth()->id() === $userId) {
            session()->flash('error', __('No puedes eliminar tu propio usuario.'));
            return;
        }

        $user = User::findOrFail($userId);
        $user->delete();

        session()->flash('status', __('Usuario eliminado con éxito.'));
    }

    private function resetForm(): void
    {
        $this->editingUserId = null;
        $this->name = '';
        $this->username = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->ruc_cedula = '';
        $this->is_customer = false;
        $this->recovery_pin = '';
        $this->selectedUserRole = '';
        $this->userPermissions = [];

        // Clear modal validation errors
        $this->resetErrorBag();
    }

    public function render()
    {
        abort_unless(Gate::allows('manage-users'), 403);

        $users = User::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('username', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('ruc_cedula', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->paginate(10);

        $roles = Role::orderBy('name')->pluck('name');
        $allPermissions = Permission::orderBy('name')->get();

        return view('livewire.admin.users', [
            'users' => $users,
            'roles' => $roles,
            'allPermissions' => $allPermissions,
        ])->layout('layouts.app', ['title' => __('Administración de Usuarios')]);
    }
}
