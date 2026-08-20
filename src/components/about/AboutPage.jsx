import React, { useState, useEffect } from "react";
import { Container, Row, Col } from "react-bootstrap";
import { API_ENDPOINTS, ASSET_URL, resolveImageUrl } from "../../config/apiEndpoints";
import BannerGrid from "../home/banners/BannerGrid";
import useHomeBanners from "../home/hooks/useHomeBanners";
import "./About.css";

/** Section content is stored as plain text or light HTML depending on the row. */
const isHtml = (value) => /<\/?[a-z][\s\S]*>/i.test(String(value || ""));

const Text = ({ value, className = "" }) =>
  !value ? null : isHtml(value) ? (
    <div className={className} dangerouslySetInnerHTML={{ __html: value }} />
  ) : (
    <p className={className}>{value}</p>
  );

const imageFor = (section) => {
  if (!section?.image_path) return null;
  return section.image_path.startsWith("http")
    ? section.image_path
    : resolveImageUrl(section.image_path);
};

const teamImage = (member) => {
  if (!member?.image_path) return null;
  return member.image_path.startsWith("http")
    ? member.image_path
    : `${ASSET_URL}${String(member.image_path).replace(/^\//, "")}`;
};

/**
 * One laurel branch, mirrored by CSS for the right-hand side. The previous
 * version hand-drew both branches for every award — 43 <path> elements in all.
 */
const Laurel = ({ flip = false }) => (
  <svg
    className={`laurel__branch${flip ? " laurel__branch--flip" : ""}`}
    viewBox="0 0 40 85" fill="none" aria-hidden="true"
  >
    <path d="M32,78 C20,70 11,52 11,35 C11,20 16,5 17,4" stroke="#f7c948" strokeWidth="2.5" strokeLinecap="round" />
    {[
      [17, 4, -10], [17, 4, 40], [14, 13, -25], [16, 14, 35],
      [12, 23, -35], [14, 24, 25], [12, 33, -35], [14, 34, 25],
      [14, 43, -35], [16, 44, 25], [18, 53, -35], [20, 54, 25],
    ].map(([x, y, r], i) => (
      <g key={i} transform={`translate(${x}, ${y}) rotate(${r})`}>
        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#f7c948" />
      </g>
    ))}
  </svg>
);

