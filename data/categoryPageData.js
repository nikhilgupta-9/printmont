// ─── Category Page Static Data ───────────────────────────────────────────────

export const categoryPageData = {
  id: "cloths-bags",
  name: "Cloths & Bags",
  description: "Premium printing on clothing & bags for corporate and personal gifting.",
  subcategories: [
    "Corporate Wear", "Bags", "T-Shirts", "Caps & Hats",
    "Laptop Bags", "Backpacks", "Tote Bags", "Uniforms",
    "Polo Shirts", "Hoodies", "Sling Bags", "Duffel Bags",
  ],
};

const BASE_IMAGES = [
  "https://picsum.photos/id/1/400/500",
  "https://picsum.photos/id/20/400/500",
  "https://picsum.photos/id/30/400/500",
  "https://picsum.photos/id/40/400/500",
  "https://picsum.photos/id/50/400/500",
  "https://picsum.photos/id/60/400/500",
  "https://picsum.photos/id/70/400/500",
  "https://picsum.photos/id/80/400/500",
];

const makeProduct = (id, brand, title, price, orig, disc, badge, customizable = false) => ({
  id,
  brand,
  title,
  image: [BASE_IMAGES[id % BASE_IMAGES.length], BASE_IMAGES[(id + 1) % BASE_IMAGES.length]],
  discountedPrice: price,
  originalPrice: orig,
  discountPercent: disc,
  sizes: ["S", "M", "L", "XL"],
  assured: true,
  sponsored: false,
  badge,          // "new-arrival" | "best-seller" | "special-offer" | null
  isCustomizable: customizable,
});

export const newArrivalProducts = [
  makeProduct(1,  "PRINTMONT", "Pacific Blue Over-Dyed Shirt For Men",          499,  999, 50, "new-arrival", true),
  makeProduct(2,  "XORDOX",    "Rust Brown Knitted Polo Shirt",                  549, 1200, 54, "new-arrival"),
  makeProduct(3,  "FOXIEFIT",  "Classic White Logo T-Shirt Unisex",              399,  799, 50, "new-arrival", true),
  makeProduct(4,  "BRANDX",    "Navy Corporate Polo with Chest Print",           599, 1099, 45, "new-arrival"),
  makeProduct(5,  "WROGN",     "Olive Green Oversized Hoodie",                   699, 1499, 53, "new-arrival"),
  makeProduct(6,  "ROADSTER",  "Solid Cotton Round-Neck T-Shirt",                299,  799, 63, "new-arrival"),
];

export const bestSellerProducts = [
  makeProduct(10, "PRINTMONT", "Premium Laptop Backpack 30L with USB Port",      999, 2499, 60, "best-seller"),
  makeProduct(11, "FASTRACK",  "Waterproof Office Messenger Bag",                749, 1799, 58, "best-seller"),
  makeProduct(12, "SKYBAGS",   "Structured Tote Bag with Logo Space",            599, 1299, 54, "best-seller", true),
  makeProduct(13, "WILDCRAFT", "Multi-Pocket Travel Duffel Bag",                1299, 2999, 57, "best-seller"),
  makeProduct(14, "AMERICAN",  "Canvas Sling Bag with Zip Pockets",              449,  999, 55, "best-seller"),
  makeProduct(15, "VUCH",      "Minimalist Leather Shoulder Bag",                899, 1999, 55, "best-seller"),
];

export const specialOfferProducts = [
  makeProduct(20, "PRINTMONT", "Custom Embroidered Cap – 50 MOQ",               199,  599, 67, "special-offer", true),
  makeProduct(21, "XORDOX",    "Dry-Fit Sports Polo – Corporate Pack",           349,  999, 65, "special-offer"),
  makeProduct(22, "FOXIEFIT",  "Full-Print Sublimation T-Shirt",                 279,  799, 65, "special-offer", true),
  makeProduct(23, "BRANDX",    "Eco Jute Tote Bag with Logo",                    149,  499, 70, "special-offer"),
  makeProduct(24, "ROADSTER",  "Fleece Zipper Jacket with Brand Print",          799, 1999, 60, "special-offer"),
  makeProduct(25, "WROGN",     "Washed Denim Shorts – Festival Pack",            349,  899, 61, "special-offer"),
];

