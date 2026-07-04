<?php

namespace App\Services\Summarization;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PdfStorageService
{
    public function store(UploadedFile $file): ?string
    {
        return $file->storeAs('pdfs', $file->hashName()) ?: null;
    }

    public function delete(?string $path): void
    {
        if ($path !== null && Storage::exists($path)) {
            Storage::delete($path);
        }
    }

    public function absolutePath(string $path): string
    {
        return Storage::path($path);
    }
}
