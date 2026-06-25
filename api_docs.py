from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import cm
from reportlab.lib import colors
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, HRFlowable
from reportlab.lib.enums import TA_LEFT, TA_CENTER

OUTPUT = "D:/xampp/htdocs/printmont-admin/Printmont_API_Documentation.pdf"

doc = SimpleDocTemplate(
    OUTPUT,
    pagesize=A4,
    rightMargin=2*cm, leftMargin=2*cm,
    topMargin=2*cm, bottomMargin=2*cm
)

styles = getSampleStyleSheet()

# Custom styles
title_style = ParagraphStyle('Title', parent=styles['Title'], fontSize=22, textColor=colors.HexColor('#1a1a2e'), spaceAfter=6)
subtitle_style = ParagraphStyle('Subtitle', parent=styles['Normal'], fontSize=11, textColor=colors.HexColor('#555'), spaceAfter=20, alignment=TA_CENTER)
section_style = ParagraphStyle('Section', parent=styles['Heading1'], fontSize=14, textColor=colors.white, backColor=colors.HexColor('#16213e'), spaceAfter=8, spaceBefore=14, leftIndent=6, leading=20)
api_name_style = ParagraphStyle('ApiName', parent=styles['Heading2'], fontSize=12, textColor=colors.HexColor('#0f3460'), spaceAfter=4, spaceBefore=10)
label_style = ParagraphStyle('Label', parent=styles['Normal'], fontSize=9, textColor=colors.HexColor('#888'), spaceAfter=2)
value_style = ParagraphStyle('Value', parent=styles['Normal'], fontSize=10, textColor=colors.HexColor('#222'), spaceAfter=4)
code_style = ParagraphStyle('Code', parent=styles['Code'], fontSize=9, backColor=colors.HexColor('#f4f4f4'), textColor=colors.HexColor('#c0392b'), leftIndent=8, spaceAfter=6, borderPad=4)
note_style = ParagraphStyle('Note', parent=styles['Normal'], fontSize=9, textColor=colors.HexColor('#666'), leftIndent=8, spaceAfter=6)

BASE = "http://localhost/printmont-admin/api"

def section(title):
    return [
        Spacer(1, 0.3*cm),
        Paragraph(f"  {title}", section_style),
    ]

def api_block(name, method, endpoint, description, params=None, auth=False, notes=None):
    items = []
    items.append(Paragraph(name, api_name_style))

    method_colors = {
        'GET': colors.HexColor('#27ae60'),
        'POST': colors.HexColor('#2980b9'),
        'PUT': colors.HexColor('#e67e22'),
        'DELETE': colors.HexColor('#c0392b'),
        'MULTI': colors.HexColor('#8e44ad'),
    }
    m_color = method_colors.get(method, colors.grey)

    # Method + Endpoint row
    method_style = ParagraphStyle('Method', parent=styles['Normal'], fontSize=9, textColor=colors.white, backColor=m_color, leftIndent=4, rightIndent=4, leading=14)
    endpoint_style = ParagraphStyle('Endpoint', parent=styles['Code'], fontSize=9, textColor=colors.HexColor('#1a1a2e'), backColor=colors.HexColor('#eef'), leftIndent=8, leading=14)

    data = [[Paragraph(f" {method} ", method_style), Paragraph(f"{BASE}{endpoint}", endpoint_style)]]
    t = Table(data, colWidths=[1.5*cm, None])
    t.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('LEFTPADDING', (0,0), (-1,-1), 4),
        ('RIGHTPADDING', (0,0), (-1,-1), 4),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
        ('BOX', (0,0), (-1,-1), 0.5, colors.HexColor('#ccc')),
    ]))
    items.append(t)
    items.append(Spacer(1, 0.15*cm))

    items.append(Paragraph(description, value_style))

    if auth:
        items.append(Paragraph("Auth: Bearer Token required (Authorization header)", note_style))

    if params:
        items.append(Paragraph("Parameters:", label_style))
        param_data = [["Name", "Type", "Required", "Description"]]
        for p in params:
            param_data.append(p)
        pt = Table(param_data, colWidths=[3*cm, 2.2*cm, 2*cm, None])
        pt.setStyle(TableStyle([
            ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#16213e')),
            ('TEXTCOLOR', (0,0), (-1,0), colors.white),
            ('FONTSIZE', (0,0), (-1,-1), 8),
            ('FONTNAME', (0,0), (-1,0), 'Helvetica-Bold'),
            ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, colors.HexColor('#f0f4f8')]),
            ('GRID', (0,0), (-1,-1), 0.4, colors.HexColor('#ddd')),
            ('LEFTPADDING', (0,0), (-1,-1), 6),
            ('RIGHTPADDING', (0,0), (-1,-1), 6),
            ('TOPPADDING', (0,0), (-1,-1), 4),
            ('BOTTOMPADDING', (0,0), (-1,-1), 4),
        ]))
        items.append(pt)
        items.append(Spacer(1, 0.1*cm))

    if notes:
        for n in notes:
            items.append(Paragraph(f"* {n}", note_style))

    items.append(HRFlowable(width="100%", thickness=0.5, color=colors.HexColor('#e0e0e0'), spaceAfter=4))
    return items

