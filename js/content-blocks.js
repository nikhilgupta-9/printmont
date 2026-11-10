/* ------------------------------------------------------------------
   Website Content — content block builder

   Every page the frontend reads by a page_key is just a group here.
   A block is one row: a pattern name plus the data that pattern's own
   field list in config/content-block-patterns.php describes. The
   server sends that field list back with every "list" call, so this
   file never hardcodes a pattern's shape — it draws whatever the
   server says the chosen pattern needs.
   ------------------------------------------------------------------ */

(function () {
    'use strict';

    var state = { blocks: [], patterns: {}, csrf: '' };
    var editingId = null;
    var idSeq = 0;

    var modalEl = document.getElementById('cbModal');
    var modal = window.bootstrap ? new bootstrap.Modal(modalEl) : null;

    var groupsBox = document.getElementById('cbGroups');
    var statusBox = document.getElementById('cbStatus');
    var fieldsBox = document.getElementById('cbFields');
    var pageKeyInput = document.getElementById('cbPageKey');
    var pageKeyList = document.getElementById('cbPageKeyList');
    var titleInput = document.getElementById('cbTitle');
    var patternSelect = document.getElementById('cbPattern');
    var patternNote = document.getElementById('cbPatternNote');
    var activeInput = document.getElementById('cbActive');
    var modalTitle = document.getElementById('cbModalTitle');

    var RICHTEXT_CONFIG = {
        height: 200,
        removePlugins: 'elementspath',
        resize_enabled: true,
        toolbar: [
            { name: 'styles', items: ['Format'] },
            { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', '-', 'RemoveFormat'] },
            { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote'] },
            { name: 'links', items: ['Link', 'Unlink'] },
            { name: 'tools', items: ['Maximize'] },
            { name: 'document', items: ['Source'] }
        ]
    };

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    }

    function nextId() { return 'cb-f' + (idSeq++); }

    function showError(message) {
        statusBox.innerHTML = '';
        var box = el('div', 'alert alert-danger alert-dismissible');
        box.textContent = message;
        var close = el('button', 'btn-close');
        close.type = 'button';
        close.setAttribute('data-bs-dismiss', 'alert');
        box.appendChild(close);
        statusBox.appendChild(box);
    }

    function showOk(message) {
        statusBox.innerHTML = '';
        var box = el('div', 'alert alert-success alert-dismissible');
        box.textContent = message;
        var close = el('button', 'btn-close');
        close.type = 'button';
        close.setAttribute('data-bs-dismiss', 'alert');
        box.appendChild(close);
        statusBox.appendChild(box);
        setTimeout(function () { box.remove(); }, 4000);
    }

    // ---------- talking to content-blocks-action.php ----------

    function api(action, body) {
        if (action === 'list') {
            return fetch('content-blocks-action.php?action=list', { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(unwrap);
        }
        body = body || new FormData();
        body.set('action', action);
        body.set('csrf', state.csrf);
        return fetch('content-blocks-action.php', { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(unwrap);
    }

    function unwrap(json) {
        if (!json || !json.success) throw new Error((json && json.message) || 'Something went wrong.');
        return json;
    }

    function load() {
        api('list').then(function (json) {
            state.blocks = json.blocks || [];
            state.patterns = json.patterns || {};
            state.csrf = json.csrf || '';
            fillPatternOptions();
            fillPageKeyDatalist();
            render();
        }).catch(function (e) { showError(e.message); });
    }

    function fillPatternOptions() {
        patternSelect.innerHTML = '';
        Object.keys(state.patterns).forEach(function (key) {
            var opt = el('option', null, state.patterns[key].label);
            opt.value = key;
            patternSelect.appendChild(opt);
        });
    }

    function fillPageKeyDatalist() {
        pageKeyList.innerHTML = '';
        var seen = {};
        state.blocks.forEach(function (b) {
            if (seen[b.page_key]) return;
            seen[b.page_key] = true;
            var opt = el('option');
            opt.value = b.page_key;
            pageKeyList.appendChild(opt);
        });
    }

    // ---------- the list of sections, grouped by page ----------

    function render() {
        groupsBox.innerHTML = '';

        if (!state.blocks.length) {
            var empty = el('div', 'cb-empty');
            empty.appendChild(el('p', null, 'No sections yet. Add the first one with "Add section" above.'));
            groupsBox.appendChild(empty);
            return;
        }

        var byPage = {};
        state.blocks.forEach(function (b) { (byPage[b.page_key] = byPage[b.page_key] || []).push(b); });

        Object.keys(byPage).sort().forEach(function (pageKey) {
            var rows = byPage[pageKey].sort(function (a, b) { return a.display_order - b.display_order; });

            var group = el('div', 'cb-group');
            var head = el('div', 'cb-group-head');
            head.appendChild(el('h5', null, pageKey));
            head.appendChild(el('span', 'badge bg-light text-dark', rows.length + (rows.length === 1 ? ' section' : ' sections')));
            group.appendChild(head);

            rows.forEach(function (row, idx) { group.appendChild(buildCard(row, rows, idx)); });
            groupsBox.appendChild(group);
        });

        if (window.feather) feather.replace();
    }

    function buildCard(row, siblings, idx) {
        var card = el('div', 'cb-card' + (row.is_active ? '' : ' is-hidden'));

        var main = el('div', 'cb-card-main');
        main.appendChild(el('div', 'cb-card-title', row.title || (state.patterns[row.pattern] || {}).label || row.pattern));
        main.appendChild(el('div', 'cb-card-pattern', (state.patterns[row.pattern] || {}).label || row.pattern));
        card.appendChild(main);

        var actions = el('div', 'cb-card-actions');

        var up = iconBtn('chevron-up', 'Move up');
        up.disabled = idx === 0;
        up.addEventListener('click', function () { move(siblings, idx, -1); });

        var down = iconBtn('chevron-down', 'Move down');
        down.disabled = idx === siblings.length - 1;
        down.addEventListener('click', function () { move(siblings, idx, 1); });

        var toggle = iconBtn(row.is_active ? 'eye' : 'eye-off', row.is_active ? 'Hide' : 'Show');
        toggle.addEventListener('click', function () {
            var body = new FormData();
            body.set('id', row.id);
            body.set('is_active', row.is_active ? 0 : 1);
            api('toggle', body).then(function () { load(); }).catch(function (e) { showError(e.message); });
        });

        var editBtn = iconBtn('edit', 'Edit');
        editBtn.addEventListener('click', function () { openDialog(row); });

        var delBtn = iconBtn('trash', 'Delete');
        delBtn.classList.add('btn-outline-danger');
        delBtn.addEventListener('click', function () {
            if (!window.confirm('Delete this section? This cannot be undone.')) return;
            var body = new FormData();
            body.set('id', row.id);
            api('delete', body).then(function () { showOk('Deleted.'); load(); }).catch(function (e) { showError(e.message); });
        });

        [up, down, toggle, editBtn, delBtn].forEach(function (b) { actions.appendChild(b); });
        card.appendChild(actions);
        return card;
    }

    function iconBtn(icon, label) {
        var btn = el('button', 'btn btn-sm btn-outline-secondary');
        btn.type = 'button';
        btn.title = label;
        btn.innerHTML = '<i data-feather="' + icon + '" style="width:14px;height:14px;"></i>';
        return btn;
    }

    function move(siblings, idx, dir) {
        var target = idx + dir;
        if (target < 0 || target >= siblings.length) return;
        var tmp = siblings[idx];
        siblings[idx] = siblings[target];
        siblings[target] = tmp;

        var ids = siblings.map(function (b) { return b.id; });
        var body = new FormData();
        body.set('ids', JSON.stringify(ids));
        api('reorder', body).then(function () { load(); }).catch(function (e) { showError(e.message); });
    }

    // ---------- add / edit dialog ----------

    document.getElementById('cbAddBtn').addEventListener('click', function () { openDialog(null); });

    function openDialog(row) {
        editingId = row ? row.id : null;
        modalTitle.textContent = row ? 'Edit section' : 'Add section';
        pageKeyInput.value = row ? row.page_key : '';
        titleInput.value = row ? row.title : '';
        activeInput.checked = row ? !!row.is_active : true;
        patternSelect.value = row ? row.pattern : Object.keys(state.patterns)[0];
        patternSelect.disabled = !!row; // the field shape only makes sense fixed once data exists
        renderFields(patternSelect.value, row ? row.data : {});
        if (modal) modal.show();
    }

    patternSelect.addEventListener('change', function () {
        renderFields(patternSelect.value, {});
    });

    function renderFields(patternKey, data) {
        destroyEditorsIn(fieldsBox);
        fieldsBox.innerHTML = '';
        var pattern = state.patterns[patternKey];
        if (!pattern) return;
        patternNote.textContent = pattern.note || '';
        pattern.fields.forEach(function (field) {
            fieldsBox.appendChild(buildField(field, data[field.name], 'data_'));
        });
    }

    function destroyEditorsIn(container) {
        if (typeof CKEDITOR === 'undefined') return;
        container.querySelectorAll('.cb-richtext').forEach(function (ta) {
            if (CKEDITOR.instances[ta.id]) CKEDITOR.instances[ta.id].destroy(true);
        });
    }

    function buildField(field, value, prefix) {
        var name = prefix + field.name;
        var wrap = el('div', 'cb-field');
        wrap.appendChild(el('label', null, field.label));

        if (field.input === 'repeater') {
            wrap.appendChild(buildRepeater(field, value || [], name));
        } else if (field.input === 'image') {
            wrap.appendChild(buildImage(field, value, name));
        } else if (field.input === 'select') {
            var select = el('select', 'form-select');
            select.id = 'input-' + name;
            (field.choices || []).forEach(function (choice) {
                var opt = el('option', null, choice.label);
                opt.value = choice.value;
                if (choice.value === value) opt.selected = true;
                select.appendChild(opt);
            });
            wrap.appendChild(select);
        } else if (field.input === 'textarea' || field.input === 'richtext') {
            var area = el('textarea', 'form-control' + (field.input === 'richtext' ? ' cb-richtext' : ''));
            area.id = 'input-' + name;
            area.rows = field.rows || 4;
            area.value = value || '';
            if (field.placeholder) area.placeholder = field.placeholder;
            wrap.appendChild(area);
            if (field.input === 'richtext') initRichText(area);
        } else {
            var input = el('input', 'form-control');
            input.type = 'text';
            input.id = 'input-' + name;
            input.value = value || '';
            if (field.placeholder) input.placeholder = field.placeholder;
            wrap.appendChild(input);
        }

        return wrap;
    }

    function initRichText(textarea) {
        if (!textarea.id) textarea.id = nextId();
        if (typeof CKEDITOR === 'undefined') return;
        if (CKEDITOR.instances[textarea.id]) CKEDITOR.instances[textarea.id].destroy(true);
        var instance = CKEDITOR.replace(textarea.id, RICHTEXT_CONFIG);
        instance.on('change', function () { textarea.value = instance.getData(); });
    }

    function buildImage(field, value, name) {
        var box = el('div');
        var preview = el('div', 'cb-image-preview', value ? '' : 'No image');
        if (value) {
            var img = el('img');
            img.src = value;
            preview.textContent = '';
            preview.appendChild(img);
        }

        var file = el('input', 'form-control mb-1');
        file.type = 'file';
        file.id = 'input-' + name;
        file.accept = '.jpg,.jpeg,.png,.gif,.webp';

        var removeFlag = el('input');
        removeFlag.type = 'hidden';
        removeFlag.id = 'remove-' + name;
        removeFlag.value = '';

        var removeBtn = el('button', 'btn btn-sm btn-outline-danger', 'Remove image');
        removeBtn.type = 'button';
        removeBtn.hidden = !value;
        removeBtn.addEventListener('click', function () {
            removeFlag.value = '1';
            file.value = '';
            preview.textContent = 'No image';
            removeBtn.hidden = true;
        });

        file.addEventListener('change', function () {
            if (!file.files.length) return;
            removeFlag.value = '';
            preview.textContent = '';
            var img2 = el('img');
            img2.src = URL.createObjectURL(file.files[0]);
            preview.appendChild(img2);
            removeBtn.hidden = false;
        });

        box.appendChild(preview);
        box.appendChild(file);
        box.appendChild(removeFlag);
        box.appendChild(removeBtn);
        return box;
    }

    function buildRepeater(field, items, name) {
        var box = el('div');
        var list = el('div');
        list.id = 'field-' + name;

        items.forEach(function (item) { list.appendChild(buildRepeaterItem(field, item)); });

        var add = el('button', 'btn btn-sm btn-outline-primary', field.addLabel || 'Add');
        add.type = 'button';
        add.addEventListener('click', function () { list.appendChild(buildRepeaterItem(field, {})); });

        box.appendChild(list);
        box.appendChild(add);
        return box;
    }

    function buildRepeaterItem(field, item) {
        var row = el('div', 'cb-repeater-item');

        var remove = el('button', 'btn btn-sm btn-outline-danger cb-repeater-remove', '×');
        remove.type = 'button';
        remove.title = 'Remove ' + (field.itemLabel || 'row');
        remove.addEventListener('click', function () {
            destroyEditorsIn(row);
            row.remove();
        });
        row.appendChild(remove);

        field.fields.forEach(function (sub) {
            var subWrap = el('div', 'mb-2');
            subWrap.appendChild(el('label', 'form-label small text-muted mb-1', sub.label));

            var elementId = nextId();
            var subEl;
            if (sub.input === 'richtext') {
                subEl = el('textarea', 'form-control cb-richtext');
                subEl.id = elementId;
                subEl.rows = sub.rows || 3;
                subEl.value = item[sub.name] || '';
                initRichText(subEl);
            } else if (sub.input === 'textarea') {
                subEl = el('textarea', 'form-control');
                subEl.id = elementId;
                subEl.rows = sub.rows || 2;
                subEl.value = item[sub.name] || '';
            } else {
                subEl = el('input', 'form-control');
                subEl.type = 'text';
                subEl.id = elementId;
                subEl.value = item[sub.name] || '';
            }
            if (sub.placeholder) subEl.placeholder = sub.placeholder;
            subEl.setAttribute('data-sub', sub.name);

            subWrap.appendChild(subEl);
            row.appendChild(subWrap);
        });

        return row;
    }

    // ---------- save ----------

    document.getElementById('cbSaveBtn').addEventListener('click', function () {
        var pageKey = pageKeyInput.value.trim();
        if (!pageKey) { showError('Tell this block which page it belongs to.'); return; }

        var body = new FormData();
        body.set('id', editingId || 0);
        body.set('page_key', pageKey);
        body.set('title', titleInput.value.trim());
        body.set('pattern', patternSelect.value);
        body.set('is_active', activeInput.checked ? 1 : 0);

        var pattern = state.patterns[patternSelect.value];
        (pattern ? pattern.fields : []).forEach(function (field) {
            appendField(body, field, 'data_');
        });

        api('save', body).then(function () {
            if (modal) modal.hide();
            showOk('Saved.');
            load();
        }).catch(function (e) { showError(e.message); });
    });

    function appendField(body, field, prefix) {
        var name = prefix + field.name;

        if (field.input === 'repeater') {
            var list = document.getElementById('field-' + name);
            var rows = list ? Array.prototype.slice.call(list.children) : [];
            body.set(name + '_count', rows.length);
            rows.forEach(function (rowEl, idx) {
                field.fields.forEach(function (sub) {
                    var subEl = rowEl.querySelector('[data-sub="' + sub.name + '"]');
                    body.set(name + '[' + idx + '][' + sub.name + ']', subEl ? subEl.value : '');
                });
            });
            return;
        }

        if (field.input === 'image') {
            var fileInput = document.getElementById('input-' + name);
            if (fileInput && fileInput.files && fileInput.files[0]) body.set(name, fileInput.files[0]);
            var removeFlag = document.getElementById('remove-' + name);
            if (removeFlag && removeFlag.value === '1') body.set(name + '__remove', '1');
            return;
        }

        var inputEl = document.getElementById('input-' + name);
        body.set(name, inputEl ? inputEl.value : '');
    }

    modalEl.addEventListener('hidden.bs.modal', function () {
        destroyEditorsIn(fieldsBox);
        fieldsBox.innerHTML = '';
        patternSelect.disabled = false;
    });

    load();
})();
