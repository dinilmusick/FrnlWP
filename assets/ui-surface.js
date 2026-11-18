/* FrnlWP browser UI surface: no browser runner or backend authority. */
(function (host) {
  'use strict';

  function createUiSurface(root) {
    if (!root || typeof root.querySelectorAll !== 'function') {
      throw new TypeError('A document or DOM element root is required');
    }
    const mounts = new WeakMap();

    function elements() {
      const found = Array.from(root.querySelectorAll('[data-frnl-component]'));
      if (typeof root.matches === 'function' && root.matches('[data-frnl-component]')) {
        found.unshift(root);
      }
      const byId = new Map();
      for (const element of found) {
        if (element.isConnected === false) continue;
        const id = element.getAttribute('data-frnl-component');
        if (!id) throw new Error('Empty Frnl component ID');
        if (byId.has(id)) throw new Error('Duplicate Frnl component ID: ' + id);
        byId.set(id, element);
        if (!mounts.has(element)) mounts.set(element, Date.now());
      }
      return byId;
    }

    function isVisible(element) {
      if (element.closest('[hidden], [aria-hidden="true"], [inert]')) return false;
      const view = element.ownerDocument && element.ownerDocument.defaultView;
      if (!view || typeof view.getComputedStyle !== 'function') return false;
      const style = view.getComputedStyle(element);
      return style.display !== 'none' && style.visibility !== 'hidden'
        && element.getClientRects().length > 0;
    }

    function controls(element) {
      const name = (element.tagName || '').toLowerCase();
      if (name === 'button' || element.matches('[role="button"], a[href]')) return ['click'];
      if (name === 'input' || name === 'textarea') return ['input', 'change'];
      if (name === 'select') return ['change'];
      return [];
    }

    function inspect() {
      const byId = elements();
      const components = [];
      for (const [id, element] of byId) {
        const ancestor = element.parentElement &&
          element.parentElement.closest('[data-frnl-component]');
        const parentId = ancestor && byId.get(ancestor.getAttribute('data-frnl-component')) === ancestor
          ? ancestor.getAttribute('data-frnl-component') : null;
        const visible = isVisible(element);
        const readOnly = element.readOnly === true || element.getAttribute('aria-readonly') === 'true';
        const enabled = !element.disabled && element.getAttribute('aria-disabled') !== 'true'
          && !element.closest('fieldset[disabled]');
        const rect = visible ? element.getBoundingClientRect() : null;
        components.push({
          id, parentId, type: element.tagName.toLowerCase(),
          state: element.getAttribute('data-frnl-state') || 'idle',
          role: element.getAttribute('role') || null,
          lifecycle: { mounted: true, rendered: true, mountedAt: mounts.get(element) },
          visible, enabled, controls: visible && enabled ? controls(element).filter(control => !readOnly || control === 'click') : [],
          rect: rect ? { x: rect.x, y: rect.y, width: rect.width, height: rect.height } : null,
          children: []
        });
      }
      const map = new Map(components.map(component => [component.id, component]));
      for (const component of components) {
        if (component.parentId) map.get(component.parentId).children.push(component.id);
      }
      return {
        apiVersion: 1, componentCount: components.length,
        capturedAt: Date.now(), components,
        tree: components.filter(component => component.parentId === null).map(component => component.id)
      };
    }

    function invoke(id, controlId, payload) {
      const byId = elements();
      const element = byId.get(id);
      const component = inspect().components.find(component => component.id === id);
      if (!element || !component) throw new Error('Frnl component is not mounted: ' + id);
      if (!component.controls.includes(controlId)) {
        throw new Error('Frnl control is unavailable: ' + id + '.' + controlId);
      }
      if (controlId === 'click') {
        element.click();
        return true;
      }
      if (payload === undefined || payload === null || typeof payload === 'object') {
        throw new TypeError('Input or change requires a scalar value');
      }
      const value = String(payload);
      if (element.tagName.toLowerCase() === 'select' &&
          !Array.from(element.options).some(option => option.value === value)) {
        throw new Error('Selection is not an available option');
      }
      if (element.tagName.toLowerCase() === 'input' &&
          (element.type === 'checkbox' || element.type === 'radio')) {
        if (value !== 'true' && value !== 'false') {
          throw new TypeError('Checkbox or radio requires a boolean value');
        }
        if (element.type === 'radio' && value === 'false') {
          throw new Error('A radio control cannot be unchecked directly');
        }
        element.checked = value === 'true';
      } else {
        element.value = value;
      }
      const view = element.ownerDocument.defaultView;
      element.dispatchEvent(new view.Event(controlId, { bubbles: true }));
      return true;
    }
    return Object.freeze({ inspect, invoke });
  }

  const api = host.FrnlWP || {};
  api.createUiSurface = createUiSurface;
  host.FrnlWP = api;
})(typeof window !== 'undefined' ? window : globalThis);