story = []

# Cover
story.append(Spacer(1, 1*cm))
story.append(Paragraph("Printmont", title_style))
story.append(Paragraph("API Documentation", ParagraphStyle('T2', parent=styles['Title'], fontSize=18, textColor=colors.HexColor('#0f3460'), spaceAfter=4)))
story.append(Paragraph("Complete REST API Reference — Backend: http://localhost/printmont-admin/api/", subtitle_style))
story.append(HRFlowable(width="100%", thickness=2, color=colors.HexColor('#16213e'), spaceAfter=16))
story.append(Paragraph("This document lists all available API endpoints in the Printmont e-commerce backend, organized by module. Each entry includes the HTTP method, endpoint URL, description, and parameters.", value_style))
story.append(Spacer(1, 0.5*cm))

# ─── AUTH ───
story += section("1. Authentication")

story += api_block(
    "User Register",
    "POST", "/user-api.php?action=register",
    "Register a new customer account.",
    params=[
        ["name", "string", "Yes", "Full name of the user"],
        ["email", "string", "Yes", "Email address (unique)"],
        ["password", "string", "Yes", "Account password"],
        ["phone", "string", "No", "Phone number"],
    ]
)

story += api_block(
    "User Login",
    "POST", "/user-api.php?action=login",
    "Authenticate a user and return a Bearer token.",
    params=[
        ["email", "string", "Yes", "Registered email address"],
        ["password", "string", "Yes", "Account password"],
    ]
)

story += api_block(
    "Get User Profile",
    "GET", "/user-api.php?action=profile",
    "Retrieve the authenticated user's profile details.",
    auth=True
)

story += api_block(
    "Update User Profile",
    "POST", "/user-api.php?action=update_profile",
    "Update the authenticated user's profile information.",
    auth=True,
    params=[
        ["name", "string", "No", "Updated full name"],
        ["phone", "string", "No", "Updated phone number"],
        ["email", "string", "No", "Updated email address"],
    ]
)

story += api_block(
    "Change Password",
    "PUT", "/router.php?action=change_password",
    "Change the authenticated user's account password.",
    auth=True,
    params=[
        ["old_password", "string", "Yes", "Current password"],
        ["new_password", "string", "Yes", "New password"],
    ]
)

# ─── ADDRESS ───
story += section("2. User Addresses")

story += api_block("Get Addresses", "GET", "/user-api.php?action=get_addresses", "Get all saved delivery addresses for the authenticated user.", auth=True)
story += api_block("Add Address", "POST", "/user-api.php?action=add_address", "Add a new delivery address.",
    auth=True,
    params=[
        ["full_name", "string", "Yes", "Recipient full name"],
        ["phone", "string", "Yes", "Contact phone"],
        ["address_line1", "string", "Yes", "Street address"],
        ["city", "string", "Yes", "City"],
        ["state", "string", "Yes", "State/Province"],
        ["pincode", "string", "Yes", "Postal code"],
    ]
)
story += api_block("Update Address", "POST", "/user-api.php?action=update_address", "Update an existing delivery address.", auth=True,
    params=[["address_id", "integer", "Yes", "ID of address to update"]])
story += api_block("Delete Address", "POST", "/user-api.php?action=delete_address", "Remove a delivery address.", auth=True,
    params=[["address_id", "integer", "Yes", "ID of address to delete"]])
