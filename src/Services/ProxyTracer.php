<?php

declare(strict_types=1);

namespace Proxytracer\Proxytracer\Services;

use Flarum\Settings\SettingsRepositoryInterface;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Cache\Repository as Cache;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Throwable;

class ProxyTracer
{
    protected SettingsRepositoryInterface $settings;

    protected Cache $cache;

    protected HttpClient $http;

    protected LoggerInterface $logger;

    public function __construct(SettingsRepositoryInterface $settings, Cache $cache, HttpClient $http, LoggerInterface $logger)
    {
        $this->settings = $settings;
        $this->cache = $cache;
        $this->http = $http;
        $this->logger = $logger;
    }

    public function isProxy(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        if ($this->isWhitelisted($ip)) {
            return false;
        }

        $cacheKey = 'proxytracer_ip_'.md5($ip);
        if ($this->cache->has($cacheKey)) {
            return (bool) $this->cache->get($cacheKey);
        }

        $rateLimitKey = 'proxytracer_rl_'.md5($ip);
        $attempts = (int) $this->cache->get($rateLimitKey, 0);

        if ($attempts > 10) {
            return ! $this->settings->get('proxytracer.fail_open', true);
        }

        $this->cache->put($rateLimitKey, $attempts + 1, 60);

        /** @var string $apiKey */
        $apiKey = $this->settings->get('proxytracer.api_key', '');

        if ($apiKey === '') {
            return false;
        }

        $timeout = (int) $this->settings->get('proxytracer.api_timeout', 2000);
        $timeoutSeconds = $timeout / 1000;

        try {
            $response = $this->http->request('GET', 'https://api.proxytracer.com/v1/check/'.$ip, [
                'headers' => [
                    'Authorization' => 'Bearer '.$apiKey,
                    'Accept' => 'application/json',
                ],
                'timeout' => $timeoutSeconds,
                'connect_timeout' => $timeoutSeconds,
            ]);

            $data = json_decode((string) $response->getBody(), true);

            // Level 8 requires guaranteeing $data is an array before checking keys
            $isProxy = is_array($data) && isset($data['proxy']) && $data['proxy'] === true;

            $expiryHours = (int) $this->settings->get('proxytracer.cache_expiry', 32);
            $this->cache->put($cacheKey, $isProxy, $expiryHours * 3600);

            return $isProxy;

        } catch (Throwable $e) {
            $failOpen = (bool) $this->settings->get('proxytracer.fail_open', true);

            return ! $failOpen;
        }
    }

    protected function isWhitelisted(string $ip): bool
    {
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return true;
        }

        /** @var string $whitelistString */
        $whitelistString = $this->settings->get('proxytracer.ip_whitelist', '');

        if (trim($whitelistString) === '') {
            return false;
        }

        $allowedIps = array_filter(array_map('trim', explode("\n", $whitelistString)));

        return IpUtils::checkIp($ip, $allowedIps);
    }
}
