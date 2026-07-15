<div class="invoice-container">
    {{-- Header Bar --}}
    <div class="bg-gray-900 border-b border-gray-800 px-6 py-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">
                Administración de <span class="text-indigo-400">Usuarios</span>
            </h1>
            <p class="text-gray-400 text-sm mt-0.5">Crea, edita, elimina y gestiona los roles, permisos y pines de acceso de los usuarios.</p>
        </div>

        <div>
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                Nuevo Usuario
            </flux:button>
        </div>
    </div>

    {{-- Messages --}}
    @if (session()->has('status'))
        <div class="mx-6 mt-4 p-4 rounded-lg bg-green-500/10 border border-green-500/20 text-green-400 text-sm">
            {{ session('status') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mx-6 mt-4 p-4 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filter Bar --}}
    <div class="px-6 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <flux:input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nombre, usuario, email..."
                icon="magnifying-glass"
                class="w-80"
            />
        </div>
    </div>

    {{-- Table --}}
    <div class="px-6 pb-6">
        <div class="invoice-card">
            <div class="overflow-x-auto">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th class="px-3 py-3 text-left">Nombre</th>
                            <th class="px-3 py-3 text-left">Usuario / Email</th>
                            <th class="px-3 py-3 text-left">RUC / Cédula</th>
                            <th class="px-3 py-3 text-center">Tipo Cliente</th>
                            <th class="px-3 py-3 text-center">PIN Recuperación</th>
                            <th class="px-3 py-3 text-left">Roles</th>
                            <th class="px-3 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @forelse ($users as $user)
                            <tr class="bg-gray-900 hover:bg-gray-800/70 transition-colors">
                                <td class="px-3 py-3">
                                    <div class="font-bold text-white">{{ $user->name }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="text-sm font-mono text-zinc-300">{{ $user->username ?? '-' }}</div>
                                    <div class="text-xs text-gray-400">{{ $user->email }}</div>
                                </td>
                                <td class="px-3 py-3 font-mono text-gray-300">
                                    {{ $user->ruc_cedula ?? '-' }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if ($user->is_customer)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            Cliente
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-zinc-800 text-zinc-400">
                                            Interno
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="font-mono bg-zinc-950 px-2 py-1 rounded border border-zinc-800 text-yellow-400 font-bold text-sm tracking-widest">
                                        {{ $user->recovery_pin ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse ($user->getRoleNames() as $roleName)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                                {{ $roleName }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-gray-500 italic">Ninguno</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <div class="inline-flex gap-2">
                                        <flux:button size="sm" variant="filled" wire:click="openEditModal({{ $user->id }})">
                                            Editar
                                        </flux:button>
                                        <flux:button size="sm" variant="danger" wire:click="deleteUser({{ $user->id }})" 
                                            confirm="¿Estás seguro de que deseas eliminar este usuario?">
                                            Eliminar
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-6 text-center text-gray-500 italic">
                                    No se encontraron usuarios.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination --}}
            @if ($users->hasPages())
                <div class="px-6 py-4 border-t border-gray-800">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- User Modal --}}
    <flux:modal name="user-modal" wire:model="showModal" class="max-w-xl md:min-w-[32rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingUserId ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}</flux:heading>
                <flux:subheading>Completa los datos del perfil y accesos del usuario.</flux:subheading>
            </div>

            <form wire:submit="saveUser" class="space-y-5">
                {{-- Name --}}
                <flux:input wire:model="name" :label="__('Nombre Completo')" required />
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Username --}}
                    <flux:input wire:model="username" :label="__('Nombre de Usuario')" required />
                    
                    {{-- Email --}}
                    <flux:input wire:model="email" type="email" :label="__('Correo Electrónico')" required />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- RUC/Cédula --}}
                    <flux:input wire:model="ruc_cedula" :label="__('RUC o Cédula')" />
                    
                    {{-- Recovery PIN --}}
                    <div class="flex flex-col">
                        <label class="text-sm font-medium text-zinc-800 dark:text-zinc-200 mb-1">PIN de Recuperación</label>
                        <div class="flex gap-2">
                            <flux:input wire:model="recovery_pin" class="flex-1 font-mono tracking-widest text-center" maxlength="6" required />
                            <flux:button type="button" variant="filled" wire:click="generatePin" class="shrink-0">
                                Generar
                            </flux:button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Password --}}
                    <flux:input wire:model="password" type="password" :label="__('Contraseña')" :placeholder="$editingUserId ? 'Dejar en blanco si no deseas cambiarla' : ''" :required="!$editingUserId" viewable />
                    
                    {{-- Password Confirmation --}}
                    <flux:input wire:model="password_confirmation" type="password" :label="__('Confirmar Contraseña')" :required="!$editingUserId" viewable />
                </div>

                {{-- is_customer --}}
                <flux:checkbox wire:model="is_customer" :label="__('¿Es un cliente externo?')" />

                <flux:separator />

                {{-- Roles --}}
                <div>
                    <label class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 block mb-2">Rol Asignado</label>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300 cursor-pointer">
                            <input type="radio" wire:model="selectedUserRole" value="" class="rounded border-zinc-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <span>Ninguno (Usuario General)</span>
                        </label>
                        @foreach ($roles as $roleName)
                            <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300 cursor-pointer">
                                <input type="radio" wire:model="selectedUserRole" value="{{ $roleName }}" class="rounded border-zinc-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <span>{{ $roleName }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Direct Permissions --}}
                @if (count($allPermissions) > 0)
                    <flux:separator />
                    <div>
                        <label class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 block mb-2">Permisos Directos Adicionales</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach ($allPermissions as $perm)
                                <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300 cursor-pointer">
                                    <input type="checkbox" wire:model="userPermissions.{{ $perm->name }}" class="rounded border-zinc-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span>{{ $perm->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex justify-end space-x-2 rtl:space-x-reverse pt-4">
                    <flux:modal.close>
                        <flux:button variant="filled">Cancelar</flux:button>
                    </flux:modal.close>

                    <flux:button variant="primary" type="submit">Guardar</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
