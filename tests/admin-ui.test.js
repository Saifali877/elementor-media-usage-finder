const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

class Element {
	constructor() { this.hidden = false; this.disabled = false; this.children = []; this.handlers = {}; this.textContent = ''; this.dataset = {}; }
	addEventListener(event, handler) { this.handlers[event] = handler; }
	click() { this.handlers.click(); }
	appendChild(node) { this.children.push(node); }
	replaceChildren() { this.children = []; }
	get childElementCount() { return this.children.length; }
}

const names = ['app', 'choose', 'scan', 'cancel', 'preview', 'placeholder', 'image-name', 'status', 'results', 'rows', 'empty', 'progress', 'result-count'];
const elements = Object.fromEntries(names.map(name => ['emuf-' + name, new Element()]));
elements['emuf-app'].dataset.attachmentId = '42';
const response = { success: true, data: { results: [{ title: 'Home', url: '/wp-admin/post.php?post=7&action=edit', type: 'Page', status: 'publish', count: 1 }], scanned: 20, next: null } };
const context = {
	document: { getElementById: id => elements[id], createElement: () => new Element() },
	window: { wp: { media: () => ({ on() {}, open() {} }) } },
	wp: { media: () => ({ on() {}, open() {} }) },
	EMUF: { ajaxUrl: '/wp-admin/admin-ajax.php', nonce: 'test', labels: { working: 'Scanning', finished: 'Complete', checked: 'checked', found: 'found', none: 'No matches', error: 'Error', cancelled: 'Cancelled' } },
	jQuery: Object.assign(() => {}, { post: () => Promise.resolve(response) }),
};
vm.runInNewContext(fs.readFileSync('assets/js/admin.js', 'utf8'), context);
elements['emuf-scan'].click();
setImmediate(() => {
	assert.equal(elements['emuf-rows'].childElementCount, 1);
	assert.equal(elements['emuf-result-count'].textContent, '1 found');
	assert.match(elements['emuf-status'].textContent, /20 checked/);
	assert.equal(elements['emuf-results'].hidden, false);
	assert.equal(elements['emuf-scan'].disabled, false);
	assert.equal(elements['emuf-cancel'].hidden, true);
	elements['emuf-cancel'].click();
	assert.match(elements['emuf-status'].textContent, /20 checked/);
	console.log('Admin scan result UI passed.');
});
