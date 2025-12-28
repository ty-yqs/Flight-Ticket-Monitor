This folder should contain the frontend vendor files for offline usage:

- `vue.min.js`  (Vue 2 runtime)
- `element-ui.js` (Element UI JS)
- `element-ui.css` (Element UI theme CSS)

To download these files locally, run from project root:

```bash
./scripts/fetch_frontend_deps.sh
```

If the script fails due to SSL/curl issues in your environment, you can manually download:

```bash
curl -L -o static/vue.min.js https://unpkg.com/vue@2.6.14/dist/vue.min.js
curl -L -o static/element-ui.js https://unpkg.com/element-ui@2.15.13/lib/index.js
curl -L -o static/element-ui.css https://unpkg.com/element-ui@2.15.13/lib/theme-chalk/index.css
```

After these files are present, the app will load the local assets at `/static/*`.
