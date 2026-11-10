import React from "react";
import useContentBlocks from "./useContentBlocks";
import BrandDirectoryPattern from "./patterns/BrandDirectoryPattern";
import TextPattern from "./patterns/TextPattern";
import FaqPattern from "./patterns/FaqPattern";
import ImageTextPattern from "./patterns/ImageTextPattern";
import LinkGridPattern from "./patterns/LinkGridPattern";

const PATTERNS = {
  brand_directory: BrandDirectoryPattern,
  text: TextPattern,
  faq: FaqPattern,
  image_text: ImageTextPattern,
  link_grid: LinkGridPattern,
};

/**
 * Renders every admin-managed content block assigned to `pageKey`
 * (Website Content in admin), in the order the admin set, each in the
 * design pattern chosen for it. Drop this anywhere a page wants
 * admin-editable sections — the home page footer strip, a category
 * page's bottom block, anywhere else — just give it that page's key.
 */
const FooterContentBlocks = ({ pageKey, className = "" }) => {
  const { blocks, loading } = useContentBlocks(pageKey);

  if (loading || blocks.length === 0) return null;

  return (
    <div className={`w-100 p-0 m-0 d-flex flex-column gap-3 ${className}`}>
      {blocks.map((block) => {
        const Pattern = PATTERNS[block.pattern];
        if (!Pattern) return null;
        return <Pattern key={block.id} data={block.data || {}} />;
      })}
    </div>
  );
};

export default FooterContentBlocks;
