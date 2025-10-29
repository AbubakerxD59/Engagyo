<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class HttpService
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Get the Guzzle HTTP client instance.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Send a GET request to the specified endpoint.
     *
     * @param string $endpoint
     * @param array $queryParameters
     * @param array $headers
     * @return array|null
     */
    public function get(string $endpoint, array $queryParameters = [], array $headers = []): ?array
    {
        try {
            $response = $this->client->get($endpoint, [
                "headers" => $headers,
                "query" => $queryParameters,
            ]);
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            $this->handleException($e);
            return null; // Or throw the exception depending on your error handling strategy
        }
    }

    /**
     * Send a POST request to the specified endpoint.
     *
     * @param string $endpoint
     * @param array $body
     * @param array $headers
     * @return array|null
     */
    public function post(string $endpoint, array $body = [], array $headers = []): ?array
    {
        try {
            $response = $this->client->post($endpoint, [
                "headers" => $headers,
                "form_params" => $body,
            ]);
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            $this->handleException($e);
            return null;
        }
    }

    /**
     * Send a POST request to the specified endpoint with JSON.
     *
     * @param string $endpoint
     * @param array $body
     * @param array $headers
     * @return array|null
     */
    public function postJson(string $endpoint, $body = null, array $headers = []): ?array
    {
        try {
            $response = $this->client->post($endpoint, [
                "headers" => $headers,
                "json" => $body,
            ]);
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            $this->handleException($e);
            return ["success" => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send a PUT request to the specified endpoint.
     *
     * @param string $endpoint
     * @param array $body
     * @param array $headers
     * @return array|null
     */
    public function put(string $endpoint, array $body = [], array $headers = []): ?array
    {
        try {
            $response = $this->client->put($endpoint, [
                'json' => $body,
                'headers' => $headers,
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            $this->handleException($e);
            return null;
        }
    }

    /**
     * Send a DELETE request to the specified endpoint.
     *
     * @param string $endpoint
     * @param array $queryParameters
     * @param array $headers
     * @return array|null
     */
    public function delete(string $endpoint, array $queryParameters = [], array $headers = []): ?array
    {
        try {
            $response = $this->client->delete($endpoint, [
                'query' => $queryParameters,
                'headers' => $headers,
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            $this->handleException($e);
            return null;
        }
    }

    /**
     * Handle the Guzzle response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return array|null
     */
    protected function handleResponse(\Psr\Http\Message\ResponseInterface $response): ?array
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        if ($statusCode >= 200 && $statusCode < 300) {
            $contentType = $response->getHeaderLine('Content-Type');
            if (strpos($contentType, 'application/json') !== false) {
                return json_decode($body, true);
            }
            return ['body' => $body, 'status' => $statusCode]; // Return raw body if not JSON
        }

        // Handle error responses based on status code
        // You might want to throw custom exceptions here
        Log::error("API Error: Status Code - {$statusCode}, Body - {$body}");
        return null;
    }

    /**
     * Handle Guzzle exceptions.
     *
     * @param GuzzleException $e
     */
    protected function handleException(GuzzleException $e): void
    {
        Log::error("Guzzle Exception: " . $e->getMessage());
        // You can also log the request details if needed:
        // if ($e instanceof ConnectException) {
        //     \Log::error("Connection Error: " . $e->getMessage());
        // } elseif ($e instanceof ClientException) {
        //     \Log::error("Client Error: " . $e->getResponse()->getBody());
        // } elseif ($e instanceof ServerException) {
        //     \Log::error("Server Error: " . $e->getResponse()->getBody());
        // }
    }

    // --- Define your API Endpoints and Parameters here ---

    /**
     * Example endpoint for fetching users.
     *
     * @param int $page
     * @param int $limit
     * @return array|null
     */
    public function getUsers(int $page = 1, int $limit = 10): ?array
    {
        return $this->get('/users', [
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * Example endpoint for creating a new user.
     *
     * @param array $userData
     * @return array|null
     */
    public function createUser(array $userData): ?array
    {
        return $this->post('/users', $userData);
    }

    /**
     * Example endpoint for fetching a specific product.
     *
     * @param string $productId
     * @return array|null
     */
    public function getProduct(string $productId): ?array
    {
        return $this->get("/products/{$productId}");
    }

    /**
     * Example endpoint for updating a product.
     *
     * @param string $productId
     * @param array $productData
     * @return array|null
     */
    public function updateProduct(string $productId, array $productData): ?array
    {
        return $this->put("/products/{$productId}", $productData);
    }

    /**
     * Example endpoint for deleting a product.
     *
     * @param string $productId
     * @return array|null
     */
    public function deleteProduct(string $productId): ?array
    {
        return $this->delete("/products/{$productId}");
    }
    /**
     * Handles the specific multi-part form data POST required for the video file upload.
     *
     * @param string $uri The full upload URL.
     * @param array $multipartData Guzzle-formatted multipart array.
     * @return array
     * @throws Exception
     */
    public function postMultipart(string $uri, array $multipartData): array
    {
        try {
            $response = $this->client->post($uri, [
                'multipart' => $multipartData,
            ]);

            // The Pinterest S3 upload step may return an empty body or an XML error on failure.
            if ($response->getStatusCode() !== 204 && $response->getStatusCode() !== 200) {
                throw new Exception("Multipart upload to {$uri} failed with status code " . $response->getStatusCode());
            }

            // Pinterest's S3 upload often returns a 204 No Content on success, so we return an empty array.
            return [];
        } catch (RequestException $e) {
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'No response body.';
            throw new Exception("Multipart request failed for {$uri}: " . $responseBody, $e->getCode());
        }
    }
    /**
     * Fetches raw content (e.g., video file content) from a URL.
     *
     * @param string $uri The full URL (e.g., temporary S3 URL).
     * @return string The raw file contents.
     * @throws Exception
     */
    public function getRaw(string $uri): string
    {
        try {
            $response = $this->client->get($uri);
            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            info("getRaw fn:" . $e->getMessage());
            throw new Exception("Failed to fetch raw content from {$uri}: " . $e->getMessage(), $e->getCode());
        }
    }
}
