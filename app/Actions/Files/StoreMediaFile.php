<?php

namespace App\Actions\Files;

use App\Enums\MediaCategory;
use App\Models\Event;
use App\Models\MediaFile;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreMediaFile
{
    /**
     * Content-derived MIME to canonical extension map. The stored extension is
     * never taken from the client filename.
     */
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function handle(
        Organization $organization,
        User $actor,
        UploadedFile $file,
        MediaCategory $category,
        ?Event $event = null,
        string $disk = 'public'
    ): MediaFile {
        $this->gate($organization, $actor, $category, $event);
        $this->validate($organization, $file, $category, $event);

        $mime = strtolower((string) $file->getMimeType());
        $extension = self::MIME_EXTENSIONS[$mime];
        $directory = $category->isEventBound() ? $category->value.'/'.$event->id : $category->value.'/'.$organization->id;
        $name = (string) Str::uuid().'.'.$extension;

        $path = Storage::disk($disk)->putFileAs($directory, $file, $name, 'public');

        if ($path === false) {
            throw new \RuntimeException('The file could not be stored on the configured filesystem.');
        }

        try {
            return DB::transaction(function () use ($organization, $event, $actor, $category, $disk, $path, $file, $mime, $extension) {
                $query = MediaFile::query()
                    ->where('is_active', true)
                    ->where('category', $category->value)
                    ->when($event !== null, fn ($q) => $q->where('event_id', $event->id));

                $query->update(['is_active' => false]);

                return MediaFile::create([
                    'organization_id' => $organization->id,
                    'event_id' => $event?->id,
                    'uploaded_by_user_id' => $actor->id,
                    'category' => $category->value,
                    'visibility' => 'public',
                    'is_active' => true,
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $mime,
                    'extension' => $extension,
                    'size_bytes' => $file->getSize(),
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk($disk)->delete($path);

            throw $e;
        }
    }

    private function gate(Organization $organization, User $actor, MediaCategory $category, ?Event $event): void
    {
        if ($category->isEventBound() && $event !== null) {
            Gate::forUser($actor)->authorize('update', $event);

            return;
        }

        Gate::forUser($actor)->authorize('manageSettings', $organization);
    }

    /**
     * @throws ValidationException
     */
    private function validate(Organization $organization, UploadedFile $file, MediaCategory $category, ?Event $event): void
    {
        if ($category->isEventBound() && $event !== null && $event->organization_id !== $organization->id) {
            throw ValidationException::withMessages([
                'event' => 'The event does not belong to this organization.',
            ]);
        }

        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw ValidationException::withMessages([
                'file' => 'The upload failed. Please try again.',
            ]);
        }

        $mime = strtolower((string) $file->getMimeType());

        if (! isset(self::MIME_EXTENSIONS[$mime])) {
            throw ValidationException::withMessages([
                'file' => 'Unsupported file type. Only JPEG, PNG, WebP, and GIF images are allowed.',
            ]);
        }

        if ($file->getSize() > $category->maxSizeBytes()) {
            throw ValidationException::withMessages([
                'file' => 'The file exceeds the maximum allowed size of '.ceil($category->maxSizeBytes() / 1048576).' MB.',
            ]);
        }
    }
}
