<x-app-layout>
    <x-slot name="header">
        <p class="workspace-eyebrow">A TU MEDIDA</p>
        <h1>Tu perfil, tu espacio<span class="greeting-dot">.</span></h1>
        <p class="profile-intro">Mantén tus datos al día y cuida la seguridad de tu cuenta.</p>
    </x-slot>

    <div class="profile-workspace">
        <div>
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                @livewire('profile.update-profile-information-form')

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <div id="account-security" class="mt-10 sm:mt-0 scroll-mt-24">
                    @livewire('profile.update-password-form')
                </div>

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div class="mt-10 sm:mt-0">
                    @livewire('profile.two-factor-authentication-form')
                </div>

                <x-section-border />
            @endif

            <div class="mt-10 sm:mt-0">
                @livewire('profile.logout-other-browser-sessions-form')
            </div>

            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                <x-section-border />

                <div class="mt-10 sm:mt-0">
                    @livewire('profile.delete-user-form')
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
