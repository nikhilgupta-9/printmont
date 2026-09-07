// src/components/StarRating.js

import React from 'react';
import { FaStar, FaRegStar } from 'react-icons/fa';

/**
 * Reusable component for displaying or selecting a star rating.
 * @param {number} rating - The current rating value.
 * @param {function} onRate - Callback when a star is clicked (if interactive).
 * @param {boolean} isInteractive - If true, the stars can be clicked to set a rating.
 */
const StarRating = ({ rating, maxRating = 5, onRate, size = 20, isInteractive = false }) => {
  const stars = [];

  for (let i = 1; i <= maxRating; i++) {
    stars.push(
      <span
        key={i}
        // className='text-success'
        style={{ cursor: isInteractive ? 'pointer' : 'default', fontSize: size }}
        onClick={() => isInteractive && onRate(i)}
      >
        {i <= rating ? <FaStar className='text-success '/> : <FaStar color='rgb(225, 227, 230)'/>}
      </span>
    );
  }

  return <div>{stars}</div>;
};

export default StarRating;