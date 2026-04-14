import app from 'flarum/admin/app';
import m from 'mithril';

app.initializers.add('proxytracer/flarum-proxytracer', () => {
  app.extensionData
    .for('proxytracer-proxytracer')
    .registerSetting({
      setting: 'proxytracer.api_key',
      label: app.translator.trans('proxytracer-proxytracer.admin.settings.api_key_label'),
      help: app.translator.trans('proxytracer-proxytracer.admin.settings.api_key_help', {
        a: <a href="https://proxytracer.com/dashboard" target="_blank" />
      }),
      type: 'password',
    })
    .registerSetting({
      setting: 'proxytracer.block_all',
      label: app.translator.trans('proxytracer-proxytracer.admin.settings.block_all_label'),
      type: 'boolean',
    })
    .registerSetting({
      setting: 'proxytracer.block_login',
      label: app.translator.trans('proxytracer-proxytracer.admin.settings.block_login_label'),
      type: 'boolean',
    })
    .registerSetting({
      setting: 'proxytracer.block_signup',
      label: app.translator.trans('proxytracer-proxytracer.admin.settings.block_signup_label'),
      type: 'boolean',
    })
    .registerSetting({
      setting: 'proxytracer.api_timeout',
      label: app.translator.trans('proxytracer-proxytracer.admin.settings.api_timeout_label'),
      type: 'number',
      default: 2000,
    })
    .registerSetting({
      setting: 'proxytracer.cache_expiry',
      label: app.translator.trans('proxytracer-proxytracer.admin.settings.cache_expiry_label'),
      type: 'number',
      default: 1,
    })
    .registerSetting({
      setting: 'proxytracer.fail_open',
      label: app.translator.trans('proxytracer-proxytracer.admin.settings.fail_open_label'),
      help: app.translator.trans('proxytracer-proxytracer.admin.settings.fail_open_help'),
      type: 'boolean',
      default: true,
    })
    .registerSetting({
      setting: 'proxytracer.trust_cloudflare',
      label: app.translator.trans('proxytracer-proxytracer.admin.settings.trust_cloudflare_label'),
      help: app.translator.trans('proxytracer-proxytracer.admin.settings.trust_cloudflare_help'),
      type: 'boolean',
      default: false,
    })
    .registerSetting({
      setting: 'proxytracer.custom_block_message',
      label: app.translator.trans('proxytracer-proxytracer.admin.settings.custom_block_message_label'),
      type: 'textarea',
      default: 'Access denied: Proxy/VPN detected.',
    })
    .registerSetting({
      setting: 'proxytracer.ip_whitelist',
      label: app.translator.trans('proxytracer-proxytracer.admin.settings.ip_whitelist_label'),
      help: app.translator.trans('proxytracer-proxytracer.admin.settings.ip_whitelist_help'),
      type: 'textarea',
    });
});
