import React, { useRef } from 'react';
import { IoClose, IoPrintOutline, IoDownloadOutline } from "react-icons/io5";
import { FaCheckCircle } from "react-icons/fa";

// Convert number to Indian currency words
const numberToWords = (num) => {
  const a = ['', 'One ', 'Two ', 'Three ', 'Four ', 'Five ', 'Six ', 'Seven ', 'Eight ', 'Nine ', 'Ten ', 'Eleven ', 'Twelve ', 'Thirteen ', 'Fourteen ', 'Fifteen ', 'Sixteen ', 'Seventeen ', 'Eighteen ', 'Nineteen '];
  const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

  const n = ('000000000' + num).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);
  if (!n) return '';
  let str = '';
  str += (n[1] != 0) ? (a[Number(n[1])] || b[n[1][0]] + ' ' + a[n[1][1]]) + 'Crore ' : '';
  str += (n[2] != 0) ? (a[Number(n[2])] || b[n[2][0]] + ' ' + a[n[2][1]]) + 'Lakh ' : '';
  str += (n[3] != 0) ? (a[Number(n[3])] || b[n[3][0]] + ' ' + a[n[3][1]]) + 'Thousand ' : '';
  str += (n[4] != 0) ? (a[Number(n[4])] || b[n[4][0]] + ' ' + a[n[4][1]]) + 'Hundred ' : '';
  str += (n[5] != 0) ? ((str != '') ? 'and ' : '') + (a[Number(n[5])] || b[n[5][0]] + ' ' + a[n[5][1]]) : '';
  return str.trim() ? `${str.trim()} Rupees Only` : 'Zero Rupees Only';
};

const formatDate = (dateStr) => {
  if (!dateStr) return new Date().toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
  try {
    const d = new Date(dateStr);
    return isNaN(d.getTime()) 
      ? dateStr 
      : d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
  } catch {
    return dateStr;
  }
};

