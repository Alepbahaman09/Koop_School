<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class SupabaseStorage
{
    public function __construct(private readonly SupabaseClient $client) {}

    public function uploadImage(
        UploadedFile $image,
        string $bucket,
        string $folder,
    ): string {
        return $this->uploadLocalImage(
            $image->getRealPath(),
            $bucket,
            $folder,
            $image->getClientOriginalExtension(),
            $image->getMimeType(),
        );
    }

    public function uploadLocalImage(
        string $filePath,
        string $bucket,
        string $folder,
        ?string $extension = null,
        ?string $contentType = null,
    ): string {
        if (! is_file($filePath)) {
            throw new RuntimeException("Image file does not exist: {$filePath}");
        }

        $extension = strtolower($extension ?: pathinfo($filePath, PATHINFO_EXTENSION) ?: 'jpg');
        $path = trim($folder, '/').'/'.Str::uuid().'.'.$extension;
        $contents = file_get_contents($filePath);

        if ($contents === false) {
            throw new RuntimeException("Unable to read image file: {$filePath}");
        }

        $this->client
            ->storage(useServiceRole: true)
            ->withBody(
                $contents,
                $contentType ?: mime_content_type($filePath) ?: 'image/jpeg',
            )
            ->post($this->objectPath($bucket, $path))
            ->throw();

        return $this->publicUrl($bucket, $path);
    }

    public function deletePublicFile(?string $url, string $bucket): void
    {
        if (! $url) {
            return;
        }

        $marker = "/storage/v1/object/public/{$bucket}/";
        $path = str_contains($url, $marker)
            ? Str::after($url, $marker)
            : null;

        if (! $path) {
            return;
        }

        $this->client
            ->storage(useServiceRole: true)
            ->delete($this->objectPath($bucket, urldecode($path)))
            ->throw();
    }

    private function publicUrl(string $bucket, string $path): string
    {
        $baseUrl = rtrim((string) config('services.supabase.url'), '/');

        return $baseUrl.'/storage/v1/object/public/'.$this->encodedPath($bucket.'/'.$path);
    }

    private function objectPath(string $bucket, string $path): string
    {
        return 'object/'.$this->encodedPath($bucket.'/'.$path);
    }

    private function encodedPath(string $path): string
    {
        return collect(explode('/', $path))
            ->map(fn (string $segment) => rawurlencode($segment))
            ->implode('/');
    }
}
