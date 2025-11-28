<?php

declare(strict_types=1);

namespace Entrolytics;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Entrolytics\Exception\AuthenticationException;
use Entrolytics\Exception\EntrolyticsException;
use Entrolytics\Exception\NetworkException;
use Entrolytics\Exception\RateLimitException;
use Entrolytics\Exception\ValidationException;

/**
 * Entrolytics PHP Client
 *
 * @example
 * $client = new Entrolytics\Client('ent_xxx');
 *
 * $client->track([
 *     'website_id' => 'abc123',
 *     'event' => 'purchase',
 *     'data' => ['revenue' => 99.99]
 * ]);
 */
class Client
{
    private const DEFAULT_HOST = 'https://entrolytics.click';
    private const DEFAULT_TIMEOUT = 10.0;
    private const VERSION = '1.1.0';

    private string $apiKey;
    private string $host;
    private float $timeout;
    private HttpClient $http;

    /**
     * Create a new Entrolytics client.
     *
     * @param string $apiKey Your Entrolytics API key
     * @param array{host?: string, timeout?: float} $options Configuration options
     */
    public function __construct(string $apiKey, array $options = [])
    {
        if (empty($apiKey)) {
            throw new AuthenticationException('API key is required');
        }

        $this->apiKey = $apiKey;
        $this->host = rtrim($options['host'] ?? self::DEFAULT_HOST, '/');
        $this->timeout = $options['timeout'] ?? self::DEFAULT_TIMEOUT;

        $this->http = new HttpClient([
            'base_uri' => $this->host,
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'User-Agent' => 'entrolytics-php/' . self::VERSION,
            ],
        ]);
    }

    /**
     * Track a custom event.
     *
     * @param array{
     *     website_id: string,
     *     event: string,
     *     data?: array<string, mixed>,
     *     url?: string,
     *     referrer?: string,
     *     user_id?: string,
     *     session_id?: string,
     *     user_agent?: string,
     *     ip_address?: string
     * } $params Event parameters
     * @return bool True on success
     * @throws EntrolyticsException
     */
    public function track(array $params): bool
    {
        $websiteId = $params['website_id'] ?? null;
        $event = $params['event'] ?? null;

        if (empty($websiteId)) {
            throw new ValidationException('website_id is required');
        }
        if (empty($event)) {
            throw new ValidationException('event is required');
        }

        $payload = [
            'type' => 'event',
            'payload' => [
                'website' => $websiteId,
                'name' => $event,
                'data' => $params['data'] ?? [],
                'url' => $params['url'] ?? null,
                'referrer' => $params['referrer'] ?? null,
                'timestamp' => date('c'),
            ],
        ];

        if (!empty($params['user_id'])) {
            $payload['payload']['userId'] = $params['user_id'];
        }
        if (!empty($params['session_id'])) {
            $payload['payload']['sessionId'] = $params['session_id'];
        }

        $headers = [];
        if (!empty($params['user_agent'])) {
            $headers['X-Forwarded-User-Agent'] = $params['user_agent'];
        }
        if (!empty($params['ip_address'])) {
            $headers['X-Forwarded-For'] = $params['ip_address'];
        }

        return $this->send($payload, $headers);
    }

    /**
     * Track a page view.
     *
     * @param array{
     *     website_id: string,
     *     url: string,
     *     referrer?: string,
     *     title?: string,
     *     user_id?: string,
     *     session_id?: string,
     *     user_agent?: string,
     *     ip_address?: string
     * } $params Page view parameters
     * @return bool True on success
     * @throws EntrolyticsException
     */
    public function pageView(array $params): bool
    {
        $websiteId = $params['website_id'] ?? null;
        $url = $params['url'] ?? null;

        if (empty($websiteId)) {
            throw new ValidationException('website_id is required');
        }
        if (empty($url)) {
            throw new ValidationException('url is required');
        }

        $data = [];
        if (!empty($params['title'])) {
            $data['title'] = $params['title'];
        }

        $payload = [
            'type' => 'event',
            'payload' => [
                'website' => $websiteId,
                'name' => '$pageview',
                'data' => $data,
                'url' => $url,
                'referrer' => $params['referrer'] ?? null,
                'timestamp' => date('c'),
            ],
        ];

        if (!empty($params['user_id'])) {
            $payload['payload']['userId'] = $params['user_id'];
        }
        if (!empty($params['session_id'])) {
            $payload['payload']['sessionId'] = $params['session_id'];
        }

        $headers = [];
        if (!empty($params['user_agent'])) {
            $headers['X-Forwarded-User-Agent'] = $params['user_agent'];
        }
        if (!empty($params['ip_address'])) {
            $headers['X-Forwarded-For'] = $params['ip_address'];
        }

        return $this->send($payload, $headers);
    }

    /**
     * Identify a user with traits.
     *
     * @param array{
     *     website_id: string,
     *     user_id: string,
     *     traits?: array<string, mixed>
     * } $params Identification parameters
     * @return bool True on success
     * @throws EntrolyticsException
     */
    public function identify(array $params): bool
    {
        $websiteId = $params['website_id'] ?? null;
        $userId = $params['user_id'] ?? null;

        if (empty($websiteId)) {
            throw new ValidationException('website_id is required');
        }
        if (empty($userId)) {
            throw new ValidationException('user_id is required');
        }

        $payload = [
            'type' => 'identify',
            'payload' => [
                'website' => $websiteId,
                'userId' => $userId,
                'traits' => $params['traits'] ?? [],
                'timestamp' => date('c'),
            ],
        ];

        return $this->send($payload);
    }

    /**
     * Send a request to the Entrolytics API.
     *
     * @param array<string, mixed> $payload Request payload
     * @param array<string, string> $headers Additional headers
     * @return bool True on success
     * @throws EntrolyticsException
     */
    private function send(array $payload, array $headers = []): bool
    {
        try {
            $response = $this->http->post('/api/send', [
                'json' => $payload,
                'headers' => $headers,
            ]);

            $statusCode = $response->getStatusCode();
            return $statusCode === 200 || $statusCode === 201;
        } catch (RequestException $e) {
            $response = $e->getResponse();

            if ($response === null) {
                throw new NetworkException('Request failed: ' . $e->getMessage(), $e);
            }

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();

            match ($statusCode) {
                401 => throw new AuthenticationException(),
                400 => throw new ValidationException(
                    $this->extractErrorMessage($body) ?? 'Invalid request'
                ),
                429 => throw new RateLimitException(
                    'Rate limit exceeded',
                    $this->extractRetryAfter($response)
                ),
                default => throw new EntrolyticsException(
                    "Request failed with status $statusCode",
                    $statusCode
                ),
            };
        } catch (GuzzleException $e) {
            throw new NetworkException('Request failed: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Extract error message from response body.
     */
    private function extractErrorMessage(string $body): ?string
    {
        $data = json_decode($body, true);
        return $data['error'] ?? null;
    }

    /**
     * Extract Retry-After header value.
     */
    private function extractRetryAfter($response): ?int
    {
        $header = $response->getHeader('Retry-After');
        if (!empty($header)) {
            return (int) $header[0];
        }
        return null;
    }
}
