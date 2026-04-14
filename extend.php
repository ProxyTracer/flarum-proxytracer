<?php

namespace Proxytracer\Proxytracer;

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

use Flarum\Extend;
use Flarum\User\Event\Saving;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Settings)
        ->default('proxytracer.api_timeout', 2000)
        ->default('proxytracer.cache_expiry', 32)
        ->default('proxytracer.fail_open', true)
        ->default('proxytracer.trust_cloudflare', false)
        ->default('proxytracer.block_all', false)
        ->default('proxytracer.block_login', false)
        ->default('proxytracer.block_signup', false)
        ->default('proxytracer.custom_block_message', 'Access denied: Proxy/VPN detected.'),

    (new Extend\Middleware('forum'))
        ->add(Listeners\BlockProxyListeners::class),

    (new Extend\Middleware('admin'))
        ->add(Listeners\BlockProxyListeners::class),

    (new Extend\Middleware('api'))
        ->add(Listeners\BlockProxyListeners::class),

    (new Extend\Event)
        ->listen(Saving::class, Listeners\BlockProxyListeners::class.'@handleSignup'),
];
