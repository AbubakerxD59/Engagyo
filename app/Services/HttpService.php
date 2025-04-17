<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;

class HttpService
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * ApiService constructor.
     *
     * @param string|null $baseUrl
     */
    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
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
                'query' => $queryParameters,
                'headers' => $headers,
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

    // Add more endpoint-specific methods here
}