story += api_block("Set Default Address", "POST", "/user-api.php?action=set_default_address", "Mark an address as the default for checkout.", auth=True,
    params=[["address_id", "integer", "Yes", "ID of address to set as default"]])

# ─── ORDERS ───
story += section("3. Orders")

story += api_block("Get All Orders", "GET", "/user-api.php?action=get_orders", "Retrieve list of all orders (admin or filtered by user).",
    params=[["user_id", "integer", "No", "Filter orders by user ID"]])
story += api_block("Get Order by ID", "GET", "/user-api.php?action=get_order&id={id}", "Retrieve a single order's full details.",
    params=[["id", "integer", "Yes", "Order ID"]])
story += api_block("Create Order", "POST", "/user-api.php?action=create_order", "Place a new order.",
    params=[
        ["user_id", "integer", "Yes", "Customer user ID"],
        ["items", "array", "Yes", "Array of {product_id, quantity, price}"],
        ["address_id", "integer", "Yes", "Delivery address ID"],
        ["payment_method", "string", "Yes", "e.g. cod, online"],
    ]
)
story += api_block("Get Customer Orders", "GET", "/router.php?action=get_customer_orders&user_id={id}", "Get all orders for a specific customer.",
    params=[["user_id", "integer", "Yes", "Customer user ID"]])
story += api_block("Update Order Status", "PUT", "/router.php?action=update_order_status&id={id}", "Change an order's fulfillment status.",
    params=[
        ["status", "string", "Yes", "New status (pending/processing/delivered/etc.)"],
        ["notes", "string", "No", "Optional admin notes"],
    ]
)
story += api_block("Update Payment Status", "PUT", "/router.php?action=update_payment_status&id={id}", "Update the payment status for an order.",
    params=[["payment_status", "string", "Yes", "paid / unpaid / refunded"]])
story += api_block("Dashboard Stats", "GET", "/router.php?action=get_dashboard_stats", "Get summary stats: total orders, revenue, customers, etc.")

# ─── PRODUCTS ───
story += section("4. Products")

story += api_block("Get All Products", "GET", "/product-api.php", "Returns all active products with images.")
story += api_block("Get Product by ID", "GET", "/product-api.php?id={id}", "Retrieve a single product with full image gallery.",
    params=[["id", "integer", "Yes", "Product ID"]])
story += api_block("Get Deactivated Products", "GET", "/product-api.php?status=deactive", "List all deactivated/hidden products.")
story += api_block("Related Products", "GET", "/related_products.php?id={id}", "Get products in the same category as the given product.",
    params=[["id", "integer", "Yes", "Product ID to find related items for"]])
story += api_block("Search Products", "GET", "/search-api.php",
    "Search products by keyword with optional filters.",
    params=[
        ["q", "string", "Yes", "Search keyword"],
        ["category", "string", "No", "Filter by category slug"],
        ["min_price", "number", "No", "Minimum price filter"],
        ["max_price", "number", "No", "Maximum price filter"],
        ["limit", "integer", "No", "Max results (default: 10)"],
    ]
)

# ─── HOME PRODUCT SECTIONS ───
story += section("5. Home Page Product Sections")

story += api_block("Home Products", "GET", "/home-product-api.php?action={action}",
    "Fetch curated product lists for homepage sections. Supported actions are listed below.",
    params=[
        ["top_selection", "string", "—", "Top selection products"],
        ["top_rated", "string", "—", "Top rated products"],
        ["top_deal", "string", "—", "Top deals grouped by category"],
        ["discount_for_you", "string", "—", "Discounted products"],
        ["recently_viewed", "string", "—", "Recently viewed products"],
        ["categories", "string", "—", "All product categories"],
        ["men_clothing", "string", "—", "Products in men-clothing category"],
        ["women_clothing", "string", "—", "Products in women-clothing category"],
        ["kids", "string", "—", "Products in kids category"],
        ["mobile", "string", "—", "Mobile products"],
        ["laptop", "string", "—", "Laptop products"],
        ["buds", "string", "—", "Earbuds/headphones"],
        ["home_decor", "string", "—", "Home decor products"],
        ["table_dinnerware", "string", "—", "Table and dinnerware"],
        ["women_outfit", "string", "—", "Women outfit products"],
        ["men", "string", "—", "Men products"],
        ["women", "string", "—", "Women products"],
    ]
)

