<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class FileHelper
{
    /**
     * Upload, resize, optimize and convert images to webp.
     *
     * @param  mixed  $files  UploadedFile or array of UploadedFile
     * @param  string $folder Folder path inside storage/app/public
     * @param  array  $options ['width' => int, 'height' => int, 'optimize' => bool]
     * @return array|string
     */
    public static function uploadImages($files, string $folder = 'uploads', array $options = [])
    {
        $uploadedPaths = [];

        // Normalize to array
        $files = is_array($files) ? $files : [$files];

        // Create Intervention Image manager with GD driver
        $manager = new ImageManager(new Driver());

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) continue;

            // Validate file type
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, ['jpg','jpeg','png','gif','webp','bmp'])) continue;

            // Generate filename with timestamp
            $filename = Str::random(20) . '.webp';
            $relativePath = "{$folder}/{$filename}";
            $storagePath = storage_path("app/public/{$relativePath}");

            // Make folder if not exists
            if (!file_exists(storage_path("app/public/{$folder}"))) {
                mkdir(storage_path("app/public/{$folder}"), 0755, true);
            }

            try {
                // Resize image if width/height provided
                $image = $manager->read($file->getRealPath());

                if (isset($options['width']) || isset($options['height'])) {
                    $width = $options['width'] ?? null;
                    $height = $options['height'] ?? null;

                    $image->resize($width, $height, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }

                // Convert to webp and save
                $image->toWebp(80)->save($storagePath);

                // Optimize image if enabled (default: true)
                if ($options['optimize'] ?? true) {
                    $optimizerChain = OptimizerChainFactory::create();
                    $optimizerChain->optimize($storagePath);
                }

                // Update file modified time to now
                touch($storagePath);

                $uploadedPaths[] = "storage/{$relativePath}";

            } catch (\Exception $e) {
                // Log error or handle exception as needed
                continue;
            }
        }

        return count($uploadedPaths) === 1 ? $uploadedPaths[0] : $uploadedPaths;
    }

    /**
     * Upload documents like PDF, DOC, DOCX, XLS, XLSX.
     *
     * @param  mixed $files UploadedFile or array of UploadedFile
     * @param  string $folder Folder path inside storage/app/public
     * @param  array $allowedExtensions Optional allowed extensions
     * @return array|string
     */
    public static function uploadDocuments($files, string $folder = 'documents', array $allowedExtensions = [])
    {
        $uploadedPaths = [];
        $files = is_array($files) ? $files : [$files];

        // Default allowed extensions if not provided
        if (empty($allowedExtensions)) {
            $allowedExtensions = ['pdf','doc','docx','xls','xlsx','txt','csv','ppt','pptx','zip','rar'];
        }

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) continue;

            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, $allowedExtensions)) continue;

            $filename = Str::random(20) . '.' . $extension;
            $relativePath = "{$folder}/{$filename}";
            $storagePath = storage_path("app/public/{$relativePath}");

            if (!file_exists(storage_path("app/public/{$folder}"))) {
                mkdir(storage_path("app/public/{$folder}"), 0755, true);
            }

            $file->move(storage_path("app/public/{$folder}"), $filename);
            touch($storagePath); // update modified time

            $uploadedPaths[] = "storage/{$relativePath}";
        }

        return count($uploadedPaths) === 1 ? $uploadedPaths[0] : $uploadedPaths;
    }

    /**
     * Update/replace an existing image with a new one
     *
     * @param  UploadedFile $newFile New image file
     * @param  string $oldFilePath Old file path (e.g., 'storage/uploads/old_image.webp')
     * @param  string $folder Folder path inside storage/app/public
     * @param  array  $options ['width' => int, 'height' => int, 'optimize' => bool]
     * @return string|false
     */
    public static function updateImage(UploadedFile $newFile, ?string $oldFilePath = null, string $folder = 'uploads', array $options = [])
    {
        // Upload the new image
        $newFilePath = self::uploadImages($newFile, $folder, $options);

        if (!$newFilePath) {
            // Upload failed
            return false;
        }

        // Remove 'storage/' prefix if present to get the relative path
        $relativePath = str_replace('storage/', '', $oldFilePath);

        $fullPath = storage_path("app/public/{$relativePath}");

        // Delete the old file if provided and exists
        if (!empty($oldFilePath) && file_exists($fullPath)) {
            self::deleteFile($oldFilePath);
        }

        // Return the new file path
        return $newFilePath;
    }

    /**
     * Update/replace an existing document with a new one
     *
     * @param  UploadedFile $newFile New document file
     * @param  string $oldFilePath Old file path (e.g., 'storage/documents/old_file.pdf')
     * @param  string $folder Folder path inside storage/app/public
     * @param  array $allowedExtensions Optional allowed extensions
     * @return string|false
     */
    public static function updateDocument(UploadedFile $newFile, string $oldFilePath, string $folder = 'documents', array $allowedExtensions = [])
    {
        // First upload the new document
        $newFilePath = self::uploadDocuments($newFile, $folder, $allowedExtensions);

        // If new file was uploaded successfully, delete the old file
        if ($newFilePath && self::deleteFile($oldFilePath)) {
            return $newFilePath;
        }

        // If something went wrong, delete the new file and return false
        if ($newFilePath) {
            self::deleteFile($newFilePath);
        }

        return false;
    }

    /**
     * Update/replace multiple files (images or documents)
     *
     * @param  array $filePairs Array of ['newFile' => UploadedFile, 'oldFilePath' => string]
     * @param  string $folder Folder path
     * @param  array $options Options for images or allowed extensions for documents
     * @param  string $type 'image' or 'document'
     * @return array
     */
    public static function updateFiles(array $filePairs, string $folder = 'uploads', array $options = [], string $type = 'image'): array
    {
        $results = [];

        foreach ($filePairs as $index => $pair) {
            if (!isset($pair['newFile']) || !isset($pair['oldFilePath'])) {
                $results[$index] = false;
                continue;
            }

            if ($type === 'image') {
                $results[$index] = self::updateImage($pair['newFile'], $pair['oldFilePath'], $folder, $options);
            } else {
                $results[$index] = self::updateDocument($pair['newFile'], $pair['oldFilePath'], $folder, $options);
            }
        }

        return $results;
    }

    /**
     * Delete file from storage
     *
     * @param string $path Relative path (e.g., 'storage/uploads/filename.webp')
     * @return bool
     */
    public static function deleteFile(string $path): bool
    {
        // Remove 'storage/' prefix if present to get the relative path
        $relativePath = str_replace('storage/', '', $path);

        $fullPath = storage_path("app/public/{$relativePath}");

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    /**
     * Check if file exists in storage
     *
     * @param string $path Relative path (e.g., 'storage/uploads/filename.webp')
     * @return bool
     */
    public static function fileExists(string $path): bool
    {
        $relativePath = str_replace('storage/', '', $path);
        $fullPath = storage_path("app/public/{$relativePath}");

        return file_exists($fullPath);
    }
}

// Usage examples:
// $docPath = FileHelper::uploadDocuments($request->file('document'), 'contracts');
// $imagePath = FileHelper::uploadImages($request->file('image'), 'products', [
//     'width' => 800,
//     'height' => 800,
// ]);

// Update single image
// $updatedImage = FileHelper::updateImage(
//     $request->file('new_image'),
//     'storage/uploads/old_image.webp',
//     'products',
//     ['width' => 800, 'height' => 600, 'optimize' => true]
// );

// Update single document
// $updatedDoc = FileHelper::updateDocument(
//     $request->file('new_document'),
//     'storage/documents/old_file.pdf',
//     'contracts',
//     ['pdf', 'docx']
// );

// Update multiple files
// $updatedFiles = FileHelper::updateFiles(
//     [
//         ['newFile' => $request->file('img1'), 'oldFilePath' => 'storage/products/img1.webp'],
//         ['newFile' => $request->file('img2'), 'oldFilePath' => 'storage/products/img2.webp']
//     ],
//     'products',
//     ['width' => 800, 'height' => 600],
//     'image'
// );

// FileHelper::deleteFile($oldImagePath);
