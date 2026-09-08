<?php

namespace App\Livewire\Organizations;

use App\Actions\Files\DeleteMediaFile;
use App\Actions\Files\StoreMediaFile;
use App\Enums\MediaCategory;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class LogoUpload extends Component
{
    use WithFileUploads;

    public Organization $organization;

    public $logo;

    public bool $uploading = false;

    public function mount(Organization $organization): void
    {
        $this->organization = $organization;

        Gate::authorize('manageSettings', $this->organization);
    }

    public function upload(StoreMediaFile $action): void
    {
        Gate::authorize('manageSettings', $this->organization);

        $this->validate([
            'logo' => ['required', 'file', 'mimes:jpeg,png,webp,gif', 'max:2048'],
        ]);

        try {
            $action->handle($this->organization, auth()->user(), $this->logo, MediaCategory::OrganizationLogo);
            session()->flash('status', 'Logo uploaded.');
        } catch (ValidationException $e) {
            $this->addError('logo', $e->errors()['file'][0] ?? 'The logo could not be uploaded.');
        } finally {
            $this->logo = null;
            $this->uploading = false;
        }
    }

    public function remove(DeleteMediaFile $action): void
    {
        Gate::authorize('manageSettings', $this->organization);

        $logo = $this->organization->activeLogo();

        if ($logo === null) {
            return;
        }

        $action->handle($logo, auth()->user());
        session()->flash('status', 'Logo removed.');
    }

    public function render()
    {
        return view('livewire.organizations.logo-upload', [
            'logo' => $this->organization->activeLogo(),
        ]);
    }
}
