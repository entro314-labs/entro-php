# entro-php

PHP SDK for [Entrolytics](https://entrolytics.click) - First-party growth analytics for the edge.

## Installation

```bash
composer require entro314labs/entro-php
```

## Quick Start

```php
<?php

use Entrolytics\Client;

$client = new Client('ent_xxx');

// Track a custom event
$client->track([
    'website_id' => 'abc123',
    'event' => 'purchase',
    'data' => [
        'revenue' => 99.99,
        'currency' => 'USD',
        'product' => 'pro-plan'
    ]
]);

// Track a page view
$client->pageView([
    'website_id' => 'abc123',
    'url' => '/pricing',
    'referrer' => 'https://google.com',
    'title' => 'Pricing - Entrolytics'
]);

// Identify a user
$client->identify([
    'website_id' => 'abc123',
    'user_id' => 'user_456',
    'traits' => [
        'email' => 'user@example.com',
        'plan' => 'pro',
        'company' => 'Acme Inc'
    ]
]);
```

## Collection Endpoints

Entrolytics provides three collection endpoints optimized for different use cases:

### `/api/collect` - Intelligent Routing (Recommended)

The default endpoint that automatically routes to the optimal storage backend based on your plan and website settings.

**Features:**
- Automatic optimization (Free/Pro → Edge, Business/Enterprise → Node.js)
- Zero configuration required
- Best balance of performance and features

**Use when:**
- You want automatic optimization based on your plan
- You're using Entrolytics Cloud
- You don't have specific latency or feature requirements

### `/api/send-native` - Edge Runtime (Fastest)

Direct edge endpoint for sub-50ms global latency.

**Features:**
- Sub-50ms response times globally
- Runs on Vercel Edge Runtime
- Upstash Redis + Neon Serverless
- Best for high-traffic applications

**Limitations:**
- No ClickHouse export
- Basic geo data (country-level)

**Use when:**
- Latency is critical (<50ms required)
- You have high request volume
- You don't need ClickHouse export

### `/api/send` - Node.js Runtime (Full-Featured)

Traditional Node.js endpoint with advanced capabilities.

**Features:**
- ClickHouse export support
- MaxMind GeoIP (city-level accuracy)
- PostgreSQL storage
- Advanced analytics features

**Latency:** 50-150ms (regional)

**Use when:**
- Self-hosted deployments without edge support
- You need ClickHouse data export
- You require city-level geo accuracy
- Custom server-side analytics workflows

## Configuration

### Default (Intelligent Routing)

```php
<?php

use Entrolytics\Client;

// Uses /api/collect by default
$client = new Client('ent_xxx');
```

### Edge Runtime Endpoint

```php
<?php

use Entrolytics\Client;

// Use edge endpoint for sub-50ms latency
$client = new Client('ent_xxx', [
    'host' => 'https://entrolytics.click',
    'endpoint' => '/api/send-native'
]);
```

### Node.js Runtime Endpoint

```php
<?php

use Entrolytics\Client;

// Use Node.js endpoint for ClickHouse export and MaxMind GeoIP
$client = new Client('ent_xxx', [
    'host' => 'https://entrolytics.click',
    'endpoint' => '/api/send'
]);
```

See the [Routing documentation](https://entrolytics.click/docs/concepts/routing) for more details.

## Laravel Integration

### Installation

The package auto-discovers the service provider in Laravel 5.5+.

### Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=entrolytics-config
```

Add to your `.env`:

```env
ENTROLYTICS_WEBSITE_ID=your-website-id
ENTROLYTICS_API_KEY=ent_xxx
```

### Blade Directive

Add the tracking script to your layout:

```blade
<!DOCTYPE html>
<html>
<head>
    @entrolytics
</head>
<body>
    {{ $slot }}
</body>
</html>
```

### Facade Usage

```php
use Entrolytics\Laravel\EntrolyticsFacade as Entrolytics;

// Track event
Entrolytics::track([
    'website_id' => config('entrolytics.website_id'),
    'event' => 'signup',
    'data' => ['plan' => 'pro']
]);

// Track page view
Entrolytics::pageView([
    'website_id' => config('entrolytics.website_id'),
    'url' => request()->fullUrl(),
    'referrer' => request()->header('Referer')
]);

// Identify user
Entrolytics::identify([
    'website_id' => config('entrolytics.website_id'),
    'user_id' => (string) auth()->id(),
    'traits' => [
        'email' => auth()->user()->email,
        'plan' => auth()->user()->subscription->plan
    ]
]);
```

### Dependency Injection

```php
use Entrolytics\Client;

class PurchaseController extends Controller
{
    public function __construct(
        protected Client $entrolytics
    ) {}

    public function store(Request $request)
    {
        // Process purchase...

        $this->entrolytics->track([
            'website_id' => config('entrolytics.website_id'),
            'event' => 'purchase',
            'data' => [
                'revenue' => $order->total,
                'currency' => 'USD'
            ],
            'user_id' => (string) $request->user()->id
        ]);

        return response()->json(['status' => 'ok']);
    }
}
```

### Automatic Page View Tracking

Register the middleware in `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ...
    \Entrolytics\Laravel\TrackPageViewMiddleware::class,
];
```

Or for specific routes:

```php
Route::middleware(['entrolytics.track'])->group(function () {
    Route::get('/dashboard', DashboardController::class);
    Route::get('/profile', ProfileController::class);
});
```

## API Reference

### Client Methods

#### `track(array $params): bool`

Track a custom event.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| website_id | string | Yes | Your Entrolytics website ID |
| event | string | Yes | Event name (e.g., 'purchase', 'signup') |
| data | array | No | Additional event data |
| url | string | No | Page URL where event occurred |
| referrer | string | No | Referrer URL |
| user_id | string | No | User identifier |
| session_id | string | No | Session identifier |
| user_agent | string | No | User agent string |
| ip_address | string | No | Client IP address |

#### `pageView(array $params): bool`

Track a page view.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| website_id | string | Yes | Your Entrolytics website ID |
| url | string | Yes | Page URL |
| referrer | string | No | Referrer URL |
| title | string | No | Page title |
| user_id | string | No | User identifier |
| user_agent | string | No | User agent string |
| ip_address | string | No | Client IP address |

#### `identify(array $params): bool`

Identify a user with traits.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| website_id | string | Yes | Your Entrolytics website ID |
| user_id | string | Yes | Unique user identifier |
| traits | array | No | User traits (email, plan, etc.) |

## Error Handling

```php
use Entrolytics\Client;
use Entrolytics\Exception\AuthenticationException;
use Entrolytics\Exception\ValidationException;
use Entrolytics\Exception\RateLimitException;
use Entrolytics\Exception\NetworkException;
use Entrolytics\Exception\EntrolyticsException;

$client = new Client('ent_xxx');

try {
    $client->track([
        'website_id' => 'abc123',
        'event' => 'test'
    ]);
} catch (AuthenticationException $e) {
    // Invalid API key
    echo "Auth error: " . $e->getMessage();

} catch (ValidationException $e) {
    // Invalid request data
    echo "Validation error: " . $e->getMessage();

} catch (RateLimitException $e) {
    // Rate limit exceeded
    echo "Rate limited. Retry after: " . $e->getRetryAfter() . " seconds";

} catch (NetworkException $e) {
    // Network request failed
    echo "Network error: " . $e->getMessage();

} catch (EntrolyticsException $e) {
    // Other API errors
    echo "Error: " . $e->getMessage();
}
```

## Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| api_key | string | Required | Your Entrolytics API key |
| host | string | `https://entrolytics.click` | Entrolytics host URL |
| timeout | float | 10.0 | Request timeout in seconds |

```php
$client = new Client('ent_xxx', [
    'host' => 'https://analytics.yourdomain.com',
    'timeout' => 5.0,
]);
```

## Self-Hosted

For self-hosted Entrolytics instances, set the custom host:

```php
$client = new Client('ent_xxx', [
    'host' => 'https://analytics.yourdomain.com'
]);
```

In Laravel, set in your `.env`:

```env
ENTROLYTICS_HOST=https://analytics.yourdomain.com
```

## License

MIT License - see [LICENSE](LICENSE) for details.
