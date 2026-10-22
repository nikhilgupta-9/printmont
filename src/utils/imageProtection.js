import { ASSET_URL, resolveImageUrl } from '../config/apiEndpoints';
import toast from 'react-hot-toast';

/**
 * Returns the watermarked download URL for product, banner, and carousel images
 */
export const getWatermarkedImageUrl = (imagePath) => {
  if (!imagePath) return '/default-img.jpg';
  const resolved = resolveImageUrl(imagePath);

  if (resolved.includes('watermark-handler.php')) {
    return resolved.includes('download=1') ? resolved : `${resolved}&download=1`;
  }

  if (resolved.includes('/uploads/')) {
    const relative = resolved.substring(resolved.indexOf('uploads/'));
    return `${ASSET_URL}watermark-handler.php?src=${encodeURIComponent(relative)}&download=1`;
  }

  return resolved;
};

/**
 * Downloads a watermarked image programmatically on frontend
 */
export const downloadWatermarkedImage = async (imagePath, defaultFilename = 'printmont-product.jpg') => {
  try {
    const wmUrl = getWatermarkedImageUrl(imagePath);
    const link = document.createElement('a');
    link.href = wmUrl;
    link.download = defaultFilename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    return true;
  } catch (error) {
    console.error('Failed to download watermarked image:', error);
    return false;
  }
};

/**
 * Initialize anti-theft image protection for Products, Banners, and Carousel images
 * Ensures clean original display on website, and applies watermark only on download/save attempt.
 */
export const initImageProtection = () => {
  if (typeof window === 'undefined') return;

  const isProtectedImage = (src, element) => {
    if (!src) return false;
    const s = src.toLowerCase();
    return (
      s.includes('/products/') ||
      s.includes('/banners/') ||
      s.includes('/crousel') ||
      s.includes('/carousel') ||
      s.includes('/card/') ||
      s.includes('/section-img/') ||
      (element && element.classList && element.classList.contains('protected-img')) ||
      (element && element.closest && element.closest('.product-card, .product-image-container, .hero-slider, .banner-section'))
    );
  };

  // Prevent dragging protected images
  document.addEventListener('dragstart', (e) => {
    if (e.target && e.target.tagName === 'IMG') {
      const src = e.target.src || '';
      if (isProtectedImage(src, e.target)) {
        e.preventDefault();
      }
    }
  }, false);

  // Intercept context menu / right click on protected images and trigger watermarked download
  document.addEventListener('contextmenu', (e) => {
    if (e.target && e.target.tagName === 'IMG') {
      const src = e.target.src || '';
      if (isProtectedImage(src, e.target)) {
        e.preventDefault();
        const filename = src.split('/').pop().split('?')[0] || 'printmont-product.jpg';
        
        toast.success('Downloading copyright-protected image...', {
          duration: 3000,
          position: 'bottom-center'
        });

        downloadWatermarkedImage(src, filename);
      }
    }
  }, false);
};
