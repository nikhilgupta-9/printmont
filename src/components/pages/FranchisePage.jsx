import React, { useState, useEffect } from "react";
import { Container, Row, Col, Accordion, Modal, Spinner } from "react-bootstrap";
import { Link } from "react-router-dom";
import {
  FaStore,
  FaChartLine,
  FaTshirt,
  FaTruck,
  FaHandshake,
  FaTools,
  FaCheckCircle,
  FaPhoneAlt,
  FaEnvelope,
  FaArrowRight,
  FaUsers,
  FaAward,
  FaMapMarkerAlt,
  FaHeadset,
  FaStar,
  FaShieldAlt,
  FaLaptopCode,
  FaBoxes,
  FaGraduationCap,
  FaDownload,
  FaBullhorn,
  FaQuoteLeft,
  FaCheck,
  FaCity,
  FaRegLightbulb,
  FaGem
} from "react-icons/fa";
import { toast } from "react-hot-toast";
import Slider from "react-slick";
import "slick-carousel/slick/slick.css";
import "slick-carousel/slick/slick-theme.css";
import { updatePageSEO } from "../../utils/seo";
import { API_ENDPOINTS, ASSET_URL, assetUrl } from "../../config/apiEndpoints";
import usePageSections from "./usePageSections";
import useHomeBanners from "../home/hooks/useHomeBanners";
import ResponsiveHeroCarousel from "../home/banners/ResponsiveHeroCarousel";
import "./FranchisePage.css";

