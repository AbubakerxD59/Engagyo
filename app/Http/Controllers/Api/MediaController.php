<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MediaController extends BaseController
{
    /**
     * Allowed image mime types.
     */
    protected $imageMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/bmp',
    ];

    /**
     * Allowed video mime types.
     */
    protected $videoMimeTypes = [
        'video/mp4',
        'video/mpeg',
        'video/quicktime',
        'video/x-msvideo',
        'video/x-ms-wmv',
        'video/webm',
        'video/3gpp',
        'video/x-flv',
    ];

    /**
     * Upload media (image or video) to S3.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        // Combine allowed mime types
        $allowedMimeTypes = array_merge($this->imageMimeTypes, $this->videoMimeTypes);
        $mimeTypesString = implode(',', $allowedMimeTypes);

        $validator = Validator::make($request->all(), [
            'media' => "required|file|mimes:jpeg,jpg,png,gif,webp,bmp,mp4,mpeg,mov,avi,wmv,webm,3gp,flv|max:102400", // 100MB max
        ], [
            'media.required' => 'Please select a media file to upload.',
            'media.file' => 'The uploaded file is invalid.',
            'media.mimes' => 'The media must be an image (jpeg, jpg, png, gif, webp, bmp) or video (mp4, mpeg, mov, avi, wmv, webm, 3gp, flv).',
            'media.max' => 'The media file size must not exceed 100MB.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $file = $request->file('media');
            $mimeType = $file->getMimeType();
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $size = $file->getSize();

            // Determine media type
            $type = $this->getMediaType($mimeType);

            if (!$type) {
                return $this->errorResponse('Unsupported media type.', 422);
            }

            // Generate unique filename
            $filename = $this->generateFilename($extension);

            // Determine folder based on type
            $folder = $type === 'image' ? 'images' : 'videos';
            $path = "{$folder}/{$filename}";

            // Upload to S3
            $uploaded = saveToS3($file);

            if (!$uploaded) {
                return $this->errorResponse('Failed to upload media to storage.', 500);
            }

            // Get the public URL
            $url = fetchFromS3($path);

            return $this->successResponse([
                'type' => $type,
                'url' => $url,
                'filename' => $filename,
                'original_filename' => $originalName,
                'size' => $size,
                'size_formatted' => $this->formatFileSize($size),
                'mimetype' => $mimeType,
                'extension' => $extension,
                'path' => $path,
            ], 'Media uploaded successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to upload media: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get media type from mime type.
     *
     * @param string $mimeType
     * @return string|null
     */
    protected function getMediaType(string $mimeType): ?string
    {
        if (in_array($mimeType, $this->imageMimeTypes)) {
            return 'image';
        }

        if (in_array($mimeType, $this->videoMimeTypes)) {
            return 'video';
        }

        // Fallback check using mime type prefix
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        return null;
    }

    /**
     * Generate unique filename.
     *
     * @param string $extension
     * @return string
     */
    protected function generateFilename(string $extension): string
    {
        $timestamp = now()->format('YmdHis');
        $random = Str::random(16);
        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Format file size to human readable format.
     *
     * @param int $bytes
     * @return string
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
