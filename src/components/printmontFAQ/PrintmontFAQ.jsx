import React, { useState } from 'react';
import 'bootstrap/dist/css/bootstrap.min.css'; // Make sure to import Bootstrap CSS in your main file or index.js

const PrintmontFAQ = () => {
  // Data structure for the FAQ. Updated company name to 'Printmont'.
  const faqData = [
    {
      id: 1,
      question: 'What is Printmont?',
      answer: 'Printmont is a powerful landing page and CRO platform that enables marketers to create high-converting pages with ease, helping to boost marketing campaign ROI. (Adapted from the original "Instapage" description.)',
    },
    {
      id: 2,
      question: 'How does Printmont work?',
      answer: 'Printmont allows users to build landing pages using a drag-and-drop editor, integrate with marketing tools, run A/B tests, and receive detailed analytics to optimize conversion rates.',
    },
    {
      id: 3,
      question: 'What are the key features of Printmont?',
      answer: 'Key features include a robust page builder, A/B testing, personalization, heatmaps/analytics, and collaboration tools.',
    },
    {
      id: 4,
      question: 'Why is Printmont a good choice for marketing campaigns?',
      answer: 'Printmont is designed specifically for maximizing conversion rates, offering faster page load speeds and superior optimization tools compared to general website builders.',
    },
    {
      id: 5,
      question: 'What are the benefits of using Printmont?',
      answer: 'Benefits include increased conversion rates, better campaign ROI, reduced time spent on design, and easy scalability of landing page efforts.',
    },
    {
      id: 6,
      question: 'What should marketers consider when using Printmont?',
      answer: 'Marketers should consider their primary need for high-converting landing pages, their budget, and how Printmont integrates with their existing marketing technology stack.',
    },
    {
      id: 7,
      question: 'What are common challenges when using landing page platforms?',
      answer: 'Common challenges include slow page load times, difficulty in A/B testing, limited design flexibility, and poor mobile optimization.',
    },
    {
      id: 8,
      question: 'How does Printmont address concerns related to landing page optimization?',
      answer: 'Printmont addresses these by offering a proprietary, fast page server, built-in A/B testing, and a highly customizable, mobile-responsive design environment.',
    },
  ];

  // State to manage which accordion item is currently open (Bootstrap default behavior)
  const [openItem, setOpenItem] = useState(null);

  const toggleAccordion = (id) => {
    setOpenItem(openItem === id ? null : id); // Close if open, otherwise open the new one
  };

  return (
    <div className='bg-white'>
        <div className="container bg-white">
      <h2 className="text-center mb-4">FAQs</h2>
      <div className="accordion" id="printmontFaqAccordion">
        {faqData.map((item) => (
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
    </div>
    </div>
  );
};

export default PrintmontFAQ;