<div class="flex flex-col gap-6">
    <x-auth-header 
        :title="$step === 1 ? __('Reset Password') : __('New Password')" 
        :description="$step === 1 ? __('Enter your username, Cedula or RUC and your account\'s recovery PIN') : __('Choose your new access password')" 
    />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    @if ($step === 1)
        <!-- Step 1 Form -->
        <form wire:submit="verifyPin" class="flex flex-col gap-6">
            <!-- Username -->
            <div>
                <flux:input
                    wire:model="username"
                    name="username"
                    :label="__('Username, Cedula or RUC')"
                    type="text"
                    required
                    autofocus
                    :placeholder="__('Username, Cedula or RUC')"
                />
            </div>

            <!-- Recovery PIN -->
            <div>
                <flux:input
                    wire:model="recovery_pin"
                    name="recovery_pin"
                    :label="__('Recovery PIN')"
                    type="password"
                    required
                    :placeholder="__('Enter your PIN')"
                    autocomplete="new-password"
                />
            </div>

            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Verify Identity') }}
            </flux:button>
        </form>
    @else
        <!-- Step 2 Form -->
        <form wire:submit="resetPassword" class="flex flex-col gap-6">
            <!-- Password -->
            <div>
                <flux:input
                    wire:model="password"
                    name="password"
                    :label="__('New Password')"
                    type="password"
                    required
                    :placeholder="__('Minimum 8 characters')"
                    viewable
                />
            </div>

            <!-- Confirm Password -->
            <div>
                <flux:input
                    wire:model="password_confirmation"
                    name="password_confirmation"
                    :label="__('Confirm New Password')"
                    type="password"
                    required
                    :placeholder="__('Confirm password')"
                    viewable
                />
            </div>

            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Set Password') }}
            </flux:button>
        </form>
    @endif

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
        <span>{{ __('Or, return to') }}</span>
        <flux:link :href="route('login')" wire:navigate>{{ __('log in') }}</flux:link>
    </div>
</div>
