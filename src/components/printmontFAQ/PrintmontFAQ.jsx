import React, { useState, useEffect } from 'react';
import 'bootstrap/dist/css/bootstrap.min.css';
import { API_ENDPOINTS } from '../../config/apiEndpoints';

const PrintmontFAQ = () => {
  const [faqs, setFaqs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [openItem, setOpenItem] = useState(null);

  useEffect(() => {
    const fetchFaqs = async () => {
      try {
        const response = await fetch(API_ENDPOINTS.FAQ);
        if (!response.ok) throw new Error("Network response was not ok");
        const json = await response.json();
        if (json && json.success && Array.isArray(json.data)) {
          setFaqs(json.data);
        }
      } catch (error) {
        console.error("Error fetching FAQs:", error);
      } finally {
        setLoading(false);
      }
    };
    fetchFaqs();
  }, []);

  const toggleAccordion = (id) => {
    setOpenItem(openItem === id ? null : id);
  };

  const activeFaqs = faqs.filter(item => item.is_active === "1");

  return (
    <div className='bg-white py-4'>
      <div className="container bg-white">
        <h2 className="text-center mb-4">FAQs</h2>
        {loading ? (
          <p className="text-muted text-center py-4">Loading FAQs...</p>
        ) : activeFaqs.length === 0 ? (
          <p className="text-muted text-center py-4">No FAQs available at this time.</p>
        ) : (
          <div className="accordion" id="printmontFaqAccordion">
            {activeFaqs.map((item) => (
              <div className="accordion-item" key={item.id}>
                {/* Accordion Header/Question */}
                <h3 className="accordion-header">
                  <button
                    className={`accordion-button ${openItem !== item.id ? 'collapsed' : ''}`}
                    type="button"
                    onClick={() => toggleAccordion(item.id)}
                    aria-expanded={openItem === item.id}
                    aria-controls={`collapse-${item.id}`}
                  >
                    {item.question}
                  </button>
                </h3>

                {/* Accordion Body/Answer */}
                <div
                  id={`collapse-${item.id}`}
                  className={`accordion-collapse collapse ${openItem === item.id ? 'show' : ''}`}
                  data-bs-parent="#printmontFaqAccordion"
                >
                  <div className="accordion-body">
                    {item.answer}
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default PrintmontFAQ;