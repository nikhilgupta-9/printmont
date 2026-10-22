import React, { useEffect, useId, useState } from 'react';
import { FaUpload, FaCheckCircle, FaTimes, FaSpinner, FaTimesCircle } from 'react-icons/fa';
import { API_ENDPOINTS } from '../../config/apiEndpoints';
import './ProductCustomizationForm.css';

// Renders the customization options the admin built for THIS product
// (product.php → section 7, stored as customization_fields). Field types:
//   select   dropdown            → values[label] = { value }
//   radio    radio buttons       → values[label] = { value }
//   color    colour swatches     → values[label] = { value }
//   multi    multi-select chips  → values[label] = { values: [] }
//   text / textarea / number     → values[label] = { text }
//   image    file upload         → values[label] = { fileUrl, fileName }
//   both     text and/or upload  → values[label] = { text, fileUrl, fileName }
// Choice values may carry a price add-on per piece (customizationExtraPrice).
const CHOICE_TYPES = ['select', 'radio', 'color', 'multi'];
const wantsText = (type) => ['text', 'textarea', 'number', 'both'].includes(type);
const wantsImage = (type) => type === 'image' || type === 'file' || type === 'both';

const optionsOf = (field) => (Array.isArray(field.options) ? field.options : []);
const priceTag = (price) => (Number(price) > 0 ? ` (+₹${Number(price).toLocaleString('en-IN')})` : '');

