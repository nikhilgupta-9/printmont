import React from "react";
import { Container } from "react-bootstrap";
import usePageSections from "../pages/usePageSections";
import "../pages/marketing-page.css";

/**
 * One prose block: an optional lead-in line, then a bulleted list where each
 * row reads "Title - content".
 */
const BulletBlock = ({ lead, rows }) => {
  if (!rows.length) return null;

  return (
    <>
      {lead && <p className="mk-lead-in">{lead}</p>}
      <ul className="mk-list">
        {rows.map((r) => (
          <li key={r.id}>
            {r.content ? (
              <>
                <span className="mk-list__term">{r.title}</span>
                {" - "}
                {r.content}
              </>
            ) : (
              r.title
            )}
          </li>
        ))}
      </ul>
    </>
  );
};

const BusinessSolutions = () => {
  const { many, one, loading, failed } = usePageSections("business-solutions");

  const hero = one("hero");
  const features = many("feature");
  const steps = many("step");
  const contact = one("contact");

  if (loading) {
    return (
      <div className="mk-page">
        <Container className="mk-article">
          <div className="shimmer-bg rounded mb-4" style={{ height: 34, width: "45%" }} />
          <div className="shimmer-bg rounded mb-3" style={{ height: 90 }} />
          <div className="shimmer-bg rounded" style={{ height: 220 }} />
        </Container>
      </div>
    );
  }

  if (failed) {
    return (
      <div className="mk-page">
        <Container className="mk-article">
          <h1 className="mk-page-title"><span>Business</span> Solutions</h1>
          <p className="mk-para">This page is unavailable right now. Please refresh in a moment.</p>
        </Container>
      </div>
    );
  }

  return (
    <div className="mk-page">
      <Container className="mk-article">

        <h1 className="mk-page-title"><span>Business</span> Solutions</h1>

        {hero && (
          <>
            <h2 className="mk-sub">{hero.title}</h2>
            {hero.content && <p className="mk-para">{hero.content}</p>}
          </>
        )}

        <BulletBlock
          lead={features.length ? "What we do for businesses:" : null}
          rows={features}
        />

        <BulletBlock
          lead={steps.length ? `${steps.length} Simple Steps to Get Started:` : null}
          rows={steps}
        />

        {contact ? (
          <p className="mk-para mk-para--close">
            {contact.title}
            {contact.extra && (
              <>
                {" "}
                <a href={`mailto:${contact.extra}`} className="mk-mail">{contact.extra}</a>
              </>
            )}
          </p>
        ) : (
          <p className="mk-para mk-para--close">
            To discuss a bulk requirement, please <a href="/contact" className="mk-mail">contact our team</a>.
          </p>
        )}
      </Container>
    </div>
  );
};

export default BusinessSolutions;