// ─── Offer Zone per mobile category section ───────────────────────────────────
export const menOfferProducts  = newArrivalProducts.slice(0, 4);
export const womenOfferProducts = bestSellerProducts.slice(0, 4);
export const footwearOfferProducts = specialOfferProducts.slice(0, 4);
export const kidsOfferProducts = newArrivalProducts.slice(2, 6);

// ─── Mobile subcategory grids ─────────────────────────────────────────────────
export const mobileCategoryLinks = [
  { name: "Men's Wear",      url: "#" },
  { name: "Women's Wear",    url: "#" },
  { name: "Footwear",        url: "#" },
  { name: "Kids",            url: "#" },
  { name: "Bags",            url: "#" },
  { name: "Accessories",     url: "#" },
];

export const menSubcategories    = ["Shirts", "T-Shirts", "Polos", "Hoodies", "Caps", "Jackets"];
export const womenSubcategories  = ["Kurtas", "Tops", "Dresses", "Scarves", "Totes", "Polos"];
export const footwearSubcategories = ["Sneakers", "Loafers", "Sandals", "Boots", "Slippers", "Sports"];
export const kidsSubcategories   = ["T-Shirts", "Shorts", "Caps", "Bags", "Rainwear", "Sets"];

// ─── Customer Reviews (exactly 5 per spec) ────────────────────────────────────
export const categoryReviews = [
  { id: 1, name: "Rahul Sharma",   rating: 5, city: "Mumbai",    comment: "Absolutely premium quality! Got 200 custom T-shirts for our startup event. Everyone loved them.",     avatar: "https://i.pravatar.cc/80?img=1" },
  { id: 2, name: "Priya Mehta",    rating: 5, city: "Delhi",     comment: "The embroidery on the caps was perfect. Delivered 3 days before schedule. Will order again!",         avatar: "https://i.pravatar.cc/80?img=5" },
  { id: 3, name: "Amit Joshi",     rating: 4, city: "Pune",      comment: "Great fabric quality. The brand logo came out very crisp and vibrant. Very happy with the result.",    avatar: "https://i.pravatar.cc/80?img=3" },
  { id: 4, name: "Sunita Rao",     rating: 5, city: "Bangalore", comment: "Used Printmont for our Diwali gifting — custom bags with logo. Clients were really impressed!",       avatar: "https://i.pravatar.cc/80?img=9" },
  { id: 5, name: "Deepak Verma",   rating: 4, city: "Hyderabad", comment: "Ordered polo shirts for 100 employees. The fit and print quality exceeded our expectations.",          avatar: "https://i.pravatar.cc/80?img=7" },
];

// ─── Banner data (layoutType 1–4) ─────────────────────────────────────────────
export const heroBanners = [
  { id: 1, src: "https://picsum.photos/id/1048/1200/400", alt: "Corporate Clothing Sale" },
  { id: 2, src: "https://picsum.photos/id/1036/1200/400", alt: "Custom Bags Collection" },
];

export const bannerSection1 = { layoutType: 2, images: [
  { src: "https://picsum.photos/id/1015/600/220", alt: "Banner A" },
  { src: "https://picsum.photos/id/1016/600/220", alt: "Banner B" },
]};
export const bannerSection2 = { layoutType: 3, images: [
  { src: "https://picsum.photos/id/1020/300/180", alt: "Banner C" },
  { src: "https://picsum.photos/id/1021/300/180", alt: "Banner D" },
  { src: "https://picsum.photos/id/1024/300/180", alt: "Banner E" },
  { src: "https://picsum.photos/id/1025/300/180", alt: "Banner F" },
]};
export const bannerSection3 = { layoutType: 1, images: [
  { src: "https://picsum.photos/id/1039/1200/280", alt: "Wide Offer Banner" },
]};
export const mobileBanner1 = { layoutType: 1, images: [
  { src: "https://picsum.photos/id/1043/800/300", alt: "Mobile Banner 1" },
  { src: "https://picsum.photos/id/1044/800/300", alt: "Mobile Banner 2" },
]};
