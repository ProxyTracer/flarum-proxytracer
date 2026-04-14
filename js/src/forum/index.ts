import app from 'flarum/forum/app';

export { default as extend } from './extend';

app.initializers.add('proxytracer-proxytracer', () => {
  console.log('[proxytracer/flarum-proxytracer] Hello, forum!');
});