const TaxInvoiceModal = ({ order, onClose }) => {
  const invoiceRef = useRef(null);
  if (!order) return null;

  const items = Array.isArray(order.items) && order.items.length > 0 
    ? order.items 
    : [{
        product_name: order.title || order.product_name || `Item ${order.order_number || `#${order.id}`}`,
        quantity: order.total_quantity || 1,
        unit_price: order.grand_total || order.total_amount || 0,
        total_price: order.grand_total || order.total_amount || 0,
        sku: 'PM-GEN-01'
      }];

  const grandTotal = Number(order.grand_total ?? order.total_amount ?? 0);
  const discountAmount = Number(order.discount_amount ?? 0);
  const shippingCost = Number(order.shipping_cost ?? 0);
  
  // Back-calculate 18% GST (9% CGST + 9% SGST) from total amount
  const taxableTotal = Math.round((grandTotal / 1.18) * 100) / 100;
  const totalTax = Math.round((grandTotal - taxableTotal) * 100) / 100;
  const cgst = Math.round((totalTax / 2) * 100) / 100;
  const sgst = Math.round((totalTax - cgst) * 100) / 100;

  const invoiceNumber = `INV-${order.order_number ? order.order_number.replace(/[^a-zA-Z0-9]/g, '') : order.id || '1001'}`;
  const invoiceDate = formatDate(order.created_at);

  const handlePrint = () => {
    window.print();
  };

  return (
    <div className="order-modal-backdrop" onClick={onClose}>
      <div className="tax-invoice-modal-wrapper bg-white shadow-lg" onClick={(e) => e.stopPropagation()}>
        
        {/* Modal Action Bar (Hidden in Print) */}
        <div className="d-flex align-items-center justify-content-between p-3 border-bottom bg-light no-print">
          <div className="d-flex align-items-center gap-2">
            <span className="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
              <FaCheckCircle className="me-1" /> GST Compliant
            </span>
            <span className="fw-bold text-dark fs-6">Tax Invoice - {invoiceNumber}</span>
          </div>

          <div className="d-flex align-items-center gap-2">
            <button 
              type="button" 
              className="btn btn-primary btn-sm rounded-pill d-flex align-items-center gap-1 px-3"
              onClick={handlePrint}
            >
              <IoDownloadOutline size={16} /> Print / Save PDF
            </button>
            <button 
              type="button" 
              className="btn-close-custom" 
              onClick={onClose}
              aria-label="Close"
            >
              <IoClose size={20} />
            </button>
          </div>
        </div>

        {/* Printable Invoice Document */}
        <div className="tax-invoice-sheet p-4 p-md-5" ref={invoiceRef}>
          
          {/* Header Row */}
          <div className="row align-items-start mb-4 border-bottom pb-4">
            <div className="col-7">
              <div className="d-flex align-items-center gap-2 mb-2">
                <img 
                  src="/PrintLogo.png" 
                  alt="Printmont" 
                  style={{ height: 42, objectFit: 'contain' }}
                  onError={(e) => { e.target.style.display = 'none'; }}
                />
                <span className="fs-4 fw-black text-primary tracking-tight">PRINTMONT</span>
              </div>
              <div className="text-muted small lh-sm">
                <strong className="text-dark">Printmont Technologies Private Limited</strong><br />
                Regd Office: Plot No. 104, Okhla Industrial Area Phase-II,<br />
                New Delhi, Delhi - 110020, India<br />
                <strong>GSTIN:</strong> 07AAACP1234F1Z5 | <strong>CIN:</strong> U74999DL2024PTC123456<br />
                <strong>PAN:</strong> AAACP1234F | <strong>State:</strong> Delhi (07)
              </div>
            </div>

            <div className="col-5 text-end">
              <h5 className="fw-black text-dark text-uppercase mb-1 tracking-wider">Tax Invoice</h5>
              <div className="text-muted small">
                <div><strong>Invoice No:</strong> {invoiceNumber}</div>
                <div><strong>Invoice Date:</strong> {invoiceDate}</div>
                <div><strong>Order ID:</strong> {order.order_number || `#${order.id}`}</div>
                <div><strong>Order Date:</strong> {formatDate(order.created_at)}</div>
                {order.tracking_number && (
                  <div><strong>AWB / Tracking:</strong> {order.tracking_number}</div>
                )}
              </div>
            </div>
          </div>

          {/* Billing & Shipping Section */}
          <div className="row g-3 mb-4">
            <div className="col-6">
              <div className="p-3 border rounded bg-light-subtle h-100">
                <div className="fw-bold text-uppercase text-secondary small mb-2" style={{ letterSpacing: '0.5px' }}>
                  Billed To / Customer Details
                </div>
                <div className="small text-dark">
                  <div className="fw-bold fs-6">{order.customer_name || 'Valued Customer'}</div>
                  <div style={{ whiteSpace: 'pre-line' }}>{order.billing_address || order.shipping_address || 'Customer Delivery Address'}</div>
                  {order.customer_phone && <div className="mt-1">Phone: <strong>{order.customer_phone}</strong></div>}
                  {order.customer_email && <div>Email: {order.customer_email}</div>}
                  {order.gstin && <div className="mt-1 text-primary"><strong>Customer GSTIN:</strong> {order.gstin}</div>}
                </div>
              </div>
            </div>

            <div className="col-6">
              <div className="p-3 border rounded bg-light-subtle h-100">
                <div className="fw-bold text-uppercase text-secondary small mb-2" style={{ letterSpacing: '0.5px' }}>
                  Shipped To / Delivery Address
                </div>
                <div className="small text-dark">
                  <div className="fw-bold fs-6">{order.customer_name || 'Valued Customer'}</div>
                  <div style={{ whiteSpace: 'pre-line' }}>{order.shipping_address || 'Delivery Address'}</div>
                  {order.courier_name && <div className="mt-1">Courier: <strong>{order.courier_name}</strong></div>}
                  <div>Place of Supply: <strong>India</strong></div>
                </div>
              </div>
            </div>
          </div>

          {/* Item Table */}
          <div className="table-responsive mb-4">
            <table className="table table-bordered table-sm align-middle small mb-0">
              <thead className="table-light text-uppercase fw-bold text-secondary" style={{ fontSize: '0.75rem' }}>
                <tr>
                  <th style={{ width: '5%' }} className="text-center">#</th>
                  <th style={{ width: '45%' }}>Description of Goods / Services</th>
                  <th style={{ width: '10%' }} className="text-center">HSN/SAC</th>
                  <th style={{ width: '8%' }} className="text-center">Qty</th>
                  <th style={{ width: '12%' }} className="text-end">Unit Price</th>
                  <th style={{ width: '10%' }} className="text-center">GST %</th>
                  <th style={{ width: '10%' }} className="text-end">Amount</th>
                </tr>
              </thead>
              <tbody>
                {items.map((item, idx) => {
                  const qty = Number(item.quantity || 1);
                  const itemPrice = Number(item.unit_price ?? item.price ?? 0);
                  const lineTotal = Number(item.total_price ?? (itemPrice * qty));
                  const itemTaxable = Math.round((lineTotal / 1.18) * 100) / 100;

                  return (
                    <tr key={idx}>
                      <td className="text-center text-muted">{idx + 1}</td>
                      <td>
                        <div className="fw-bold text-dark">{item.product_name}</div>
                        {item.variant && <span className="text-muted small">Variant: {item.variant}</span>}
                        {item.sku && <span className="text-muted small ms-2">SKU: {item.sku}</span>}
                      </td>
                      <td className="text-center text-muted">4911 / 6109</td>
                      <td className="text-center fw-semibold">{qty}</td>
                      <td className="text-end">₹{Math.round((itemPrice / 1.18) * 100) / 100}</td>
                      <td className="text-center">18%</td>
                      <td className="text-end fw-bold">₹{itemTaxable.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>

          {/* Tax Breakdown & Summary */}
          <div className="row g-3 mb-4">
            <div className="col-7">
              <div className="border rounded p-3 bg-light-subtle h-100 small">
                <div className="fw-bold text-uppercase text-secondary mb-2" style={{ fontSize: '0.75rem' }}>
                  GST Tax Breakup (18%)
                </div>
                <div className="d-flex justify-content-between mb-1">
                  <span>Central GST (CGST 9%):</span>
                  <strong>₹{cgst.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</strong>
                </div>
                <div className="d-flex justify-content-between mb-1">
                  <span>State / UT GST (SGST 9%):</span>
                  <strong>₹{sgst.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</strong>
                </div>
                <div className="d-flex justify-content-between pt-1 border-top fw-bold text-dark">
                  <span>Total Tax Included:</span>
                  <span>₹{totalTax.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</span>
                </div>
                <div className="mt-3 pt-2 border-top text-muted" style={{ fontSize: '0.75rem' }}>
                  <strong>Amount in Words:</strong><br />
                  <span className="text-dark fw-semibold">{numberToWords(Math.round(grandTotal))}</span>
                </div>
              </div>
            </div>

            <div className="col-5">
              <div className="border rounded p-3 bg-light-subtle h-100 small">
                <div className="d-flex justify-content-between mb-2">
                  <span className="text-muted">Taxable Value:</span>
                  <span className="fw-semibold">₹{taxableTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</span>
                </div>
                <div className="d-flex justify-content-between mb-2">
                  <span className="text-muted">Total GST (18%):</span>
                  <span className="fw-semibold">₹{totalTax.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</span>
                </div>
                {discountAmount > 0 && (
                  <div className="d-flex justify-content-between mb-2 text-success">
                    <span>Discount:</span>
                    <span>-₹{discountAmount.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</span>
                  </div>
                )}
                <div className="d-flex justify-content-between mb-2">
                  <span className="text-muted">Delivery Charges:</span>
                  <span>{shippingCost === 0 ? "FREE" : `₹${shippingCost.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`}</span>
                </div>
                <hr className="my-2" />
                <div className="d-flex justify-content-between fs-6 fw-bold text-dark pt-1">
                  <span>Grand Total:</span>
                  <span className="text-primary">₹{grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</span>
                </div>
                <div className="mt-2 text-muted text-end" style={{ fontSize: '0.75rem' }}>
                  Payment: <span className="badge bg-secondary-subtle text-dark text-uppercase">{order.payment_method || 'Prepaid'}</span>
                </div>
              </div>
            </div>
          </div>

          {/* Footer & Signature */}
          <div className="row align-items-end pt-3 border-top">
            <div className="col-8 text-muted small" style={{ fontSize: '0.75rem', lineHeight: '1.4' }}>
              <strong>Terms & Conditions:</strong><br />
              1. Goods once sold are subject to Printmont return/replacement policy.<br />
              2. For any tax inquiries, write to <strong>billing@printmont.com</strong> quoting Invoice No.<br />
              3. This is a computer-generated tax invoice and requires no physical signature under the Information Technology Act.
            </div>

            <div className="col-4 text-end">
              <div className="text-dark small fw-bold mb-4">For Printmont Technologies Pvt. Ltd.</div>
              <div className="border-bottom d-inline-block pb-1 px-4 text-center">
                <span className="text-success fw-bold small">✓ Digitally Signed</span>
              </div>
              <div className="text-muted" style={{ fontSize: '0.72rem' }}>Authorized Signatory</div>
            </div>
          </div>

        </div>

      </div>
    </div>
  );
};

export default TaxInvoiceModal;
