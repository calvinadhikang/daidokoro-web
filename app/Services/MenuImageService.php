<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MenuImageService
{
    public function upload(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $filename = Str::uuid()->toString().'.'.$extension;

        $options = [];

        if ($this->diskName() === 'public') {
            $options['visibility'] = $this->visibility();
        }

        $this->disk()->putFileAs(
            $this->uploadDirectory(),
            $file,
            $filename,
            $options,
        );

        return $this->publicUrl($this->objectPath($filename));
    }

    public function deleteIfStored(?string $imageUrl): void
    {
        if ($imageUrl === null || $imageUrl === '') {
            return;
        }

        $objectPath = $this->objectPathFromUrl($imageUrl);

        if ($objectPath === null) {
            return;
        }

        $storagePath = $this->storagePathFromObjectPath($objectPath);

        if ($this->disk()->exists($storagePath)) {
            $this->disk()->delete($storagePath);
        }
    }

    public function publicUrl(string $objectPath): string
    {
        $diskName = $this->diskName();

        if ($diskName === 'public') {
            return $this->disk()->url($objectPath);
        }

        $baseUrl = rtrim((string) config("filesystems.disks.{$diskName}.url"), '/');

        if ($baseUrl === '') {
            $bucket = (string) config("filesystems.disks.{$diskName}.bucket");

            if ($bucket === '') {
                throw new RuntimeException('GCS_PUBLIC_URL or GCS_BUCKET must be configured for menu image URLs.');
            }

            $baseUrl = 'https://storage.googleapis.com/'.$bucket;
        }

        return $baseUrl.'/'.ltrim($objectPath, '/');
    }

    private function objectPath(string $filename): string
    {
        $prefix = $this->pathPrefix();

        if ($this->diskName() === 'public') {
            return 'menus/'.$filename;
        }

        return $prefix !== '' ? $prefix.'/'.$filename : $filename;
    }

    private function storagePathFromObjectPath(string $objectPath): string
    {
        $prefix = $this->pathPrefix();

        if ($this->diskName() === 'public' || $prefix === '') {
            return $objectPath;
        }

        if (str_starts_with($objectPath, $prefix.'/')) {
            return substr($objectPath, strlen($prefix) + 1);
        }

        return $objectPath;
    }

    private function objectPathFromUrl(string $imageUrl): ?string
    {
        $diskName = $this->diskName();
        $baseUrl = rtrim((string) config("filesystems.disks.{$diskName}.url"), '/');

        if ($baseUrl === '') {
            $bucket = (string) config("filesystems.disks.{$diskName}.bucket");

            if ($bucket !== '') {
                $baseUrl = 'https://storage.googleapis.com/'.$bucket;
            }
        }

        if ($baseUrl !== '' && str_starts_with($imageUrl, $baseUrl.'/')) {
            return ltrim(substr($imageUrl, strlen($baseUrl) + 1), '/');
        }

        if ($diskName === 'public') {
            $publicBase = rtrim((string) config('app.url'), '/').'/storage/';

            if (str_starts_with($imageUrl, $publicBase)) {
                return ltrim(substr($imageUrl, strlen($publicBase)), '/');
            }
        }

        return null;
    }

    private function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    private function diskName(): string
    {
        return (string) config('media.menu_image_disk', 'gcs');
    }

    private function uploadDirectory(): string
    {
        if ($this->diskName() === 'public') {
            return 'menus';
        }

        return '';
    }

    private function pathPrefix(): string
    {
        return trim((string) config('filesystems.disks.'.$this->diskName().'.path_prefix', ''), '/');
    }

    private function visibility(): string
    {
        return (string) config('filesystems.disks.'.$this->diskName().'.visibility', 'public');
    }
}
