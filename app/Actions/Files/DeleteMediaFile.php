<?php

namespace App\Actions\Files;

use App\Models\MediaFile;
use App\Models\User;
use App\Support\Demo\DemoGate;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DeleteMediaFile
{
    public function handle(MediaFile $media, User $actor): void
    {
        DemoGate::denyOnDemoOrganization(
            $media->organization_id,
            __('Demo mode does not allow permanently deleting files.'),
        );

        if ($media->category->isEventBound() && $media->event !== null) {
            Gate::forUser($actor)->authorize('update', $media->event);
        } else {
            Gate::forUser($actor)->authorize('manageSettings', $media->organization);
        }

        Storage::disk($media->disk)->delete($media->path);

        $media->delete();
    }
}
