import React, { useState } from "react";
import { IoChevronDown } from "react-icons/io5";

/** A heading plus a simple accordion of question/answer pairs. */
const FaqPattern = ({ data }) => {
  const items = Array.isArray(data.items) ? data.items : [];
  const [openIndex, setOpenIndex] = useState(null);

  return (
    <div className="bg-white p-3 rounded-3 border">
      {data.heading && <h5 className="fw-bold mb-3">{data.heading}</h5>}
      {items.map((item, idx) => {
        const isOpen = openIndex === idx;
        return (
          <div key={idx} className="border-bottom py-2">
            <button
              type="button"
              className="d-flex w-100 justify-content-between align-items-center bg-transparent border-0 text-start fw-medium"
              onClick={() => setOpenIndex(isOpen ? null : idx)}
            >
              <span>{item.question}</span>
              <IoChevronDown style={{ transform: isOpen ? "rotate(180deg)" : "none", transition: "transform .2s" }} />
            </button>
            {isOpen && (
              <div
                className="text-secondary mt-2 small"
                dangerouslySetInnerHTML={{ __html: item.answer_html || "" }}
              />
            )}
          </div>
        );
      })}
    </div>
  );
};

export default FaqPattern;
