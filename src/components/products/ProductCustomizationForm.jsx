import React, { useState } from 'react';
import { FaUpload, FaCheckCircle, FaTimes, FaSpinner } from 'react-icons/fa';
import { API_ENDPOINTS } from '../../config/apiEndpoints';

// A field's real "type" (see edit-product.php's renderCustomVariantBox) is
// 'text', 'image', or 'both' — 'both' means one field wants a text answer
// AND lets the buyer upload an image for it (e.g. type a name OR upload a
// styled logo of it). 'file' is legacy naming the admin form itself already
// normalizes to 'image', so it's treated the same way here.
const wantsText = (type) => type === 'text' || type === 'both';
const wantsImage = (type) => type === 'image' || type === 'file' || type === 'both';

// Renders whatever fields the admin configured for THIS product
// (customization_fields on the product, e.g. "Chest Logo Upload" image +
// "Custom Name/Text" text+image) — not a fixed set of slots. Only ever
// mounted when the product has customization_label_status enabled.
const ProductCustomizationForm = ({ fields, values, onChange }) => {
  const [uploadingLabel, setUploadingLabel] = useState(null);

  const updateField = (label, patch) => {
    const current = values[label] || {};
    onChange({ ...values, [label]: { ...current, ...patch } });
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

  const removeFile = (label) => {
    updateField(label, { fileUrl: undefined, fileName: undefined });
  };

  if (!fields || fields.length === 0) return null;

  return (
    <div className="customization-form-section border rounded p-3 mb-3 bg-light">
      <h6 className="fw-bold text-dark mb-3">Customize This Product</h6>
      <div className="d-flex flex-column gap-3">
        {fields.map((field, idx) => {
          const label = field.label;
          const value = values[label] || {};
          const showText = wantsText(field.type);
          const showImage = wantsImage(field.type);

          return (
            <div key={idx}>
              <label className="form-label fw-semibold text-secondary" style={{ fontSize: '0.85rem' }}>
                {label} {field.required && <span className="text-danger">*</span>}
              </label>

              <div className="d-flex flex-column gap-2">
                {showText && (
                  <input
                    type="text"
                    className="form-control"
                    value={value.text || ''}
                    onChange={(e) => updateField(label, { text: e.target.value })}
                    placeholder={`Enter ${label.toLowerCase()}`}
                  />
                )}

                {showImage && (
                  value.fileUrl ? (
                    <div className="d-flex align-items-center gap-2 border rounded p-2 bg-white" style={{ maxWidth: '320px' }}>
                      <FaCheckCircle className="text-success flex-shrink-0" />
                      <span className="text-truncate flex-grow-1" style={{ fontSize: '0.85rem' }}>{value.fileName}</span>
                      <button
                        type="button"
                        className="btn btn-sm btn-link text-danger p-0"
                        onClick={() => removeFile(label)}
                        aria-label="Remove file"
                      >
                        <FaTimes />
                      </button>
                    </div>
                  ) : (
                    <label
                      className="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2"
                      style={{ cursor: uploadingLabel === label ? 'wait' : 'pointer', width: 'fit-content' }}
                    >
                      {uploadingLabel === label ? <FaSpinner className="fa-spin" /> : <FaUpload />}
                      {uploadingLabel === label ? 'Uploading...' : (showText ? 'Or upload an image' : 'Choose Image')}
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
            </div>
          );
        })}
      </div>
    </div>
  );
};

// True only when every required field has at least one of its allowed inputs
// filled in (text and/or image, whichever the field's type offers) — used to
// gate Add to Cart.
export const isCustomizationComplete = (fields, values) => {
  if (!fields || fields.length === 0) return true;
  return fields.every((f) => {
    if (!f.required) return true;
    const v = values[f.label] || {};
    const hasText = wantsText(f.type) && !!(v.text && v.text.trim());
    const hasImage = wantsImage(f.type) && !!v.fileUrl;
    return hasText || hasImage;
  });
};

export default ProductCustomizationForm;
