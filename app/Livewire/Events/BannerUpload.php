<?php

namespace App\Livewire\Events;

use App\Actions\Files\DeleteMediaFile;
use App\Actions\Files\StoreMediaFile;
use App\Enums\MediaCategory;
use App\Models\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class BannerUpload extends Component
{
    use WithFileUploads;

    public Event $event;

    public $banner;

    public bool $uploading = false;

    public function mount(Event $event): void
    {
        $this->event = $event;

        Gate::authorize('update', $this->event);
    }

    public function upload(StoreMediaFile $action): void
    {
        Gate::authorize('update', $this->event);

        $this->validate([
            'banner' => ['required', 'file', 'mimes:jpeg,png,webp,gif', 'max:5120'],
        ]);

        try {
            $action->handle($this->event->organization, auth()->user(), $this->banner, MediaCategory::EventBanner, $this->event);
            session()->flash('status', 'Banner uploaded.');
        } catch (ValidationException $e) {
            $this->addError('banner', $e->errors()['file'][0] ?? 'The banner could not be uploaded.');
        } finally {
            $this->banner = null;
            $this->uploading = false;
        }
    }

    public function remove(DeleteMediaFile $action): void
    {
        Gate::authorize('update', $this->event);

        $banner = $this->event->banner();

        if ($banner === null) {
            return;
        }

        $action->handle($banner, auth()->user());
        session()->flash('status', 'Banner removed.');
    }

    public function render()
    {
        return view('livewire.events.banner-upload', [
            'banner' => $this->event->banner(),
        ]);
    }
}
