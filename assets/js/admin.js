(function ($) {
	'use strict';
	const app = document.getElementById('emuf-app');
	if (!app || typeof EMUF === 'undefined' || !window.wp || !wp.media) return;

	const el = {};
	['choose', 'scan', 'cancel', 'preview', 'placeholder', 'image-name', 'status', 'results', 'rows', 'empty', 'progress', 'result-count'].forEach(function (name) {
		el[name] = document.getElementById('emuf-' + name);
	});
	let attachmentId = Number(app.dataset.attachmentId) || 0;
	let run = 0;
	let request = null;
	let matches = 0;
	let scanning = false;

	function status(message) { el.status.textContent = message; }
	function busy(value) {
		scanning = value;
		el.scan.disabled = value || !attachmentId;
		el.choose.disabled = value;
		el.cancel.hidden = !value;
		el.progress.hidden = !value;
	}
	function reset() {
		el.rows.replaceChildren();
		el.results.hidden = true;
		el['result-count'].hidden = true;
		el.empty.hidden = false;
		matches = 0;
	}
	function addResult(item) {
		const row = document.createElement('tr');
		const title = document.createElement('td');
		if (item.url) {
			const link = document.createElement('a');
			link.href = item.url;
			link.textContent = item.title;
			title.appendChild(link);
		} else {
			title.textContent = item.title;
		}
		row.appendChild(title);
		[item.type, item.status, item.count].forEach(function (value) {
			const cell = document.createElement('td');
			cell.textContent = String(value);
			row.appendChild(cell);
		});
		el.rows.appendChild(row);
		el.results.hidden = false;
		el.empty.hidden = true;
		matches++;
	}

	async function scanPage(page, token, scanned) {
		try {
			request = $.post(EMUF.ajaxUrl, {
				action: 'emuf_scan_batch', nonce: EMUF.nonce,
				attachment_id: attachmentId, page_number: page
			});
			const response = await request;
			if (token !== run) return;
			if (!response || !response.success || !response.data) throw new Error(EMUF.labels.error);
			response.data.results.forEach(addResult);
			const total = scanned + response.data.scanned;
			status(EMUF.labels.working + ' ' + total + ' ' + EMUF.labels.checked);
			if (response.data.next) {
				await scanPage(response.data.next, token, total);
			} else {
				busy(false);
				status(EMUF.labels.finished + '. ' + total + ' ' + EMUF.labels.checked + '.');
				if (!matches) el.empty.textContent = EMUF.labels.none;
				el['result-count'].textContent = matches + ' ' + EMUF.labels.found;
				el['result-count'].hidden = false;
			}
		} catch (error) {
			if (token !== run) return;
			busy(false);
			status(error && error.responseJSON && error.responseJSON.data && error.responseJSON.data.message || EMUF.labels.error);
			if (!matches) el.empty.textContent = EMUF.labels.error;
		}
	}

	el.choose.addEventListener('click', function () {
		const frame = wp.media({ title: EMUF.labels.choose, button: { text: EMUF.labels.select }, library: { type: 'image' }, multiple: false });
		frame.on('select', function () {
			const selected = frame.state().get('selection').first().toJSON();
			attachmentId = Number(selected.id);
			el.preview.src = selected.sizes && selected.sizes.thumbnail ? selected.sizes.thumbnail.url : selected.url;
			el.preview.hidden = false;
			el.placeholder.hidden = true;
			el['image-name'].textContent = selected.title || selected.filename || EMUF.labels.image + ' #' + attachmentId;
			el.scan.disabled = false;
			reset();
			status('');
		});
		frame.open();
	});
	el.scan.addEventListener('click', function () {
		if (!attachmentId || el.scan.disabled) return;
		++run;
		reset();
		el.empty.textContent = EMUF.labels.working;
		busy(true);
		status(EMUF.labels.working);
		scanPage(1, run, 0);
	});
	el.cancel.addEventListener('click', function () {
		if (!scanning) return;
		++run;
		if (request) request.abort();
		busy(false);
		status(EMUF.labels.cancelled);
		if (!matches) el.empty.textContent = EMUF.labels.cancelled;
	});
})(jQuery);
