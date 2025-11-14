export const initialReviews = [
  {
    id: 1,
    user: 'Amit K.',
    overallRating: 4.8,
    specsRating: { camera: 4, battery: 3, display: 4, design: 5 },
    comment: "Absolutely stellar performance and a very premium feel. The value for money is unmatched!",
    images: ['https://picsum.photos/id/244/500/500', 'https://picsum.photos/id/250/500/500', 'https://picsum.photos/id/251/500/500', 'https://picsum.photos/id/247/500/500', 'https://picsum.photos/id/248/500/500', 'https://picsum.photos/id/249/500/500'],
    likes: 12,
    dislikes: 2,
  },
  {
    id: 2,
    user: 'Priya D.',
    overallRating: 3.2,
    specsRating: { camera: 4, battery: 3, display: 4, design: 5 },
    comment: "It's okay, but the design is a bit clunky. Good value, but wish the quality was better.",
    images: ['https://picsum.photos/id/253/500/500', 'https://picsum.photos/id/254/500/500'],
    likes: 5,
    dislikes: 10,
  },
  // Add more mock data for realistic testing
];
export const productSpecs = ["Camera", "Battery", "Display", "Design"];
export const Singleproductdata = {
  title: 'POCO M6 Pro 5G (Forest Green, 128 GB) (6 GB RAM)',
  rating: 4.3,
  reviewCount: '1,47,243',
  ratingCount: '11,417',
  currentPrice: '10,999',
  originalPrice: '16,999',
  discount: '35%',
  offerText: "Big Billion Days",
  offerEnds: Date.now() + 1,
  images: [
    'https://picsum.photos/id/237/500/500',
    'https://picsum.photos/id/238/500/500',
    'https://picsum.photos/id/239/500/500',
    'https://picsum.photos/id/240/500/500',
    'https://picsum.photos/id/241/500/500',
    'https://picsum.photos/id/242/500/500',
    'https://picsum.photos/id/243/500/500',
    'https://picsum.photos/id/244/500/500',
  ],
  offers: [
    { type: 'Bank Offer', text: '10% off on ICICI Bank Credit Card, up to ₹1250 on orders of ₹5,000 and above' },
    { type: 'Bank Offer', text: '10% off on ICICI Bank Credit Card EMI Transactions, up to ₹1500 on orders of ₹5,000 and above' },
    { type: 'Special Price', text: 'Get extra ₹2000 off (price inclusive of cashback/coupon)' },
  ],
  highlights: [
    '6 GB RAM | 128 GB ROM | Expandable Upto 1 TB',
    '17.25 cm (6.79 inch) Full HD+ Display',
    '50MP + 2MP | 8MP Front Camera',
    '5000 mAh Battery',
    'Snapdragon 4 Gen 2 Processor',
  ],
  specifications: [
    {
      group: 'General',
      details: [
        { label: 'Model Name', value: 'M6 Pro 5G' },
        { label: 'Color', value: 'Forest Green' },
        { label: 'SIM Type', value: 'Dual SIM' },
        { label: 'OTG Compatible', value: 'Yes' },
      ],
    },
    {
      group: 'Display Features',
      details: [
        { label: 'Display Size', value: '17.25 cm (6.79 inch)' },
        { label: 'Resolution', value: '2460 x 1080 Pixels' },
        { label: 'Display Type', value: 'Full HD+' },
      ],
    },
    {
      group: 'Memory & Storage Features',
      details: [
        { label: 'Internal Storage', value: '128 GB' },
        { label: 'RAM', value: '6 GB' },
        { label: 'Expandable Storage', value: '1 TB' },
      ],
    },
    {
      group: 'Warranty',
      details: [
        { label: 'Warranty Summary', value: '1 Year Warranty on Handset and 6 Months on Accessories' },
        { label: 'Domestic Warranty', value: '1 Year' },
      ],
    },
  ],
};