const ProductCustomizationForm = ({ fields, values, onChange }) => {
  const [uploadingLabel, setUploadingLabel] = useState(null);
  // Desktop and mobile layouts both render this form; unique radio names stop their groups from clashing
  const formId = useId();

  // Pre-select the first value of single-choice options (and of required chip groups),
  // so the shown price already matches what will be ordered
  useEffect(() => {
    if (!fields || !fields.length) return;
    const defaults = {};
    fields.forEach((f) => {
      const opts = optionsOf(f);
      if (!opts.length || values[f.label]) return;
      if (['select', 'radio', 'color'].includes(f.type)) defaults[f.label] = { value: opts[0].label };
      if (f.type === 'multi' && f.required) defaults[f.label] = { values: [opts[0].label] };
    });
    if (Object.keys(defaults).length) onChange({ ...values, ...defaults });
    // only when the product's fields change
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [fields]);

  const updateField = (label, patch) => {
    const current = values[label] || {};
    onChange({ ...values, [label]: { ...current, ...patch } });
  };

  const toggleChip = (label, optLabel) => {
    const current = (values[label] && values[label].values) || [];
    const next = current.includes(optLabel) ? current.filter((v) => v !== optLabel) : [...current, optLabel];
    updateField(label, { values: next });
  };

  const handleFileChange = async (label, file) => {
    if (!file) return;
    setUploadingLabel(label);
    try {
      const formData = new FormData();
      formData.append('file', file);
      const res = await fetch(API_ENDPOINTS.CUSTOMIZATION_UPLOAD, { method: 'POST', body: formData });
      const data = await res.json();
      if (data?.success) {
        updateField(label, { fileUrl: data.url, fileName: file.name });
      } else {
        alert(data?.error || 'Upload failed, please try a different file.');
      }
    } catch (err) {
      alert('Upload failed, please try again.');
    } finally {
      setUploadingLabel(null);
    }
  };

  if (!fields || fields.length === 0) return null;

  const renderControl = (field) => {
    const { label, type } = field;
    const value = values[label] || {};
    const opts = optionsOf(field);

    if (type === 'select') {
      return (
        <select
          className="pcf-select"
          value={value.value || ''}
          onChange={(e) => updateField(label, { value: e.target.value })}
        >
          {!field.required && <option value="">Select {label}</option>}
          {opts.map((o) => (
            <option key={o.label} value={o.label}>{o.label}{priceTag(o.price)}</option>
          ))}
        </select>
      );
    }

    if (type === 'radio') {
      return (
        <div className="pcf-radios">
          {opts.map((o) => (
            <label key={o.label} className={`pcf-radio ${value.value === o.label ? 'is-active' : ''}`}>
              <input
                type="radio"
                name={`pcf-${formId}-${label}`}
                checked={value.value === o.label}
                onChange={() => updateField(label, { value: o.label })}
              />
              {o.label}{priceTag(o.price)}
            </label>
          ))}
        </div>
      );
    }

    if (type === 'color') {
      const selected = opts.find((o) => o.label === value.value);
      return (
        <div>
          <div className="pcf-swatches">
            {opts.map((o) => (
              <button
                key={o.label}
                type="button"
                className={`pcf-swatch ${value.value === o.label ? 'is-active' : ''}`}
                style={{ background: o.hex || '#ccc' }}
                title={`${o.label}${priceTag(o.price)}`}
                aria-label={o.label}
                onClick={() => updateField(label, { value: o.label })}
              />
            ))}
          </div>
          {selected && <div className="pcf-selected">{selected.label}{priceTag(selected.price)}</div>}
        </div>
      );
    }

    if (type === 'multi') {
      const chosen = value.values || [];
      return (
        <div className="pcf-chips">
          {opts.map((o) => {
            const on = chosen.includes(o.label);
            return (
              <button
                key={o.label}
                type="button"
                className={`pcf-chip ${on ? 'is-active' : ''}`}
                onClick={() => toggleChip(label, o.label)}
              >
                {o.label}{priceTag(o.price)}
                {on && <FaTimesCircle size={12} />}
              </button>
            );
          })}
        </div>
      );
    }

    return (
      <div className="pcf-stack">
        {type === 'textarea' && (
          <textarea
            className="pcf-input"
            rows={3}
            value={value.text || ''}
            onChange={(e) => updateField(label, { text: e.target.value })}
            placeholder={`Enter ${label.toLowerCase()}`}
          />
        )}
        {(type === 'text' || type === 'both') && (
          <input
            type="text"
            className="pcf-input"
            value={value.text || ''}
            onChange={(e) => updateField(label, { text: e.target.value })}
            placeholder={`Enter ${label.toLowerCase()}`}
          />
        )}
        {type === 'number' && (
          <input
            type="number"
            className="pcf-input"
            value={value.text || ''}
            onChange={(e) => updateField(label, { text: e.target.value })}
            placeholder="0"
          />
        )}
        {wantsImage(type) && (
          value.fileUrl ? (
            <div className="pcf-file">
              <FaCheckCircle className="text-success flex-shrink-0" />
              <span className="text-truncate flex-grow-1">{value.fileName}</span>
              <button
                type="button"
                className="btn btn-sm btn-link text-danger p-0"
                onClick={() => updateField(label, { fileUrl: undefined, fileName: undefined })}
                aria-label="Remove file"
              >
                <FaTimes />
              </button>
            </div>
          ) : (
            <label className="pcf-upload" style={{ cursor: uploadingLabel === label ? 'wait' : 'pointer' }}>
              {uploadingLabel === label ? <FaSpinner className="fa-spin" /> : <FaUpload />}
              {uploadingLabel === label ? 'Uploading...' : (type === 'both' ? 'Or upload an image' : 'Choose file')}
              <input
                type="file"
                accept="image/*,application/pdf"
                style={{ display: 'none' }}
                disabled={uploadingLabel === label}
                onChange={(e) => handleFileChange(label, e.target.files[0])}
              />
            </label>
          )
        )}
      </div>
    );
  };

  return (
    <div className="pcf">
      {fields.map((field, idx) => (
        <div key={`${field.label}-${idx}`} className="pcf-row">
          <span className="pcf-label">
            {field.label} {field.required ? <span className="text-danger">*</span> : null}
          </span>
          <div className="pcf-control">
            {renderControl(field)}
            {field.help && <div className="pcf-help">{field.help}</div>}
          </div>
        </div>
      ))}
    </div>
  );
};

// True only when every required option has an answer — used to gate Add to Cart / Buy Now.
export const isCustomizationComplete = (fields, values) => {
  if (!fields || fields.length === 0) return true;
  return fields.every((f) => {
    if (!f.required) return true;
    const v = values[f.label] || {};
    if (CHOICE_TYPES.includes(f.type)) {
      return f.type === 'multi' ? (v.values || []).length > 0 : !!v.value;
    }
    const hasText = wantsText(f.type) && !!(v.text && String(v.text).trim());
    const hasImage = wantsImage(f.type) && !!v.fileUrl;
    return hasText || hasImage;
  });
};

// Extra price per piece from the chosen option values (admin "+₹" per value).
export const customizationExtraPrice = (fields, values) => {
  if (!fields || !fields.length) return 0;
  return fields.reduce((sum, f) => {
    const v = values[f.label] || {};
    const opts = optionsOf(f);
    if (f.type === 'multi') {
      return sum + opts.filter((o) => (v.values || []).includes(o.label)).reduce((s, o) => s + (Number(o.price) || 0), 0);
    }
    if (CHOICE_TYPES.includes(f.type)) {
      const o = opts.find((x) => x.label === v.value);
      return sum + (o ? Number(o.price) || 0 : 0);
    }
    return sum;
  }, 0);
};

export default ProductCustomizationForm;
