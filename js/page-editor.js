/* ------------------------------------------------------------------
   Page editor

   Draws the whole screen from window.PAGE_EDITOR — the band layout and
   the current rows — and talks to page-editor-action.php. The browser
   never names a database table or column: it sends the band, the part
   and the editor's own field names, and the server maps them from the
   same schema this file was drawn from.

   Rows arrive grouped by the part that owns them ("stories.1"), because
   one screen can edit several tables at once and ids only mean anything
   inside their own table.
   ------------------------------------------------------------------ */

(function () {
    'use strict';

    var cfg = window.PAGE_EDITOR;
    if (!cfg) return;

    var data = cfg.rows || {};
    var dirtyForms = new Set();

    var ICON = {
        caret: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>',
        handle: '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><circle cx="9" cy="6" r="1.6"/><circle cx="15" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/><circle cx="15" cy="18" r="1.6"/></svg>',
        edit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>',
        copy: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>',
        trash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>'
    };

    // ---------- small helpers ----------

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    }

    function icon(name, className) {
        var span = el('span', className || 'pe-ico');
        span.innerHTML = ICON[name];
        return span;
    }

    function bucketOf(bandKey, partIndex) {
        return bandKey + '.' + partIndex;
    }

    function rowsIn(bandKey, partIndex) {
        return data[bucketOf(bandKey, partIndex)] || [];
    }

    function toast(message, bad) {
        var box = document.getElementById('peToasts');
        var node = el('div', 'pe-toast' + (bad ? ' is-bad' : ''), message);
        box.appendChild(node);
        setTimeout(function () {
            node.style.opacity = '0';
            setTimeout(function () { node.remove(); }, 200);
        }, bad ? 4500 : 2200);
    }

    function status(text, busy) {
        var node = document.getElementById('peStatus');
        node.textContent = busy ? '' : (text || '');
        node.className = 'pe-status' + (text || busy ? ' is-on' : '') + (busy ? ' is-busy' : '');
    }

    /** Post to the action endpoint. Always resolves with the parsed body. */
    function send(action, form) {
        var body = form instanceof FormData ? form : new FormData();
        body.set('action', action);
        body.set('page', cfg.page);
        body.set('csrf', cfg.csrf);

        status('', true);

        return fetch(cfg.endpoint, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (res) {
                return res.text().then(function (text) {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        // A redirect to the login page lands here as HTML.
                        throw new Error(res.status === 401
                            ? 'Your session has expired. Sign in again.'
                            : 'The server sent an unexpected response.');
                    }
                });
            })
            .then(function (payload) {
                if (!payload.success) throw new Error(payload.message || 'Something went wrong.');
                status('All changes saved');
                return payload;
            })
            .catch(function (err) {
                status('');
                toast(err.message, true);
                throw err;
            });
    }

    /** Adds the address every write needs: which part of which page. */
    function addressed(bandKey, partIndex, id) {
        var body = new FormData();
        body.set('band', bandKey);
        body.set('part', partIndex);
        if (id !== undefined) body.set('id', id);
        return body;
    }

    // ---------- reading values ----------

    /** Values held in one column and split by a separator. */
    function fieldValue(row, field) {
        if (!row) return '';
        var raw = row[field.column] || '';
        if (field.part === undefined || field.part === null) return raw;
        return String(raw).split(field.separator || '|')[field.part] || '';
    }

    function firstField(fields, flag) {
        for (var i = 0; i < fields.length; i++) {
            if (fields[i][flag]) return fields[i];
        }
        return null;
    }

    function imageField(fields) {
        for (var i = 0; i < fields.length; i++) {
            if (fields[i].input === 'image') return fields[i];
        }
        return null;
    }

    /** Everything in a row that the search box should look through. */
    function searchText(row, fields) {
        return fields.map(function (f) { return fieldValue(row, f); }).join(' ').toLowerCase();
    }

    function replaceRow(bucket, row) {
        var list = data[bucket] || (data[bucket] = []);
        var index = list.findIndex(function (r) { return r.id === row.id; });
        if (index === -1) list.push(row);
        else list[index] = row;
    }

    // ---------- field widgets ----------

    /** "1920 × 640 px" -> [1920, 640] */
    function targetSize(field) {
        var found = String(field.size || '').match(/(\d+)\s*[×x]\s*(\d+)/);
        return found ? [Number(found[1]), Number(found[2])] : null;
    }

    /** The CSS aspect-ratio the storefront crops this image to. */
    function targetRatio(field) {
        if (field.ratio === 'square') return '1 / 1';
        if (field.ratio && field.ratio.indexOf(':') !== -1) return field.ratio.replace(':', ' / ');
        var size = targetSize(field);
        return size ? size[0] + ' / ' + size[1] : '4 / 3';
    }

    /**
     * Say what will happen to the file the editor just picked. The storefront
     * crops to a fixed box, so the wrong shape silently loses its edges —
     * worth saying before it is saved rather than after.
     */
    function sizeNote(field, width, height) {
        var size = targetSize(field);
        if (!size) return '';

        var wanted = size[0] / size[1];
        var got = width / height;

        if (Math.abs(got - wanted) / wanted > 0.02) {
            return 'This image is ' + width + ' × ' + height + '. It will be cropped to '
                + (field.ratio && field.ratio !== 'square' ? field.ratio : 'a square')
                + ' — upload ' + field.size + ' to control what stays in frame.';
        }
        if (width < size[0] * 0.8) {
            return 'This image is ' + width + ' × ' + height + ', smaller than ' + field.size
                + '. It will look soft on large screens.';
        }
        return '';
    }

    function buildField(field, value, idPrefix) {
        var wrap = el('div', 'pe-field');
        var inputId = idPrefix + '-' + field.name;

        if (field.input === 'textarea' || field.input === 'image' || field.input === 'html') {
            wrap.classList.add('is-wide');
        }

        var label = el('label', null, field.label);
        label.setAttribute('for', inputId);
        if (field.required) {
            label.appendChild(el('span', 'pe-req', ' *'));
        }
        // The size an image must be uploaded at belongs next to its label,
        // not buried in the help text underneath.
        if (field.size) {
            label.appendChild(el('span', 'pe-dim', field.size + (field.ratio ? ' · ' + field.ratio : '')));
        }
        wrap.appendChild(label);

        if (field.input === 'image') {
            wrap.appendChild(buildImage(field, value, inputId));
        } else if (field.input === 'select') {
            var select = el('select', 'pe-select');
            select.id = inputId;
            select.name = field.name;
            if (!field.required) select.appendChild(el('option', null, field.placeholder || '— none —'));
            (field.choices || []).forEach(function (choice) {
                var option = el('option', null, choice.label);
                option.value = choice.value;
                if (String(choice.value) === String(value)) option.selected = true;
                select.appendChild(option);
            });
            wrap.appendChild(select);
        } else if (field.input === 'textarea' || field.input === 'html') {
            var area = el('textarea', field.input === 'html' ? 'pe-code' : null);
            area.id = inputId;
            area.name = field.name;
            area.rows = field.rows || (field.input === 'html' ? 10 : 3);
            area.value = value || '';
            if (field.placeholder) area.placeholder = field.placeholder;
            wrap.appendChild(area);
        } else {
            var input = el('input');
            input.type = 'text';
            input.id = inputId;
            input.name = field.name;
            input.value = value || '';
            if (field.placeholder) input.placeholder = field.placeholder;
            wrap.appendChild(input);
        }

        if (field.help) wrap.appendChild(el('p', 'pe-help', field.help));
        wrap.appendChild(el('p', 'pe-error'));

        return wrap;
    }

    /** Preview, a file picker and a remove flag, with drop support. */
    function buildImage(field, value, inputId) {
        var box = el('div', 'pe-image');
        var preview = el('div', 'pe-image-preview');
        var actions = el('div', 'pe-image-actions');
        var note = el('p', 'pe-note-warn');

        preview.style.aspectRatio = targetRatio(field);
        preview.style.height = 'auto';

        var file = el('input');
        file.type = 'file';
        file.id = inputId;
        file.name = field.name;
        file.accept = '.jpg,.jpeg,.png,.gif,.webp';

        var removeFlag = el('input');
        removeFlag.type = 'hidden';
        removeFlag.name = field.name + '__remove';
        removeFlag.value = '';

        var choose = el('button', 'pe-btn pe-btn-sm', value ? 'Replace image' : 'Upload image');
        choose.type = 'button';

        var remove = el('button', 'pe-btn pe-btn-sm pe-btn-danger', 'Remove');
        remove.type = 'button';
        remove.hidden = !value;

        function paint(src) {
            preview.textContent = '';
            if (src) {
                var img = el('img');
                img.src = src;
                img.alt = '';
                preview.appendChild(img);
            } else {
                preview.textContent = 'No image';
            }
            choose.textContent = src ? 'Replace image' : 'Upload image';
            remove.hidden = !src;
        }

        /** Measure what was picked and report anything the crop will lose. */
        function inspect(src) {
            note.textContent = '';
            var probe = new Image();
            probe.onload = function () {
                note.textContent = sizeNote(field, probe.naturalWidth, probe.naturalHeight);
            };
            probe.src = src;
        }

        function take(files) {
            if (!files || !files.length) return;
            file.files = files;
            removeFlag.value = '';
            var src = URL.createObjectURL(files[0]);
            paint(src);
            inspect(src);
            box.dispatchEvent(new Event('change', { bubbles: true }));
        }

        choose.addEventListener('click', function () { file.click(); });
        file.addEventListener('change', function () {
            if (!file.files.length) return;
            removeFlag.value = '';
            var src = URL.createObjectURL(file.files[0]);
            paint(src);
            inspect(src);
        });

        remove.addEventListener('click', function () {
            file.value = '';
            removeFlag.value = '1';
            note.textContent = '';
            paint('');
            box.dispatchEvent(new Event('change', { bubbles: true }));
        });

        ['dragenter', 'dragover'].forEach(function (name) {
            box.addEventListener(name, function (e) {
                e.preventDefault();
                box.classList.add('is-hover');
            });
        });
        ['dragleave', 'drop'].forEach(function (name) {
            box.addEventListener(name, function (e) {
                e.preventDefault();
                box.classList.remove('is-hover');
                if (name === 'drop') take(e.dataTransfer.files);
            });
        });

        paint(value || '');
        actions.appendChild(choose);
        actions.appendChild(remove);
        actions.appendChild(el('span', 'pe-help',
            (field.size ? 'Upload ' + field.size + '. ' : '')
            + 'JPG, PNG, GIF or WEBP, up to 5MB. You can drop a file here.'));
        actions.appendChild(note);

        box.appendChild(preview);
        box.appendChild(actions);
        box.appendChild(file);
        box.appendChild(removeFlag);
        return box;
    }

    function buildSwitch(checked, onLabel, offLabel) {
        var wrap = el('label', 'pe-switch');
        var input = el('input');
        input.type = 'checkbox';
        input.checked = !!checked;

        var track = el('span', 'pe-track');
        track.appendChild(el('span', 'pe-knob'));

        var text = el('span', 'pe-switch-text', checked ? onLabel : offLabel);
        input.addEventListener('change', function () {
            text.textContent = input.checked ? onLabel : offLabel;
        });

        wrap.appendChild(input);
        wrap.appendChild(track);
        wrap.appendChild(text);
        wrap.input = input;
        return wrap;
    }

    function validate(form, fields) {
        var ok = true;
        fields.forEach(function (field) {
            if (field.input === 'image') return;
            var input = form.querySelector('[name="' + field.name + '"]');
            if (!input) return;
            var error = input.closest('.pe-field').querySelector('.pe-error');
            if (field.required && !input.value.trim()) {
                error.textContent = field.label + ' cannot be empty.';
                if (ok) input.focus();
                ok = false;
            } else {
                error.textContent = '';
            }
        });
        return ok;
    }

    function markDirty(form, saveButton, isDirty) {
        if (isDirty) {
            dirtyForms.add(form);
            saveButton.disabled = false;
            status('Unsaved changes');
        } else {
            dirtyForms.delete(form);
            saveButton.disabled = true;
        }
    }

    /** Uploaded files are already stored; do not send them again on the next save. */
    function clearFiles(form) {
        form.querySelectorAll('input[type="file"]').forEach(function (input) { input.value = ''; });
        form.querySelectorAll('input[type="hidden"][name$="__remove"]').forEach(function (input) { input.value = ''; });
    }

    // ---------- inline form for a one-row part ----------

    function buildSingle(band, partIndex, part) {
        var row = rowsIn(band.key, partIndex)[0] || null;
        var host = el('div', 'pe-part');
        var prefix = band.key + '-' + partIndex;

        var head = el('div', 'pe-part-head');
        head.appendChild(el('h3', null, part.label));
        host.appendChild(head);

        var form = el('form', 'pe-fields');
        form.noValidate = true;

        part.fields.forEach(function (field) {
            form.appendChild(buildField(field, fieldValue(row, field), prefix));
        });

        var foot = el('div', 'pe-field is-wide pe-form-foot');
        var visible = buildSwitch(!row || row.is_active === 1, 'Visible', 'Hidden');
        var save = el('button', 'pe-btn pe-btn-primary', 'Save');
        save.type = 'submit';
        save.disabled = true;

        foot.appendChild(visible);
        foot.appendChild(save);
        form.appendChild(foot);

        form.addEventListener('input', function () { markDirty(form, save, true); });
        form.addEventListener('change', function () { markDirty(form, save, true); });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!validate(form, part.fields)) return;

            var body = new FormData(form);
            body.set('band', band.key);
            body.set('part', partIndex);
            body.set('id', row ? row.id : 0);
            body.set('is_active', visible.input.checked ? 1 : 0);

            save.disabled = true;
            send('save', body).then(function (payload) {
                row = payload.section;
                replaceRow(payload.bucket, row);
                host.dataset.search = searchText(row, part.fields);
                markDirty(form, save, false);
                clearFiles(form);
                toast(payload.message);
            }).catch(function () { save.disabled = false; });
        });

        host.appendChild(form);
        host.dataset.search = row ? searchText(row, part.fields) : '';
        return host;
    }

    // ---------- list of rows ----------

    function buildList(band, partIndex, part) {
        var host = el('div', 'pe-part');

        var head = el('div', 'pe-part-head');
        head.appendChild(el('h3', null, part.label));

        var add = el('button', 'pe-btn pe-btn-sm', part.addLabel || 'Add');
        add.type = 'button';
        add.addEventListener('click', function () { openDialog(band, partIndex, part, null); });
        head.appendChild(add);
        host.appendChild(head);

        var list = el('div', 'pe-items');
        list.dataset.band = band.key;
        list.dataset.part = partIndex;
        host.appendChild(list);

        renderList(list, band, partIndex, part);
        enableDragging(list, band, partIndex);
        return host;
    }

    function renderList(list, band, partIndex, part) {
        list.textContent = '';
        var rows = rowsIn(band.key, partIndex);

        if (!rows.length) {
            list.appendChild(el('div', 'pe-empty',
                'Nothing here yet. Use "' + (part.addLabel || 'Add') + '" to create the first one.'));
            return;
        }

        rows.forEach(function (row) {
            list.appendChild(buildRow(band, partIndex, part, row, list));
        });
    }

    function buildRow(band, partIndex, part, row, list) {
        var node = el('div', 'pe-row');
        node.dataset.id = row.id;
        node.dataset.search = searchText(row, part.fields);
        node.draggable = true;
        if (row.is_active !== 1) node.classList.add('is-hidden');

        node.appendChild(icon('handle', 'pe-handle'));

        if (imageField(part.fields)) {
            if (row.image_path) {
                var img = el('img', 'pe-thumb');
                img.src = row.image_path;
                img.alt = '';
                node.appendChild(img);
            } else {
                node.appendChild(el('div', 'pe-thumb-none', 'none'));
            }
        }

        var primary = firstField(part.fields, 'primary') || part.fields[0];
        var secondary = firstField(part.fields, 'secondary');

        var text = el('div', 'pe-row-text');
        text.appendChild(el('div', 'pe-row-title', fieldValue(row, primary) || 'Untitled'));
        if (secondary) {
            var sub = fieldValue(row, secondary);
            if (sub) text.appendChild(el('div', 'pe-row-sub', sub));
        }
        node.appendChild(text);

        var actions = el('div', 'pe-row-actions');

        var visible = buildSwitch(row.is_active === 1, 'Visible', 'Hidden');
        visible.input.addEventListener('change', function () {
            var body = addressed(band.key, partIndex, row.id);
            body.set('is_active', visible.input.checked ? 1 : 0);
            send('toggle', body).then(function (payload) {
                row.is_active = visible.input.checked ? 1 : 0;
                node.classList.toggle('is-hidden', row.is_active !== 1);
                toast(payload.message);
            }).catch(function () {
                visible.input.checked = !visible.input.checked;
                visible.input.dispatchEvent(new Event('change'));
            });
        });
        actions.appendChild(visible);

        var edit = el('button', 'pe-btn pe-btn-sm pe-btn-icon');
        edit.type = 'button';
        edit.title = 'Edit';
        edit.appendChild(icon('edit'));
        edit.addEventListener('click', function () { openDialog(band, partIndex, part, row); });
        actions.appendChild(edit);

        var copy = el('button', 'pe-btn pe-btn-sm pe-btn-icon');
        copy.type = 'button';
        copy.title = 'Duplicate';
        copy.appendChild(icon('copy'));
        copy.addEventListener('click', function () {
            send('duplicate', addressed(band.key, partIndex, row.id)).then(function (payload) {
                replaceRow(payload.bucket, payload.section);
                renderList(list, band, partIndex, part);
                refreshCounts();
                applySearch();
                toast(payload.message);
            });
        });
        actions.appendChild(copy);

        var remove = el('button', 'pe-btn pe-btn-sm pe-btn-icon pe-btn-danger');
        remove.type = 'button';
        remove.title = 'Delete';
        remove.appendChild(icon('trash'));
        remove.addEventListener('click', function () {
            var name = fieldValue(row, primary) || 'this item';
            if (!confirm('Delete "' + name + '"? This cannot be undone.')) return;

            send('delete', addressed(band.key, partIndex, row.id)).then(function (payload) {
                var bucket = bucketOf(band.key, partIndex);
                data[bucket] = rowsIn(band.key, partIndex).filter(function (r) { return r.id !== row.id; });
                renderList(list, band, partIndex, part);
                refreshCounts();
                applySearch();
                toast(payload.message);
            });
        });
        actions.appendChild(remove);

        node.appendChild(actions);
        return node;
    }

    // ---------- drag to reorder ----------

    function enableDragging(list, band, partIndex) {
        var dragged = null;

        list.addEventListener('dragstart', function (e) {
            var row = e.target.closest('.pe-row');
            if (!row) return;
            dragged = row;
            row.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', row.dataset.id);
        });

        list.addEventListener('dragover', function (e) {
            if (!dragged) return;
            e.preventDefault();
            var over = e.target.closest('.pe-row');
            if (!over || over === dragged) return;

            var box = over.getBoundingClientRect();
            var below = (e.clientY - box.top) / box.height > 0.5;
            list.insertBefore(dragged, below ? over.nextSibling : over);
        });

        list.addEventListener('drop', function (e) { e.preventDefault(); });

        list.addEventListener('dragend', function () {
            if (!dragged) return;
            dragged.classList.remove('is-dragging');
            dragged = null;

            var ids = Array.prototype.map.call(
                list.querySelectorAll('.pe-row'),
                function (row) { return Number(row.dataset.id); }
            );

            var body = addressed(band.key, partIndex);
            body.set('ids', JSON.stringify(ids));

            send('reorder', body).then(function () {
                // Keep the in-memory copy in the order the server now holds.
                var rows = rowsIn(band.key, partIndex);
                ids.forEach(function (id, index) {
                    var row = rows.find(function (r) { return r.id === id; });
                    if (row) row.display_order = index + 1;
                });
                rows.sort(function (a, b) {
                    return a.display_order - b.display_order || a.id - b.id;
                });
                toast('Order saved.');
            });
        });
    }

    // ---------- dialog ----------

    var dialog = {
        overlay: document.getElementById('peOverlay'),
        form: document.getElementById('peDialogForm'),
        title: document.getElementById('peDialogTitle'),
        save: document.getElementById('peDialogSave')
    };

    var openContext = null;

    function singular(part) {
        return (part.addLabel || part.label || 'item').replace(/^Add\s+/i, '').toLowerCase();
    }

    function openDialog(band, partIndex, part, row) {
        openContext = { band: band, partIndex: partIndex, part: part, row: row };
        var prefix = 'dlg-' + band.key + '-' + partIndex;

        dialog.title.textContent = (row ? 'Edit ' : 'New ') + singular(part);
        dialog.form.textContent = '';

        part.fields.forEach(function (field) {
            dialog.form.appendChild(buildField(field, fieldValue(row, field), prefix));
        });

        var visibleWrap = el('div', 'pe-field is-wide');
        visibleWrap.appendChild(el('label', null, 'Show on the website'));
        var visible = buildSwitch(!row || row.is_active === 1, 'Visible', 'Hidden');
        visibleWrap.appendChild(visible);
        dialog.form.appendChild(visibleWrap);
        dialog.form.visibleSwitch = visible;

        dialog.overlay.hidden = false;
        dialog.save.disabled = false;

        var first = dialog.form.querySelector('input[type="text"], textarea, select');
        if (first) first.focus();
    }

    function closeDialog() {
        dialog.overlay.hidden = true;
        dialog.form.textContent = '';
        openContext = null;
    }

    dialog.form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!openContext) return;

        var ctx = openContext;
        if (!validate(dialog.form, ctx.part.fields)) return;

        var body = new FormData(dialog.form);
        body.set('band', ctx.band.key);
        body.set('part', ctx.partIndex);
        body.set('id', ctx.row ? ctx.row.id : 0);
        body.set('is_active', dialog.form.visibleSwitch.input.checked ? 1 : 0);

        dialog.save.disabled = true;
        send('save', body).then(function (payload) {
            replaceRow(payload.bucket, payload.section);
            var list = document.querySelector(
                '.pe-items[data-band="' + ctx.band.key + '"][data-part="' + ctx.partIndex + '"]'
            );
            if (list) renderList(list, ctx.band, ctx.partIndex, ctx.part);
            refreshCounts();
            applySearch();
            closeDialog();
            toast(payload.message);
        }).catch(function () { dialog.save.disabled = false; });
    });

    document.getElementById('peDialogClose').addEventListener('click', closeDialog);
    document.getElementById('peDialogCancel').addEventListener('click', closeDialog);

    dialog.overlay.addEventListener('mousedown', function (e) {
        if (e.target === dialog.overlay) closeDialog();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !dialog.overlay.hidden) closeDialog();
    });

    // ---------- search ----------

    var searchBox = document.getElementById('peSearch');

    /** Hide rows, then whole bands, that do not contain the typed text. */
    function applySearch() {
        var term = (searchBox && searchBox.value || '').trim().toLowerCase();

        document.querySelectorAll('.pe-band').forEach(function (panel) {
            var hits = 0;

            panel.querySelectorAll('.pe-row, .pe-part[data-search]').forEach(function (node) {
                var match = !term || (node.dataset.search || '').indexOf(term) !== -1;
                node.hidden = !match;
                if (match) hits++;
            });

            // An empty list still counts while nothing is being searched for.
            var show = !term || hits > 0;
            panel.hidden = !show;
            if (term) panel.classList.remove('is-closed');

            var link = document.querySelector('.pe-rail a[href="#' + panel.id + '"]');
            if (link) link.hidden = !show;
        });
    }

    if (searchBox) {
        searchBox.addEventListener('input', applySearch);
        searchBox.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                searchBox.value = '';
                applySearch();
            }
        });
    }

    // ---------- page ----------

    function countFor(band) {
        return band.parts.reduce(function (total, part, index) {
            return total + (part.kind === 'list' ? rowsIn(band.key, index).length : 0);
        }, 0);
    }

    function refreshCounts() {
        cfg.bands.forEach(function (band) {
            var node = document.querySelector('.pe-rail a[href="#band-' + band.key + '"] .pe-count');
            if (!node) return;
            var count = countFor(band);
            node.textContent = count ? String(count) : '';
        });
    }

    function render() {
        var rail = document.getElementById('peRail');
        var host = document.getElementById('peBands');

        cfg.bands.forEach(function (band) {
            var link = el('a', null);
            link.href = '#band-' + band.key;
            link.appendChild(el('span', null, band.label));
            link.appendChild(el('span', 'pe-count'));
            rail.appendChild(link);

            var panel = el('section', 'pe-band');
            panel.id = 'band-' + band.key;

            var head = el('div', 'pe-band-head');
            head.appendChild(icon('caret', 'pe-caret'));

            var titles = el('div');
            titles.appendChild(el('h2', null, band.label));
            if (band.note) titles.appendChild(el('p', 'pe-band-note', band.note));
            head.appendChild(titles);

            head.addEventListener('click', function () { panel.classList.toggle('is-closed'); });
            panel.appendChild(head);

            var body = el('div', 'pe-band-body');
            band.parts.forEach(function (part, index) {
                body.appendChild(part.kind === 'list'
                    ? buildList(band, index, part)
                    : buildSingle(band, index, part));
            });
            panel.appendChild(body);

            host.appendChild(panel);
        });

        refreshCounts();
        trackRail();
    }

    /** Highlight the rail entry for whichever band is on screen. */
    function trackRail() {
        var links = document.querySelectorAll('.pe-rail a');
        var panels = document.querySelectorAll('.pe-band');

        if (!('IntersectionObserver' in window)) return;

        var seen = new Map();
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) { seen.set(entry.target.id, entry.intersectionRatio); });

            var best = null;
            var bestRatio = 0;
            seen.forEach(function (ratio, id) {
                if (ratio > bestRatio) { bestRatio = ratio; best = id; }
            });

            links.forEach(function (link) {
                link.classList.toggle('is-current', best !== null && link.getAttribute('href') === '#' + best);
            });
        }, { rootMargin: '-80px 0px -55% 0px', threshold: [0, 0.25, 0.5, 1] });

        panels.forEach(function (panel) { observer.observe(panel); });
    }

    window.addEventListener('beforeunload', function (e) {
        if (!dirtyForms.size) return;
        e.preventDefault();
        e.returnValue = '';
    });

    render();
})();
