<?php

declare(strict_types=1);

namespace Proxytracer\Proxytracer\Listeners;

use Flarum\Foundation\ValidationException;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Event\Saving;
use Flarum\User\Exception\PermissionDeniedException;
use Laminas\Diactoros\Response\HtmlResponse;
use Proxytracer\Proxytracer\Services\ProxyTracer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BlockProxyListeners implements MiddlewareInterface
{
    protected SettingsRepositoryInterface $settings;

    protected ProxyTracer $proxyTracer;

    public function __construct(SettingsRepositoryInterface $settings, ProxyTracer $proxyTracer)
    {
        $this->settings = $settings;
        $this->proxyTracer = $proxyTracer;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        $routeName = $request->getAttribute('routeName', '');
        $isApi = strpos((string) $routeName, 'api.') === 0 || strpos($path, '/api') === 0;

        if ((bool) $this->settings->get('proxytracer.block_all', false)) {
            $ip = $this->getClientIp();

            if ($this->isProxyBlocked($ip, 'all')) {
                /** @var string $message */
                $message = $this->settings->get('proxytracer.custom_block_message', 'Access denied: Proxy/VPN detected.');

                if ($isApi) {
                    throw new PermissionDeniedException($message);
                }

                $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

                return new HtmlResponse(
                    "<div style='text-align: center; padding-top: 10%; font-family: system-ui, sans-serif;'>
                        <div style='font-size: 6rem; margin-bottom: 10px;'>⚠️</div>
                        <p style='font-size: 1.2rem; color: #555;'>".$safeMessage.'</p>
                    </div>',
                    403
                );
            }
        }

        if ($method === 'POST' && str_ends_with($path, '/token')) {
            if ((bool) $this->settings->get('proxytracer.block_login', false)) {
                $ip = $this->getClientIp();

                if ($this->isProxyBlocked($ip, 'login')) {
                    /** @var string $message */
                    $message = $this->settings->get('proxytracer.custom_block_message', 'Access denied: Proxy/VPN detected.');

                    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
                    throw new ValidationException(['identification' => $safeMessage]);
                }
            }
        }

        return $handler->handle($request);
    }

    public function handleSignup(Saving $event): void
    {
        if (! $event->user->exists && (bool) $this->settings->get('proxytracer.block_signup', false)) {
            $ip = $this->getClientIp();

            if ($this->isProxyBlocked($ip, 'signup')) {
                /** @var string $message */
                $message = $this->settings->get('proxytracer.custom_block_message', '⚠️ Access denied: Proxy/VPN detected.');

                $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
                throw new ValidationException(['proxy' => $safeMessage]);
            }
        }
    }

    protected function getClientIp(): string
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

        if (isset($_SERVER['HTTP_X_REAL_IP']) && is_string($_SERVER['HTTP_X_REAL_IP']) && $_SERVER['HTTP_X_REAL_IP'] !== '') {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && is_string($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] !== '') {
            $ips = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
            $ip = $ips[0];
        }

        if ((bool) $this->settings->get('proxytracer.trust_cloudflare', false)) {
            if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && is_string($_SERVER['HTTP_CF_CONNECTING_IP']) && $_SERVER['HTTP_CF_CONNECTING_IP'] !== '') {
                $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
            }
        }

        return $ip;
    }

    protected function isProxyBlocked(string $ip, string $context): bool
    {
        try {
            return $this->proxyTracer->isProxy($ip);
        } catch (\Throwable $e) {
            if ($context === 'all') {
                throw new \RuntimeException('API CRASH REPORT: '.$e->getMessage());
            }

            $field = $context === 'login' ? 'identification' : 'proxy';
            throw new ValidationException([$field => 'API CRASH REPORT: '.$e->getMessage()]);
        }
    }
}
