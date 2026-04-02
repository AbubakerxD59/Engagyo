<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class JoggAiController extends BaseController
{
    private const UPLOAD_ASSET_URL = 'https://api.jogg.ai/v2/upload/asset';

    /**
     * Download remote media to public/uploads/jogg_ai, register with Jogg upload/asset, then PUT bytes to sign_url.
     */
    public function uploadMedia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'nullable|url',
            'video' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $apiKey = $request->header('api_key');
        dd($apiKey);
        if ($apiKey === null || $apiKey === '') {
            return $this->errorResponse('API Key header is required.', 401);
        }

        $imageUrl = trim((string) $request->input('image', ''));
        $videoUrl = trim((string) $request->input('video', ''));

        if ($imageUrl === '' && $videoUrl === '') {
            return $this->errorResponse('Either image or video URL is required.', 422);
        }

        $mediaUrl = $imageUrl !== '' ? $imageUrl : $videoUrl;

        try {
            $response = Http::timeout(120)->get($mediaUrl);

            if (!$response->successful()) {
                return $this->errorResponse('Unable to download media from the provided URL.', 400);
            }

            $contentType = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
            $allowedContentTypes = [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/webp',
                'video/mp4',
                'video/quicktime',
                'video/x-msvideo',
                'video/x-matroska',
                'video/webm',
            ];

            if (!in_array($contentType, $allowedContentTypes, true)) {
                return $this->errorResponse('Only image or video URLs are allowed.', 422);
            }

            $mediaCategory = Str::startsWith($contentType, 'image/') ? 'image' : 'video';
            $extensionMap = [
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'video/mp4' => 'mp4',
                'video/quicktime' => 'mov',
                'video/x-msvideo' => 'avi',
                'video/x-matroska' => 'mkv',
                'video/webm' => 'webm',
            ];
            $extension = $extensionMap[$contentType] ?? 'bin';

            $uploadDirectory = public_path('uploads/jogg_ai');
            if (!File::exists($uploadDirectory)) {
                File::makeDirectory($uploadDirectory, 0755, true);
            }

            $fileName = 'jogg_ai_' . time() . '_' . Str::random(8) . '.' . $extension;
            $absolutePath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;

            File::put($absolutePath, $response->body());

            if (!File::exists($absolutePath)) {
                return $this->errorResponse('Failed to store downloaded media.', 500);
            }

            $fileSize = File::size($absolutePath);

            $joggResponse = Http::timeout(60)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'Accept' => 'application/json',
                ])
                ->asJson()
                ->post(self::UPLOAD_ASSET_URL, [
                    'filename' => $fileName,
                    'content_type' => $contentType,
                    'file_size' => $fileSize,
                ]);

            if (!$joggResponse->successful()) {
                Log::warning('Jogg AI upload/asset failed', [
                    'status' => $joggResponse->status(),
                    'body' => $joggResponse->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Media saved locally but Jogg AI upload/asset failed.',
                    'data' => [
                        'file_name' => $fileName,
                        'file_size' => $fileSize,
                        'path' => 'uploads/jogg_ai/' . $fileName,
                        'url' => url('uploads/jogg_ai/' . $fileName),
                        'jogg_status' => $joggResponse->status(),
                        'jogg_body' => $joggResponse->json() ?? $joggResponse->body(),
                    ],
                ], $joggResponse->status() >= 400 && $joggResponse->status() < 600
                    ? $joggResponse->status()
                    : 502);
            }

            $joggPayload = $joggResponse->json();
            $joggCode = $joggPayload['code'] ?? null;
            if ($joggCode !== 0) {
                $msg = $joggPayload['msg'] ?? 'Jogg AI returned an error';
                Log::warning('Jogg AI upload/asset business error', ['payload' => $joggPayload]);

                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'data' => [
                        'file_name' => $fileName,
                        'file_size' => $fileSize,
                        'path' => 'uploads/jogg_ai/' . $fileName,
                        'url' => url('uploads/jogg_ai/' . $fileName),
                        'jogg' => $joggPayload,
                    ],
                ], 422);
            }

            $signUrl = $joggPayload['data']['sign_url'] ?? null;
            $assetUrl = $joggPayload['data']['asset_url'] ?? null;

            if ($signUrl === null || $signUrl === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Jogg AI response did not include sign_url.',
                    'data' => [
                        'file_name' => $fileName,
                        'file_size' => $fileSize,
                        'path' => 'uploads/jogg_ai/' . $fileName,
                        'url' => url('uploads/jogg_ai/' . $fileName),
                        'jogg' => $joggPayload,
                    ],
                ], 502);
            }

            $fileBody = File::get($absolutePath);
            $putTimeout = max(120, (int) ceil($fileSize / (512 * 1024)));

            $ossResponse = Http::timeout($putTimeout)
                ->withBody($fileBody, $contentType)
                ->put($signUrl);

            if (!$ossResponse->successful()) {
                Log::warning('Jogg AI presigned upload (PUT) failed', [
                    'status' => $ossResponse->status(),
                    'body' => $ossResponse->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Media saved locally and registered with Jogg, but upload to sign_url failed.',
                    'data' => [
                        'file_name' => $fileName,
                        'file_size' => $fileSize,
                        'path' => 'uploads/jogg_ai/' . $fileName,
                        'url' => url('uploads/jogg_ai/' . $fileName),
                        'asset_url' => $assetUrl,
                        'jogg_register' => $joggPayload,
                        'sign_upload_status' => $ossResponse->status(),
                        'sign_upload_body' => $ossResponse->body(),
                    ],
                ], $ossResponse->status() >= 400 && $ossResponse->status() < 600
                    ? $ossResponse->status()
                    : 502);
            }

            return $this->successResponse([
                'media_type' => $mediaCategory,
                'mime_type' => $contentType,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'path' => 'uploads/jogg_ai/' . $fileName,
                'url' => url('uploads/jogg_ai/' . $fileName),
                'asset_url' => $assetUrl,
                'jogg_register' => $joggPayload,
                'sign_upload_status' => $ossResponse->status(),
                'sign_upload_body' => $ossResponse->body() !== '' ? $ossResponse->body() : null,
            ], 'Media uploaded to Jogg AI storage');
        } catch (\Throwable $e) {
            Log::error('Jogg AI media upload failed: ' . $e->getMessage(), [
                'media_url' => $mediaUrl,
            ]);

            return $this->errorResponse('Failed to process media URL.', 500);
        }
    }
}