story += api_block("Top Rated Products", "GET", "/top-rated-products.php", "Get list of top-rated products.")
story += api_block("Set Top Rated Status", "POST", "/top-rated-products.php", "Mark/unmark a product as top-rated.",
    params=[
        ["product_id", "integer", "Yes", "Product ID"],
        ["status", "integer", "Yes", "1 = top-rated, 0 = remove"],
    ]
)
story += api_block("Top Selection Products", "GET", "/top-selection-products.php", "Get list of top-selection products.")
story += api_block("Set Top Selection Status", "POST", "/top-selection-products.php", "Mark/unmark a product as top-selection.",
    params=[
        ["product_id", "integer", "Yes", "Product ID"],
        ["status", "integer", "Yes", "1 = top-selection, 0 = remove"],
    ]
)
story += api_block("Bestseller Products", "GET", "/bestseller-products.php", "Get list of bestseller products.")
story += api_block("Set Bestseller Status", "POST", "/bestseller-products.php", "Mark/unmark a product as bestseller.",
    params=[
        ["product_id", "integer", "Yes", "Product ID"],
        ["status", "integer", "Yes", "1 = bestseller, 0 = remove"],
    ]
)

# ─── CATEGORIES ───
story += section("6. Categories")

story += api_block("Get All Categories", "GET", "/category-api.php", "Retrieve all product categories with hierarchy (main > sub > sub-sub).")

# ─── CART ───
story += section("7. Cart")

story += api_block("Get Cart", "GET", "/cart-api.php", "Get all items in the current user's cart.", auth=True)
story += api_block("Add to Cart", "POST", "/cart-api.php", "Add a product to the cart.",
    auth=True,
    params=[
        ["product_id", "integer", "Yes", "Product to add"],
        ["quantity", "integer", "No", "Quantity (default: 1)"],
    ]
)
story += api_block("Update Cart Item", "PUT", "/cart-api.php", "Update quantity of an item in the cart.",
    auth=True,
    params=[
        ["item_id", "integer", "Yes", "Cart item ID"],
        ["quantity", "integer", "Yes", "New quantity"],
    ]
)
story += api_block("Remove from Cart", "DELETE", "/cart-api.php?item_id={id}", "Remove an item from the cart.",
    auth=True,
    params=[["item_id", "integer", "Yes", "Cart item ID to remove"]]
)

# ─── WISHLIST ───
story += section("8. Wishlist")

story += api_block("Get Wishlist", "GET", "/wishlist-api.php", "Get all products in the user's wishlist.", auth=True)
story += api_block("Add to Wishlist", "POST", "/wishlist-api.php", "Add a product to the wishlist.",
    auth=True,
    params=[["product_id", "integer", "Yes", "Product ID to add"]]
)
story += api_block("Remove from Wishlist", "DELETE", "/wishlist-api.php?product_id={id}", "Remove a product from the wishlist.",
    auth=True,
    params=[["product_id", "integer", "Yes", "Product ID to remove"]]
)

# ─── BANNERS ───
story += section("9. Banners")

story += api_block("Homepage Banners", "GET", "/banner_api.php", "Get all active banners for the homepage carousel.")
story += api_block("Blog Page Banner", "GET", "/blog-page-banner-api.php", "Get banner image for the blog listing page.")

# ─── BLOG ───
story += section("10. Blog")

story += api_block("Get All Blog Posts", "GET", "/blog-api.php/posts", "Get all published blog posts.")
story += api_block("Get Blog Post by ID or Slug", "GET", "/blog-api.php/posts/{id|slug}", "Get a single published post. Increments view count.",
    params=[["id or slug", "string", "Yes", "Numeric ID or URL slug of the post"]]
)
story += api_block("Get Blog Post by Slug", "GET", "/blog-single.php?slug={slug}", "Alternative endpoint to fetch a single post by slug.",
    params=[["slug", "string", "Yes", "URL slug of the blog post"]]
)
story += api_block("Get Blog Categories", "GET", "/blog-api.php/categories", "Get all active blog categories.")
story += api_block("Get Category with Posts", "GET", "/blog-api.php/categories/{id}", "Get a single blog category and its associated posts.",
    params=[["id", "integer", "Yes", "Blog category ID"]]
)
story += api_block("Recent Blog Posts", "GET", "/blog-api.php/recent", "Get the most recent blog posts.",
    params=[["limit", "integer", "No", "Number of posts to return (default: 5)"]]
)
story += api_block("Popular Blog Posts", "GET", "/blog-api.php/popular", "Get the most-viewed blog posts.",
    params=[["limit", "integer", "No", "Number of posts to return (default: 5)"]]
)

