import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

class FakeElement {
  constructor(id, tagName = 'DIV', parentElement = null) {
    this.attrs = new Map([['data-frnl-component', id]]);
    this.tagName = tagName;
    this.parentElement = parentElement;
    this.ownerDocument = {
      defaultView: {
        getComputedStyle: () => ({ display: 'block', visibility: 'visible' }),
        Event: class { constructor(type, init) { this.type = type; this.bubbles = init.bubbles; } }
      }
    };
    this.isConnected = true;
    this.visible = true;
    this.disabled = false;
    this.value = '';
    this.options = [];
    this.events = [];
    this.clicks = 0;
  }
  getAttribute(key) { return this.attrs.get(key) ?? null; }
  matches(selector) {
    if (selector === '[data-frnl-component]') return this.attrs.has('data-frnl-component');
    if (selector === '[role="button"], a[href]') {
      return this.getAttribute('role') === 'button' || this.attrs.has('href');
    }
    return false;
  }
  closest(selector) {
    for (let el = this; el; el = el.parentElement) {
      if (selector === '[data-frnl-component]' && el.matches(selector)) return el;
      if (selector.includes('[hidden]') && (el.attrs.has('hidden') || el.getAttribute('aria-hidden') === 'true')) return el;
    }
    return null;
  }
  getClientRects() { return this.visible ? [{ x: 2, y: 3, width: 20, height: 30 }] : []; }
  getBoundingClientRect() { return { x: 2, y: 3, width: 20, height: 30 }; }
  click() { this.clicks++; this.events.push('click'); }
  dispatchEvent(event) { this.events.push(event.type); return true; }
}
const js = fs.readFileSync(new URL('../assets/ui-surface.js', import.meta.url), 'utf8');
function setup() {
  const window = {};
  vm.runInNewContext(js, { window });
  const parent = new FakeElement('root');
  const button = new FakeElement('submit', 'BUTTON', parent);
  const select = new FakeElement('country', 'SELECT', parent);
  select.options = [{ value: '0' }, { value: 'in' }];
  const text = new FakeElement('notes', 'TEXTAREA', parent);
  let mounted = [parent, button, select, text];
  const doc = { querySelectorAll: () => mounted };
  const surface = window.FrnlWP.createUiSurface(doc);
  return { surface, parent, button, select, text, mount: (items) => { mounted = items; } };
}
test('reflects mounted hierarchy, geometry and actual DOM state', () => {
  const { surface, button, mount } = setup();
  const snapshot = surface.inspect();
  assert.equal(snapshot.componentCount, 4);
  assert.deepEqual(Array.from(snapshot.tree), ['root']);
  assert.deepEqual(Array.from(snapshot.components[0].children), ['submit', 'country', 'notes']);
  assert.equal(snapshot.components[1].rect.width, 20);
  const mountTime = snapshot.components[1].lifecycle.mountedAt;
  assert.equal(surface.inspect().components[1].lifecycle.mountedAt, mountTime);
  button.isConnected = false;
  assert.equal(surface.inspect().componentCount, 3);
  mount([]);
  assert.equal(surface.inspect().componentCount, 0);
});
test('invokes the native UI event path and validates payloads', () => {
  const { surface, button, select, text } = setup();
  assert.equal(surface.invoke('submit', 'click'), true);
  assert.equal(button.clicks, 1);
  assert.equal(surface.invoke('country', 'change', '0'), true);
  assert.equal(select.value, '0');
  assert.deepEqual(select.events, ['change']);
  assert.equal(surface.invoke('notes', 'input', 'Hello'), true);
  assert.equal(text.value, 'Hello');
  assert.deepEqual(text.events, ['input']);
  assert.throws(() => surface.invoke('country', 'change', 'absent'), /available option/);
  assert.throws(() => surface.invoke('notes', 'input', {}), /scalar/);
  assert.throws(() => surface.invoke('absent', 'click'), /not mounted/);
  assert.throws(() => surface.invoke('root', 'click'), /unavailable/);
});
test('rejects hidden, disabled and ambiguous component targets', () => {
  const { surface, button, select, mount, parent } = setup();
  button.visible = false;
  assert.equal(surface.inspect().components[1].visible, false);
  assert.throws(() => surface.invoke('submit', 'click'), /unavailable/);
  button.visible = true;
  parent.attrs.set('aria-hidden', 'true');
  assert.throws(() => surface.invoke('submit', 'click'), /unavailable/);
  parent.attrs.delete('aria-hidden');
  select.disabled = true;
  assert.throws(() => surface.invoke('country', 'change', 'in'), /unavailable/);
  mount([parent, new FakeElement('dup'), new FakeElement('dup')]);
  assert.throws(() => surface.inspect(), /Duplicate Frnl component ID/);
});

test('readonly controls are inspectable but cannot be synthetically edited', () => {
  const { surface, text, select, button } = setup();
  text.readOnly = true;
  select.attrs.set('aria-readonly', 'true');
  const snapshot = surface.inspect();
  assert.deepEqual(Array.from(snapshot.components.find(c => c.id === 'notes').controls), []);
  assert.deepEqual(Array.from(snapshot.components.find(c => c.id === 'country').controls), []);
  assert.equal(snapshot.components.find(c => c.id === 'notes').enabled, true);
  assert.throws(() => surface.invoke('notes', 'input', 'override'), /unavailable/);
  assert.throws(() => surface.invoke('country', 'change', 'in'), /unavailable/);
  assert.equal(text.value, '');
  assert.equal(select.value, '');
  assert.equal(surface.invoke('submit', 'click'), true);
  assert.equal(button.clicks, 1);
  text.readOnly = false;
  select.attrs.delete('aria-readonly');
  assert.equal(surface.invoke('notes', 'input', 'editable'), true);
  assert.equal(text.value, 'editable');
});