// src/data/filters.js
export const accordionFilters = [
  {
    key: 'occasion',
    title: 'OCCASION',
    options: ['Casual', 'Formal'],
  },
  {
    key: 'gender',
    title: 'GENDER',
    options: ['Men', 'Women'],
  },
  {
    key: 'brand',
    title: 'BRAND',
    options: ['Brand A', 'Brand B'],
  },
  {
    key: 'fabric',
    title: 'FABRIC',
    options: ['Cotton', 'Linen'],
  },
  {
    key: 'size',
    title: 'SIZE',
    options: ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL'],
  },
  {
    key: 'color',
    title: 'COLOR',
    options: ['White', 'Blue', 'Black', 'Green', 'Multicolour', 'Red'],
  },
  {
    key: 'fit',
    title: 'FIT',
    options: ['Regular', 'Slim', 'Relaxed', 'Comfort', 'Tailored', 'Boxy', 'Oversized'],
  },
  {
    key: 'pattern',
    title: 'PATTERN',
    options: ['Solid', 'Checkered', 'Printed', 'Self Design', 'Striped', 'Geometric Print'],
  },
];
export const productsData = [
  {
    id: 1,
    brand: "FOXIEFIT",
    title: "Men Relaxed Fit Mid Rise Baggy Jeans with Distressing",
    // image: 
    image: [
      "https://picsum.photos/id/1/750/1000",
      'https://picsum.photos/id/53/750/1000',
      'https://picsum.photos/id/54/750/1000',
      'https://picsum.photos/id/55/750/1000',
      'https://picsum.photos/id/56/750/1000'
    ],
    originalPrice: 2499,
    discountedPrice: 474,
    discountPercent: 81,
    sizes: [28, 30, 32, 34],
    assured: true,
    sponsored: true,
  },
  {
    id: 2,
    brand: "ROADSTER",
    title: "Solid Cotton T-shirt with Round Neck",
    image: [
      "https://picsum.photos/id/2/750/1000",
      "https://picsum.photos/id/2/750/1000",
    ],
    originalPrice: 999,
    discountedPrice: 299,
    discountPercent: 70,
    sizes: ['S', 'M', 'L', 'XL'],
    assured: true,
    sponsored: false,
  },
  {
    id: 3,
    brand: "HRX by Hrithik Roshan",
    title: "Running Shoes with Lightweight Sole",
    image: [
      "https://picsum.photos/id/3/750/1000",
      "https://picsum.photos/id/3/750/1000"
    ],
    originalPrice: 4999,
    discountedPrice: 1999,
    discountPercent: 60,
    sizes: [8, 9, 10, 11],
    assured: true,
    sponsored: true,
  },
  {
    id: 4,
    brand: "WROGN",
    title: "Slim Fit Casual Shirt with Printed Design",
    image: [
      "https://picsum.photos/id/4/750/1000",
      "https://picsum.photos/id/4/750/1000",
    ],
    originalPrice: 1599,
    discountedPrice: 599,
    discountPercent: 62,
    sizes: ['M', 'L'],
    assured: false,
    sponsored: false,
  },
  {
    id: 5,
    brand: "DENIM CO.",
    title: "Straight Fit Washed Denim Trousers",
    image: [
      "https://picsum.photos/id/5/750/1000",
      "https://picsum.photos/id/5/750/1000",
    ],
    originalPrice: 3199,
    discountedPrice: 895,
    discountPercent: 72,
    sizes: [30, 32, 34, 36],
    assured: true,
    sponsored: false,
  },
  {
    id: 6,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/6/750/1000",
      "https://picsum.photos/id/6/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 7,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/8/750/1000",
      "https://picsum.photos/id/8/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 8,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/7/750/1000",
      "https://picsum.photos/id/7/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 9,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/10/750/1000",
      "https://picsum.photos/id/10/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 10,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/11/750/1000",
      "https://picsum.photos/id/11/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 11,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/12/750/1000",
      "https://picsum.photos/id/12/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 12,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/13/750/1000",
      "https://picsum.photos/id/13/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 13,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/14/750/1000",
      "https://picsum.photos/id/14/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 14,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/15/750/1000",
      "https://picsum.photos/id/14/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 15,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/16/750/1000",
      "https://picsum.photos/id/17/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 16,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/17/750/1000",
      "https://picsum.photos/id/18/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 17,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/18/750/1000",
      "https://picsum.photos/id/19/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 18,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/19/750/1000",
      "https://picsum.photos/id/20/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 19,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/20/750/1000",
      "https://picsum.photos/id/21/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 20,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/21/750/1000",
      "https://picsum.photos/id/22/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id:21,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/22/750/1000",
      "https://picsum.photos/id/23/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 22,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/25/750/1000",
      "https://picsum.photos/id/26/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 23,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/26/750/1000",
      "https://picsum.photos/id/27/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 24,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/27/750/1000",
      "https://picsum.photos/id/28/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 25,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/28/750/1000",
      "https://picsum.photos/id/29/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 26,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/30/750/1000",
      "https://picsum.photos/id/31/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 27,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/31/750/1000",
      "https://picsum.photos/id/32/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 28,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/34/750/1000",
      "https://picsum.photos/id/35/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 29,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/35/750/1000",
      "https://picsum.photos/id/36/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 30,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/36/750/1000",
      "https://picsum.photos/id/37/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 31,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/37/750/1000",
      "https://picsum.photos/id/38/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 32,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/40/750/1000",
      "https://picsum.photos/id/41/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 33,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/41/750/1000",
      "https://picsum.photos/id/42/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 34,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/44/750/1000",
      "https://picsum.photos/id/45/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 35,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/45/750/1000",
      "https://picsum.photos/id/46/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 36,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/46/750/1000",
      "https://picsum.photos/id/47/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 37,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/47/750/1000",
      "https://picsum.photos/id/48/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 38,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/48/750/1000",
      "https://picsum.photos/id/49/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 39,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/49/750/1000",
      "https://picsum.photos/id/50/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 40,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/50/750/1000",
      "https://picsum.photos/id/51/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 41,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/51/750/1000",
      "https://picsum.photos/id/52/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 42,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/52/750/1000",
      "https://picsum.photos/id/53/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 43,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/53/750/1000",
      "https://picsum.photos/id/54/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 44,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/53/750/1000",
      "https://picsum.photos/id/54/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 45,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/54/750/1000",
      "https://picsum.photos/id/55/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 46,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/56/750/1000",
      "https://picsum.photos/id/57/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 47,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/57/750/1000",
      "https://picsum.photos/id/58/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 48,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/58/750/1000",
      "https://picsum.photos/id/59/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 49,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/59/750/1000",
      "https://picsum.photos/id/60/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 50,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/60/750/1000",
      "https://picsum.photos/id/61/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 51,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/61/750/1000",
      "https://picsum.photos/id/62/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 52,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/62/750/1000",
      "https://picsum.photos/id/63/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 53,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/63/750/1000",
      "https://picsum.photos/id/64/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 54,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/64/750/1000",
      "https://picsum.photos/id/65/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 55,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/65/750/1000",
      "https://picsum.photos/id/66/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  {
    id: 56,
    brand: "LIBAS",
    title: "Women's Ethnic Kurta Set with Dupatta",
    image: [
      "https://picsum.photos/id/66/750/1000",
      "https://picsum.photos/id/67/750/1000"
    ],
    originalPrice: 3500,
    discountedPrice: 1250,
    discountPercent: 64,
    sizes: ['XS', 'S', 'M', 'L'],
    assured: true,
    sponsored: true,
  },
  
];