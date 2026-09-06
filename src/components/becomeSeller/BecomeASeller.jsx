import React, { useState } from "react";
import { Container, Row, Col, Form, Spinner } from "react-bootstrap";
import { Link } from "react-router-dom";
import {
  FaChevronLeft, FaChevronRight, FaQuoteLeft, FaStore,
  FaBinoculars, FaRocket, FaChartLine, FaHeadset,
  FaBoxOpen, FaBullhorn, FaTags, FaGraduationCap, FaUserTie, FaMobileAlt,
} from "react-icons/fa";
import { toast } from "react-hot-toast";
import usePageSections from "../pages/usePageSections";
import { API_ENDPOINTS, resolveImageUrl } from "../../config/apiEndpoints";
import "./become-seller.css";

/** Two-tone heading: leading words dark, trailing words in the theme colour. */
const Heading = ({ dark, blue, className = "" }) => (
  <h2 className={`bs-h2 ${className}`}>
    {dark} <span>{blue}</span>
  </h2>
);

/* Icons stand in until an admin uploads artwork for a row. */
const BENEFIT_ICONS = [FaBinoculars, FaRocket, FaChartLine, FaHeadset];
const TOOL_ICONS = [FaBoxOpen, FaBullhorn, FaTags, FaGraduationCap, FaUserTie, FaMobileAlt];