# ─── CAREERS ───
story += section("11. Careers")

story += api_block("Get All Job Listings", "GET", "/career-get-api.php",
    "Get all active job openings with optional filters and pagination.",
    params=[
        ["active_only", "integer", "No", "1 = active only (default), 0 = all"],
        ["department", "string", "No", "Filter by department name"],
        ["job_type", "string", "No", "Filter by job type (full-time, part-time, etc.)"],
        ["featured", "integer", "No", "1 = featured only"],
        ["limit", "integer", "No", "Max results"],
        ["offset", "integer", "No", "Pagination offset (default: 0)"],
    ]
)
story += api_block("Get Job by ID", "GET", "/career-get-api.php?id={id}", "Get a single job listing. Increments view count.",
    params=[["id", "integer", "Yes", "Career/job listing ID"]]
)
story += api_block("Submit Job Application", "POST", "/career-post-api.php",
    "Submit a job application with resume upload (multipart/form-data).",
    params=[
        ["career_id", "integer", "Yes", "ID of the job being applied for"],
        ["full_name", "string", "Yes", "Applicant's full name"],
        ["email", "string", "Yes", "Applicant's email address"],
        ["phone", "string", "Yes", "Applicant's phone number"],
        ["resume", "file", "Yes", "Resume file upload (PDF/DOC)"],
        ["cover_letter", "string", "No", "Cover letter text"],
        ["experience", "string", "No", "Work experience summary"],
        ["education", "string", "No", "Education background"],
        ["skills", "string", "No", "Key skills"],
        ["linkedin_url", "string", "No", "LinkedIn profile URL"],
        ["portfolio_url", "string", "No", "Portfolio/website URL"],
    ]
)

# ─── CONTENT / CMS ───
story += section("12. Content & CMS")

story += api_block("About Us", "GET", "/about-api.php", "Get all About Us page sections (team, mission, vision, etc.).")
story += api_block("Contact Information", "GET", "/contact-api.php", "Get the store's contact details (email, phone, address, social links).")
story += api_block("FAQ List", "GET", "/faq-api.php", "Get all FAQs with optional active/inactive filter.",
    params=[["is_active", "integer", "No", "1 = active only, 0 = inactive only, omit = all"]]
)
story += api_block("Help Center", "GET", "/help-center-api.php",
    "Get all FAQ categories with their associated FAQs nested inside.",
    params=[
        ["active_only", "boolean", "No", "true/false (default: true)"],
        ["include_empty", "boolean", "No", "Include categories with no FAQs (default: true)"],
        ["type", "string", "No", "Filter categories by type"],
    ]
)
story += api_block("Policies", "GET", "/policies-api.php", "Get all policy pages (Privacy Policy, Return Policy, Terms, etc.).")
story += api_block("Logo", "GET", "/logo-api.php", "Get all logos.",
    params=[
        ["favicon", "flag", "No", "Pass ?favicon to get favicon only"],
        ["desktop_logo", "flag", "No", "Pass ?desktop_logo to get website logo"],
        ["id", "integer", "No", "Get specific logo by ID"],
        ["type", "string", "No", "Get logos by type"],
        ["active_type", "string", "No", "Get the active logo for a given type"],
    ]
)

# Footer
story.append(Spacer(1, 1*cm))
story.append(HRFlowable(width="100%", thickness=1, color=colors.HexColor('#16213e')))
story.append(Spacer(1, 0.3*cm))
story.append(Paragraph("Printmont API Documentation  |  Generated 2026  |  http://localhost/printmont-admin/", subtitle_style))

doc.build(story)
print(f"PDF saved to: {OUTPUT}")
