
<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Paramètres du profil')] class extends Component {
    use ProfileValidationRules;
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public $avatar = null;

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            ...$this->profileRules($user->id),
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $newAvatarPath = $this->avatar?->storePublicly('avatars', 'public');
        unset($validated['avatar']);

        $user->fill($validated);

        $oldAvatarPath = null;
        if ($newAvatarPath) {
            $oldAvatarPath = $user->avatar_path;
            $user->avatar_path = $newAvatarPath;
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($oldAvatarPath) {
            Storage::disk('public')->delete($oldAvatarPath);
        }

        $this->avatar = null;

        Flux::toast(variant: 'success', text: 'Profil mis à jour.');
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();

        if (filled($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }

        $this->avatar = null;
        Flux::toast(variant: 'success', text: 'Photo de profil supprimée.');
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        $user = Auth::user();

        return $user?->hasRole('admin') === true
            && (! ($user instanceof MustVerifyEmail) || $user->hasVerifiedEmail());
    }
}; ?>

<section class="w-full pbb-settings">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">
        Paramètres du profil
    </flux:heading>

    <x-pages::settings.layout
        :heading="'Mon profil'"
        :subheading="'Modifiez votre nom et votre adresse e-mail'">

        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">

            <div class="pbb-profile-avatar">
                @if ($avatar)
                    <img src="{{ $avatar->temporaryUrl() }}" alt="Aperçu de la photo de profil" class="h-16 w-16 rounded-full object-cover">
                @elseif (auth()->user()->avatarUrl())
                    <img src="{{ auth()->user()->avatarUrl() }}" alt="Photo de profil" referrerpolicy="no-referrer" class="h-20 w-20 rounded-full object-cover ring-2 ring-green-100">
                @else
                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-green-800 text-xl font-bold text-white">{{ auth()->user()->initials() }}</div>
                @endif
                <div class="pbb-avatar-controls">
                    <strong>Photo de profil</strong>
                    <label class="pbb-avatar-upload"><span>Choisir une image</span><input wire:model="avatar" type="file" accept="image/jpeg,image/png,image/webp" aria-label="Choisir une photo de profil"></label>
                    <span>JPG, PNG ou WebP, 3 Mo maximum.</span>
                    @if ($avatar)<span>{{ $avatar->getClientOriginalName() }}</span>@endif
                    @if (auth()->user()->avatar_path)<button type="button" class="pbb-avatar-remove" wire:click="removeAvatar">Supprimer la photo</button>@endif
                    @error('avatar') <span class="pbb-avatar-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <flux:input
                wire:model="name"
                label="Nom"
                type="text"
                required
                autofocus
                autocomplete="name"
            />

            <div>
                <flux:input
                    wire:model="email"
                    label="Adresse e-mail"
                    type="email"
                    required
                    autocomplete="email"
                />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            Votre adresse e-mail n'est pas encore vérifiée.

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                Cliquez ici pour renvoyer l'e-mail de vérification.
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                Un nouveau lien de vérification a été envoyé à votre adresse e-mail.
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full pbb-profile-submit" data-test="update-profile-button">
                        Enregistrer
                    </flux:button>
                </div>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif

    </x-pages::settings.layout>
</section>
