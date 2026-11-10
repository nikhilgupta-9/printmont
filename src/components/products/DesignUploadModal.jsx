import React, { useEffect, useRef, useState } from "react";
import { Modal, Spinner } from "react-bootstrap";
import { FaImage, FaPlusCircle, FaTimes } from "react-icons/fa";
import toast from "react-hot-toast";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import "./DesignUploadModal.css";

const MIN_BYTES = 100 * 1024;
const MAX_BYTES = 10 * 1024 * 1024;
const ALLOWED = ["image/jpeg", "image/png"];

const emptyImages = (count) => Array(Math.max(0, count)).fill(null);
const emptyTexts = (count) => Array(Math.max(0, count)).fill("");

// "Upload your design" popup on the product page.
// Admin dynamically sets maxImages and maxTexts per product.
// value / onSave: { images: [{ url, name }], texts: [string] } (filled slots only)
const DesignUploadModal = ({
  show,
  onHide,
  initialTab = "upload",
  maxImages = 4,
  maxTexts = 2,
  value,
  onSave
}) => {
  const imageCount = Math.max(0, parseInt(maxImages ?? 4));
  const textCount = Math.max(0, parseInt(maxTexts ?? 2));

  const [tab, setTab] = useState(imageCount > 0 ? (initialTab || "upload") : "text");
  const [images, setImages] = useState(() => emptyImages(imageCount));
  const [texts, setTexts] = useState(() => emptyTexts(textCount));
  const [uploadingSlot, setUploadingSlot] = useState(null);
  const fileInput = useRef(null);
  const targetSlot = useRef(0);

  // Load the saved designs into the slots each time the popup opens
  useEffect(() => {
    if (!show) return;
    setTab(imageCount > 0 ? (initialTab || "upload") : "text");
    const imgs = emptyImages(imageCount);
    (value?.images || []).slice(0, imageCount).forEach((img, i) => { imgs[i] = img; });
    const txt = emptyTexts(textCount);
    (value?.texts || []).slice(0, textCount).forEach((t, i) => { txt[i] = t; });
    setImages(imgs);
    setTexts(txt);
  }, [show, initialTab, value, imageCount, textCount]);

  const pickFile = (slot) => {
    if (uploadingSlot !== null) return;
    targetSlot.current = slot;
    fileInput.current?.click();
  };

  const handleFile = async (file) => {
    if (!file) return;
    if (!ALLOWED.includes(file.type)) {
      toast.error("Upload only JPG, JPEG or PNG images.");
      return;
    }
    if (file.size < MIN_BYTES || file.size > MAX_BYTES) {
      toast.error("File size should be between 100KB and 10MB.");
      return;
    }
    const slot = targetSlot.current;
    setUploadingSlot(slot);
    try {
      const body = new FormData();
      body.append("file", file);
      const res = await fetch(API_ENDPOINTS.CUSTOMIZATION_UPLOAD, { method: "POST", body });
      const data = await res.json();
      if (!data?.success) throw new Error(data?.error || "Upload failed");
      setImages((prev) => {
        const next = [...prev];
        next[slot] = { url: data.url, name: file.name };
        return next;
      });
    } catch (err) {
      toast.error(err.message || "Upload failed, please try again.");
    } finally {
      setUploadingSlot(null);
    }
  };

  const removeImage = (slot) => {
    setImages((prev) => {
      const next = [...prev];
      next[slot] = null;
      return next;
    });
  };

  const handleContinue = () => {
    onSave({
      images: images.filter(Boolean),
      texts: texts.map((t) => t.trim()).filter(Boolean),
    });
    onHide();
  };

  return (
    <Modal show={show} onHide={onHide} centered size="xl" className="dum-modal">
      <Modal.Body className="dum-body">
        <button type="button" className="dum-close" onClick={onHide} aria-label="Close">
          <FaTimes />
        </button>

        {imageCount > 0 && textCount > 0 && (
          <div className="dum-tabs" role="tablist">
            <button
              type="button"
              role="tab"
              aria-selected={tab === "upload"}
              className={`dum-tab ${tab === "upload" ? "is-active" : ""}`}
              onClick={() => setTab("upload")}
            >
              Upload Designs ({imageCount})
            </button>
            <button
              type="button"
              role="tab"
              aria-selected={tab === "text"}
              className={`dum-tab ${tab === "text" ? "is-active" : ""}`}
              onClick={() => setTab("text")}
            >
              Enter Text ({textCount})
            </button>
          </div>
        )}

        <div className="dum-panel">
          {tab === "upload" && imageCount > 0 ? (
            <div className="dum-slots">
              {images.map((img, i) => (
                <div
                  key={i}
                  className={`dum-slot ${img ? "has-image" : ""}`}
                  onClick={() => !img && pickFile(i)}
                  role={img ? undefined : "button"}
                  tabIndex={img ? -1 : 0}
                  onKeyDown={(e) => { if (!img && (e.key === "Enter" || e.key === " ")) { e.preventDefault(); pickFile(i); } }}
                  aria-label={img ? img.name : `Upload design slot ${i + 1}`}
                >
                  {uploadingSlot === i ? (
                    <Spinner animation="border" variant="primary" />
                  ) : img ? (
                    <>
                      <img src={img.url} alt={img.name} />
                      <button
                        type="button"
                        className="dum-remove"
                        onClick={(e) => { e.stopPropagation(); removeImage(i); }}
                        aria-label="Remove design"
                      >
                        <FaTimes />
                      </button>
                    </>
                  ) : (
                    <>
                      <FaImage className="dum-slot-icon" />
                      <FaPlusCircle className="dum-plus" />
                      <span className="dum-slot-label" style={{ fontSize: '10px', marginTop: '4px', color: '#64748b' }}>Slot {i + 1}</span>
                    </>
                  )}
                </div>
              ))}
            </div>
          ) : (
            <div className="dum-texts">
              {texts.map((t, i) => (
                <input
                  key={i}
                  type="text"
                  className="dum-text"
                  maxLength={80}
                  placeholder={`Custom Text / Instruction Input #${i + 1}`}
                  value={t}
                  onChange={(e) => {
                    const next = [...texts];
                    next[i] = e.target.value;
                    setTexts(next);
                  }}
                />
              ))}
            </div>
          )}
        </div>

        <div className="dum-footer">
          <div>
            <h3 className="dum-ins-title">Instructions</h3>
            <ul className="dum-ins">
              <li>File size should be 100KB - 10MB only</li>
              <li>Upload only JPG, JPEG, PNG.</li>
              <li>Please upload a good quality image.</li>
              <li>Please ensure you have rights to use the image.</li>
            </ul>
          </div>
          <button type="button" className="dum-continue" onClick={handleContinue} disabled={uploadingSlot !== null}>
            Continue
          </button>
        </div>

        <input
          ref={fileInput}
          type="file"
          accept="image/jpeg,image/png"
          hidden
          onChange={(e) => {
            handleFile(e.target.files?.[0]);
            e.target.value = "";
          }}
        />
      </Modal.Body>
    </Modal>
  );
};

export default DesignUploadModal;
