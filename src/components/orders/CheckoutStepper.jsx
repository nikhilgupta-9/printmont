import React from 'react';
import { BsCheck2 } from 'react-icons/bs';

/**
 * One row of the vertical checkout accordion.
 *
 * The design stacks the steps as full-width bars in the main column: the step
 * being worked on is a solid blue bar, finished steps collapse to a white bar
 * with a tick and a CHANGE link, and the body of a finished step (the saved
 * address, say) sits underneath its own bar.
 *
 *   number    the digit in the leading square; omit for an unnumbered row
 *             such as the buyer header, which is not one of the steps
 *   title     bar text; uppercased by the stylesheet
 *   active    solid blue treatment
 *   done      tick after the title, CHANGE link when onChange is given
 *   aside     right-aligned text (used for "Buyer Mobile no.")
 *   onChange  renders the CHANGE button and calls back when clicked
 *   children  collapsed body under the bar, e.g. the saved address line
 */
const CheckoutStepBar = ({
  number = null,
  title,
  active = false,
  done = false,
  plainTitle = false,
  aside = null,
  onChange = null,
  children = null,
}) => (
  <>
    <div className={`step-bar${active ? ' step-bar--active' : ''}`}>
      {/* Unnumbered rows keep the square as a spacer so every bar's title
          lines up on the same left edge. */}
      <span className={`step-bar__num${number ? '' : ' step-bar__num--empty'}`}>
        {number}
      </span>

      <span className={`step-bar__title${plainTitle ? ' step-bar__title--plain' : ''}`}>
        {title}
      </span>

      {done && <BsCheck2 className="step-bar__check" size={18} strokeWidth={1} />}

      {aside && <span className="step-bar__aside">{aside}</span>}

      {onChange && (
        <button type="button" className="step-bar__change" onClick={onChange}>
          CHANGE
        </button>
      )}
    </div>

    {children && <div className="step-bar__body">{children}</div>}
  </>
);

export default CheckoutStepBar;