const AboutPage = () => {
  const [sections, setSections] = useState([]);
  const [team, setTeam] = useState([]);
  const [loading, setLoading] = useState(true);

  const { banners: accoladeBanners } = useHomeBanners(API_ENDPOINTS.BANNERS, "about_accolades");
  const { banners: timelineBanners } = useHomeBanners(API_ENDPOINTS.BANNERS, "about_timeline");

  useEffect(() => {
    window.scrollTo(0, 0);
    let cancelled = false;

    const fetchAboutData = async () => {
      try {
        const response = await fetch(API_ENDPOINTS.ABOUT);
        if (!response.ok) throw new Error("Network response was not ok");
        const json = await response.json();
        if (cancelled) return;

        if (json?.success) {
          setSections(Array.isArray(json.data) ? json.data.filter((s) => Number(s.is_active) !== 0) : []);
          setTeam(Array.isArray(json.team) ? json.team.filter((t) => Number(t.is_active) !== 0) : []);
        }
      } catch (error) {
        console.error("Error fetching About data:", error);
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    fetchAboutData();
    return () => { cancelled = true; };
  }, []);

  const one = (type) => sections.find((s) => s.section_type === type) || null;
  const many = (type) => sections.filter((s) => s.section_type === type);

  const hero = one("hero");
  const story = one("story");
  const highlight = one("highlight");
  const vision = one("history");
  const mission = one("mission");
  const features = many("feature");
  const stats = many("stat");
  const accolades = many("accolade");

  if (loading) {
    return (
      <div className="about-fnp-page">
        <div className="shimmer-bg" style={{ height: "60vh" }} />
        <Container className="py-5">
          <div className="shimmer-bg rounded mb-3" style={{ height: 120 }} />
          <div className="shimmer-bg rounded" style={{ height: 260 }} />
        </Container>
      </div>
    );
  }

  return (
    <div className="about-fnp-page">

      {/* 🌸 HERO BANNER WITH OVERLAY */}
      {hero && (
        <div
          className="fnp-hero-banner-new d-flex align-items-center"
          style={imageFor(hero) ? { backgroundImage: `url('${imageFor(hero)}')` } : undefined}
        >
          <div className="fnp-hero-overlay" />
          <Container className="position-relative">
            <div className="fnp-hero-text-wrap text-white text-center mx-auto">
              <p className="fnp-hero-subtitle text-uppercase mb-2">About Printmont</p>
              <h1 className="fnp-hero-main-title mb-3">
                <span className="highlight-text">{hero.section_title}</span>
              </h1>
              <Text value={hero.section_content} className="mb-0 fs-6 text-white-50" />
            </div>
          </Container>
        </div>
      )}

      {/* 📜 HOW IT ALL STARTED */}
      {story && (
        <div className="fnp-how-it-started py-5 bg-white">
          <Container>
            <Row className="justify-content-center">
              <Col lg={9} className="text-center">
                <h2 className="fnp-theme-heading mb-4">{story.section_title}</h2>
                <Text value={story.section_content} className="fnp-started-desc mb-0" />
              </Col>
            </Row>
          </Container>
        </div>
      )}

      {/* 📦 ICE-BLUE SPLIT PANEL WITH DELIVERY BADGE */}
      {highlight && (
        <div className="fnp-beige-section py-5">
          <Container>
            <div className="fnp-beige-card rounded-3 overflow-hidden">
              <Row className="g-0 align-items-stretch">
                <Col md={5}>
                  <div className="fnp-beige-img-wrap">
                    {imageFor(highlight) && (
                      <img src={imageFor(highlight)} alt={highlight.section_title} loading="lazy" />
                    )}
                    <div className="fnp-delivery-badge">
                      <div className="fnp-badge-inner">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                          <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16zm1-13h-2v6l5 3 1-1.7-4-2.3V7z" />
                        </svg>
                        <span className="fnp-badge-txt">DELIVERY WORLDWIDE</span>
                      </div>
                    </div>
                  </div>
                </Col>
                <Col md={7}>
                  <div className="p-4 p-md-5">
                    <h2 className="fnp-theme-heading mb-3">{highlight.section_title}</h2>
                    <Text value={highlight.section_content} className="fnp-started-desc mb-0" />
                  </div>
                </Col>
              </Row>
            </div>
          </Container>
        </div>
      )}

      {/* ⭐ THE PERFECT SURPRISE */}
      {features.length > 0 && (
        <div className="fnp-perfect-surprise py-5 bg-white">
          <Container>
            <h2 className="fnp-theme-heading text-center mb-5">The Perfect Surprise</h2>
            <Row className="justify-content-center gy-4">
              {features.map((f) => (
                <Col xs={12} md={4} key={f.id} className="text-center">
                  <div className="fnp-surprise-card h-100">
                    <div className="fnp-surprise-icon-wrap mx-auto mb-3">
                      {imageFor(f) ? (
                        <img
                          src={imageFor(f)}
                          alt=""
                          style={{ maxWidth: "100%", maxHeight: "100%", objectFit: "contain" }}
                          loading="lazy"
                        />
                      ) : (
                        <svg viewBox="0 0 64 64" fill="none" width="56" height="56" aria-hidden="true">
                          <circle cx="32" cy="32" r="30" fill="rgba(11, 83, 161, 0.08)" />
                          <path
                            d="M20 28h24v18H20V28zm-2-8h28v8H18v-8zm14 0v26"
                            stroke="rgb(11, 83, 161)"
                            strokeWidth="2.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                          />
                        </svg>
                      )}
                    </div>
                    <h5 className="fnp-surprise-title fw-bold mb-2">{f.section_title}</h5>
                    <Text value={f.section_content} className="fnp-started-desc mb-0" />
                  </div>
                </Col>
              ))}
            </Row>
          </Container>
        </div>
      )}

      {/* 👁️ VISION & MISSION */}
      {(vision || mission) && (
        <div className="fnp-vision-mission-cards py-5 fnp-beige-section">
          <Container>
            <Row className="g-4">
              {[
                { data: vision, cls: "fnp-vision-card" },
                { data: mission, cls: "fnp-mission-card" },
              ].filter((c) => c.data).map(({ data, cls }) => (
                <Col md={6} key={data.id}>
                  <div className={`${cls} bg-white rounded-3 overflow-hidden h-100 shadow-sm`}>
                    {imageFor(data) && (
                      <img
                        src={imageFor(data)}
                        alt={data.section_title}
                        className="w-100"
                        style={{ height: "220px", objectFit: "cover" }}
                        loading="lazy"
                      />
                    )}
                    <div className="p-4">
                      <h3 className="fnp-theme-heading mb-3" style={{ fontSize: "1.35rem" }}>
                        {data.section_title}
                      </h3>
                      <Text value={data.section_content} className="fnp-started-desc mb-0" />
                    </div>
                  </div>
                </Col>
              ))}
            </Row>
          </Container>
        </div>
      )}

      {/* 📅 OUR JOURNEY TIMELINE — banner managed */}
      {timelineBanners.length > 0 && (
        <BannerGrid banners={timelineBanners} columns={1} mobileColumns={1} />
      )}

      {/* 🏆 ACCOLADES & MILESTONES — laurel wreaths around each award name */}
      {(accolades.length > 0 || accoladeBanners.length > 0) && (
        <div className="fnp-accolades-section py-5">
          <Container>
            <h2 className="fnp-theme-heading text-center mb-5">Accolades &amp; Milestones</h2>

            {accolades.length > 0 ? (
              <Row className="gy-5 justify-content-center">
                {accolades.map((a) => (
                  <Col xs={12} md={6} lg={4} key={a.id}>
                    <div className="laurel">
                      <Laurel />
                      <span className="laurel__text">{a.section_title}</span>
                      <Laurel flip />
                    </div>
                  </Col>
                ))}
              </Row>
            ) : (
              <BannerGrid banners={accoladeBanners} columns={3} mobileColumns={1} />
            )}
          </Container>
        </div>
      )}

      {/* 👥 MEET THE TEAM */}
      {team.length > 0 && (
        <div className="fnp-leadership-grid-section py-5 bg-white">
          <Container>
            <h2 className="fnp-theme-heading text-center mb-5">Meet the Team</h2>
            <Row className="g-3 g-md-4 justify-content-center">
                {team.map((member) => (
                  <Col xs={6} sm={4} lg={3} key={member.id}>
                    <div className="fnp-leader">
                      <div className="fnp-leader__photo">
                      {teamImage(member) ? (
                        <img
                          src={teamImage(member)}
                          alt={member.name}
                          loading="lazy"
                          onError={(e) => { e.target.style.visibility = "hidden"; }}
                        />
                      ) : (
                        <div className="fnp-leader__fallback">
                          <span>
                            {String(member.name || "?")
                              .split(" ").map((w) => w[0]).slice(0, 2).join("").toUpperCase()}
                          </span>
                        </div>
                      )}
                      </div>
                      <h3 className="fnp-leader__name">{member.name}</h3>
                      <p className="fnp-leader__role">{member.position}</p>
                    </div>
                  </Col>
                ))}
            </Row>
          </Container>
        </div>
      )}

      {/* 📊 STATS COUNTER BAR */}
      {stats.length > 0 && (
        <div className="fnp-green-stats-bar py-5">
          <Container>
            <Row className="text-center gy-4 gy-md-0">
              {stats.map((s) => (
                <Col xs={6} md={12 / Math.min(stats.length, 4)} key={s.id} className="fnp-stats-col">
                  <h3 className="fnp-stats-num-white text-white mb-2">{s.section_title}</h3>
                  <p className="fnp-stats-label-gold mb-0">{s.section_content}</p>
                </Col>
              ))}
            </Row>
          </Container>
        </div>
      )}
    </div>
  );
};

export default AboutPage;
