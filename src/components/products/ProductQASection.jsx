import React, { useState, useEffect } from "react";
import { Form, Modal, Button, Spinner, Alert } from "react-bootstrap";
import { FaThumbsUp, FaThumbsDown, FaSearch, FaChevronDown, FaChevronUp, FaChevronRight, FaQuestionCircle, FaPaperPlane, FaCheckCircle, FaTimes } from "react-icons/fa";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import "./ProductQASection.css";

const ANSWER_PREVIEW_CHARS = 180;

const ProductQASection = ({ productId, isDesktop = false }) => {
  const [questions, setQuestions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isOpen, setIsOpen] = useState(false); // collapsed until the customer opens it
  const [search, setSearch] = useState("");
  const [allQuestionsModalOpen, setAllQuestionsModalOpen] = useState(false);
  
  // Ask Question Modal State
  const [askModalOpen, setAskModalOpen] = useState(false);
  const [newQuestion, setNewQuestion] = useState("");
  const [askName, setAskName] = useState("");
  const [askSubmitting, setAskSubmitting] = useState(false);
  const [askSuccessMsg, setAskSuccessMsg] = useState("");
  const [askErrorMsg, setAskErrorMsg] = useState("");
  const [expanded, setExpanded] = useState({});

  // Track user reactions in local storage so they persist across refreshes
  const [userReactions, setUserReactions] = useState(() => {
    try {
      const saved = localStorage.getItem("printmont_qa_reactions");
      return saved ? JSON.parse(saved) : {};
    } catch {
      return {};
    }
  });

  // Fetch Questions from backend
  useEffect(() => {
    let isMounted = true;
    const fetchQA = async () => {
      setLoading(true);
      try {
        const url = API_ENDPOINTS.PRODUCT_QA(productId);
        const res = await fetch(url);
        const json = await res.json();
        if (isMounted && json && json.success && Array.isArray(json.data)) {
          setQuestions(json.data);
        }
      } catch (err) {
        console.warn("Error fetching Q&A data:", err);
      } finally {
        if (isMounted) setLoading(false);
      }
    };

    fetchQA();
    return () => {
      isMounted = false;
    };
  }, [productId]);

  // ------------------------
  // Like handler
  // ------------------------
  const handleLike = async (id) => {
    const currentReaction = userReactions[id];
    const newReaction = currentReaction === "like" ? null : "like";

    // Optimistic UI update
    setQuestions((prev) =>
      prev.map((q) => {
        if (q.id === id) {
          let likes = parseInt(q.likes, 10) || 0;
          let dislikes = parseInt(q.dislikes, 10) || 0;

          if (currentReaction === "like") {
            likes = Math.max(0, likes - 1);
          } else if (currentReaction === "dislike") {
            likes += 1;
            dislikes = Math.max(0, dislikes - 1);
          } else {
            likes += 1;
          }
          return { ...q, likes, dislikes };
        }
        return q;
      })
    );

    const updatedReactions = { ...userReactions, [id]: newReaction };
    setUserReactions(updatedReactions);
    try {
      localStorage.setItem("printmont_qa_reactions", JSON.stringify(updatedReactions));
    } catch (e) {
      // ignore
    }

    if (newReaction === "like") {
      try {
        await fetch(API_ENDPOINTS.PRODUCT_QA_SUBMIT, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: "reaction", faq_id: id, type: "like" }),
        });
      } catch (e) {
        console.warn("Failed to record like:", e);
      }
    }
  };

  // ------------------------
  // Dislike handler
  // ------------------------
  const handleDislike = async (id) => {
    const currentReaction = userReactions[id];
    const newReaction = currentReaction === "dislike" ? null : "dislike";

    // Optimistic UI update
    setQuestions((prev) =>
      prev.map((q) => {
        if (q.id === id) {
          let likes = parseInt(q.likes, 10) || 0;
          let dislikes = parseInt(q.dislikes, 10) || 0;

          if (currentReaction === "dislike") {
            dislikes = Math.max(0, dislikes - 1);
          } else if (currentReaction === "like") {
            dislikes += 1;
            likes = Math.max(0, likes - 1);
          } else {
            dislikes += 1;
          }
          return { ...q, likes, dislikes };
        }
        return q;
      })
    );

    const updatedReactions = { ...userReactions, [id]: newReaction };
    setUserReactions(updatedReactions);
    try {
      localStorage.setItem("printmont_qa_reactions", JSON.stringify(updatedReactions));
    } catch (e) {
      // ignore
    }

    if (newReaction === "dislike") {
      try {
        await fetch(API_ENDPOINTS.PRODUCT_QA_SUBMIT, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: "reaction", faq_id: id, type: "dislike" }),
        });
      } catch (e) {
        console.warn("Failed to record dislike:", e);
      }
    }
  };

  // Submit new Question
  const handleAskSubmit = async (e) => {
    e.preventDefault();
    if (!newQuestion.trim()) return;

    setAskSubmitting(true);
    setAskSuccessMsg("");
    setAskErrorMsg("");

    try {
      const res = await fetch(API_ENDPOINTS.PRODUCT_QA_SUBMIT, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "ask",
          product_id: productId,
          question: newQuestion.trim(),
          name: askName.trim() || "Customer",
        }),
      });
      const data = await res.json();
      if (data && data.success) {
        setAskSuccessMsg("Your question has been submitted! Our product specialist will answer shortly.");
        // Add to local state display
        const submittedItem = {
          id: Date.now(),
          question: newQuestion.trim(),
          answer: "",
          author: askName.trim() || "You",
          seller: "",
          pending: true,
          likes: 0,
          dislikes: 0,
        };
        setQuestions((prev) => [submittedItem, ...prev]);
        setNewQuestion("");
        setAskName("");
        setTimeout(() => {
          setAskModalOpen(false);
          setAskSuccessMsg("");
        }, 2200);
      } else {
        setAskErrorMsg(data?.error || "Failed to submit question. Please try again.");
      }
    } catch (err) {
      setAskErrorMsg("Network error. Please try again later.");
    } finally {
      setAskSubmitting(false);
    }
  };

  const filtered = questions.filter((q) =>
    (q.question || "").toLowerCase().includes(search.toLowerCase()) ||
    (q.answer || "").toLowerCase().includes(search.toLowerCase())
  );

  const displayedList = filtered.slice(0, isDesktop ? 5 : 4);

  const renderItem = (item) => {
    const reaction = userReactions[item.id];
    const answer = (item.answer || "").trim();
    const isLong = answer.length > ANSWER_PREVIEW_CHARS;
    const showFull = expanded[item.id];
    const isSeller = !item.pending && (item.seller || "").length > 0;

    return (
      <div key={item.id} className="prq__item">
        <p className="prq__q">
          <span className="prq__label">Q:</span>
          <span>{item.question}</span>
        </p>
        {answer ? (
          <p className="prq__a">
            <span className="prq__label">A:</span>
            <span>
              {isLong && !showFull ? `${answer.slice(0, ANSWER_PREVIEW_CHARS).trim()}...` : answer}
              {isLong && (
                <button
                  type="button"
                  className="prq__more"
                  onClick={() => setExpanded((prev) => ({ ...prev, [item.id]: !showFull }))}
                >
                  {showFull ? "Read less" : "Read more"}
                </button>
              )}
            </span>
          </p>
        ) : (
          <p className="prq__a prq__a--pending">
            <span className="prq__label">A:</span>
            <span>Awaiting answer from the seller</span>
          </p>
        )}

        <div className="prq__foot">
          <div className="prq__meta">
            <span className="prq__author">{item.author || "Printmont Seller"}</span>
            {isSeller && (
              <span className="prq__badge">
                <FaCheckCircle size={10} /> {item.seller}
              </span>
            )}
          </div>
          {!item.pending && (
            <div className="prq__reactions">
              <button
                type="button"
                className={`prq__react ${reaction === "like" ? "is-like" : ""}`}
                onClick={() => handleLike(item.id)}
                aria-label="Helpful"
              >
                <FaThumbsUp size={11} /> {item.likes || 0}
              </button>
              <button
                type="button"
                className={`prq__react ${reaction === "dislike" ? "is-dislike" : ""}`}
                onClick={() => handleDislike(item.id)}
                aria-label="Not helpful"
              >
                <FaThumbsDown size={11} /> {item.dislikes || 0}
              </button>
            </div>
          )}
        </div>
      </div>
    );
  };

  return (
    <div className={`prq product-qa-wrapper ${isDesktop ? "prq--desktop" : ""}`}>
      {/* ===== Header ===== */}
      <div className="prq__head">
        <div onClick={() => setIsOpen(!isOpen)} style={{ cursor: "pointer" }}>
          <h2 className="prq__heading">Questions and Answers</h2>
          {!loading && (
            <div className="prq__sub">
              {questions.length === 0
                ? "No questions yet"
                : `${questions.length} question${questions.length === 1 ? "" : "s"}`}
            </div>
          )}
        </div>
        <button
          type="button"
          className="prq__toggle"
          onClick={() => setIsOpen(!isOpen)}
          aria-label={isOpen ? "Collapse questions" : "Expand questions"}
        >
          {isOpen ? <FaChevronUp size={12} /> : <FaChevronDown size={12} />}
        </button>
      </div>

      {isOpen && (
        <div className="prq__body">
          {loading ? (
            <div className="text-center py-4 text-muted small">
              <Spinner animation="border" size="sm" className="me-2 text-primary" />
              Loading questions & answers...
            </div>
          ) : (
            <>
              {questions.length > 0 && (
                <div className="prq__search">
                  <FaSearch size={13} />
                  <input
                    type="search"
                    placeholder="Search questions"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                  />
                  {search && (
                    <button type="button" className="prq__more" onClick={() => setSearch("")} aria-label="Clear search">
                      <FaTimes size={11} />
                    </button>
                  )}
                </div>
              )}

              {displayedList.length > 0 ? (
                <div className="prq__list">{displayedList.map(renderItem)}</div>
              ) : (
                <div className="prq__empty">
                  {questions.length === 0 ? "No questions have been asked yet." : "No questions match your search."}
                </div>
              )}

              {filtered.length > displayedList.length && (
                <button type="button" className="prq__all" onClick={() => setAllQuestionsModalOpen(true)}>
                  All {filtered.length} questions <FaChevronRight size={10} />
                </button>
              )}


              <div className="prq__trust">Safe and Secure Payments. Easy returns. 100% Authentic products.</div>
            </>
          )}
        </div>
      )}

          {/* Post a question — always visible, even while the Q&A list is collapsed */}
          <div className="prq__ask">
            <div>
              <div className="prq__ask-title">Have doubts regarding this product?</div>
              <div className="prq__ask-sub">Ask the seller and get an answer here</div>
            </div>
            <button type="button" className="prq__ask-btn" onClick={() => setAskModalOpen(true)}>
              <FaQuestionCircle size={12} /> Post your question
            </button>
          </div>

      {/* === Ask Question Modal === */}
      <Modal show={askModalOpen} onHide={() => setAskModalOpen(false)} centered>
        <Modal.Header closeButton className="border-bottom-0 pb-0">
          <Modal.Title className="fw-bold text-dark" style={{ fontSize: "1.1rem" }}>
            Ask a Question
          </Modal.Title>
        </Modal.Header>
        <Modal.Body className="pt-2">
          {askSuccessMsg && <Alert variant="success" className="py-2 small">{askSuccessMsg}</Alert>}
          {askErrorMsg && <Alert variant="danger" className="py-2 small">{askErrorMsg}</Alert>}
          <Form onSubmit={handleAskSubmit}>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold text-secondary">Your Name (Optional)</Form.Label>
              <Form.Control
                type="text"
                placeholder="Enter your name"
                value={askName}
                onChange={(e) => setAskName(e.target.value)}
                size="sm"
              />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold text-secondary">Your Question <span className="text-danger">*</span></Form.Label>
              <Form.Control
                as="textarea"
                rows={3}
                placeholder="e.g. Does this product include custom text engraving?"
                value={newQuestion}
                onChange={(e) => setNewQuestion(e.target.value)}
                required
                size="sm"
              />
            </Form.Group>
            <div className="d-flex justify-content-end gap-2">
              <Button variant="light" size="sm" onClick={() => setAskModalOpen(false)}>
                Cancel
              </Button>
              <Button variant="primary" size="sm" type="submit" disabled={askSubmitting || !newQuestion.trim()}>
                {askSubmitting ? (
                  <>
                    <Spinner animation="border" size="sm" className="me-1" /> Submitting...
                  </>
                ) : (
                  <>
                    <FaPaperPlane className="me-1" size={11} /> Submit Question
                  </>
                )}
              </Button>
            </div>
          </Form>
        </Modal.Body>
      </Modal>

      {/* === All Questions Modal === */}
      <Modal
        show={allQuestionsModalOpen}
        onHide={() => setAllQuestionsModalOpen(false)}
        scrollable
        centered
        size="lg"
      >
        <Modal.Header closeButton>
          <Modal.Title className="fw-bold text-dark" style={{ fontSize: "1.15rem" }}>
            All Questions & Answers ({questions.length})
          </Modal.Title>
        </Modal.Header>
        <Modal.Body className="bg-light p-3">
          <div className="d-flex align-items-center border rounded-2 px-3 py-2 bg-white mb-3 shadow-sm">
            <FaSearch className="me-2 text-muted" />
            <Form.Control
              type="text"
              placeholder="Search questions & answers..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="border-0 p-0 shadow-none"
              style={{ fontSize: "0.9rem" }}
            />
          </div>

          <div className="prq prq--desktop rounded-2 border py-0">
            {filtered.length > 0 ? filtered.map(renderItem) : (
              <div className="prq__empty">No questions match your search.</div>
            )}
          </div>
        </Modal.Body>
      </Modal>
    </div>
  );
};

export default ProductQASection;