const FranchisePage = () => {
  useEffect(() => {
    window.scrollTo(0, 0);
    updatePageSEO({
      title: "Printmont Franchise Opportunity | Own a Custom Printing & Gifting Studio",
      description: "Partner with India's fastest growing customized printing & personalized gifting brand. High gross margins (35%-50%), turnkey store setup, omnichannel online order routing, and complete machinery support.",
      keywords: "printmont franchise, custom printing franchise india, gift shop franchise, personalized gifts store franchise, retail franchise india 2026",
      type: "website"
    });
  }, []);

  // Mobile compact active states
  const [activeModelTab, setActiveModelTab] = useState("");
  const [showAllProducts, setShowAllProducts] = useState(false);

  // Form State
  const [formData, setFormData] = useState({
    full_name: "",
    contact_number: "",
    email_id: "",
    city: "",
    state: "",
    model: "STUDIO & EXPERIENCE CENTER",
    budget: "₹8 Lakhs – ₹14 Lakhs",
    space: "350 - 700 sq.ft.",
    message: ""
  });

  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitSuccess, setSubmitSuccess] = useState(false);

  // Modals state
  const [showCallbackModal, setShowCallbackModal] = useState(false);
  const [showBrochureModal, setShowBrochureModal] = useState(false);

  const [callbackData, setCallbackData] = useState({
    full_name: "",
    contact_number: "",
    city: ""
  });
  const [isCallbackSubmitting, setIsCallbackSubmitting] = useState(false);

  const [brochureData, setBrochureData] = useState({
    full_name: "",
    email_id: "",
    contact_number: ""
  });
  const [isBrochureSubmitting, setIsBrochureSubmitting] = useState(false);

  // Admin-managed content (Admin → Website Pages → Franchise Page) and banners (Admin → Page Banners)
  const { many, one } = usePageSections("franchise");
  const { banners: topBanners } = useHomeBanners(`${API_ENDPOINTS.BANNERS}?section=franchise_top_banner`);
  const { banners: midBanners } = useHomeBanners(`${API_ENDPOINTS.BANNERS}?section=franchise_mid_banner`);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleModelSelect = (modelName, budgetVal, spaceVal) => {
    setFormData((prev) => ({
      ...prev,
      model: modelName,
      budget: budgetVal,
      space: spaceVal
    }));
    const formElement = document.getElementById("franchiseQueryFormContainer");
    if (formElement) {
      formElement.scrollIntoView({ behavior: "smooth" });
    }
  };

  const scrollToForm = () => {
    const formElement = document.getElementById("franchiseQueryFormContainer");
    if (formElement) {
      formElement.scrollIntoView({ behavior: "smooth" });
    }
  };

  // Submit main inquiry
  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!formData.full_name.trim() || !formData.contact_number.trim() || !formData.email_id.trim()) {
      toast.error("Please fill in your name, contact number, and email address.");
      return;
    }

    try {
      setIsSubmitting(true);
      const payload = {
        full_name: formData.full_name.trim(),
        contact_number: formData.contact_number.trim(),
        email_id: formData.email_id.trim(),
        company_name: `${formData.city ? formData.city + ", " : ""}${formData.state || "India"} (Space: ${formData.space})`,
        budget: formData.budget,
        product_requirement: `Franchise Model: ${formData.model}. Space: ${formData.space}. Notes: ${formData.message || "Interested in opening Printmont franchise"}`,
        source_page: "franchise",
        device_type: window.innerWidth < 992 ? "mobile" : "desktop"
      };

      const endpoint = API_ENDPOINTS.LEADS_SUBMIT || API_ENDPOINTS.LEADS || "http://localhost/printmont/printmont-backend/api/leads-api.php";
      const res = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      if (data && data.success) {
        setSubmitSuccess(true);
        toast.success("Thank you! Your franchise inquiry has been submitted successfully.");
      } else {
        toast.error(data.message || "Failed to submit inquiry. Please try again.");
      }
    } catch (err) {
      console.error("Franchise inquiry submit error:", err);
      toast.error("Could not send your inquiry. Please check your connection and try again.");
    } finally {
      setIsSubmitting(false);
    }
  };

  // Submit quick callback modal
  const handleCallbackSubmit = async (e) => {
    e.preventDefault();
    if (!callbackData.full_name.trim() || !callbackData.contact_number.trim()) {
      toast.error("Please enter your name and contact number.");
      return;
    }

    try {
      setIsCallbackSubmitting(true);
      const payload = {
        full_name: callbackData.full_name.trim(),
        contact_number: callbackData.contact_number.trim(),
        email_id: "callback-request@printmont.com",
        company_name: callbackData.city ? `City: ${callbackData.city}` : "India",
        budget: "Quick Callback Request",
        product_requirement: "Prospect requested an instant 30-second franchise callback.",
        source_page: "franchise-callback-modal",
        device_type: window.innerWidth < 992 ? "mobile" : "desktop"
      };

      const endpoint = API_ENDPOINTS.LEADS_SUBMIT || API_ENDPOINTS.LEADS || "http://localhost/printmont/printmont-backend/api/leads-api.php";
      const res = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!data?.success) {
        toast.error(data?.message || "Could not submit your request. Please try again.");
        return;
      }

      toast.success("Callback request submitted! Our franchise expert will call you shortly.");
      setShowCallbackModal(false);
      setCallbackData({ full_name: "", contact_number: "", city: "" });
    } catch {
      toast.error("Could not submit your request. Please check your connection and try again.");
    } finally {
      setIsCallbackSubmitting(false);
    }
  };

  // Submit brochure download modal
  const handleBrochureSubmit = async (e) => {
    e.preventDefault();
    if (!brochureData.full_name.trim() || !brochureData.email_id.trim() || !brochureData.contact_number.trim()) {
      toast.error("Please fill in your name, email, and contact number.");
      return;
    }

    try {
      setIsBrochureSubmitting(true);
      const payload = {
        full_name: brochureData.full_name.trim(),
        contact_number: brochureData.contact_number.trim(),
        email_id: brochureData.email_id.trim(),
        company_name: "Brochure Prospect",
        budget: "Prospectus Download",
        product_requirement: "Prospect downloaded Printmont Official Franchise Prospectus 2026.",
        source_page: "franchise-brochure-modal",
        device_type: window.innerWidth < 992 ? "mobile" : "desktop"
      };

      const endpoint = API_ENDPOINTS.LEADS_SUBMIT || API_ENDPOINTS.LEADS || "http://localhost/printmont/printmont-backend/api/leads-api.php";
      const res = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!data?.success) {
        toast.error(data?.message || "Could not submit your request. Please try again.");
        return;
      }

      toast.success("Thank you! The Printmont Franchise Prospectus 2026 has been sent to your email.");
      setShowBrochureModal(false);
      setBrochureData({ full_name: "", email_id: "", contact_number: "" });
    } catch {
      toast.error("Could not submit your request. Please check your connection and try again.");
    } finally {
      setIsBrochureSubmitting(false);
    }
  };

  // 6 Printmont Pillars (Winni 6-Pillars layout)
  const defaultPillars = [
    {
      icon: <FaAward />,
      title: "Strong Brand",
      desc: "Recognized and trusted by thousands across India"
    },
    {
      icon: <FaTshirt />,
      title: "Wide Product Range",
      desc: "350+ SKUs: Custom apparel, mugs, frames, neon signs & hampers"
    },
    {
      icon: <FaTruck />,
      title: "Omnichannel Model",
      desc: "Strong Online + Offline Demands with local order routing"
    },
    {
      icon: <FaChartLine />,
      title: "Profitable Business Model",
      desc: "Low Risk, High Return with 35% to 50% profit margins"
    },
    {
      icon: <FaBullhorn />,
      title: "Marketing Support",
      desc: "Pan-India digital campaigns, local store SEO & festive blitz"
    },
    {
      icon: <FaHeadset />,
      title: "End-to-End Support",
      desc: "From 3D store fit-out and machinery setup to daily operations"
    }
  ];

  // 3 Franchise Models (Winni Models Grid Specs)
  const defaultModels = [
    {
      id: "kiosk",
      shortName: "Express Kiosk",
      name: "QSR / KIOSK MODEL",
      tag: "Low Investment",
      popular: false,
      headerClass: "qsr-header",
      cardClass: "qsr-card",
      img: "/franchise/model-kiosk.jpg",
      area: "100 - 250 sq.ft.",
      investment: "₹ 4 - 7 Lakh* + GST",
      profit: "Upto ₹ 1.2 Lakh*",
      roi: "6 - 9 Months",
      idealFor: "Heavy Footfall Areas, Near Colleges, Metro Stations & Shopping Malls",
      features: [
        "Instant mug, phone cover & keychain printing",
        "Personalized keychains, acrylic desk frames & quick gifts",
        "Compact footprint with high walk-in impulse sales",
        "Minimal staff requirement (1-2 members)",
        "Turnkey setup with certified compact machinery"
      ]
    },
    {
      id: "studio",
      shortName: "Studio Center",
      name: "STUDIO & EXPERIENCE CENTER",
      tag: "Most Popular",
      popular: true,
      headerClass: "studio-header",
      cardClass: "studio-card",
      img: "/franchise/model-studio.jpg",
      area: "350 - 700 sq.ft.",
      investment: "₹ 8 - 14 Lakh* + GST",
      profit: "Upto ₹ 2.5 Lakh*",
      roi: "9 - 14 Months",
      idealFor: "Market Places, Commercial Areas & Prime High Streets",
      features: [
        "Live customized apparel lounge (T-Shirts, Hoodies, Caps)",
        "On-site customer digital design & touch preview stations",
        "Automatic routing of local Printmont.com online orders",
        "Acrylic photo frames, laser-engraved gifts & hampers",
        "Corporate merchandising & consultation desk",
        "Integrated POS, ERP billing & digital catalog system"
      ]
    },
    {
      id: "hub",
      shortName: "Regional Hub",
      name: "REGIONAL PRODUCTION HUB",
      tag: "Master Partner",
      popular: false,
      headerClass: "hub-header",
      cardClass: "hub-card",
      img: "/franchise/model-hub.jpg",
      area: "1000 - 2000 sq.ft.",
      investment: "₹ 20 - 35 Lakh* + GST",
      profit: "Upto ₹ 5.5 Lakh*",
      roi: "14 - 18 Months",
      idealFor: "Main Roads, Commercial Corridors & District Hubs",
      features: [
        "Large-format industrial digital printing setup",
        "Territory-wide B2B corporate fulfillment rights",
        "Master inventory hub supplying district kiosk partners",
        "High-volume institutional client contracts",
        "Exclusive territory protection with maximum yield"
      ]
    }
  ];

  // Journey of Growth Timeline
  const defaultMilestones = [
    {
      year: "2019",
      title: "Brand Inception",
      desc: "Founded in New Delhi with custom apparel and precision direct-to-garment digital printing."
    },
    {
      year: "2021",
      title: "50K+ Milestone",
      desc: "Crossed 50,000+ orders nationwide. Expanded catalogue to personalized mugs, canvas & acrylic photo gifts."
    },
    {
      year: "2023",
      title: "Central Automated Hub",
      desc: "Commissioned 15,000 sq.ft state-of-the-art production facility for factory-direct corporate merchandise."
    },
    {
      year: "2024",
      title: "Omnichannel Model",
      desc: "Launched proprietary cloud order-routing engine, connecting physical studios with printmont.com orders."
    },
    {
      year: "2026",
      title: "100+ Stores Nationwide",
      desc: "Pan-India retail franchise network across 50+ cities, delivering high margins to proud franchise partners."
    }
  ];

  // Recent Store Openings
  const defaultStores = [
    {
      city: "New Delhi",
      name: "South Extension Flagship Studio",
      img: "/franchise/store-delhi.jpg",
      tag: "Experience Studio"
    },
    {
      city: "Mumbai",
      name: "Bandra West Custom Lounge",
      img: "/franchise/store-mumbai.jpg",
      tag: "Flagship Store"
    },
    {
      city: "Bengaluru",
      name: "Indiranagar 100ft Rd Studio",
      img: "/franchise/store-bangalore.jpg",
      tag: "Experience Center"
    }
  ];

  // Printmont 6-Step How it Works (Winni 6 Steps)
  const defaultSteps = [
    {
      step: "1",
      title: "Apply",
      desc: "Share your details and preferred city"
    },
    {
      step: "2",
      title: "Discussion",
      desc: "We connect & understand your business plan"
    },
    {
      step: "3",
      title: "Location Review",
      desc: "We evaluate & help you finalize prime spot"
    },
    {
      step: "4",
      title: "Agreement",
      desc: "Sign franchise agreement with territory exclusivity"
    },
    {
      step: "5",
      title: "Store Setup",
      desc: "We help you with 3D design & machine installation"
    },
    {
      step: "6",
      title: "Launch",
      desc: "Start your business with marketing ads & web order routing"
    }
  ];

  // Beyond Store Launch Support (Winni 5 Pillars)
  const defaultSupport = [
    {
      icon: <FaUsers />,
      title: "1:1 Relationship Manager",
      desc: "Get a dedicated business manager to guide and support you at every stage of growth."
    },
    {
      icon: <FaBullhorn />,
      title: "Digital Marketing Support",
      desc: "Drive walk-in customers with expert digital marketing, Meta ads, and festive campaigns."
    },
    {
      icon: <FaBoxes />,
      title: "Continuous New Products",
      desc: "Stay ahead with regular new apparel blanks, trendy gift collections, and festive items."
    },
    {
      icon: <FaGraduationCap />,
      title: "Manpower & Training Support",
      desc: "Hands-on assistance with hiring, machine operator certification, and customer service SOPs."
    },
    {
      icon: <FaStore />,
      title: "Offline Media Support",
      desc: "Boost local visibility with premium shopfront branding, acrylic displays, and standees."
    }
  ];

  // Products Ecosystem List (21 Items)
  const defaultEcosystem = [
    { name: "Custom T-Shirts", icon: "👕" },
    { name: "Hoodies & Jackets", icon: "🧥" },
    { name: "Ceramic Coffee Mugs", icon: "☕" },
    { name: "Magic Color Mugs", icon: "✨" },
    { name: "3D Illusion Lamps", icon: "💡" },
    { name: "Custom Neon Signs", icon: "⚡" },
    { name: "Framed Canvas Art", icon: "🖼️" },
    { name: "Engraved Wooden Plaques", icon: "🪵" },
    { name: "Corporate Gift Kits", icon: "🎁" },
    { name: "Insulated Steel Sippers", icon: "🥤" },
    { name: "Custom Phone Covers", icon: "📱" },
    { name: "Pop Sockets & Grips", icon: "🔘" },
    { name: "Badges & ID Lanyards", icon: "🏷️" },
    { name: "Canvas Tote Bags", icon: "🛍️" },
    { name: "Personalized Diaries", icon: "📓" },
    { name: "Laser-Engraved Pens", icon: "✒️" },
    { name: "Acrylic Desk Clocks", icon: "⏰" },
    { name: "Metal Keychains", icon: "🔑" },
    { name: "Custom Water Bottles", icon: "🍶" },
    { name: "Trophies & Mementos", icon: "🏆" },
    { name: "Luxury Gift Boxes", icon: "📦" }
  ];

  // Franchisee Stories / Testimonials (Expanded for smooth carousel)
  const defaultStories = [
    {
      quote: "Opening a Printmont store was our best business decision. The omnichannel web order routing brought customers from day one, and we achieved operational break-even within 45 days!",
      author: "Amit Verma",
      role: "Franchise Partner",
      city: "South Extension, New Delhi",
      revenue: "₹3.8 Lakh / Month",
      rating: 5
    },
    {
      quote: "The hands-on machinery training and turnkey setup support is incredible. Our store caters to college students for instant custom gifts and local IT companies for bulk uniforms.",
      author: "Pooja Kulkarni",
      role: "Studio Owner",
      city: "FC Road, Pune",
      revenue: "₹4.2 Lakh / Month",
      rating: 5
    },
    {
      quote: "Direct factory pricing on apparel blanks and mugs allows us to maintain 45%+ gross margins. The Printmont team's tech support and territory protection makes it a winning model.",
      author: "Karthik Rao",
      role: "Master Hub Partner",
      city: "Indiranagar, Bengaluru",
      revenue: "₹5.6 Lakh / Month",
      rating: 5
    },
    {
      quote: "The live sublimation printing station attracts college students and corporate clients daily. We recovered our initial machinery setup costs within 8 months!",
      author: "Vikramaditya Shekhawat",
      role: "Studio Partner",
      city: "MI Road, Jaipur",
      revenue: "₹3.4 Lakh / Month",
      rating: 5
    },
    {
      quote: "Even with a compact 200 sq.ft kiosk, we process 40+ personalized gifts daily. The Printmont central supply chain delivers blanks super fast with consistent quality.",
      author: "Sneha Patel",
      role: "Express Kiosk Owner",
      city: "SG Highway, Ahmedabad",
      revenue: "₹2.9 Lakh / Month",
      rating: 5
    }
  ];

  // Story carousel: slides per view follows the screen width (3 desktop, 2 tablet, 1 mobile)
  const getStorySlides = () => (window.innerWidth < 768 ? 1 : window.innerWidth < 992 ? 2 : 3);
  const [storySlides, setStorySlides] = useState(getStorySlides);
  useEffect(() => {
    const onResize = () => setStorySlides(getStorySlides());
    window.addEventListener("resize", onResize);
    return () => window.removeEventListener("resize", onResize);
  }, []);

  const storySliderSettings = {
    dots: true,
    infinite: true,
    speed: 500,
    slidesToShow: storySlides,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 4500,
    pauseOnHover: true,
    arrows: false
  };

  // FAQs
  const defaultFaqs = [
    {
      q: "What is the minimum investment required to open a Printmont franchise?",
      a: "Our franchise investment models start at ₹4 Lakhs to ₹7 Lakhs for a QSR / Express Kiosk (100–250 sq.ft) and ₹8 to ₹14 Lakhs for a standard Studio Experience Center (350–700 sq.ft). This includes 3D interior design guidance, certified machinery, initial blank inventory, POS software setup, and comprehensive staff training."
    },
    {
      q: "Do I need prior technical experience in printing or gifting to run this business?",
      a: "No prior technical experience in printing is needed! Printmont provides complete operational, machinery, and customer management training. Modern digital print equipment is automated, user-friendly, and simple to operate after our 14-day hands-on training program."
    },
    {
      q: "How does Printmont's Omnichannel Online Order Routing benefit my store?",
      a: "Unlike traditional franchises where online e-commerce competes with physical stores, Printmont actively routes online orders placed on Printmont.com from your assigned territory directly to your store for local fulfillment and pickup, providing an immediate recurring revenue stream from day one."
    },
    {
      q: "What is the expected ROI and break-even timeline?",
      a: "Most Printmont franchise stores achieve monthly operational break-even within the first 45–60 days and full return on capital investment (ROI) within 9 to 14 months, driven by healthy 35% to 50% gross profit margins across personalized merchandise."
    },
    {
      q: "Does Printmont supply the printing machines, blanks, and raw consumables?",
      a: "Yes! Printmont manages end-to-end supply chain logistics. All certified industrial heat presses, sublimation printers, apparel blanks, ceramic mugs, photo frames, and branded gift boxes are supplied directly from our central warehouses at factory wholesale rates."
    },
    {
      q: "Can my franchise store handle large corporate bulk orders?",
      a: "Absolutely! While retail customers enjoy instant customized gifts in-store, franchise owners are encouraged to secure corporate and institutional orders in their city. For very large runs, our centralized mega factory manufactures the products on your behalf while you earn profit margins."
    }
  ];

  const splitParts = (value, count) => {
    const parts = String(value || "").split("|");
    return Array.from({ length: count }, (_, i) => (parts[i] || "").trim());
  };

  const resolveImg = (img) => {
    if (!img) return "";
    if (/^(https?:)?\/\//.test(img) || img.startsWith("/")) return img;
    return assetUrl(img);
  };

  const MODEL_STYLES = [
    { headerClass: "qsr-header", cardClass: "qsr-card" },
    { headerClass: "studio-header", cardClass: "studio-card" },
    { headerClass: "hub-header", cardClass: "hub-card" }
  ];

  const cmsPillars = many("pillar");
  const whyPartnerPillars = cmsPillars.length
    ? cmsPillars.map((r, i) => ({ icon: defaultPillars[i % defaultPillars.length].icon, title: r.title, desc: r.content }))
    : defaultPillars;

  const cmsModels = many("model");
  const franchiseModels = cmsModels.length
    ? cmsModels.map((r, i) => {
        const [tag, popular, area, investment, profit, roi] = splitParts(r.extra, 6);
        const [ideal, ...rest] = String(r.content || "").split("|");
        const style = MODEL_STYLES[i % MODEL_STYLES.length];
        return {
          id: `model-${r.id}`,
          shortName: defaultModels[i]?.shortName || r.title,
          name: r.title,
          tag,
          popular: popular.toLowerCase() === "yes",
          headerClass: style.headerClass,
          cardClass: style.cardClass,
          img: resolveImg(r.image_path) || defaultModels[i % defaultModels.length].img,
          area,
          investment,
          profit,
          roi,
          idealFor: (ideal || "").trim(),
          features: rest.join("|").split("\n").map((f) => f.trim()).filter(Boolean)
        };
      })
    : defaultModels;
  const currentModel = franchiseModels.find((m) => m.id === activeModelTab) || franchiseModels.find((m) => m.popular) || franchiseModels[0];

  const cmsMilestones = many("milestone");
  const journeyMilestones = cmsMilestones.length
    ? cmsMilestones.map((r) => ({ year: r.extra, title: r.title, desc: r.content }))
    : defaultMilestones;

  const cmsStores = many("store");
  const recentStores = cmsStores.length
    ? cmsStores.map((r, i) => ({ city: r.title, name: r.content, tag: r.extra, img: resolveImg(r.image_path) || defaultStores[i % defaultStores.length].img }))
    : defaultStores;

  const cmsSteps = many("step");
  const howItWorksSteps = cmsSteps.length
    ? cmsSteps.map((r, i) => ({ step: String(i + 1), title: r.title, desc: r.content }))
    : defaultSteps;

  const cmsSupport = many("support");
  const beyondLaunchSupport = cmsSupport.length
    ? cmsSupport.map((r, i) => ({ icon: defaultSupport[i % defaultSupport.length].icon, title: r.title, desc: r.content }))
    : defaultSupport;

  const cmsEcosystem = many("ecosystem");
  const productsEcosystem = cmsEcosystem.length
    ? cmsEcosystem.map((r) => ({ name: r.title, icon: r.extra, img: resolveImg(r.image_path) }))
    : defaultEcosystem;

  const cmsStories = many("story");
  const franchiseeStories = cmsStories.length
    ? cmsStories.map((r) => {
        const [role, city, revenue] = splitParts(r.extra, 3);
        return { quote: r.content, author: r.title, role, city, revenue, rating: 5 };
      })
    : defaultStories;

  const cmsFaqs = many("faq");
  const faqs = cmsFaqs.length ? cmsFaqs.map((r) => ({ q: r.title, a: r.content })) : defaultFaqs;

  // Hero text
  const cmsHero = one("hero");
  const [heroBadgeCms, heroHighlightCms] = splitParts(cmsHero?.extra, 2);
  const heroBadge = cmsHero ? heroBadgeCms : "India's Fastest Growing Customized Printing & Gifting Franchise";
  const heroHeadline = cmsHero?.title || "Start Your Entrepreneurial Journey With";
  const heroHighlight = cmsHero ? heroHighlightCms : "Printmont";
  const heroSubtitle = cmsHero?.content || "Be an Entrepreneur, A Business Owner. Join 100+ high-growth retail stores across 50+ cities. Leverage our automated online order routing from printmont.com, 350+ personalized products, and earn healthy 35%–50% operating profit margins.";
  const HERO_POINT_ICONS = [FaTruck, FaChartLine, FaStore, FaTools];
  const cmsHeroPoints = many("hero_point");
  const heroPoints = cmsHeroPoints.length
    ? cmsHeroPoints.map((r) => r.title)
    : ["Omnichannel Web Order Routing", "35% - 50% Profit Margins", "Fast 9 - 14 Months ROI", "Turnkey Setup & Machinery"];

  const STAT_ICONS = [FaStore, FaCity, FaUsers, FaChartLine, FaStar];
  const cmsStats = many("stat");
  const statItems = cmsStats.length
    ? cmsStats.map((r) => ({ value: r.title, label: r.content }))
    : [
        { value: "100+", label: "Stores" },
        { value: "50+", label: "Cities" },
        { value: "1.5L+", label: "Happy Customers" },
        { value: "35-50%", label: "Gross Margin" },
        { value: "4.8/5", label: "Partner Rating" }
      ];

  // Contact details
  const cmsContact = one("contact");
  const [contactPhoneCms, contactEmailCms, contactHqCms] = splitParts(cmsContact?.extra, 3);
  const contactPhone = contactPhoneCms || "+91 98185 32463";
  const contactEmail = contactEmailCms || "franchise@printmont.com";
  const contactHq = contactHqCms || "New Delhi, India";
  const contactHeading = cmsContact?.title || "Talk to Franchise Expansion Director";
  const contactImg = resolveImg(cmsContact?.image_path) || "/franchise/store-delhi.jpg";
  const contactTel = `tel:${contactPhone.replace(/[^+\d]/g, "")}`;

  const BUDGET_OPTIONS = ["₹ 4 - 7 Lakh* + GST", "₹ 8 - 14 Lakh* + GST", "₹ 15 - 25 Lakh* + GST", "₹ 25 Lakhs+"];
  const SPACE_OPTIONS = ["100 - 250 sq.ft.", "350 - 700 sq.ft.", "1000 - 2000 sq.ft.", "Planning to Lease / Rent"];

  useEffect(() => {
    if (!cmsModels.length) return;
    setFormData((prev) => {
      if (cmsModels.some((r) => r.title === prev.model)) return prev;
      const popular = cmsModels.find((r) => splitParts(r.extra, 6)[1].toLowerCase() === "yes") || cmsModels[0];
      return { ...prev, model: popular.title };
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [cmsModels.length]);

  return (
    <div className="franchise-page-wrapper">
      
      {/* =================================================================
          1. HERO SECTION (100% FULL-WIDTH BANNER WITH RICH GRADIENT OVERLAY)
          Modeled exactly like Winni.in full width banner with gradient
          ================================================================= */}
      <section className="header-section__franchise">
        <div className="winni-hero-wrapper width100">
          
          {/* Top banner — managed in Admin → Page Banners → Franchise Top Banner (desktop + mobile images) */}
          <div className="fr-banner-wrap">
            {topBanners.length > 0 ? (
              <ResponsiveHeroCarousel banners={topBanners} carouselId="franchiseTopCarousel" />
            ) : (
              <picture>
                <img src="/franchise-store-hero.jpg" alt="Printmont franchise store" className="fr-banner-fallback" />
              </picture>
            )}
          </div>

          {/* Hero text — managed in Admin → Website Pages → Franchise Page → Hero */}
          <div className="fr-hero-light">
            <div className="franchise-container">
              <div className="hero-left-content">
                {heroBadge && (
                  <div className="hero-top-pill">
                    <FaStar className="me-2 theme-pill-icon" />
                    <span>{heroBadge}</span>
                  </div>
                )}

                <h1 className="hero-main-title">
                  {heroHeadline}
                  {heroHighlight && <> <span className="highlight-theme">{heroHighlight}</span></>}
                </h1>

                <p className="hero-main-subtitle">{heroSubtitle}</p>

                <div className="cta-group">
                  <button type="button" className="btn-winni-cta btn-theme-cta" onClick={() => setShowCallbackModal(true)}>
                    Request Callback <FaPhoneAlt className="ms-2" />
                  </button>
                  <button type="button" className="btn-winni-cta btn-white-cta" onClick={() => setShowBrochureModal(true)}>
                    Download Brochure <FaDownload className="ms-2" />
                  </button>
                  <button type="button" className="btn-winni-cta btn-ghost-cta" onClick={scrollToForm}>
                    Apply Online <FaArrowRight className="ms-2" />
                  </button>
                </div>

                <div className="hero-quick-highlights">
                  {heroPoints.map((text, idx) => {
                    const Icon = HERO_POINT_ICONS[idx % HERO_POINT_ICONS.length];
                    return (
                      <div className="quick-highlight-item" key={idx}>
                        <Icon className="highlight-icon theme-hero-icon" />
                        <span>{text}</span>
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>
          </div>

          {/* Bottom Metrics Bar (Winni Style stats-bar docked directly under banner - 100% Full Width) */}
          <div className="stats-bar-outer width100">
            <div className="stats-bar-fullwidth width100">
              <div className="stats-bar">
                {statItems.map((stat, idx) => {
                  const Icon = STAT_ICONS[idx % STAT_ICONS.length];
                  const isAccent = Icon === FaStar;
                  return (
                    <div className="stat-item" key={idx}>
                      <div className="stat-icon-wrapper">
                        <Icon className={`stat-bar-icon ${isAccent ? "theme-accent-icon" : ""}`} />
                      </div>
                      <div className="stat-info">
                        <div className={`stat-value ${isAccent ? "theme-accent-text" : ""}`}>{stat.value}</div>
                        <div className="stat-label">{stat.label}</div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>

        </div>
      </section>

      {/* Main interior container following Rule 7 (1400px Max-Width) */}
      <div className="franchise-container">

        {/* =================================================================
            2. WHY PARTNER WITH PRINTMONT? (6-CARD GRID WITH DIVIDERS)
            ================================================================= */}
        <section className="why-winni-section franchise-section-space width100">
          <h2 className="why-winni-title">
            <span className="color3333">Why Partner With</span> <span className="color-theme-blue">Printmont?</span>
          </h2>

          <div className="why-winni-grid">
            {whyPartnerPillars.map((p, idx) => (
              <div className="why-winni-card" key={idx}>
                <div className="card-icon-wrapper">
                  <div className="card-icon-inner">{p.icon}</div>
                </div>
                <h3 className="card-title">{p.title}</h3>
                <p className="card-description">{p.desc}</p>
              </div>
            ))}
          </div>
        </section>

        {/* =================================================================
            3. OUR FRANCHISE MODELS (DESKTOP 3-CARD GRID + MOBILE TABBED SELECTOR)
            ================================================================= */}
        <section className="franchise-models-section franchise-section-space">
          <div className="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
              <h2 className="franchise-models-title mb-1">
                <span className="color3333">Our</span> <span className="color-theme-blue">Franchise Models</span>
              </h2>
              <p className="text-muted small mb-0 d-none d-md-block">
                Choose the store format that fits your investment capacity and retail location.
              </p>
            </div>
            {/* Quick brochure link */}
            <button 
              type="button" 
              className="btn btn-sm btn-outline-primary rounded-pill d-none d-md-inline-flex align-items-center gap-1"
              onClick={() => setShowBrochureModal(true)}
            >
              <FaDownload className="small" /> Compare All Specs
            </button>
          </div>

          {/* Mobile Model Selector Tabs (Strictly visible on mobile/tablet < 992px) */}
          <div className="models-mobile-nav d-lg-none mb-3">
            <div className="models-tab-pill-group">
              {franchiseModels.map((m) => (
                <button
                  key={m.id}
                  type="button"
                  className={`model-pill-btn ${currentModel?.id === m.id ? "active" : ""}`}
                  onClick={() => setActiveModelTab(m.id)}
                >
                  {m.popular && <span className="pill-star-badge">★</span>}
                  <span className="pill-title">{m.shortName || m.name}</span>
                  <span className="pill-sub">{(m.investment || "").split("+")[0].trim()}</span>
                </button>
              ))}
            </div>
          </div>

          {/* Desktop 3-Card Grid (>= 992px) */}
          <div className="models-grid d-none d-lg-grid">
            {franchiseModels.map((m) => (
              <div className={`model-card ${m.cardClass} ${m.popular ? "is-popular" : ""}`} key={m.id}>
                {m.popular && m.tag && <span className="pm-model-ribbon">{m.tag}</span>}
                
                {/* Colored Header Strip */}
                <div className={`model-header__desktop ${m.headerClass}`}>
                  {m.name}
                </div>

                {/* Model Storefront Image */}
                <div className="model-image-wrapper">
                  <img src={m.img} alt={`${m.name} Storefront`} loading="lazy" />
                </div>

                {/* Specs Table (Winni 3-column row) */}
                <div className="model-body__desktop">
                  <div className="spec-row">
                    <span className="spec-label">Area Required</span>
                    <span className="spec-separator">-</span>
                    <span className="spec-value">{m.area}</span>
                  </div>

                  <div className="spec-row">
                    <span className="spec-label">Investment</span>
                    <span className="spec-separator">-</span>
                    <span className="spec-value highlight-bold">{m.investment}</span>
                  </div>

                  <div className="spec-row">
                    <span className="spec-label">Profit / Month</span>
                    <span className="spec-separator">-</span>
                    <span className="spec-value profit-green">{m.profit}</span>
                  </div>

                  <div className="spec-row">
                    <span className="spec-label">ROI</span>
                    <span className="spec-separator">-</span>
                    <span className="spec-value highlight-bold">{m.roi}</span>
                  </div>

                  <div className="spec-row ideal-for-row">
                    <span className="spec-label">Ideal For</span>
                    <span className="spec-separator">-</span>
                    <span className="spec-value">{m.idealFor}</span>
                  </div>

                  {/* Bullet features */}
                  <ul className="model-bullet-points">
                    {m.features.map((feat, fIdx) => (
                      <li key={fIdx}>
                        <FaCheck className="bullet-check" /> <span>{feat}</span>
                      </li>
                    ))}
                  </ul>

                  {/* Select button */}
                  <div className="model-action-wrap">
                    <button 
                      type="button" 
                      className={`btn-select-model ${m.popular ? "btn-popular" : ""}`}
                      onClick={() => handleModelSelect(m.name, m.investment, m.area)}
                    >
                      Select {m.name.split(" ")[0]} Model &amp; Apply
                    </button>
                  </div>
                </div>

              </div>
            ))}
          </div>

          {/* Mobile Single Card View (< 992px) - Compact & Elegant */}
          <div className="models-single-mobile d-lg-none">
            {(() => {
              const m = currentModel;
              if (!m) return null;
              return (
                <div className={`model-card ${m.cardClass} ${m.popular ? "is-popular" : ""}`}>
                  {m.popular && m.tag && <span className="pm-model-ribbon">{m.tag}</span>}
                  <div className={`model-header__desktop ${m.headerClass}`}>
                    {m.name}
                  </div>
                  <div className="model-image-wrapper">
                    <img src={m.img} alt={`${m.name} Storefront`} loading="lazy" />
                  </div>
                  <div className="model-body__desktop p-3">
                    <div className="spec-row">
                      <span className="spec-label">Area</span>
                      <span className="spec-separator">-</span>
                      <span className="spec-value">{m.area}</span>
                    </div>
                    <div className="spec-row">
                      <span className="spec-label">Investment</span>
                      <span className="spec-separator">-</span>
                      <span className="spec-value highlight-bold">{m.investment}</span>
                    </div>
                    <div className="spec-row">
                      <span className="spec-label">Profit / Mo</span>
                      <span className="spec-separator">-</span>
                      <span className="spec-value profit-green">{m.profit}</span>
                    </div>
                    <div className="spec-row">
                      <span className="spec-label">ROI</span>
                      <span className="spec-separator">-</span>
                      <span className="spec-value highlight-bold">{m.roi}</span>
                    </div>
                    <div className="spec-row ideal-for-row">
                      <span className="spec-label">Ideal For</span>
                      <span className="spec-separator">-</span>
                      <span className="spec-value">{m.idealFor}</span>
                    </div>
                    <ul className="model-bullet-points">
                      {m.features.slice(0, 4).map((feat, fIdx) => (
                        <li key={fIdx}>
                          <FaCheck className="bullet-check" /> <span>{feat}</span>
                        </li>
                      ))}
                    </ul>
                    <div className="model-action-wrap mt-2">
                      <button 
                        type="button" 
                        className={`btn-select-model w-100 ${m.popular ? "btn-popular" : ""}`}
                        onClick={() => handleModelSelect(m.name, m.investment, m.area)}
                      >
                        Select &amp; Apply Now
                      </button>
                    </div>
                  </div>
                </div>
              );
            })()}
          </div>
        </section>

        {/* =================================================================
            4. HORIZONTAL STRIP CTA (WINNI STRIP CTA BANNER)
            ================================================================= */}
        <section className="winni-strip-cta__section franchise-section-space">
          <div className="cta-banner-container">
            <div className="banner-highlights">
              
              <div className="highlight-item">
                <div className="circle-icon-wrapper">
                  <span>🚀</span>
                </div>
                <span className="highlight-text">Low Investment, High Returns</span>
              </div>

              <div className="highlight-item">
                <div className="circle-icon-wrapper">
                  <span>🛡️</span>
                </div>
                <span className="highlight-text">Proven Business Model</span>
              </div>

              <div className="highlight-item">
                <div className="circle-icon-wrapper">
                  <span>🏬</span>
                </div>
                <span className="highlight-text">100+ Stores Across India</span>
              </div>

              <div className="highlight-item">
                <div className="circle-icon-wrapper">
                  <span>⭐</span>
                </div>
                <span className="highlight-text">Trusted by 1.5L+ Customers</span>
              </div>

            </div>

            {/* Action Buttons */}
            <div className="banner-actions">
              <button 
                type="button" 
                className="btn-strip-cta btn-strip-white"
                onClick={() => setShowBrochureModal(true)}
              >
                Download Brochure <FaDownload className="ms-2" />
              </button>
              <button 
                type="button" 
                className="btn-strip-cta btn-strip-theme"
                onClick={() => setShowCallbackModal(true)}
              >
                Request Callback <FaPhoneAlt className="ms-2" />
              </button>
            </div>
          </div>
        </section>

        {/* =================================================================
            5. OUR JOURNEY OF GROWTH (HERO CARD & TIMELINE)
            ================================================================= */}
        <section className="journey-section franchise-section-space">
          <div className="journey-card-wrapper position-relative">
            <img 
              src="/franchise-store-interior.jpg" 
              alt="Printmont Studio Journey of Growth" 
              className="journey-hero-img" 
              loading="lazy" 
            />
            <div className="journey-growth-title__wrapper">
              <h2 className="journey-growth-title">
                <span className="color3333">Our</span> <span className="color-theme-blue">Journey Of Growth</span>
              </h2>
              <p className="journey-growth-subtitle">
                From a small beginning to one of India's fastest growing customized printing &amp; gifting brands.
              </p>
            </div>
          </div>

          {/* 5 Milestone Step Cards */}
          <div className="journey-timeline-grid mt-4">
            {journeyMilestones.map((m, idx) => (
              <div className="journey-milestone-card" key={idx}>
                <div className="milestone-step-pill">Step 0{idx + 1}</div>
                <div className="milestone-year">{m.year}</div>
                <h4 className="milestone-title">{m.title}</h4>
                <p className="milestone-desc">{m.desc}</p>
              </div>
            ))}
          </div>
        </section>
      </div> {/* End franchise-container for sections 2-5 */}

      {/* =================================================================
          6. PAN-INDIA PRESENCE & CELEBRATING EVERY MOMENT SINCE 2019 (100% FULL-WIDTH)
          ================================================================= */}
      <section className="presence-section-fullbleed width100">
        <div className="presence-fullbleed-container width100">
          <h2 className="presence-title">
            <span className="color3333">Celebrating Every</span> <span className="color-theme-blue">Moment Since 2019</span>
          </h2>

          <div className="presence-container width100">
            {/* Left: Map / Network card */}
            <div className="map-wrapper-card">
              <div className="network-card-header">
                <img src="/PrintLogo.png" alt="Printmont" height="28" />
                <span className="network-live-tag">
                  <span className="pulse-dot"></span> Live Fulfillment Active
                </span>
              </div>

              <div className="network-stats-hero">
                <div className="hero-stat-large">100+ Stores | 50+ Cities</div>
                <div className="hero-stat-sub">18 States &amp; UT Across India</div>
              </div>

              {/* City hubs pills */}
              <div className="city-hubs-grid">
                <div className="hub-pill flagship">Delhi NCR • 18 Outlets</div>
                <div className="hub-pill">Mumbai • 14 Outlets</div>
                <div className="hub-pill">Bengaluru • 12 Outlets</div>
                <div className="hub-pill">Pune • 8 Outlets</div>
                <div className="hub-pill">Hyderabad • 7 Outlets</div>
                <div className="hub-pill">Jaipur • 6 Outlets</div>
                <div className="hub-pill">Ahmedabad • 5 Outlets</div>
                <div className="hub-pill">Chandigarh • 5 Outlets</div>
              </div>

              <div className="network-bottom-note">
                <FaTruck className="me-2 theme-pill-icon" />
                <span>Real-time web order routing active across 500+ postal pin codes.</span>
              </div>
            </div>

            {/* Right: Recent Store Openings */}
            <div className="presence-content">
              <div className="presence-content-header">
                <h3 className="section-subtitle">Our Recent Store Openings</h3>
                <span className="store-swipe-hint d-md-none">
                  Swipe <FaArrowRight className="swipe-arrow-anim ms-1" />
                </span>
              </div>
              
              <div className="store-cards-grid">
                {recentStores.map((st, sIdx) => (
                  <div className="store-card" key={sIdx}>
                    <div className="store-card-img-wrap">
                      <img src={st.img} alt={`${st.city} Printmont Store`} loading="lazy" />
                      <span className="store-card-tag">{st.tag}</span>
                    </div>
                    <div className="store-card-details">
                      <h4 className="store-title">
                        <FaMapMarkerAlt className="text-danger me-1" /> {st.city}
                      </h4>
                      <p className="store-subtitle">{st.name}</p>
                    </div>
                  </div>
                ))}
              </div>

              <button 
                type="button" 
                className="btn-view-all"
                onClick={scrollToForm}
              >
                View All Opportunities
              </button>
            </div>

          </div>
        </div>
      </section>

      {/* Main interior container following Rule 7 (1400px Max-Width) re-opened for sections 7-13 */}
      <div className="franchise-container">

        {/* =================================================================
            7. HOW IT WORKS - YOUR JOURNEY WITH PRINTMONT (6 STEPS)
            ================================================================= */}
        <section className="how-it-works-section franchise-section-space width100">
          <h2 className="how-it-works-title">
            <span className="color3333">How it Works —</span> <span className="color-theme-blue">Your Journey with Printmont</span>
          </h2>

          <div className="steps-container">
            {howItWorksSteps.map((s, idx) => (
              <React.Fragment key={idx}>
                <div className="step-item">
                  <div className="step-badge">{s.step}</div>
                  <div className="step-icon-wrapper">
                    <span className="step-emoji">
                      {idx === 0 ? "📝" : idx === 1 ? "🤝" : idx === 2 ? "📍" : idx === 3 ? "📜" : idx === 4 ? "🛠️" : "🚀"}
                    </span>
                  </div>
                  <div className="step-title">{s.title}</div>
                  <div className="step-desc">{s.desc}</div>
                </div>

                {idx < howItWorksSteps.length - 1 && (
                  <div className="step-connector d-none d-lg-flex">
                    <div className="connector-line"></div>
                    <FaArrowRight className="connector-arrow" />
                  </div>
                )}
              </React.Fragment>
            ))}
          </div>
        </section>

        {/* =================================================================
            8. BEYOND STORE LAUNCH, WE'RE WITH YOU (5 SUPPORT MODULES)
            ================================================================= */}
        <section className="support-section franchise-section-space width100">
          <h2 className="support-title">
            Beyond <span className="color-theme-blue">Store Launch</span>, We're With You
          </h2>

          <div className="support-grid">
            {beyondLaunchSupport.map((sup, idx) => (
              <div className="support-card" key={idx}>
                <div className="support-image-wrapper">
                  <div className="support-icon-circle">{sup.icon}</div>
                </div>
                <div className="support-card-title">{sup.title}</div>
                <div className="support-card-desc">{sup.desc}</div>
              </div>
            ))}
          </div>
        </section>

        {/* =================================================================
            9. OUR PRODUCTS ECOSYSTEM (WINNI MULTI-ITEM GRID)
            ================================================================= */}
        <section className="ecosystem-section franchise-section-space width100">
          <div className="d-flex flex-wrap justify-content-between align-items-center mb-2">
            <div>
              <h2 className="ecosystem-title mb-1">
                <span className="color3333">Our</span> <span className="color-theme-blue">Products Ecosystem</span>
              </h2>
              <p className="ecosystem-subtitle mb-0 d-none d-md-block">
                Offer over 350+ fast-moving personalized products and customized merchandise that generate steady repeat revenue all year round.
              </p>
            </div>
            <span className="badge bg-light text-primary border d-none d-md-inline-block">350+ Fast Moving SKUs</span>
          </div>

          <div className="products-grid">
            {(showAllProducts ? productsEcosystem : productsEcosystem.slice(0, 9)).map((prod, pIdx) => (
              <div className="product-card" key={pIdx}>
                <div className="product-image-wrapper">
                  {prod.img ? <img src={prod.img} alt={prod.name} className="prod-photo" loading="lazy" /> : <span className="prod-emoji">{prod.icon}</span>}
                </div>
                <span className="product-name">{prod.name}</span>
              </div>
            ))}
          </div>

          {/* Mobile view toggle */}
          <div className="text-center mt-3 d-md-none">
            <button 
              type="button" 
              className="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 font-size-13"
              onClick={() => setShowAllProducts(!showAllProducts)}
            >
              {showAllProducts ? "Show Less" : `View All ${productsEcosystem.length} Categories +`}
            </button>
          </div>
        </section>

        {/* Mid-page banner — managed in Admin → Page Banners → Franchise Mid Banner */}
        {midBanners.length > 0 && (
          <section className="franchise-section-space width100 fr-mid-banner">
            <ResponsiveHeroCarousel banners={midBanners} carouselId="franchiseMidCarousel" />
          </section>
        )}

        {/* =================================================================
            10. FRANCHISEE SUCCESS STORIES (INTERACTIVE TOUCH CAROUSEL)
            ================================================================= */}
        <section className="franchise-section-space width100 stories-carousel-section">
          <div className="text-center mb-3 mb-md-4">
            <h2 className="support-title text-center mb-1">
              Franchisee <span className="color-theme-blue">Success Stories</span>
            </h2>
            <p className="text-center text-muted mb-0 small">
              Hear directly from entrepreneur partners who turned their passion for customized gifts into high-yielding retail businesses.
            </p>
          </div>

          <div className="franchisee-carousel-container">
            <Slider key={storySlides} {...storySliderSettings} className="franchisee-stories-slider">
              {franchiseeStories.map((story, stIdx) => (
                <div key={stIdx} className="story-slide-item">
                  <div className="pm-testimonial-card">
                    <div className="quote-mark">
                      <FaQuoteLeft />
                    </div>
                    <div className="star-rating mb-2">
                      {[...Array(story.rating)].map((_, i) => (
                        <FaStar key={i} className="theme-star-icon me-1" />
                      ))}
                    </div>
                    <p className="story-quote-text">"{story.quote}"</p>
                    
                    <div className="story-author-box">
                      <div className="author-avatar-badge">
                        {story.author.charAt(0)}
                      </div>
                      <div>
                        <h5 className="author-name">{story.author}</h5>
                        <span className="author-role">{story.role} • {story.city}</span>
                        <div className="author-revenue">Verified Revenue: {story.revenue}</div>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </Slider>
          </div>
        </section>

        {/* =================================================================
            11. AWARDS & RECOGNITIONS + FEATURED IN (WINNI RECOGNITION SECTION)
            ================================================================= */}
        <section className="recognition-section franchise-section-space width100">
          <div className="recognition-container">
            
            {/* Left: Awards & Recognitions */}
            <div>
              <h2 className="section-title">Awards &amp; Recognitions</h2>
              <div className="awards-grid">
                <div className="award-card">
                  <div className="award-trophy-symbol">🏆</div>
                  <div className="award-badge-title">Top 10 Retail Franchises</div>
                  <div className="award-badge-org">SiliconIndia Magazine</div>
                  <span className="award-tag">Printmont 2024</span>
                </div>
                <div className="award-card">
                  <div className="award-trophy-symbol">🎖️</div>
                  <div className="award-badge-title">FRO Retail Excellence</div>
                  <div className="award-badge-org">Franchise India Awards</div>
                  <span className="award-tag">Best Partner ROI</span>
                </div>
                <div className="award-card">
                  <div className="award-trophy-symbol">⭐</div>
                  <div className="award-badge-title">D2C Brand of the Year</div>
                  <div className="award-badge-org">National Retail Summit</div>
                  <span className="award-tag">Custom Gifting</span>
                </div>
              </div>
            </div>

            {/* Right: Featured In */}
            <div>
              <h2 className="section-title">Featured In Top Media</h2>
              <div className="media-grid">
                <div className="media-card">YourStory</div>
                <div className="media-card">Economic Times</div>
                <div className="media-card">Business Standard</div>
                <div className="media-card">Inc42</div>
                <div className="media-card">LiveMint</div>
                <div className="media-card">Zee Business</div>
              </div>
            </div>

          </div>
        </section>

        {/* =================================================================
            12. START YOUR FRANCHISE JOURNEY TODAY (WINNI 2-COLUMN LEAD FORM)
            ================================================================= */}
        <section className="franchise-form-section franchise-section-space width100" id="franchiseQueryFormContainer">
          <div className="franchise-form-container">
            
            {/* Left Column: Store Visual & Direct Contacts */}
            <div className="franchise-image-wrapper">
              <div className="form-left-showcase">
                <img 
                  src={contactImg}
                  alt="Printmont Studio Flagship" 
                  className="form-showcase-img"
                  loading="lazy" 
                />
                <div className="form-direct-contacts-box">
                  <h4 className="contacts-box-title">{contactHeading}</h4>
                  <div className="contact-line">
                    <FaPhoneAlt className="contact-ico" />
                    <div>
                      <div className="contact-label">Direct Helpline:</div>
                      <a href={contactTel} className="contact-val">{contactPhone}</a>
                    </div>
                  </div>
                  <div className="contact-line">
                    <FaEnvelope className="contact-ico" />
                    <div>
                      <div className="contact-label">Official Email:</div>
                      <a href={`mailto:${contactEmail}`} className="contact-val">{contactEmail}</a>
                    </div>
                  </div>
                  <div className="contact-line">
                    <FaMapMarkerAlt className="contact-ico" />
                    <div>
                      <div className="contact-label">Headquarters:</div>
                      <span className="contact-val">{contactHq}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Right Column: High-Converting Form */}
            <div>
              <div className="form-header">
                <h2 className="form-title__text">
                  <span className="color3333">Start Your</span><br />
                  <span className="color-theme-blue">Franchise Journey Today</span>
                </h2>
                <p className="form-subtitle__text">
                  Talk to our franchise experts and get personalized guidance on investment, location selection, expected returns, and store setup.
                </p>
              </div>

              {submitSuccess ? (
                <div className="form-success-box text-center py-5">
                  <FaCheckCircle className="text-success fs-1 mb-3" />
                  <h3 className="fw-bold text-dark mb-2">Application Received!</h3>
                  <p className="text-muted small mb-4">
                    Thank you for your interest in partnering with Printmont. Our Franchise Expansion Director will connect with you within 24 hours.
                  </p>
                  <button 
                    type="button" 
                    className="btn btn-outline-primary rounded-pill px-4"
                    onClick={() => setSubmitSuccess(false)}
                  >
                    Submit Another Inquiry
                  </button>
                </div>
              ) : (
                <form id="franchiseQueryFormCms" onSubmit={handleSubmit}>
                  <div className="franchise-form">
                    
                    {/* Full Name */}
                    <div className="form-group-full">
                      <input 
                        type="text" 
                        required 
                        name="full_name"
                        value={formData.full_name}
                        onChange={handleInputChange}
                        maxLength="60" 
                        className="form-control" 
                        placeholder="Your Name *" 
                      />
                    </div>

                    {/* Mobile Number with +91 Prefix */}
                    <div className="phone-input-group form-group-full">
                      <span className="phone-prefix">+91</span>
                      <input 
                        type="tel" 
                        required 
                        maxLength="10" 
                        name="contact_number"
                        value={formData.contact_number}
                        onChange={handleInputChange}
                        className="form-control" 
                        placeholder="Phone Number *" 
                      />
                    </div>

                    {/* Email */}
                    <div className="form-group-col">
                      <input 
                        type="email" 
                        required
                        name="email_id"
                        value={formData.email_id}
                        onChange={handleInputChange}
                        className="form-control" 
                        placeholder="Email Address *" 
                      />
                    </div>

                    {/* City */}
                    <div className="form-group-col">
                      <input 
                        type="text" 
                        required 
                        name="city"
                        value={formData.city}
                        onChange={handleInputChange}
                        maxLength="50" 
                        className="form-control" 
                        placeholder="City *" 
                      />
                    </div>

                    {/* State */}
                    <div className="form-group-col">
                      <input 
                        type="text" 
                        required 
                        name="state"
                        value={formData.state}
                        onChange={handleInputChange}
                        maxLength="50" 
                        className="form-control" 
                        placeholder="State *" 
                      />
                    </div>

                    {/* Preferred Model */}
                    <div className="form-group-col">
                      <select 
                        className="form-control" 
                        name="model" 
                        value={formData.model}
                        onChange={handleInputChange}
                      >
                        {franchiseModels.map((m) => (
                          <option key={m.id} value={m.name}>{m.name}{m.area ? ` (${m.area})` : ""}</option>
                        ))}
                        {!franchiseModels.some((m) => m.name === formData.model) && (
                          <option value={formData.model}>{formData.model}</option>
                        )}
                      </select>
                    </div>

                    {/* Investment Budget */}
                    <div className="form-group-col">
                      <select 
                        className="form-control" 
                        name="budget" 
                        value={formData.budget}
                        onChange={handleInputChange}
                      >
                        {!BUDGET_OPTIONS.includes(formData.budget) && <option value={formData.budget}>{formData.budget}</option>}
                        <option value="₹ 4 - 7 Lakh* + GST">₹4 Lakhs – ₹7 Lakhs</option>
                        <option value="₹ 8 - 14 Lakh* + GST">₹8 Lakhs – ₹14 Lakhs</option>
                        <option value="₹ 15 - 25 Lakh* + GST">₹15 Lakhs – ₹25 Lakhs</option>
                        <option value="₹ 25 Lakhs+">₹25 Lakhs+</option>
                      </select>
                    </div>

                    {/* Available Space */}
                    <div className="form-group-col">
                      <select 
                        className="form-control" 
                        name="space" 
                        value={formData.space}
                        onChange={handleInputChange}
                      >
                        {!SPACE_OPTIONS.includes(formData.space) && <option value={formData.space}>{formData.space}</option>}
                        <option value="100 - 250 sq.ft.">100 - 250 sq.ft. (Kiosk/Mall)</option>
                        <option value="350 - 700 sq.ft.">350 - 700 sq.ft. (Standard Studio)</option>
                        <option value="1000 - 2000 sq.ft.">1000 - 2000 sq.ft. (Production Hub)</option>
                        <option value="Planning to Lease / Rent">Planning to Lease</option>
                      </select>
                    </div>

                    {/* Message */}
                    <div className="form-group-full">
                      <textarea 
                        name="message"
                        rows="2"
                        value={formData.message}
                        onChange={handleInputChange}
                        placeholder="Message or target location details (Optional)..."
                        className="form-control"
                        style={{ height: "auto", padding: "10px 14px", resize: "none" }}
                      ></textarea>
                    </div>

                  </div>

                  {/* Submit Button */}
                  <div className="form-submit-row mt-3">
                    <button 
                      type="submit" 
                      disabled={isSubmitting}
                      className="submit-btn__form-bottom"
                    >
                      {isSubmitting ? (
                        <>
                          <Spinner animation="border" size="sm" className="me-2" /> Submitting...
                        </>
                      ) : (
                        <>
                          Submit Inquiry <FaArrowRight className="ms-2" />
                        </>
                      )}
                    </button>
                    <span className="privacy-secure-note">
                      🔒 100% Confidential. Directly delivered to Franchise Director.
                    </span>
                  </div>

                </form>
              )}
            </div>

          </div>
        </section>

        {/* =================================================================
            13. FREQUENTLY ASKED QUESTIONS (ACCORDION)
            ================================================================= */}
        <section className="franchise-section-space width100">
          <div className="faqc-header text-center mb-4">
            <h2 className="section-title fs42 fw600 margin-zero">
              <span className="color3333">Franchise Ownership</span> <span className="color-theme-blue">FAQ's</span>
            </h2>
            <p className="text-muted small">Everything you need to know about starting your Printmont franchise in India.</p>
          </div>

          <div className="franchise-accordion max-w-900 mx-auto" style={{ maxWidth: "860px" }}>
            <Accordion defaultActiveKey="0">
              {faqs.map((faq, idx) => (
                <Accordion.Item eventKey={String(idx)} key={idx}>
                  <Accordion.Header>{faq.q}</Accordion.Header>
                  <Accordion.Body>{faq.a}</Accordion.Body>
                </Accordion.Item>
              ))}
            </Accordion>
          </div>
        </section>

        {/* =================================================================
            14. BOTTOM CTA CARD
            ================================================================= */}
        <section className="franchise-cta-card franchise-section-space">
          <h2 className="franchise-cta-title">Ready to Own a Printmont Franchise?</h2>
          <p className="franchise-cta-sub">
            Speak directly with our National Franchise Expansion Team. Let's discuss prime locations available in your city and evaluate your store feasibility.
          </p>

          <button 
            type="button"
            className="btn-winni-cta btn-theme-cta px-4 py-2.5 rounded-pill shadow-sm fs-6 text-white"
            onClick={scrollToForm}
          >
            Apply For Franchise Today <FaArrowRight className="ms-2" />
          </button>

          <div className="franchise-cta-contacts">
            <span>
              <FaPhoneAlt className="me-2 theme-pill-icon" />
              Phone / WhatsApp: <strong>{contactPhone}</strong>
            </span>
            <span>•</span>
            <span>
              <FaEnvelope className="me-2 theme-pill-icon" />
              Email: <a href={`mailto:${contactEmail}`}>{contactEmail}</a>
            </span>
            <span>•</span>
            <span>
              <FaMapMarkerAlt className="me-2 theme-pill-icon" />
              Corporate Office: {contactHq}
            </span>
          </div>
        </section>

      </div>

      {/* =================================================================
          15. MOBILE STICKY BOTTOM ACTION BAR (Strictly Mobile <768px)
          ================================================================= */}
      <div className="franchise-mobile-sticky-bar d-md-none">
        <button 
          type="button" 
          className="mobile-sticky-btn callback-btn"
          onClick={() => setShowCallbackModal(true)}
        >
          <FaPhoneAlt className="me-1" /> Request Call
        </button>
        <button 
          type="button" 
          className="mobile-sticky-btn apply-btn"
          onClick={scrollToForm}
        >
          Apply Now <FaArrowRight className="ms-1" />
        </button>
      </div>

      {/* =================================================================
          MODAL 1: QUICK CALLBACK MODAL
          ================================================================= */}
      <Modal show={showCallbackModal} onHide={() => setShowCallbackModal(false)} centered>
        <Modal.Header closeButton className="border-0 pb-0">
          <Modal.Title className="fw-bold fs-5 text-dark">
            <FaPhoneAlt className="theme-accent-text me-2" /> Request Instant Callback
          </Modal.Title>
        </Modal.Header>
        <Modal.Body className="pt-2">
          <p className="small text-muted mb-3">
            Leave your contact details and our Franchise Expansion Director will call you within 30 minutes to discuss store feasibility in your city.
          </p>
          <form onSubmit={handleCallbackSubmit}>
            <div className="mb-2">
              <label className="franchise-form-label">Full Name *</label>
              <input 
                type="text" 
                required 
                className="form-control" 
                placeholder="Your Name" 
                value={callbackData.full_name} 
                onChange={(e) => setCallbackData(prev => ({ ...prev, full_name: e.target.value }))} 
              />
            </div>
            <div className="mb-2">
              <label className="franchise-form-label">Mobile Number *</label>
              <input 
                type="tel" 
                required 
                maxLength="10" 
                className="form-control" 
                placeholder="10-digit mobile number" 
                value={callbackData.contact_number} 
                onChange={(e) => setCallbackData(prev => ({ ...prev, contact_number: e.target.value }))} 
              />
            </div>
            <div className="mb-3">
              <label className="franchise-form-label">Preferred City *</label>
              <input 
                type="text" 
                required 
                className="form-control" 
                placeholder="e.g. New Delhi, Mumbai, Pune" 
                value={callbackData.city} 
                onChange={(e) => setCallbackData(prev => ({ ...prev, city: e.target.value }))} 
              />
            </div>
            <button 
              type="submit" 
              className="submit-btn__form-bottom w-100" 
              disabled={isCallbackSubmitting}
            >
              {isCallbackSubmitting ? <Spinner animation="border" size="sm" /> : "Call Me Now"}
            </button>
          </form>
        </Modal.Body>
      </Modal>

      {/* =================================================================
          MODAL 2: DOWNLOAD BROCHURE MODAL
          ================================================================= */}
      <Modal show={showBrochureModal} onHide={() => setShowBrochureModal(false)} centered>
        <Modal.Header closeButton className="border-0 pb-0">
          <Modal.Title className="fw-bold fs-5 text-dark">
            <FaDownload className="text-primary me-2" /> Download Franchise Prospectus
          </Modal.Title>
        </Modal.Header>
        <Modal.Body className="pt-2">
          <p className="small text-muted mb-3">
            Get the complete 2026 Printmont Franchise Prospectus with itemized CapEx breakdown, machine specs, profit margins, and royalty terms.
          </p>
          <form onSubmit={handleBrochureSubmit}>
            <div className="mb-2">
              <label className="franchise-form-label">Full Name *</label>
              <input 
                type="text" 
                required 
                className="form-control" 
                placeholder="Your Name" 
                value={brochureData.full_name} 
                onChange={(e) => setBrochureData(prev => ({ ...prev, full_name: e.target.value }))} 
              />
            </div>
            <div className="mb-2">
              <label className="franchise-form-label">Email Address *</label>
              <input 
                type="email" 
                required 
                className="form-control" 
                placeholder="your.email@example.com" 
                value={brochureData.email_id} 
                onChange={(e) => setBrochureData(prev => ({ ...prev, email_id: e.target.value }))} 
              />
            </div>
            <div className="mb-3">
              <label className="franchise-form-label">Mobile Number *</label>
              <input 
                type="tel" 
                required 
                maxLength="10" 
                className="form-control" 
                placeholder="10-digit mobile number" 
                value={brochureData.contact_number} 
                onChange={(e) => setBrochureData(prev => ({ ...prev, contact_number: e.target.value }))} 
              />
            </div>
            <button 
              type="submit" 
              className="submit-btn__form-bottom w-100" 
              disabled={isBrochureSubmitting}
            >
              {isBrochureSubmitting ? <Spinner animation="border" size="sm" /> : "Download PDF Prospectus"}
            </button>
          </form>
        </Modal.Body>
      </Modal>

    </div>
  );
};

export default FranchisePage;
