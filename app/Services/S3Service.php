<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class S3Service
{
    /**
     * S3 disk instance
     *
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    private $disk;

    /**
     * Default folder structure
     */
    const FOLDER_IMAGES = 'images';
    const FOLDER_VIDEOS = 'videos';
    const FOLDER_DOCUMENTS = 'documents';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->disk = Storage::disk('s3');
    }

    /**
     * Upload an image file to S3
     *
     * @param UploadedFile|string $file File to upload (UploadedFile instance or local file path)
     * @param string|null $folder Optional subfolder within images directory
     * @param string|null $fileName Optional custom filename (without extension)
     * @param bool $public Whether the file should be publicly accessible
     * @return array ['success' => bool, 'path' => string, 'url' => string, 'message' => string]
     */
    public function uploadImage($file, $folder = null, $fileName = null, $public = true)
    {
        try {
            $baseFolder = self::FOLDER_IMAGES;
            if ($folder) {
                $baseFolder = $baseFolder . '/' . trim($folder, '/');
            }

            // Handle UploadedFile instance
            if ($file instanceof UploadedFile) {
                $extension = $file->getClientOriginalExtension();
                $originalName = $file->getClientOriginalName();
                $mimeType = $file->getMimeType();

                // Validate image type
                if (!$this->isValidImageType($mimeType)) {
                    return [
                        'success' => false,
                        'path' => null,
                        'url' => null,
                        'message' => 'Invalid image type. Allowed types: jpg, jpeg, png, gif, webp, svg'
                    ];
                }

                // Generate filename if not provided
                if (!$fileName) {
                    $fileName = $this->generateFileName($originalName, $extension);
                } else {
                    $fileName = $fileName . '.' . $extension;
                }

                $s3Path = $baseFolder . '/' . $fileName;

                // Upload file
                $uploaded = $this->disk->putFileAs(
                    dirname($s3Path),
                    $file,
                    basename($s3Path),
                    $public ? 'public' : 'private'
                );

                if (!$uploaded) {
                    return [
                        'success' => false,
                        'path' => null,
                        'url' => null,
                        'message' => 'Failed to upload image to S3'
                    ];
                }

                $url = $this->disk->url($s3Path);

                return [
                    'success' => true,
                    'path' => $s3Path,
                    'url' => $url,
                    'message' => 'Image uploaded successfully'
                ];
            }

            // Handle local file path (string)
            if (is_string($file) && file_exists($file)) {
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $mimeType = mime_content_type($file);

                // Validate image type
                if (!$this->isValidImageType($mimeType)) {
                    return [
                        'success' => false,
                        'path' => null,
                        'url' => null,
                        'message' => 'Invalid image type. Allowed types: jpg, jpeg, png, gif, webp, svg'
                    ];
                }

                // Generate filename if not provided
                if (!$fileName) {
                    $fileName = $this->generateFileName(basename($file), $extension);
                } else {
                    $fileName = $fileName . '.' . $extension;
                }

                $s3Path = $baseFolder . '/' . $fileName;

                // Read file contents
                $fileContents = file_get_contents($file);

                // Upload file
                $uploaded = $this->disk->put($s3Path, $fileContents, $public ? 'public' : 'private');

                if (!$uploaded) {
                    return [
                        'success' => false,
                        'path' => null,
                        'url' => null,
                        'message' => 'Failed to upload image to S3'
                    ];
                }

                $url = $this->disk->url($s3Path);

                return [
                    'success' => true,
                    'path' => $s3Path,
                    'url' => $url,
                    'message' => 'Image uploaded successfully'
                ];
            }

            return [
                'success' => false,
                'path' => null,
                'url' => null,
                'message' => 'Invalid file provided. Expected UploadedFile instance or valid file path.'
            ];
        } catch (Exception $e) {
            Log::error('S3Service uploadImage error: ' . $e->getMessage());
            return [
                'success' => false,
                'path' => null,
                'url' => null,
                'message' => 'Error uploading image: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload a video file to S3
     *
     * @param UploadedFile|string $file File to upload (UploadedFile instance or local file path)
     * @param string|null $folder Optional subfolder within videos directory
     * @param string|null $fileName Optional custom filename (without extension)
     * @param bool $public Whether the file should be publicly accessible
     * @return array ['success' => bool, 'path' => string, 'url' => string, 'message' => string]
     */
    public function uploadVideo($file, $folder = null, $fileName = null, $public = true)
    {
        try {
            $baseFolder = self::FOLDER_VIDEOS;
            if ($folder) {
                $baseFolder = $baseFolder . '/' . trim($folder, '/');
            }

            // Handle UploadedFile instance
            if ($file instanceof UploadedFile) {
                $extension = $file->getClientOriginalExtension();
                $originalName = $file->getClientOriginalName();
                $mimeType = $file->getMimeType();

                // Validate video type
                if (!$this->isValidVideoType($mimeType)) {
                    return [
                        'success' => false,
                        'path' => null,
                        'url' => null,
                        'message' => 'Invalid video type. Allowed types: mp4, mov, avi, mkv, webm, flv, wmv'
                    ];
                }

                // Generate filename if not provided
                if (!$fileName) {
                    $fileName = $this->generateFileName($originalName, $extension);
                } else {
                    $fileName = $fileName . '.' . $extension;
                }

                $s3Path = $baseFolder . '/' . $fileName;

                // Upload file
                $uploaded = $this->disk->putFileAs(
                    dirname($s3Path),
                    $file,
                    basename($s3Path),
                    $public ? 'public' : 'private'
                );

                if (!$uploaded) {
                    return [
                        'success' => false,
                        'path' => null,
                        'url' => null,
                        'message' => 'Failed to upload video to S3'
                    ];
                }

                $url = $this->disk->url($s3Path);

                return [
                    'success' => true,
                    'path' => $s3Path,
                    'url' => $url,
                    'message' => 'Video uploaded successfully'
                ];
            }

            // Handle local file path (string)
            if (is_string($file) && file_exists($file)) {
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $mimeType = mime_content_type($file);

                // Validate video type
                if (!$this->isValidVideoType($mimeType)) {
                    return [
                        'success' => false,
                        'path' => null,
                        'url' => null,
                        'message' => 'Invalid video type. Allowed types: mp4, mov, avi, mkv, webm, flv, wmv'
                    ];
                }

                // Generate filename if not provided
                if (!$fileName) {
                    $fileName = $this->generateFileName(basename($file), $extension);
                } else {
                    $fileName = $fileName . '.' . $extension;
                }

                $s3Path = $baseFolder . '/' . $fileName;

                // Read file contents
                $fileContents = file_get_contents($file);

                // Upload file
                $uploaded = $this->disk->put($s3Path, $fileContents, $public ? 'public' : 'private');

                if (!$uploaded) {
                    return [
                        'success' => false,
                        'path' => null,
                        'url' => null,
                        'message' => 'Failed to upload video to S3'
                    ];
                }

                $url = $this->disk->url($s3Path);

                return [
                    'success' => true,
                    'path' => $s3Path,
                    'url' => $url,
                    'message' => 'Video uploaded successfully'
                ];
            }

            return [
                'success' => false,
                'path' => null,
                'url' => null,
                'message' => 'Invalid file provided. Expected UploadedFile instance or valid file path.'
            ];
        } catch (Exception $e) {
            Log::error('S3Service uploadVideo error: ' . $e->getMessage());
            return [
                'success' => false,
                'path' => null,
                'url' => null,
                'message' => 'Error uploading video: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Download file from URL and upload to S3
     *
     * @param string $url Public URL of the file to download
     * @param string $type File type: 'image' or 'video'
     * @param string|null $folder Optional subfolder
     * @param string|null $fileName Optional custom filename
     * @param bool $public Whether the file should be publicly accessible
     * @return array ['success' => bool, 'path' => string, 'url' => string, 'message' => string]
     */
    public function downloadAndUpload($url, $type = 'image', $folder = null, $fileName = null, $public = true)
    {
        try {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return [
                    'success' => false,
                    'path' => null,
                    'url' => null,
                    'message' => 'Invalid URL provided'
                ];
            }

            // Download file
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $fileContents = @file_get_contents($url, false, $context);

            if ($fileContents === false) {
                return [
                    'success' => false,
                    'path' => null,
                    'url' => null,
                    'message' => 'Failed to download file from URL'
                ];
            }

            // Determine file extension from URL or Content-Type
            $extension = $this->getExtensionFromUrl($url);
            if (!$extension) {
                // Try to get from Content-Type header
                $headers = get_headers($url, 1);
                if (isset($headers['Content-Type'])) {
                    $contentType = is_array($headers['Content-Type']) 
                        ? end($headers['Content-Type']) 
                        : $headers['Content-Type'];
                    $extension = $this->getExtensionFromMimeType($contentType);
                }
            }

            if (!$extension) {
                $extension = $type === 'video' ? 'mp4' : 'jpg';
            }

            // Generate filename if not provided
            if (!$fileName) {
                $fileName = $this->generateFileName('downloaded', $extension);
            } else {
                $fileName = $fileName . '.' . $extension;
            }

            // Determine base folder
            $baseFolder = $type === 'video' ? self::FOLDER_VIDEOS : self::FOLDER_IMAGES;
            if ($folder) {
                $baseFolder = $baseFolder . '/' . trim($folder, '/');
            }

            $s3Path = $baseFolder . '/' . $fileName;

            // Upload to S3
            $uploaded = $this->disk->put($s3Path, $fileContents, $public ? 'public' : 'private');

            if (!$uploaded) {
                return [
                    'success' => false,
                    'path' => null,
                    'url' => null,
                    'message' => 'Failed to upload file to S3'
                ];
            }

            $url = $this->disk->url($s3Path);

            return [
                'success' => true,
                'path' => $s3Path,
                'url' => $url,
                'message' => 'File downloaded and uploaded successfully'
            ];
        } catch (Exception $e) {
            Log::error('S3Service downloadAndUpload error: ' . $e->getMessage());
            return [
                'success' => false,
                'path' => null,
                'url' => null,
                'message' => 'Error downloading and uploading file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get public URL of a file in S3
     *
     * @param string $path S3 file path
     * @return string|null Public URL or null if file doesn't exist
     */
    public function getUrl($path)
    {
        try {
            if ($this->disk->exists($path)) {
                return $this->disk->url($path);
            }
            return null;
        } catch (Exception $e) {
            Log::error('S3Service getUrl error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get temporary signed URL (for private files)
     *
     * @param string $path S3 file path
     * @param int $expirationMinutes URL expiration time in minutes (default: 60)
     * @return string|null Signed URL or null if file doesn't exist
     */
    public function getTemporaryUrl($path, $expirationMinutes = 60)
    {
        try {
            if ($this->disk->exists($path)) {
                return $this->disk->temporaryUrl($path, now()->addMinutes($expirationMinutes));
            }
            return null;
        } catch (Exception $e) {
            Log::error('S3Service getTemporaryUrl error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Download file from S3 to local storage
     *
     * @param string $path S3 file path
     * @param string|null $localPath Local path to save file (optional)
     * @return array ['success' => bool, 'local_path' => string, 'message' => string]
     */
    public function download($path, $localPath = null)
    {
        try {
            if (!$this->disk->exists($path)) {
                return [
                    'success' => false,
                    'local_path' => null,
                    'message' => 'File does not exist in S3'
                ];
            }

            // Get file contents
            $fileContents = $this->disk->get($path);

            // Generate local path if not provided
            if (!$localPath) {
                $fileName = basename($path);
                $localPath = storage_path('app/temp/' . $fileName);
                
                // Create directory if it doesn't exist
                $dir = dirname($localPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }

            // Save file locally
            file_put_contents($localPath, $fileContents);

            return [
                'success' => true,
                'local_path' => $localPath,
                'message' => 'File downloaded successfully'
            ];
        } catch (Exception $e) {
            Log::error('S3Service download error: ' . $e->getMessage());
            return [
                'success' => false,
                'local_path' => null,
                'message' => 'Error downloading file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if file exists in S3
     *
     * @param string $path S3 file path
     * @return bool
     */
    public function exists($path)
    {
        try {
            return $this->disk->exists($path);
        } catch (Exception $e) {
            Log::error('S3Service exists error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete file from S3
     *
     * @param string $path S3 file path
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete($path)
    {
        try {
            if (!$this->disk->exists($path)) {
                return [
                    'success' => false,
                    'message' => 'File does not exist in S3'
                ];
            }

            $deleted = $this->disk->delete($path);

            if ($deleted) {
                return [
                    'success' => true,
                    'message' => 'File deleted successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to delete file from S3'
            ];
        } catch (Exception $e) {
            Log::error('S3Service delete error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error deleting file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete multiple files from S3
     *
     * @param array $paths Array of S3 file paths
     * @return array ['success' => bool, 'deleted' => int, 'failed' => int, 'message' => string]
     */
    public function deleteMultiple(array $paths)
    {
        try {
            $deleted = 0;
            $failed = 0;

            foreach ($paths as $path) {
                $result = $this->delete($path);
                if ($result['success']) {
                    $deleted++;
                } else {
                    $failed++;
                }
            }

            return [
                'success' => $failed === 0,
                'deleted' => $deleted,
                'failed' => $failed,
                'message' => "Deleted {$deleted} file(s), {$failed} failed"
            ];
        } catch (Exception $e) {
            Log::error('S3Service deleteMultiple error: ' . $e->getMessage());
            return [
                'success' => false,
                'deleted' => 0,
                'failed' => count($paths),
                'message' => 'Error deleting files: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get file size from S3
     *
     * @param string $path S3 file path
     * @return int|null File size in bytes or null if file doesn't exist
     */
    public function getSize($path)
    {
        try {
            if ($this->disk->exists($path)) {
                return $this->disk->size($path);
            }
            return null;
        } catch (Exception $e) {
            Log::error('S3Service getSize error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get file MIME type from S3
     *
     * @param string $path S3 file path
     * @return string|null MIME type or null if file doesn't exist
     */
    public function getMimeType($path)
    {
        try {
            if ($this->disk->exists($path)) {
                return $this->disk->mimeType($path);
            }
            return null;
        } catch (Exception $e) {
            Log::error('S3Service getMimeType error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * List files in a directory
     *
     * @param string $folder Folder path
     * @param bool $recursive Whether to list recursively
     * @return array Array of file paths
     */
    public function listFiles($folder = '', $recursive = false)
    {
        try {
            if ($recursive) {
                return $this->disk->allFiles($folder);
            }
            return $this->disk->files($folder);
        } catch (Exception $e) {
            Log::error('S3Service listFiles error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Copy file within S3
     *
     * @param string $sourcePath Source file path
     * @param string $destinationPath Destination file path
     * @return array ['success' => bool, 'message' => string]
     */
    public function copy($sourcePath, $destinationPath)
    {
        try {
            if (!$this->disk->exists($sourcePath)) {
                return [
                    'success' => false,
                    'message' => 'Source file does not exist'
                ];
            }

            $copied = $this->disk->copy($sourcePath, $destinationPath);

            if ($copied) {
                return [
                    'success' => true,
                    'message' => 'File copied successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to copy file'
            ];
        } catch (Exception $e) {
            Log::error('S3Service copy error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error copying file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Move/rename file within S3
     *
     * @param string $sourcePath Source file path
     * @param string $destinationPath Destination file path
     * @return array ['success' => bool, 'message' => string]
     */
    public function move($sourcePath, $destinationPath)
    {
        try {
            if (!$this->disk->exists($sourcePath)) {
                return [
                    'success' => false,
                    'message' => 'Source file does not exist'
                ];
            }

            $moved = $this->disk->move($sourcePath, $destinationPath);

            if ($moved) {
                return [
                    'success' => true,
                    'message' => 'File moved successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to move file'
            ];
        } catch (Exception $e) {
            Log::error('S3Service move error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error moving file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate a unique filename
     *
     * @param string $originalName Original filename
     * @param string $extension File extension
     * @return string Generated filename
     */
    private function generateFileName($originalName, $extension)
    {
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $name = Str::slug($name);
        $timestamp = time();
        $random = Str::random(8);
        
        return "{$name}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Validate image MIME type
     *
     * @param string $mimeType MIME type
     * @return bool
     */
    private function isValidImageType($mimeType)
    {
        $validTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/bmp'
        ];

        return in_array(strtolower($mimeType), $validTypes);
    }

    /**
     * Validate video MIME type
     *
     * @param string $mimeType MIME type
     * @return bool
     */
    private function isValidVideoType($mimeType)
    {
        $validTypes = [
            'video/mp4',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-matroska',
            'video/webm',
            'video/x-flv',
            'video/x-ms-wmv',
            'video/3gpp',
            'video/ogg'
        ];

        return in_array(strtolower($mimeType), $validTypes);
    }

    /**
     * Get file extension from URL
     *
     * @param string $url URL
     * @return string|null Extension or null
     */
    private function getExtensionFromUrl($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            return $extension ? strtolower($extension) : null;
        }
        return null;
    }

    /**
     * Get file extension from MIME type
     *
     * @param string $mimeType MIME type
     * @return string|null Extension or null
     */
    private function getExtensionFromMimeType($mimeType)
    {
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            'video/x-matroska' => 'mkv',
            'video/webm' => 'webm',
            'video/x-flv' => 'flv',
        ];

        return $mimeToExt[strtolower($mimeType)] ?? null;
    }
}

