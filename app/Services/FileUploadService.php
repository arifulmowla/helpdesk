<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Upload an image file and return the URL.
     */
    public function uploadImage(UploadedFile $file, string $directory = 'images'): string
    {
        // Generate a unique filename
        $filename = $this->generateUniqueFilename($file);
        
        // Store the file
        $path = $file->storeAs($directory, $filename, 'public');
        
        // Return the full URL
        return Storage::disk('public')->url($path);
    }

    /**
     * Upload a file and return the URL.
     */
    public function uploadFile(UploadedFile $file, string $directory = 'files'): string
    {
        // Generate a unique filename
        $filename = $this->generateUniqueFilename($file);
        
        // Store the file
        $path = $file->storeAs($directory, $filename, 'public');
        
        // Return the full URL
        return Storage::disk('public')->url($path);
    }

    /**
     * Delete a file by its URL.
     */
    public function deleteFile(string $url): bool
    {
        // Extract the path from the URL
        $path = $this->getPathFromUrl($url);
        
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }
        
        return false;
    }

    /**
     * Generate a unique filename for the uploaded file.
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        
        // Sanitize the filename
        $name = Str::slug($name);
        
        // Add timestamp and random string for uniqueness
        $timestamp = now()->format('Y-m-d-H-i-s');
        $random = Str::random(8);
        
        return "{$name}-{$timestamp}-{$random}.{$extension}";
    }

    /**
     * Extract the storage path from a public URL.
     */
    private function getPathFromUrl(string $url): ?string
    {
        $publicUrl = Storage::disk('public')->url('');
        
        if (Str::startsWith($url, $publicUrl)) {
            return Str::after($url, $publicUrl);
        }
        
        return null;
    }

    /**
     * Validate image file type and size.
     */
    public function validateImage(UploadedFile $file, int $maxSizeKb = 2048): array
    {
        $errors = [];
        
        // Check file type
        $allowedTypes = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = 'File must be an image (jpeg, jpg, png, gif, webp).';
        }
        
        // Check file size
        $fileSizeKb = $file->getSize() / 1024;
        if ($fileSizeKb > $maxSizeKb) {
            $errors[] = "File size must not exceed {$maxSizeKb}KB.";
        }
        
        return $errors;
    }

    /**
     * Validate general file type and size.
     */
    public function validateFile(UploadedFile $file, array $allowedTypes = ['pdf', 'doc', 'docx', 'txt', 'zip'], int $maxSizeKb = 10240): array
    {
        $errors = [];
        
        // Check file type
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $allowedTypes);
        }
        
        // Check file size
        $fileSizeKb = $file->getSize() / 1024;
        if ($fileSizeKb > $maxSizeKb) {
            $errors[] = "File size must not exceed " . round($maxSizeKb / 1024, 1) . "MB.";
        }
        
        return $errors;
    }
}
