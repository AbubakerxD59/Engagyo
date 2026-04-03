<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Services\VideoUrlDownloadService;
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

        $apiKey = $this->resolveJoggApiKey($request);
        if ($apiKey === null || $apiKey === '') {
            return $this->errorResponse(
                'Jogg API key is required. Send header x-api-key (recommended), api-key, or Authorization: Bearer <key>. ' .
                    'Avoid header name api_key — many proxies strip underscores and it will not reach the app.',
                401
            );
        }

        $imageUrl = trim((string) $request->input('image', ''));
        $videoUrl = trim((string) $request->input('video', ''));

        if ($imageUrl === '' && $videoUrl === '') {
            return $this->errorResponse('Either image or video URL is required.', 422);
        }

        $mediaUrl = $imageUrl !== '' ? $imageUrl : $videoUrl;

        try {
            $fetched = VideoUrlDownloadService::fetchBinary(
                $mediaUrl,
                VideoUrlDownloadService::joggAllowedContentTypes(),
                120
            );

            if ($fetched === null) {
                return $this->errorResponse(
                    'Unable to download media from the provided URL, or the format is not allowed (image/video only). For Google Drive, use a share link with "Anyone with the link" or a direct file URL.',
                    422
                );
            }

            $contentType = $fetched['content_type'];
            $extension = $fetched['extension'];
            $mediaCategory = Str::startsWith($contentType, 'image/') ? 'image' : 'video';

            $uploadDirectory = public_path('uploads/jogg_ai');
            if (!File::exists($uploadDirectory)) {
                File::makeDirectory($uploadDirectory, 0755, true);
            }

            $fileName = 'jogg_ai_' . time() . '_' . Str::random(8) . '.' . $extension;
            $absolutePath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;

            File::put($absolutePath, $fetched['body']);

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

    /**
     * Read Jogg key from headers. Prefer hyphenated names; underscores are often dropped by nginx before PHP.
     */
    private function resolveJoggApiKey(Request $request): ?string
    {
        $candidates = [
            'x-api-key',
            'X-Api-Key',
            'api-key',
            'API_KEY'
        ];

        foreach ($candidates as $name) {
            $value = $request->header($name);
            if (is_array($value)) {
                $value = $value[0] ?? null;
            }
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        foreach (['HTTP_X_API_KEY', 'HTTP_API_KEY'] as $serverKey) {
            $value = $request->server($serverKey);
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        $bearer = $request->bearerToken();
        if ($bearer !== null && trim($bearer) !== '') {
            return trim($bearer);
        }

        return null;
    }
}
