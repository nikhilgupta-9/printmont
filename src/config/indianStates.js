/**
 * All 28 states and 8 union territories of India, alphabetical.
 *
 * Shared so the checkout address step and Manage Addresses cannot drift apart —
 * they previously each kept their own partial list (22 and 11 entries), which
 * meant an address saved in one screen could hold a state the other's dropdown
 * did not offer.
 *
 * Union territories are included because they are all deliverable; the list is
 * flat rather than grouped since the distinction does not matter to someone
 * filling in a shipping address.
 */
export const INDIAN_STATES = [
  'Andaman and Nicobar Islands',
  'Andhra Pradesh',
  'Arunachal Pradesh',
  'Assam',
  'Bihar',
  'Chandigarh',
  'Chhattisgarh',
  'Dadra and Nagar Haveli and Daman and Diu',
  'Delhi',
  'Goa',
  'Gujarat',
  'Haryana',
  'Himachal Pradesh',
  'Jammu and Kashmir',
  'Jharkhand',
  'Karnataka',
  'Kerala',
  'Ladakh',
  'Lakshadweep',
  'Madhya Pradesh',
  'Maharashtra',
  'Manipur',
  'Meghalaya',
  'Mizoram',
  'Nagaland',
  'Odisha',
  'Puducherry',
  'Punjab',
  'Rajasthan',
  'Sikkim',
  'Tamil Nadu',
  'Telangana',
  'Tripura',
  'Uttar Pradesh',
  'Uttarakhand',
  'West Bengal',
];

export default INDIAN_STATES;
