import React from "react";
import { Container } from "react-bootstrap";
import usePageSections from "./usePageSections";
import "./marketing-page.css";

/**
 * Renders one prose block: an optional lead-in line followed by a bulleted
 * list. Each row contributes "Title - content" as a single bullet, which is
 * how the reference page reads.
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

const AffiliateProgram = () => {
  const { many, one, loading, failed } = usePageSections("affiliate");

  const hero = one("hero");
  const highlights = many("highlight");
  const steps = many("step");
  const rates = many("rate");
  const faqs = many("faq");
  const contact = one("contact");

  if (loading) {
    return (
      <div className="mk-page">
        <Container className="mk-article">
          <div className="shimmer-bg rounded mb-4" style={{ height: 34, width: "40%" }} />
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
          <h1 className="mk-page-title"><span>Affiliate</span> Program</h1>
          <p className="mk-para">This page is unavailable right now. Please refresh in a moment.</p>
        </Container>
      </div>
    );
  }

  return (
    <div className="mk-page">
      <Container className="mk-article">

        {/* Ruled, two-tone page title */}
        <h1 className="mk-page-title"><span>Affiliate</span> Program</h1>

        {/* Intro */}
        {hero && (
          <>
            <h2 className="mk-sub">{hero.title}</h2>
            {hero.content && <p className="mk-para">{hero.content}</p>}
          </>
        )}

        {/* Steps */}
        <BulletBlock
          lead={steps.length ? `${steps.length} Simple Steps to Partner with us!` : null}
          rows={steps}
        />

        {/* Benefits */}
        <BulletBlock
          lead={highlights.length ? "Benefits of the Printmont Affiliate Program include:" : null}
          rows={highlights}
        />

        {/* Commission rates, as a plain list rather than a table */}
        {rates.length > 0 && (
          <>
            <p className="mk-lead-in">Commission rates by product category:</p>
            <ul className="mk-list">
              {rates.map((r) => (
                <li key={r.id}>
                  <span className="mk-list__term">{r.title}</span>
                  {r.extra ? ` - ${r.extra}` : null}
                </li>
              ))}
            </ul>
          </>
        )}

        {/* FAQs, kept as prose Q&A to match the rest of the page */}
        {faqs.length > 0 && (
          <>
            <p className="mk-lead-in">Frequently asked questions:</p>
            {faqs.map((f) => (
              <div className="mk-qa" key={f.id}>
                <p className="mk-qa__q">{f.title}</p>
                {f.content && <p className="mk-para mb-0">{f.content}</p>}
              </div>
            ))}
          </>
        )}

        {/* Closing line */}
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
            To become our partner, please <a href="/contact" className="mk-mail">contact our Affiliate Team</a>.
          </p>
        )}
      </Container>
    </div>
  );
};

export default AffiliateProgram;
