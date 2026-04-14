import app from 'flarum/common/app';

app.initializers.add('proxytracer-proxytracer-common', () => {
  console.log('[proxytracer/flarum-proxytracer] Hello, forum and admin!');
});
