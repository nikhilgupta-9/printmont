import React, { useState } from "react";
import { Container, Row, Col, Card, Form } from "react-bootstrap";
import { FaThumbsUp, FaThumbsDown, FaUser, FaSearch } from "react-icons/fa";
import { Link } from "react-router";

const ProductQASection = () => {
  const [questions, setQuestions] = useState([
    {
      id: 1,
      question: "Does it support reverse charging?",
      answer:
        "Yes, the realme P4 Pro 5G does support only wired reverse charging. Thank you!",
      author: "PETILANTE Online",
      seller: "Printmont Seller",
      likes: 119,
      dislikes: 7,
      userReaction: null, // "like" | "dislike" | null
    },
    {
      id: 2,
      question: "What are the WIFI bands used in realme P4 Pro 5G?",
      answer: "realme P4 Pro uses 2×2 MIMO, WiFi 2.4G, WiFi 5G.",
      author: "PETILANTE Online",
      seller: "Printmont Seller",
      likes: 51,
      dislikes: 4,
      userReaction: null,
    },
    {
      id: 3,
      question: "What is the network type?",
      answer: "realme P4 Pro 5G has 5G network type.",
      author: "PETILANTE Online",
      seller: "Printmont Seller",
      likes: 96,
      dislikes: 7,
      userReaction: null,
    },
  ]);

  const [search, setSearch] = useState("");

  // ------------------------
  // Like handler
  // ------------------------
  const handleLike = (id) => {
    setQuestions((prev) =>
      prev.map((q) => {
        if (q.id === id) {
          // Toggle logic
          if (q.userReaction === "like") {
            // Remove like
            return { ...q, likes: q.likes - 1, userReaction: null };
          } else if (q.userReaction === "dislike") {
            // Switch from dislike to like
            return {
              ...q,
              likes: q.likes + 1,
              dislikes: q.dislikes - 1,
              userReaction: "like",
            };
          } else {
            // Add new like
            return { ...q, likes: q.likes + 1, userReaction: "like" };
          }
        }
        return q;
      })
    );
  };

  // ------------------------
  // Dislike handler
  // ------------------------
  const handleDislike = (id) => {
    setQuestions((prev) =>
      prev.map((q) => {
        if (q.id === id) {
          if (q.userReaction === "dislike") {
            // Remove dislike
            return { ...q, dislikes: q.dislikes - 1, userReaction: null };
          } else if (q.userReaction === "like") {
            // Switch from like to dislike
            return {
              ...q,
              likes: q.likes - 1,
              dislikes: q.dislikes + 1,
              userReaction: "dislike",
            };
          } else {
            // Add new dislike
            return { ...q, dislikes: q.dislikes + 1, userReaction: "dislike" };
          }
        }
        return q;
      })
    );
  };

  const filtered = questions.filter((q) =>
    q.question.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <Container className="my-0 my-lg-4 border rounded p-4 bg-white">
      <Row className="align-items-center mb-3">
        <Col>
          <h4 className="fw-bold">Questions and Answers</h4>
        </Col>
        <Col xs="auto">
          <div className="d-flex align-items-center border rounded px-2 py-1">
            <FaSearch className="me-2 text-muted" />
            <Form.Control
              type="text"
              placeholder="Search..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="border-0 p-0 shadow-none"
              style={{ width: "200px" }}
            />
          </div>
        </Col>
      </Row>

      {filtered.map((item) => (
        <Card key={item.id} className="border-0 border-bottom rounded-0 mb-3">
          <Card.Body className="p-0 pb-3">
            <p className="fw-bold mb-1">
              Q: <span className="fw-semibold ">{item.question}</span>
            </p>
            <p className="mb-1 ">
              <strong>A:</strong> {item.answer}
            </p>
            <small className="text-secondary fw-semibold mb-1 txsm">{item.author}</small>
            <p className="text-muted txex d-flex align-items-center mb-2">
              <FaUser className="me-1" /> {item.seller}
            </p>
            <div className="d-flex justify-content-end align-items-center gap-3 text-muted small">
              <span
                className="d-flex align-items-center"
                style={{
                  cursor: "pointer",
                  color: item.userReaction === "like" ? "#007bff" : "gray",
                }}
                onClick={() => handleLike(item.id)}
              >
                <FaThumbsUp className="me-1" /> {item.likes}
              </span>
              <span
                className="d-flex align-items-center"
                style={{
                  cursor: "pointer",
                  color: item.userReaction === "dislike" ? "#dc3545" : "gray",
                }}
                onClick={() => handleDislike(item.id)}
              >
                <FaThumbsDown className="me-1" /> {item.dislikes}
              </span>
            </div>
          </Card.Body>
        </Card>
      ))}

      <Link
        to={'#'}
        className="text-primary fw-semibold mt-2 text-decoration-none"
        style={{ cursor: "pointer" }} 
      >
        All questions
      </Link>

      <div className="border-top mt-4 pt-3 text-center text-muted small">
        
        Safe and Secure Payments. Easy returns. 100% Authentic products.
      </div>
    </Container>
  );
};

export default ProductQASection;
