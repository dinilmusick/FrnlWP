# FrnlWP runtime bridge

FrnlWP keeps the same split as FrnlTS:

- PHP defines semantic component contracts, states, events, themes and flows.
- Browser JavaScript owns interactive runtime state.
- Zustand is the concrete state engine in the browser bundle.
- WordPress only enqueues the generated runtime asset.

Build the browser asset with:

`npm install`
`npm run build`

Do not duplicate state machinery inside Elementor widgets; widgets emit Frnl semantic attributes and consume the shared runtime.

## Shipped browser-native UI surface

The repository now ships `assets/ui-surface.js` without an npm build. It is
a DOM-backed semantic inspection/action adapter for FloksWP and other rendered
components carrying `data-frnl-component` and `data-frnl-state`.

When the package's assets have been copied to a **publicly served** URL:

```php
\FrnlWP\WordPress\Assets::enqueueSurface($publicFrnlWpPackageBaseUrl);
```

```js
const surface = window.FrnlWP.createUiSurface(document);
const snapshot = surface.inspect();
surface.invoke('submit-button', 'click');  // native button click path
surface.invoke('country-picker', 'change', 'in'); // native change event
```

Snapshots are sampled from **currently mounted DOM nodes**, with parent/child
relationships, visibility, mounted timestamps, realized geometry and supported
controls. Controls on hidden or disabled nodes are not exposed; duplicate IDs
cause an error. `invoke` uses the existing DOM event path, not a second
callback registry. No server, browser automation process, screenshot, network
endpoint or credential is created. Do not expose it over HTTP without
application-owned session authorization and an active browser session.

This is a separate, optional asset from the compiled Zustand state bundle
(`Assets::enqueue()`, which still expects `dist/runtime.global.js` generated
by the npm build). The UI surface does not replace that bundle or implement
full FrnlTS semantic state parity. A Composer dependency alone does not make
assets publicly accessible; the consuming plugin/theme must publish the file
and pass the correct URL. Real WordPress/Elementor browser verification
remains required before production conformance certification.
