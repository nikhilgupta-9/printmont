import { API_ENDPOINTS } from '../config/apiEndpoints';

// Delivery check for a pincode (backend: locations-api.php?action=check_pincode).
// Resolves to { deliverable, message, city_name, state_name, delivery_by_label, cod_available, ... }.
export default async function checkPincode(pincode) {
  const pin = String(pincode || '').trim();
  if (!/^[1-9]\d{5}$/.test(pin)) {
    return { deliverable: false, message: 'Please enter a valid 6-digit pincode.' };
  }
  try {
    const res = await fetch(API_ENDPOINTS.LOCATIONS_CHECK(pin));
    const data = await res.json();
    if (data && typeof data.deliverable === 'boolean') return data;
    return { deliverable: false, message: data?.message || 'Could not check this pincode. Please try again.' };
  } catch {
    return { deliverable: false, message: 'Could not check this pincode. Please check your connection.' };
  }
}
