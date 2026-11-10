import React, { useState, useEffect, useRef, useMemo } from 'react';
import {
  FaTimes,
  FaFont,
  FaUpload,
  FaIcons,
  FaLayerGroup,
  FaUndo,
  FaRedo,
  FaTrashAlt,
  FaSearchPlus,
  FaSearchMinus,
  FaCheck,
  FaEye,
  FaEyeSlash,
  FaSyncAlt,
  FaBold,
  FaItalic,
  FaUnderline,
  FaAlignCenter,
  FaAlignLeft,
  FaAlignRight,
  FaShoppingBag,
  FaExchangeAlt
} from 'react-icons/fa';
import { MdOutlineRotateRight, MdAspectRatio } from 'react-icons/md';
import toast from 'react-hot-toast';
import { API_ENDPOINTS, resolveImageUrl } from '../../config/apiEndpoints';
import './CustomizerStudioModal.css';

const DEFAULT_FONTS = [
  { family: 'Inter', label: 'Inter (Modern Standard)' },
  { family: 'Roboto', label: 'Roboto (Clean Sans)' },
  { family: 'Poppins', label: 'Poppins (Geometric Bold)' },
  { family: 'Montserrat', label: 'Montserrat (Brand Pro)' },
  { family: 'Playfair Display', label: 'Playfair Display (Luxury Serif)' },
  { family: 'Bebas Neue', label: 'Bebas Neue (Impact All-Caps)' },
  { family: 'Pacifico', label: 'Pacifico (Brush Script)' },
  { family: 'Oswald', label: 'Oswald (Compact Bold)' },
  { family: 'Dancing Script', label: 'Dancing Script (Cursive)' },
  { family: 'Courier Prime', label: 'Courier Prime (Monospace)' }
];

const COLOR_PALETTE = [
  '#000000', '#ffffff', '#1e293b', '#64748b', '#0b53a1',
  '#0284c7', '#059669', '#16a34a', '#dc2626', '#e11d48',
  '#d97706', '#f59e0b', '#7c3aed', '#9333ea', '#c026d3'
];

