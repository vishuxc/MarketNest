<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ListingPublisher
{
    public function publish(User $owner, array $attributes, array $uploads, ?Listing $listing = null, array $removeIds = []): Listing
    {
        $paths = [];

        try {
            foreach ($uploads as $upload) {
                $path = $upload->store('listings', 'public');

                if ($path === false) {
                    throw ValidationException::withMessages(['images' => 'An image could not be saved. Please try again.']);
                }

                $paths[] = $path;
            }

            [$saved, $removed] = DB::transaction(function () use ($owner, $attributes, $listing, $removeIds, $paths) {
                if ($listing === null) {
                    $prefix = Str::limit(Str::slug($attributes['name']) ?: 'listing', 150, '');
                    $attributes['slug'] = $prefix.'-'.Str::lower((string) Str::ulid());
                    $saved = $owner->listings()->create($attributes);
                } else {
                    $saved = $owner->listings()->lockForUpdate()->findOrFail($listing->id);
                    $saved->update($attributes);
                }

                $removed = [];
                if ($removeIds !== []) {
                    $toRemove = $saved->images()->whereIn('id', $removeIds);
                    $removed = $toRemove->pluck('image_path')->all();
                    $toRemove->delete();
                }

                if ($paths === []) {
                    return [$saved, $removed];
                }

                $existingCount = $listing === null ? 0 : $saved->images()->count();
                if ($existingCount + count($paths) > 5) {
                    throw ValidationException::withMessages(['images' => 'A listing can have at most five images.']);
                }

                $nextOrder = $existingCount === 0 ? 0 : $saved->images()->max('sort_order') + 1;

                foreach ($paths as $path) {
                    $saved->images()->create(['image_path' => $path, 'sort_order' => $nextOrder++]);
                }

                return [$saved, $removed];
            });
        } catch (Throwable $exception) {
            $this->removeFiles($paths);

            throw $exception;
        }

        $this->removeFiles($removed);

        return $saved;
    }

    public function delete(User $owner, Listing $listing): void
    {
        $paths = DB::transaction(function () use ($owner, $listing) {
            $locked = $owner->listings()->lockForUpdate()->findOrFail($listing->id);
            $paths = $locked->images()->pluck('image_path')->all();
            $locked->delete();

            return $paths;
        });

        $this->removeFiles($paths);
    }

    private function removeFiles(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if (! ListingImage::isManagedPath($path)) {
                report(new RuntimeException('Skipped cleanup of an image path outside managed listing storage.'));

                continue;
            }

            try {
                if (! Storage::disk('public')->delete($path)) {
                    report(new RuntimeException('A listing image could not be removed: '.$path));
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