const BecomeASeller = () => {
  const { many, one, loading } = usePageSections("become-a-seller");

  const hero = one("hero");
  const stats = many("stat");
  const benefits = many("benefit");
  const stories = many("story");
  const journey = many("journey");
  const tools = many("tool");
  const platform = many("platform");

  const headings = many("heading");
  const labels = many("label");
  const topics = many("topic");
  const asides = many("aside");

  /**
   * A band's heading row. `content` packs two values behind a pipe —
   * "<blue half>|<intro paragraph>" — so the three text columns cover a
   * two-tone title plus its intro without widening the table.
   */
  const band = (key) => {
    const row = headings.find((h) => h.extra === key);
    if (!row) return null;
    const [blue = "", intro = ""] = String(row.content || "").split("|");
    return { dark: row.title, blue, intro };
  };

  /** Button or label caption by key. */
  const label = (key, fallback = "") =>
    labels.find((l) => l.extra === key)?.title || fallback;

  /**
   * The photograph in a band's side panel. Until one is uploaded the panel
   * keeps its icon, so `Aside` decides between the two rather than the
   * markup at each call site.
   */
  const Aside = ({ band: key, icon: Icon, className = "" }) => {
    const row = asides.find((a) => a.extra === key);
    const src = row?.image_path ? resolveImageUrl(row.image_path) : "";

    return (
      <div className={`bs-aside ${className}`} aria-hidden={src ? undefined : "true"}>
        {src
          ? <img src={src} alt={row.title || ""} loading="lazy" />
          : <Icon className="bs-aside__icon" />}
      </div>
    );
  };

  const [storyIndex, setStoryIndex] = useState(0);
  const [slideIndex, setSlideIndex] = useState(0);

  const [query, setQuery] = useState({ name: "", contact: "", topic: "", message: "" });
  const [sending, setSending] = useState(false);
  const [sent, setSent] = useState(false);

  const step = (list, index, dir) => (index + dir + list.length) % list.length;

  const submitQuery = async (e) => {
    e.preventDefault();
    if (sending) return;

    if (!query.name.trim() || !query.contact.trim() || !query.message.trim()) {
      toast.error("Please fill in all required fields.");
      return;
    }

    setSending(true);
    try {
      // Seller queries go through the same contact endpoint, tagged by topic
      // so support can tell them apart in the admin inbox.
      const looksLikeEmail = query.contact.includes("@");
      const res = await fetch(API_ENDPOINTS.CONTACT, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: query.name.trim(),
          email: looksLikeEmail ? query.contact.trim() : "",
          phone: looksLikeEmail ? "" : query.contact.trim(),
          subject: `Seller enquiry${query.topic ? ` — ${query.topic}` : ""}`,
          message: query.message.trim(),
        }),
      });
      const data = await res.json();

      if (!res.ok || !data.success) {
        toast.error(data.message || "Could not send your query. Please try again.");
        return;
      }

      setSent(true);
      toast.success("Thanks — we have received your query.");
    } catch (err) {
      console.error("Seller query failed:", err);
      toast.error("Could not reach the server. Please try again.");
    } finally {
      setSending(false);
    }
  };

  if (loading) {
    return (
      <div className="bs-page">
        <div className="shimmer-bg" style={{ height: 280 }} />
        <Container className="py-5">
          <div className="shimmer-bg rounded mb-3" style={{ height: 90 }} />
          <div className="shimmer-bg rounded" style={{ height: 260 }} />
        </Container>
      </div>
    );
  }

  const story = stories[storyIndex];
  const slide = platform[slideIndex];

  return (
    <div className="bs-page">

      {/* ---------- HERO + STATS STRIP ---------- */}
      {hero && (
        <section className="bs-hero-wrap">
          <div
            className="bs-hero"
            style={hero.image_path ? { backgroundImage: `url('${resolveImageUrl(hero.image_path)}')` } : undefined}
          >
            <div className="bs-hero__scrim" />
            <Container className="bs-hero__inner">
              <h1 className="bs-hero__title">{hero.title}</h1>
              {hero.content && <p className="bs-hero__lead">{hero.content}</p>}
              {hero.extra && <Link to="/login" className="bs-btn">{hero.extra}</Link>}
            </Container>
          </div>

          {stats.length > 0 && (
            <Container>
              <div className="bs-stats">
                {stats.map((s) => (
                  <div className="bs-stats__item" key={s.id}>
                    <div className="bs-stats__num">{s.title}</div>
                    <div className="bs-stats__label">{s.content}</div>
                  </div>
                ))}
              </div>
            </Container>
          )}
        </section>
      )}

      {/* ---------- WHY SELL ---------- */}
      {benefits.length > 0 && (
        <section className="bs-sec">
          <Container>
            <Heading dark={band("why")?.dark} blue={band("why")?.blue} />
            {band("why")?.intro && <p className="bs-intro">{band("why").intro}</p>}

            <Row className="g-4 align-items-center">
              <Col xs={12} lg={8}>
                <Row className="g-3">
                  {benefits.map((b, i) => {
                    const Icon = BENEFIT_ICONS[i % BENEFIT_ICONS.length];
                    return (
                      <Col xs={12} md={6} key={b.id}>
                        <div className="bs-card h-100">
                          <div className="bs-card__head">
                            {b.image_path
                              ? <img className="bs-card__img" src={resolveImageUrl(b.image_path)} alt="" loading="lazy" />
                              : <Icon className="bs-card__icon" />}
                            <h3 className="bs-card__title">{b.title}</h3>
                          </div>
                          <p className="bs-card__text mb-0">{b.content}</p>
                        </div>
                      </Col>
                    );
                  })}
                </Row>
              </Col>
              <Col xs={12} lg={4} className="d-none d-lg-block">
                <Aside band="why" icon={FaStore} />
              </Col>
            </Row>
          </Container>
        </section>
      )}

      {/* ---------- SELLER STORIES ---------- */}
      {stories.length > 0 && (
        <section className="bs-sec bs-sec--tint">
          <Container>
            <Row className="g-4 align-items-center">
              <Col xs={12} lg={5}>
                <Heading dark={band("stories")?.dark} blue={band("stories")?.blue} className="bs-h2--left" />
                {band("stories")?.intro && (
                  <p className="bs-intro bs-intro--left">{band("stories").intro}</p>
                )}
                <Link to="/contact" className="bs-btn bs-btn--ghost">
                  {label("stories_cta", "See All Stories")}
                </Link>
              </Col>

              <Col xs={12} lg={7}>
                <div className="bs-story-wrap">
                  {stories.length > 1 && (
                    <button
                      type="button" className="bs-arrow bs-arrow--prev"
                      onClick={() => setStoryIndex(step(stories, storyIndex, -1))}
                      aria-label="Previous story"
                    >
                      <FaChevronLeft />
                    </button>
                  )}

                  <div className="bs-story">
                    <div className="bs-story__avatar">
                      {story.image_path
                        ? <img src={resolveImageUrl(story.image_path)} alt={story.title} loading="lazy" />
                        : <span>{story.title.split(" ").map((w) => w[0]).slice(0, 2).join("")}</span>}
                    </div>
                    <div className="bs-story__name">{story.title}</div>
                    {story.extra && <div className="bs-story__co">{story.extra}</div>}
                    <FaQuoteLeft className="bs-story__quote" />
                    <p className="bs-story__text">{story.content}</p>
                  </div>

                  {stories.length > 1 && (
                    <button
                      type="button" className="bs-arrow bs-arrow--next"
                      onClick={() => setStoryIndex(step(stories, storyIndex, 1))}
                      aria-label="Next story"
                    >
                      <FaChevronRight />
                    </button>
                  )}
                </div>
              </Col>
            </Row>
          </Container>
        </section>
      )}

      {/* ---------- YOUR JOURNEY ---------- */}
      {journey.length > 0 && (
        <section className="bs-sec">
          <Container>
            <Heading dark={band("journey")?.dark} blue={band("journey")?.blue} className="bs-h2--left" />
            {band("journey")?.intro && (
              <p className="bs-intro bs-intro--left">{band("journey").intro}</p>
            )}

            <Row className="g-3 g-md-4">
              {journey.map((j, i) => (
                <Col xs={12} sm={6} md={4} lg key={j.id}>
                  <div className="bs-journey">
                    <div className="bs-journey__art">
                      {j.image_path
                        ? <img src={resolveImageUrl(j.image_path)} alt="" loading="lazy" />
                        : <span className="bs-journey__num">{i + 1}</span>}
                    </div>
                    <div className="bs-journey__body">
                      <h3 className="bs-journey__title">{j.title}</h3>
                      <p className="bs-journey__text mb-0">{j.content}</p>
                    </div>
                  </div>
                </Col>
              ))}
            </Row>

            <div className="text-center mt-4">
              <Link to="/contact" className="bs-btn bs-btn--ghost">
                {label("journey_cta", "Download Launch Kit")}
              </Link>
            </div>
          </Container>
        </section>
      )}

      {/* ---------- GROWTH TOOLS ---------- */}
      {tools.length > 0 && (
        <section className="bs-sec bs-sec--tint bs-tools">
          <Container>
            <Heading dark={band("tools")?.dark} blue={band("tools")?.blue} className="bs-h2--left" />
            {band("tools")?.intro && (
              <p className="bs-intro bs-intro--left">{band("tools").intro}</p>
            )}

            {label("tools_watermark") && (
              <div className="bs-watermark" aria-hidden="true">{label("tools_watermark")}</div>
            )}

            <Row className="g-3">
              {tools.map((t, i) => {
                const Icon = TOOL_ICONS[i % TOOL_ICONS.length];
                return (
                  <Col xs={12} md={6} lg={4} key={t.id}>
                    <div className="bs-card h-100">
                      <div className="bs-card__head">
                        {t.image_path
                          ? <img className="bs-card__img" src={resolveImageUrl(t.image_path)} alt="" loading="lazy" />
                          : <Icon className="bs-card__icon" />}
                        <h3 className="bs-card__title">{t.title}</h3>
                      </div>
                      <p className="bs-card__text">{t.content}</p>
                      <Link to={t.extra || "/contact"} className="bs-learn">Learn More</Link>
                    </div>
                  </Col>
                );
              })}
            </Row>
          </Container>
        </section>
      )}

      {/* ---------- PLATFORM PEEK ---------- */}
      {platform.length > 0 && (
        <section className="bs-sec bs-sec--tint">
          <Container>
            <Heading dark={band("platform")?.dark} blue={band("platform")?.blue} className="bs-h2--left" />

            <div className="bs-peek-wrap">
              {platform.length > 1 && (
                <button
                  type="button" className="bs-arrow bs-arrow--prev"
                  onClick={() => setSlideIndex(step(platform, slideIndex, -1))}
                  aria-label="Previous slide"
                >
                  <FaChevronLeft />
                </button>
              )}

              <div className="bs-peek">
                <Row className="g-4 align-items-center">
                  <Col xs={12} md={5}>
                    <h3 className="bs-peek__title">{slide.title}</h3>
                    <p className="bs-peek__text">{slide.content}</p>
                    {slide.extra && (
                      <Link to="/contact" className="bs-btn bs-btn--ghost">{slide.extra}</Link>
                    )}
                  </Col>
                  <Col xs={12} md={7}>
                    <div className="bs-peek__art">
                      {slide.image_path
                        ? <img src={resolveImageUrl(slide.image_path)} alt={slide.title} loading="lazy" />
                        : <div className="bs-peek__placeholder" aria-hidden="true" />}
                    </div>
                  </Col>
                </Row>
              </div>

              {platform.length > 1 && (
                <button
                  type="button" className="bs-arrow bs-arrow--next"
                  onClick={() => setSlideIndex(step(platform, slideIndex, 1))}
                  aria-label="Next slide"
                >
                  <FaChevronRight />
                </button>
              )}
            </div>
          </Container>
        </section>
      )}

      {/* ---------- HELP FORM ---------- */}
      <section className="bs-sec">
        <Container>
          <Heading dark={band("help")?.dark} blue={band("help")?.blue} className="bs-h2--left" />
          {band("help")?.intro && (
            <p className="bs-intro bs-intro--left">{band("help").intro}</p>
          )}

          <Row className="g-4 align-items-center">
            <Col xs={12} lg={5}>
              {sent ? (
                <div className="bs-sent">
                  <h3 className="bs-card__title mb-2">Query received</h3>
                  <p className="bs-card__text mb-3">
                    Thanks for getting in touch. Our seller team will reply shortly.
                  </p>
                  <button type="button" className="bs-btn bs-btn--ghost" onClick={() => setSent(false)}>
                    Send another query
                  </button>
                </div>
              ) : (
                <Form onSubmit={submitQuery} noValidate>
                  <Form.Control
                    className="bs-field" placeholder="Enter Full Name *"
                    value={query.name} onChange={(e) => setQuery({ ...query, name: e.target.value })}
                    required
                  />
                  <Form.Control
                    className="bs-field" placeholder="Enter Mobile Number / Email ID *"
                    value={query.contact} onChange={(e) => setQuery({ ...query, contact: e.target.value })}
                    required
                  />
                  <Form.Select
                    className="bs-field"
                    value={query.topic} onChange={(e) => setQuery({ ...query, topic: e.target.value })}
                  >
                    <option value="">Select A Topic</option>
                    {topics.map((t) => (
                      <option key={t.id}>{t.title}</option>
                    ))}
                  </Form.Select>
                  <Form.Control
                    as="textarea" rows={4} className="bs-field"
                    placeholder="Type Your Message *"
                    value={query.message} onChange={(e) => setQuery({ ...query, message: e.target.value })}
                    required
                  />
                  <button type="submit" className="bs-btn" disabled={sending}>
                    {sending
                      ? <><Spinner size="sm" animation="border" /> Sending…</>
                      : label("form_submit", "Send Query")}
                  </button>
                </Form>
              )}
            </Col>

            <Col xs={12} lg={7} className="d-none d-lg-block">
              <Aside band="help" icon={FaHeadset} className="bs-aside--support" />
            </Col>
          </Row>
        </Container>
      </section>
    </div>
  );
};

export default BecomeASeller;