export default function CustomizerStudioModal({
  show,
  onHide,
  productData = {},
  selectedColor = '',
  quantity = 1,
  addToCart,
  onSaveDesign
}) {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [template, setTemplate] = useState(null);
  const [availableTemplates, setAvailableTemplates] = useState([]);
  const [cliparts, setCliparts] = useState([]);
  const [fonts, setFonts] = useState(DEFAULT_FONTS);
  const [activeToolTab, setActiveToolTab] = useState('text');
  const [toolsOpen, setToolsOpen] = useState(false);
  const [currentViewIndex, setCurrentViewIndex] = useState(0);
  const [sidesData, setSidesData] = useState({});
  const [selectedLayerId, setSelectedLayerId] = useState(null);
  const [showSafeGuides, setShowSafeGuides] = useState(true);
  const [zoomLevel, setZoomLevel] = useState(1);
  const [history, setHistory] = useState([]);
  const [historyIdx, setHistoryIdx] = useState(-1);

  // Text builder draft state
  const [textDraft, setTextDraft] = useState('Your Text Here');
  const [fontDraft, setFontDraft] = useState('Inter');
  const [colorDraft, setColorDraft] = useState('#1e293b');
  const [sizeDraft, setSizeDraft] = useState(26);
  const [boldDraft, setBoldDraft] = useState(false);
  const [italicDraft, setItalicDraft] = useState(false);
  const [underlineDraft, setUnderlineDraft] = useState(false);
  const [alignDraft, setAlignDraft] = useState('center');
  const [arcDraft, setArcDraft] = useState(0);

  // References
  const stageRef = useRef(null);
  const fileInputRef = useRef(null);
  const dragInfoRef = useRef(null);

  // 1. Load Google Fonts stylesheet into document
  useEffect(() => {
    const fontId = 'pm-customizer-google-fonts';
    if (!document.getElementById(fontId)) {
      const link = document.createElement('link');
      link.id = fontId;
      link.rel = 'stylesheet';
      link.href = 'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Courier+Prime&family=Dancing+Script:wght@600&family=Inter:wght@400;600;700&family=Montserrat:wght@400;600;800&family=Oswald:wght@500;700&family=Pacifico&family=Playfair+Display:ital,wght@0,600;1,600&family=Poppins:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap';
      document.head.appendChild(link);
    }
  }, []);

  // 2. Fetch matched Customizer Template from API
  const fetchTemplate = async (explicitKeyword = '') => {
    setLoading(true);
    try {
      const url = API_ENDPOINTS.CUSTOMIZER_GET_TEMPLATE(productData?.id, explicitKeyword || productData?.name || productData?.type || '');
      const res = await fetch(url);
      const data = await res.json();

      if (data?.success && data?.template) {
        setTemplate(data.template);
        if (Array.isArray(data.available_templates)) {
          setAvailableTemplates(data.available_templates);
        }
        if (Array.isArray(data.cliparts)) {
          setCliparts(data.cliparts);
        }
        if (Array.isArray(data.fonts)) {
          setFonts(data.fonts);
        }

        // Initialize sides data
        const initialSides = {};
        (data.template.views || []).forEach((v, idx) => {
          initialSides[v.id] = { layers: [] };
        });
        setSidesData(initialSides);
        setCurrentViewIndex(0);
        setSelectedLayerId(null);
      }
    } catch (err) {
      console.error('Failed to load customizer template:', err);
      toast.error('Could not load customizer template. Falling back to universal mode.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (show) {
      fetchTemplate();
    }
  }, [show, productData?.id]);

  // Current view object
  const currentView = useMemo(() => {
    if (!template?.views || template.views.length === 0) return null;
    return template.views[currentViewIndex] || template.views[0];
  }, [template, currentViewIndex]);

  const currentLayers = useMemo(() => {
    if (!currentView) return [];
    return sidesData[currentView.id]?.layers || [];
  }, [sidesData, currentView]);

  const selectedLayer = useMemo(() => {
    if (!selectedLayerId) return null;
    return currentLayers.find((l) => l.id === selectedLayerId) || null;
  }, [currentLayers, selectedLayerId]);

  // Sync draft inputs when selecting a text layer
  useEffect(() => {
    if (selectedLayer && selectedLayer.type === 'text') {
      setTextDraft(selectedLayer.text || '');
      setFontDraft(selectedLayer.font || 'Inter');
      setColorDraft(selectedLayer.color || '#1e293b');
      setSizeDraft(selectedLayer.size || 26);
      setBoldDraft(!!selectedLayer.bold);
      setItalicDraft(!!selectedLayer.italic);
      setUnderlineDraft(!!selectedLayer.underline);
      setAlignDraft(selectedLayer.align || 'center');
      setArcDraft(selectedLayer.arc || 0);
    }
  }, [selectedLayerId]);

  // Push state to Undo history
  const pushState = (newSides) => {
    setSidesData(newSides);
    setHistory((prev) => [...prev.slice(0, historyIdx + 1), JSON.stringify(newSides)]);
    setHistoryIdx((prev) => prev + 1);
  };

  const handleUndo = () => {
    if (historyIdx > 0) {
      const prevSides = JSON.parse(history[historyIdx - 1]);
      setSidesData(prevSides);
      setHistoryIdx((i) => i - 1);
      setSelectedLayerId(null);
    }
  };

  const handleRedo = () => {
    if (historyIdx < history.length - 1) {
      const nextSides = JSON.parse(history[historyIdx + 1]);
      setSidesData(nextSides);
      setHistoryIdx((i) => i + 1);
      setSelectedLayerId(null);
    }
  };

  // Add / Update Text Layer
  const handleAddOrUpdateText = () => {
    if (!currentView) return;
    const viewId = currentView.id;
    const layers = [...(sidesData[viewId]?.layers || [])];

    if (selectedLayer && selectedLayer.type === 'text') {
      // Update existing
      const updated = layers.map((l) =>
        l.id === selectedLayer.id
          ? {
              ...l,
              text: textDraft,
              font: fontDraft,
              color: colorDraft,
              size: sizeDraft,
              bold: boldDraft,
              italic: italicDraft,
              underline: underlineDraft,
              align: alignDraft,
              arc: arcDraft
            }
          : l
      );
      pushState({ ...sidesData, [viewId]: { layers: updated } });
      toast.success('Text updated');
    } else {
      // Add new text
      const newLayer = {
        id: 'layer_' + Date.now(),
        type: 'text',
        text: textDraft || 'Company Name',
        font: fontDraft,
        color: colorDraft,
        size: sizeDraft,
        bold: boldDraft,
        italic: italicDraft,
        underline: underlineDraft,
        align: alignDraft,
        arc: arcDraft,
        x: 50, // center %
        y: 50,
        rotation: 0
      };
      pushState({ ...sidesData, [viewId]: { layers: [...layers, newLayer] } });
      setSelectedLayerId(newLayer.id);
      toast.success('Text added to canvas');
    }
  };

  // Upload Logo
  const handleLogoUpload = async (file) => {
    if (!file) return;
    const allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml', 'image/webp'];
    if (!allowed.includes(file.type)) {
      toast.error('Upload only PNG, JPG, JPEG, WEBP or SVG images.');
      return;
    }
    if (file.size > 15 * 1024 * 1024) {
      toast.error('File size must be under 15MB.');
      return;
    }

    const toastId = toast.loading('Uploading logo artwork...');
    try {
      const formData = new FormData();
      formData.append('file', file);
      const res = await fetch(API_ENDPOINTS.CUSTOMIZER_UPLOAD_LOGO, {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (!data?.success) throw new Error(data?.error || 'Upload failed');

      toast.dismiss(toastId);
      toast.success('Logo uploaded!');

      // Add as image layer
      if (currentView) {
        const viewId = currentView.id;
        const newLayer = {
          id: 'layer_' + Date.now(),
          type: 'image',
          url: data.url,
          name: file.name,
          width: 120, // px on stage
          height: 120,
          x: 50,
          y: 45,
          rotation: 0,
          opacity: 1
        };
        const layers = [...(sidesData[viewId]?.layers || []), newLayer];
        pushState({ ...sidesData, [viewId]: { layers } });
        setSelectedLayerId(newLayer.id);
      }
    } catch (err) {
      toast.dismiss(toastId);
      toast.error(err.message || 'Failed to upload logo.');
    }
  };

  // Add Clipart Layer
  const handleAddClipart = (clip) => {
    if (!currentView) return;
    const viewId = currentView.id;
    const newLayer = {
      id: 'layer_' + Date.now(),
      type: 'clipart',
      name: clip.name,
      svg: clip.svg,
      color: '#0b53a1',
      width: 90,
      height: 90,
      x: 50,
      y: 50,
      rotation: 0
    };
    const layers = [...(sidesData[viewId]?.layers || []), newLayer];
    pushState({ ...sidesData, [viewId]: { layers } });
    setSelectedLayerId(newLayer.id);
    toast.success(`${clip.name} added`);
  };

  // Layer Actions: Delete, Reorder, Center
  const handleDeleteLayer = (id) => {
    if (!currentView) return;
    const viewId = currentView.id;
    const layers = (sidesData[viewId]?.layers || []).filter((l) => l.id !== id);
    pushState({ ...sidesData, [viewId]: { layers } });
    if (selectedLayerId === id) setSelectedLayerId(null);
  };

  const handleCenterLayer = (id) => {
    if (!currentView) return;
    const viewId = currentView.id;
    const layers = (sidesData[viewId]?.layers || []).map((l) =>
      l.id === id ? { ...l, x: 50, y: 50 } : l
    );
    pushState({ ...sidesData, [viewId]: { layers } });
  };

  // Interactive Layer Drag on Canvas
  const handleStartDrag = (e, layer) => {
    e.stopPropagation();
    setSelectedLayerId(layer.id);

    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;

    const stageEl = stageRef.current;
    if (!stageEl) return;
    const rect = stageEl.getBoundingClientRect();

    dragInfoRef.current = {
      layerId: layer.id,
      startX: clientX,
      startY: clientY,
      initX: layer.x,
      initY: layer.y,
      stageWidth: rect.width,
      stageHeight: rect.height
    };

    window.addEventListener('mousemove', handleDragMove);
    window.addEventListener('mouseup', handleDragEnd);
    window.addEventListener('touchmove', handleDragMove, { passive: false });
    window.addEventListener('touchend', handleDragEnd);
  };

  const handleDragMove = (e) => {
    if (!dragInfoRef.current || !currentView) return;
    if (e.cancelable) e.preventDefault();

    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;

    const { layerId, startX, startY, initX, initY, stageWidth, stageHeight } = dragInfoRef.current;
    const deltaXPercent = ((clientX - startX) / stageWidth) * 100;
    const deltaYPercent = ((clientY - startY) / stageHeight) * 100;

    const newX = Math.max(10, Math.min(90, initX + deltaXPercent));
    const newY = Math.max(10, Math.min(90, initY + deltaYPercent));

    const viewId = currentView.id;
    setSidesData((prev) => ({
      ...prev,
      [viewId]: {
        layers: (prev[viewId]?.layers || []).map((l) =>
          l.id === layerId ? { ...l, x: newX, y: newY } : l
        )
      }
    }));
  };

  const handleDragEnd = () => {
    window.removeEventListener('mousemove', handleDragMove);
    window.removeEventListener('mouseup', handleDragEnd);
    window.removeEventListener('touchmove', handleDragMove);
    window.removeEventListener('touchend', handleDragEnd);
    dragInfoRef.current = null;
  };

  // Generate High-Res Offscreen Snapshot and Add to Cart
  const handleSaveAndAddToCart = async () => {
    setSaving(true);
    const toastId = toast.loading('Rendering high-res product mockup...');

    try {
      // Create offscreen canvas
      const canvas = document.createElement('canvas');
      canvas.width = 1200;
      canvas.height = 1400;
      const ctx = canvas.getContext('2d');

      // 1. Fill clean background
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, canvas.width, canvas.height);

      // 2. Load mockup image
      const mockupUrl = currentView?.mockup_url || '/mockups/general_gift.svg';
      const mockupImg = new Image();
      mockupImg.crossOrigin = 'anonymous';
      await new Promise((resolve) => {
        mockupImg.onload = resolve;
        mockupImg.onerror = resolve;
        mockupImg.src = mockupUrl;
      });

      // Draw mockup centered
      ctx.drawImage(mockupImg, 100, 100, 1000, 1200);

      // 3. Draw layers on printable area
      const printArea = currentView?.print_area || { x: 25, y: 25, width: 50, height: 50 };
      const px = (printArea.x / 100) * 1000 + 100;
      const py = (printArea.y / 100) * 1200 + 100;
      const pw = (printArea.width / 100) * 1000;
      const ph = (printArea.height / 100) * 1200;

      for (const layer of currentLayers) {
        ctx.save();
        const lx = px + (layer.x / 100) * pw;
        const ly = py + (layer.y / 100) * ph;
        ctx.translate(lx, ly);
        if (layer.rotation) {
          ctx.rotate((layer.rotation * Math.PI) / 180);
        }

        if (layer.type === 'text') {
          ctx.fillStyle = layer.color || '#1e293b';
          const fontStyle = `${layer.italic ? 'italic ' : ''}${layer.bold ? 'bold ' : ''}${layer.size * 2}px "${layer.font || 'Inter'}", sans-serif`;
          ctx.font = fontStyle;
          ctx.textAlign = layer.align || 'center';
          ctx.textBaseline = 'middle';
          ctx.fillText(layer.text, 0, 0);
        } else if (layer.type === 'image' && layer.url) {
          const img = new Image();
          img.crossOrigin = 'anonymous';
          await new Promise((res) => {
            img.onload = res;
            img.onerror = res;
            img.src = layer.url;
          });
          const imgW = (layer.width || 120) * 2;
          const imgH = (layer.height || 120) * 2;
          ctx.globalAlpha = layer.opacity || 1;
          ctx.drawImage(img, -imgW / 2, -imgH / 2, imgW, imgH);
        }
        ctx.restore();
      }

      // Convert to base64
      const previewDataUrl = canvas.toDataURL('image/png', 0.95);

      // Save design to backend API
      const saveRes = await fetch(API_ENDPOINTS.CUSTOMIZER_SAVE_DESIGN, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          preview_image: previewDataUrl,
          design_data: sidesData,
          product_id: productData?.id || 0
        })
      });
      const saveData = await saveRes.json();
      const finalPreviewUrl = saveData?.preview_url || previewDataUrl;

      // Pack custom design data
      const designPayload = {
        preview_url: finalPreviewUrl,
        template_title: template?.title || 'Custom Product',
        sides: sidesData,
        texts: saveData?.design_data?.texts || [],
        images: saveData?.design_data?.images || []
      };

      toast.dismiss(toastId);

      // 1. Add to cart with custom design attached
      if (addToCart) {
        addToCart(productData, quantity, {
          image: finalPreviewUrl,
          customDesign: designPayload,
          uploadedArtwork: `Custom Design (${designPayload.texts.length} texts, ${designPayload.images.length} logos)`,
          color: selectedColor || productData?.color || 'Default'
        });
      }

      // 2. Call onSaveDesign so the PDP updates the active mockup image
      if (onSaveDesign) {
        onSaveDesign(designPayload);
      }

      toast.success('Your custom design was applied and added to cart!', { duration: 3500 });
      onHide();
    } catch (err) {
      console.error('Failed to save design:', err);
      toast.dismiss(toastId);
      toast.error('Failed to render design snapshot. Please try again.');
    } finally {
      setSaving(false);
    }
  };

  if (!show) return null;

  return (
    <div className="csm-studio-overlay" role="dialog" aria-modal="true">
      {/* ──────── TOP HEADER ──────── */}
      <header className="csm-header">
        <div className="csm-header-left">
          <button type="button" className="csm-back-btn" onClick={onHide} title="Exit Studio">
            <FaTimes />
          </button>
          <div className="csm-product-info">
            <span className="csm-product-title">{productData?.title || productData?.name || 'Custom Product'}</span>
            <span className="csm-template-badge">
              <span className="badge bg-primary-subtle text-primary border" style={{ fontSize: '10px' }}>
                {template?.title || 'Custom Canvas'}
              </span>
            </span>
          </div>
        </div>

        {/* View Switcher Pills */}
        <div className="csm-header-center">
          {(template?.views || []).map((v, idx) => {
            const count = (sidesData[v.id]?.layers || []).length;
            const isActive = currentViewIndex === idx;
            return (
              <button
                key={v.id}
                type="button"
                className={`csm-view-pill ${isActive ? 'active' : ''}`}
                onClick={() => {
                  setCurrentViewIndex(idx);
                  setSelectedLayerId(null);
                }}
              >
                <span>{v.name}</span>
                {count > 0 && <span className="csm-view-pill-count">{count}</span>}
              </button>
            );
          })}
        </div>

        <div className="csm-header-right">
          <button type="button" className="csm-action-btn" onClick={handleUndo} disabled={historyIdx <= 0} title="Undo">
            <FaUndo size={12} /> <span className="d-none d-md-inline">Undo</span>
          </button>
          <button type="button" className="csm-action-btn" onClick={handleRedo} disabled={historyIdx >= history.length - 1} title="Redo">
            <FaRedo size={12} /> <span className="d-none d-md-inline">Redo</span>
          </button>
          <button
            type="button"
            className="csm-save-cart-btn"
            onClick={handleSaveAndAddToCart}
            disabled={saving}
          >
            {saving ? (
              <span className="spinner-border spinner-border-sm" role="status" />
            ) : (
              <>
                <FaShoppingBag />
                <span>Done & Add to Cart</span>
              </>
            )}
          </button>
        </div>
      </header>

      {/* ──────── MAIN STUDIO BODY ──────── */}
      <div className="csm-body">
        {/* LEFT TOOLBAR */}
        <aside className={`csm-sidebar ${toolsOpen ? 'mobile-open' : ''}`}>
          {/* Tool Navigation Bar */}
          <nav className="csm-tool-nav">
            <button
              type="button"
              className={`csm-tool-tab ${activeToolTab === 'text' ? 'active' : ''}`}
              onClick={() => { setActiveToolTab('text'); setToolsOpen(true); }}
            >
              <FaFont />
              <span>Add Text</span>
            </button>
            <button
              type="button"
              className={`csm-tool-tab ${activeToolTab === 'image' ? 'active' : ''}`}
              onClick={() => { setActiveToolTab('image'); setToolsOpen(true); }}
            >
              <FaUpload />
              <span>Upload Logo</span>
            </button>
            <button
              type="button"
              className={`csm-tool-tab ${activeToolTab === 'clipart' ? 'active' : ''}`}
              onClick={() => { setActiveToolTab('clipart'); setToolsOpen(true); }}
            >
              <FaIcons />
              <span>Clipart</span>
            </button>
            <button
              type="button"
              className={`csm-tool-tab ${activeToolTab === 'layers' ? 'active' : ''}`}
              onClick={() => { setActiveToolTab('layers'); setToolsOpen(true); }}
            >
              <FaLayerGroup />
              <span>Layers ({currentLayers.length})</span>
            </button>
            <button
              type="button"
              className={`csm-tool-tab ${activeToolTab === 'templates' ? 'active' : ''}`}
              onClick={() => { setActiveToolTab('templates'); setToolsOpen(true); }}
            >
              <FaExchangeAlt />
              <span>Mockup</span>
            </button>
          </nav>

          {/* Tool Content Panels */}
          <div className="csm-tool-content">
            <button type="button" className="csm-mobile-panel-close" onClick={() => setToolsOpen(false)} aria-label="Close tools"><FaTimes /></button>
            {/* 1. TEXT TOOL */}
            {activeToolTab === 'text' && (
              <div className="csm-tool-panel">
                <div className="csm-panel-heading">
                  <span>{selectedLayer?.type === 'text' ? 'Edit Selected Text' : 'Add Custom Text'}</span>
                  {selectedLayer?.type === 'text' && (
                    <button type="button" className="btn btn-sm btn-link text-danger p-0" onClick={() => handleDeleteLayer(selectedLayer.id)}>
                      <FaTrashAlt />
                    </button>
                  )}
                </div>

                <div className="csm-field-group">
                  <label className="csm-label">Your Text Message / Name</label>
                  <input
                    type="text"
                    className="csm-input"
                    value={textDraft}
                    onChange={(e) => setTextDraft(e.target.value)}
                    placeholder="Enter company name, tagline, or employee name"
                  />
                </div>

                {/* Typography Controls */}
                <div className="csm-field-group">
                  <label className="csm-label">Font Family</label>
                  <div className="csm-font-selector">
                    {fonts.map((f) => (
                      <div
                        key={f.family}
                        className={`csm-font-option ${fontDraft === f.family ? 'active' : ''}`}
                        onClick={() => setFontDraft(f.family)}
                        style={{ fontFamily: `'${f.family}', sans-serif` }}
                      >
                        <span>{f.family}</span>
                        {fontDraft === f.family && <FaCheck size={11} />}
                      </div>
                    ))}
                  </div>
                </div>

                {/* Font Size & Stepper */}
                <div className="csm-field-group">
                  <div className="d-flex justify-content-between align-items-center mb-1">
                    <label className="csm-label mb-0">Font Size ({sizeDraft}px)</label>
                    <div className="btn-group btn-group-sm">
                      <button type="button" className="btn btn-outline-secondary py-0" onClick={() => setSizeDraft(Math.max(12, sizeDraft - 2))}>-</button>
                      <button type="button" className="btn btn-outline-secondary py-0" onClick={() => setSizeDraft(Math.min(72, sizeDraft + 2))}>+</button>
                    </div>
                  </div>
                  <input
                    type="range"
                    className="form-range"
                    min="12"
                    max="72"
                    value={sizeDraft}
                    onChange={(e) => setSizeDraft(Number(e.target.value))}
                  />
                </div>

                {/* Color Palette */}
                <div className="csm-field-group">
                  <label className="csm-label">Text Color</label>
                  <div className="csm-color-swatches">
                    {COLOR_PALETTE.map((hex) => (
                      <span
                        key={hex}
                        className={`csm-color-bubble ${colorDraft === hex ? 'active' : ''}`}
                        style={{ backgroundColor: hex }}
                        onClick={() => setColorDraft(hex)}
                      />
                    ))}
                    <input
                      type="color"
                      className="csm-color-picker-input"
                      value={colorDraft}
                      onChange={(e) => setColorDraft(e.target.value)}
                      title="Custom Color"
                    />
                  </div>
                </div>

                {/* Style Buttons (Bold, Italic, Align) */}
                <div className="csm-field-group">
                  <label className="csm-label">Styling</label>
                  <div className="d-flex gap-2">
                    <button
                      type="button"
                      className={`btn btn-sm btn-outline-secondary ${boldDraft ? 'active' : ''}`}
                      onClick={() => setBoldDraft(!boldDraft)}
                    >
                      <FaBold />
                    </button>
                    <button
                      type="button"
                      className={`btn btn-sm btn-outline-secondary ${italicDraft ? 'active' : ''}`}
                      onClick={() => setItalicDraft(!italicDraft)}
                    >
                      <FaItalic />
                    </button>
                    <button
                      type="button"
                      className={`btn btn-sm btn-outline-secondary ${alignDraft === 'left' ? 'active' : ''}`}
                      onClick={() => setAlignDraft('left')}
                    >
                      <FaAlignLeft />
                    </button>
                    <button
                      type="button"
                      className={`btn btn-sm btn-outline-secondary ${alignDraft === 'center' ? 'active' : ''}`}
                      onClick={() => setAlignDraft('center')}
                    >
                      <FaAlignCenter />
                    </button>
                    <button
                      type="button"
                      className={`btn btn-sm btn-outline-secondary ${alignDraft === 'right' ? 'active' : ''}`}
                      onClick={() => setAlignDraft('right')}
                    >
                      <FaAlignRight />
                    </button>
                  </div>
                </div>

                {/* Curved / Arc Text Slider */}
                <div className="csm-field-group">
                  <div className="d-flex justify-content-between">
                    <label className="csm-label">Curved / Arc Text ({arcDraft}°)</label>
                    {arcDraft !== 0 && (
                      <button type="button" className="btn btn-link btn-sm p-0 text-muted" onClick={() => setArcDraft(0)}>Reset</button>
                    )}
                  </div>
                  <input
                    type="range"
                    className="form-range"
                    min="-80"
                    max="80"
                    value={arcDraft}
                    onChange={(e) => setArcDraft(Number(e.target.value))}
                  />
                </div>

                <button
                  type="button"
                  className="btn btn-primary w-100 py-2 fw-bold"
                  onClick={handleAddOrUpdateText}
                >
                  <FaCheck className="me-1" />
                  {selectedLayer?.type === 'text' ? 'Update Canvas Text' : 'Place Text on Product'}
                </button>
              </div>
            )}

            {/* 2. LOGO UPLOAD TOOL */}
            {activeToolTab === 'image' && (
              <div className="csm-tool-panel">
                <div className="csm-panel-heading">
                  <span>Upload Logo / Artwork</span>
                </div>

                <div
                  className="csm-dropzone"
                  onClick={() => fileInputRef.current?.click()}
                  onDragOver={(e) => e.preventDefault()}
                  onDrop={(e) => {
                    e.preventDefault();
                    if (e.dataTransfer.files?.[0]) handleLogoUpload(e.dataTransfer.files[0]);
                  }}
                >
                  <FaUpload className="csm-dropzone-icon" />
                  <div className="fw-bold text-dark small mb-1">Click to Upload Company Logo</div>
                  <div className="text-muted" style={{ fontSize: '11px' }}>
                    PNG with transparent background, JPG, or SVG
                  </div>
                  <div className="badge bg-light text-muted border mt-2" style={{ fontSize: '10px' }}>
                    Max 15MB • 300 DPI Recommended
                  </div>
                </div>

                <input
                  type="file"
                  ref={fileInputRef}
                  style={{ display: 'none' }}
                  accept="image/png,image/jpeg,image/svg+xml,image/webp"
                  onChange={(e) => {
                    if (e.target.files?.[0]) handleLogoUpload(e.target.files[0]);
                  }}
                />

                {/* Selected Image Controls */}
                {selectedLayer?.type === 'image' && (
                  <div className="mt-4 p-3 bg-light rounded border">
                    <div className="d-flex justify-content-between align-items-center mb-2">
                      <span className="fw-bold small text-dark">Selected Logo Size</span>
                      <button type="button" className="btn btn-sm btn-link text-danger p-0" onClick={() => handleDeleteLayer(selectedLayer.id)}>
                        <FaTrashAlt />
                      </button>
                    </div>
                    <input
                      type="range"
                      className="form-range"
                      min="50"
                      max="280"
                      value={selectedLayer.width || 120}
                      onChange={(e) => {
                        const val = Number(e.target.value);
                        const viewId = currentView.id;
                        setSidesData((prev) => ({
                          ...prev,
                          [viewId]: {
                            layers: (prev[viewId]?.layers || []).map((l) =>
                              l.id === selectedLayer.id ? { ...l, width: val, height: val } : l
                            )
                          }
                        }));
                      }}
                    />
                    <button
                      type="button"
                      className="btn btn-sm btn-outline-secondary w-100 mt-2"
                      onClick={() => handleCenterLayer(selectedLayer.id)}
                    >
                      <FaAlignCenter className="me-1" /> Center Logo on Print Zone
                    </button>
                  </div>
                )}
              </div>
            )}

            {/* 3. CLIPART TOOL */}
            {activeToolTab === 'clipart' && (
              <div className="csm-tool-panel">
                <div className="csm-panel-heading">
                  <span>Corporate Icons & Badges</span>
                </div>
                <p className="text-muted small mb-3">Click any icon to place on your product.</p>
                <div className="csm-clipart-grid">
                  {cliparts.map((clip) => (
                    <div
                      key={clip.id}
                      className="csm-clipart-item"
                      onClick={() => handleAddClipart(clip)}
                      title={clip.name}
                    >
                      {clip.svg ? (
                        <div dangerouslySetInnerHTML={{ __html: clip.svg }} />
                      ) : (
                        <FaIcons />
                      )}
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* 4. LAYERS LIST */}
            {activeToolTab === 'layers' && (
              <div className="csm-tool-panel">
                <div className="csm-panel-heading">
                  <span>Layers on {currentView?.name}</span>
                </div>
                {currentLayers.length === 0 ? (
                  <div className="text-center py-4 text-muted small">
                    No design elements added to this view yet.
                  </div>
                ) : (
                  <div className="csm-layers-list">
                    {currentLayers.map((l, i) => (
                      <div
                        key={l.id}
                        className={`csm-layer-item ${selectedLayerId === l.id ? 'active' : ''}`}
                        onClick={() => setSelectedLayerId(l.id)}
                      >
                        <div className="csm-layer-label">
                          {l.type === 'text' ? <FaFont className="text-primary" /> : l.type === 'image' ? <FaUpload className="text-success" /> : <FaIcons className="text-info" />}
                          <span>{l.text || l.name || `Layer ${i + 1}`}</span>
                        </div>
                        <div className="csm-layer-actions">
                          <button
                            type="button"
                            className="csm-layer-action-btn"
                            onClick={(e) => {
                              e.stopPropagation();
                              handleCenterLayer(l.id);
                            }}
                            title="Center"
                          >
                            <FaAlignCenter />
                          </button>
                          <button
                            type="button"
                            className="csm-layer-action-btn"
                            onClick={(e) => {
                              e.stopPropagation();
                              handleDeleteLayer(l.id);
                            }}
                            title="Delete"
                          >
                            <FaTrashAlt />
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

            {/* 5. MOCKUP TEMPLATES SWITCHER */}
            {activeToolTab === 'templates' && (
              <div className="csm-tool-panel">
                <div className="csm-panel-heading">
                  <span>Switch Product Canvas Mockup</span>
                </div>
                <p className="text-muted small mb-2">
                  Adapt this customizer for a different corporate gift item:
                </p>
                <div className="list-group">
                  {availableTemplates.map((t) => (
                    <button
                      key={t.id}
                      type="button"
                      className={`list-group-item list-group-item-action ${template?.id === t.id ? 'active' : ''}`}
                      onClick={() => fetchTemplate(t.keyword)}
                    >
                      <div className="fw-semibold">{t.title}</div>
                      <small className="opacity-75">Category: {t.keyword}</small>
                    </button>
                  ))}
                </div>
              </div>
            )}
          </div>
        </aside>

        {/* ──────── CENTER CANVAS WORKSPACE ──────── */}
        <main className="csm-canvas-area" onClick={() => { setSelectedLayerId(null); setToolsOpen(false); }}>
          <div
            className="csm-mockup-stage"
            ref={stageRef}
            style={{
              transform: `scale(${zoomLevel})`
            }}
          >
            {/* Product Mockup Backdrop */}
            {currentView?.mockup_url && (
              <img
                src={currentView.mockup_url}
                alt="Product Mockup"
                className="csm-mockup-bg"
                style={{
                  filter: currentView.tintable && selectedColor ? `drop-shadow(0 4px 12px rgba(0,0,0,0.1))` : 'none'
                }}
              />
            )}

            {/* Safe Printable Area */}
            {currentView?.print_area && (
              <div
                className={`csm-safe-print-area ${!showSafeGuides ? 'hidden-guides' : ''}`}
                style={{
                  left: `${currentView.print_area.x}%`,
                  top: `${currentView.print_area.y}%`,
                  width: `${currentView.print_area.width}%`,
                  height: `${currentView.print_area.height}%`
                }}
              >
                {showSafeGuides && <span className="csm-safe-tag">Safe Print Zone</span>}

                {/* Render Layers inside Printable Area */}
                {currentLayers.map((layer) => {
                  const isSelected = selectedLayerId === layer.id;

                  return (
                    <div
                      key={layer.id}
                      className={`csm-canvas-layer ${isSelected ? 'selected' : ''}`}
                      style={{
                        left: `${layer.x}%`,
                        top: `${layer.y}%`,
                        transform: `translate(-50%, -50%) rotate(${layer.rotation || 0}deg)`
                      }}
                      onMouseDown={(e) => handleStartDrag(e, layer)}
                      onTouchStart={(e) => handleStartDrag(e, layer)}
                    >
                      {/* TEXT LAYER */}
                      {layer.type === 'text' && (
                        <div
                          style={{
                            fontFamily: `'${layer.font || 'Inter'}', sans-serif`,
                            fontSize: `${layer.size || 26}px`,
                            color: layer.color || '#1e293b',
                            fontWeight: layer.bold ? '700' : '400',
                            fontStyle: layer.italic ? 'italic' : 'normal',
                            textDecoration: layer.underline ? 'underline' : 'none',
                            textAlign: layer.align || 'center',
                            whiteSpace: 'nowrap',
                            lineHeight: 1.2
                          }}
                        >
                          {layer.text}
                        </div>
                      )}

                      {/* IMAGE LAYER */}
                      {layer.type === 'image' && (
                        <img
                          src={layer.url}
                          alt="Logo layer"
                          style={{
                            width: `${layer.width || 120}px`,
                            height: `${layer.height || 120}px`,
                            objectFit: 'contain',
                            opacity: layer.opacity || 1,
                            pointerEvents: 'none'
                          }}
                        />
                      )}

                      {/* CLIPART LAYER */}
                      {layer.type === 'clipart' && (
                        <div
                          style={{
                            width: `${layer.width || 90}px`,
                            height: `${layer.height || 90}px`,
                            color: layer.color || '#0b53a1',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            pointerEvents: 'none'
                          }}
                          dangerouslySetInnerHTML={{ __html: layer.svg }}
                        />
                      )}

                      {/* TRANSFORM HANDLES (WHEN SELECTED) */}
                      {isSelected && (
                        <>
                          <div className="csm-handle csm-handle-nw" />
                          <div className="csm-handle csm-handle-ne" />
                          <div className="csm-handle csm-handle-sw" />
                          <div className="csm-handle csm-handle-se" />
                          <div
                            className="csm-handle-del"
                            title="Delete"
                            onClick={(e) => {
                              e.stopPropagation();
                              handleDeleteLayer(layer.id);
                            }}
                          >
                            <FaTimes />
                          </div>
                        </>
                      )}
                    </div>
                  );
                })}
              </div>
            )}
          </div>

          {/* Floating Canvas Controls */}
          <div className="csm-floating-controls">
            <button
              type="button"
              className="csm-float-btn"
              onClick={() => setShowSafeGuides(!showSafeGuides)}
              title={showSafeGuides ? 'Hide Printable Guides' : 'Show Printable Guides'}
            >
              {showSafeGuides ? <FaEye /> : <FaEyeSlash />}
            </button>
            <button
              type="button"
              className="csm-float-btn"
              onClick={() => setZoomLevel((z) => Math.max(0.7, z - 0.15))}
              title="Zoom Out"
            >
              <FaSearchMinus />
            </button>
            <span className="csm-float-text">{Math.round(zoomLevel * 100)}%</span>
            <button
              type="button"
              className="csm-float-btn"
              onClick={() => setZoomLevel((z) => Math.min(1.8, z + 0.15))}
              title="Zoom In"
            >
              <FaSearchPlus />
            </button>
            <button
              type="button"
              className="csm-float-btn"
              onClick={() => setZoomLevel(1)}
              title="Reset Zoom"
            >
              <FaSyncAlt size={11} />
            </button>
          </div>
        </main>
      </div>
    </div>
  );
}
