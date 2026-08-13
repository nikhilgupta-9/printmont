import React, { useState, useEffect } from 'react';
import './orders.css';
import { BsChevronDown, BsChevronUp } from 'react-icons/bs';
import OrderAddress from './OrderAddress';
import OrderSummary from './OrderSummary';
import PaymentGateway from './PaymentGateway';
import { Modal, Button, Form } from 'react-bootstrap';

import CheckoutStepBar from './CheckoutStepper';
import CartProductCard from './CartProductCard';
import PriceDetails from './PriceDetails';
import TrustBar from './TrustBar';
import PrintmontCoinsPromo from './PrintmontCoinsPromo';

import { useCheckout } from '../../context/CheckoutContext';
import { useAuth } from '../../context/AuthContext';
import { useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';

/**
 * The cart is not a step. Place Order starts the three numbered steps:
 *
 *   0  cart           (no step bars at all)
 *   1  Address
 *   2  Order Summary
 *   3  Payment
 *
 * Once checkout starts, the design stacks the steps as numbered bars in the
 * main column: the current one is a blue bar with its panel underneath, the
 * finished ones collapse to a white bar with a tick and a CHANGE link. The
 * buyer name/mobile row sits above them unnumbered, since it is a header
 * rather than a step of its own.
 */
const SCREEN_CART = 0;
const STEP_ADDRESS = 1;
const STEP_SUMMARY = 2;
const STEP_PAYMENT = 3;

const Cart = () => {
    const { user } = useAuth();
    const navigate = useNavigate();

    const {
        checkoutStep, setCheckoutStep,
        cartItems, isLoading,
        savedItems,
        cartTotals,
        buyerDetails,
        address,
        deliveryPincode, setDeliveryPincode,
    } = useCheckout();

    const [show, setShow] = useState(false);
    const [showSaved, setShowSaved] = useState(false);

    // Draft for the pincode modal; only a valid one is committed to context,
    // where the address step picks it up.
    const [pincode, setPincode] = useState(deliveryPincode);
    const [pincodeError, setPincodeError] = useState("");

    // Opening /cart always starts at the cart itself, never mid-checkout.
    useEffect(() => {
        setCheckoutStep(SCREEN_CART);
    }, [setCheckoutStep]);

    const goToStep = (step) => {
        setCheckoutStep(step);
        window.scrollTo(0, 0);
    };

    const openPincodeModal = () => {
        setPincode(deliveryPincode);
        setPincodeError("");
        setShow(true);
    };

    const closePincodeModal = () => {
        setPincodeError("");
        setShow(false);
    };

    const handlePincodeSubmit = (e) => {
        e.preventDefault();
        const entered = pincode.trim();
        if (!/^\d{6}$/.test(entered)) {
            setPincodeError("Enter a 6-digit pincode");
            return;
        }
        setDeliveryPincode(entered);
        closePincodeModal();
    };

    const handlePlaceOrder = () => {
        if (!user) {
            toast.error("Please login to proceed with your order!");
            navigate('/login?redirect=/cart');
            return;
        }
        goToStep(STEP_ADDRESS);
    };

    if (isLoading) {
        return <div className="text-center mt-5">Loading Cart...</div>;
    }

    const isCart = checkoutStep === SCREEN_CART;
    const hasItems = cartItems.length > 0;

    // The step-1 bar echoes back what the shopper entered on the address form.
    const buyerName = buyerDetails?.name || 'Buyer name';
    const buyerMobile = buyerDetails?.mobile || 'Buyer Mobile no.';

    const savedAddressLine = [
        buyerDetails?.name,
        address?.addressLine || address?.address || address?.addressArea,
        address?.landmark,
        address?.city,
        address?.state,
        address?.pincode && `- ${address.pincode}`,
    ].filter(Boolean).join(', ');

    return (
        <div className="checkout-page pb-5" style={{ minHeight: '100vh', backgroundColor: '#f1f3f6' }}>

            <div className="container p-0 py-md-4">
                <div className="row g-0 g-md-3">

                    <div className="col-12 col-lg-9 checkout-main-col order-md-1">

                        {/* ---------- CART ---------- */}
                        {isCart && (
                            <>
                                <div className="d-flex justify-content-between align-items-center bg-white p-2 p-lg-3 mb-2 border-bottom shadow-sm">
                                    <span className="mb-0 text-secondary" style={{ fontSize: '13px' }}>From Saved Addresses</span>
                                    <button
                                        className="btn fw-bold px-2 py-1 px-md-3 py-md-2 bg-white"
                                        style={{ border: '1px solid #0b53a1', color: '#0b53a1', fontSize: '12px', borderRadius: '4px' }}
                                        onClick={openPincodeModal}
                                    >
                                        Enter Delivery Pincode
                                    </button>
                                </div>

                                {hasItems && (
                                    <div className="d-md-none p-2 fw-semibold d-flex align-items-center gap-2 border-bottom" style={{ fontSize: '13px', backgroundColor: '#f8f9fa' }}>
                                        <input type="checkbox" className="form-check-input shadow-none m-0" defaultChecked style={{ width: '16px', height: '16px' }} />
                                        <span className="text-dark">{cartTotals.totalItems}/{cartItems.length} items selected</span>
                                    </div>
                                )}

                                <div className="cart-products-list">
                                    {!hasItems ? (
                                        <div className="bg-white p-5 text-center my-3 shadow-sm rounded">
                                            <h5 className="fw-bold text-dark mb-3">Your Cart is Empty</h5>
                                            <p className="text-muted mb-4">Add products to your cart to see them here.</p>
                                            <button className="btn checkout-cta" onClick={() => navigate('/allproducts')}>
                                                Continue Shopping
                                            </button>
                                        </div>
                                    ) : (
                                        cartItems.map(item => <CartProductCard key={item.id} item={item} />)
                                    )}
                                </div>

                                {hasItems && (
                                    <div className="checkout-panel-band d-md-none mb-4">
                                        <div className="px-3 py-2 checkout-panel-title">PRICE DETAILS.</div>
                                        <div className="px-3 pb-3">
                                            <div className="bg-white rounded-3 p-3 shadow-sm border">
                                                <PriceDetails
                                                    itemCount={cartTotals.totalItems}
                                                    totalPrice={cartTotals.originalTotalPrice}
                                                    discount={cartTotals.totalDiscount}
                                                    couponApplied={cartTotals.couponApplied}
                                                    deliveryCharges={cartTotals.deliveryCharges}
                                                    cashCoinsApplied={cartTotals.cashCoinsApplied}
                                                    isCart
                                                    isMobile
                                                />

                                                <div className="mt-3 mb-3 d-flex border rounded-1">
                                                    <input type="text" className="form-control border-0 shadow-none px-2" placeholder="Have a Discount Coupon?" style={{ fontSize: '13px', color: '#1a73e8' }} />
                                                    <button className="btn fw-bold px-3 rounded-0" style={{ backgroundColor: '#e7eefa', color: '#0b53a1', fontSize: '13px' }}>Applied</button>
                                                </div>

                                                <PrintmontCoinsPromo />
                                            </div>

                                            <div className="mt-3 bg-light rounded text-center">
                                                <TrustBar />
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {hasItems && (
                                    <div className="d-none d-lg-flex justify-content-end bg-white p-3 mb-4 shadow-sm">
                                        <button className="btn checkout-cta" onClick={handlePlaceOrder}>Place Order</button>
                                    </div>
                                )}

                                {/* Collapsed by default, as in the design. */}
                                {savedItems.length > 0 && (
                                    <div className="card shadow-sm border-light rounded-0 mt-3 d-none d-md-block mb-3">
                                        <div className="card-body p-0">
                                            <button
                                                type="button"
                                                className="btn w-100 d-flex align-items-center justify-content-between p-3 bg-white rounded-0 text-start shadow-none"
                                                onClick={() => setShowSaved((v) => !v)}
                                                aria-expanded={showSaved}
                                            >
                                                <span className="fs-6 mb-0 text-dark">Saved For Later ({savedItems.length})</span>
                                                {showSaved
                                                    ? <BsChevronUp size={13} className="text-secondary" />
                                                    : <BsChevronDown size={13} className="text-secondary" />}
                                            </button>

                                            {showSaved && (
                                                <div className="cart-products-list bg-light p-3 border-top">
                                                    {savedItems.map(item => (
                                                        <CartProductCard key={item.id} item={item} isSavedForLater />
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </>
                        )}

                        {/* ---------- CHECKOUT STEPS ---------- */}
                        {!isCart && (
                            <div className="checkout-steps shadow-sm">

                                {/* Buyer header — not a step, so no number. */}
                                <CheckoutStepBar
                                    title={buyerName}
                                    plainTitle
                                    done={checkoutStep > STEP_ADDRESS}
                                    aside={buyerMobile}
                                />

                                {/* 1 — address */}
                                {checkoutStep === STEP_ADDRESS ? (
                                    <>
                                        <CheckoutStepBar number="1" title="ADD DELIVERY ADDRESS" active />
                                        <OrderAddress onAddressSaved={() => goToStep(STEP_SUMMARY)} />
                                    </>
                                ) : (
                                    <CheckoutStepBar
                                        number="1"
                                        title="DELIVERY DETAILS"
                                        done
                                        onChange={() => goToStep(STEP_ADDRESS)}
                                    >
                                        {savedAddressLine}
                                    </CheckoutStepBar>
                                )}

                                {/* 2 — order summary */}
                                {checkoutStep === STEP_SUMMARY && (
                                    <>
                                        <CheckoutStepBar number="2" title="ORDER SUMMARY" active />
                                        <OrderSummary onContinue={() => goToStep(STEP_PAYMENT)} />
                                    </>
                                )}
                                {checkoutStep > STEP_SUMMARY && (
                                    <CheckoutStepBar
                                        number="2"
                                        title="ORDER SUMMARY"
                                        done
                                        onChange={() => goToStep(STEP_SUMMARY)}
                                    />
                                )}

                                {/* 3 — payment */}
                                {checkoutStep === STEP_PAYMENT && (
                                    <>
                                        <CheckoutStepBar number="3" title="COMPLETE PAYMENT" active />
                                        <PaymentGateway onPaymentSuccess={() => navigate('/orders')} />
                                    </>
                                )}

                            </div>
                        )}

                        {/* Clears the fixed bottom bar on mobile. */}
                        <div className="d-md-none" style={{ height: '80px' }} />

                    </div>

                    {/* Desktop sidebar. The steps render their own price panel on mobile. */}
                    {hasItems && (
                        <div className="col-12 col-lg-3 checkout-sidebar-col order-md-2 d-none d-lg-block">
                            <div className="checkout-sidebar-sticky">
                                <PriceDetails
                                    itemCount={cartTotals.totalItems}
                                    totalPrice={cartTotals.originalTotalPrice}
                                    discount={cartTotals.totalDiscount}
                                    couponApplied={cartTotals.couponApplied}
                                    deliveryCharges={cartTotals.deliveryCharges}
                                    cashCoinsApplied={cartTotals.cashCoinsApplied}
                                    isCart={isCart}
                                />

                                {!isCart && <PrintmontCoinsPromo />}

                                <div className="px-0 mt-3">
                                    <TrustBar />
                                </div>
                            </div>
                        </div>
                    )}

                </div>
            </div>

            {/* Cart's own bottom bar. Each step supplies its own. */}
            {isCart && hasItems && (
                <div className="d-md-none checkout-bottom-bar">
                    <button className="btn checkout-cta w-100" onClick={handlePlaceOrder}>Place Order</button>
                </div>
            )}

            <Modal show={show} onHide={closePincodeModal} size="sm" centered>
                <Modal.Header closeButton>
                    <Modal.Title className="fs-6">Enter Pincode</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form className="d-flex gap-2" onSubmit={handlePincodeSubmit}>
                        <Form.Control
                            type="text" inputMode="numeric" maxLength={6}
                            placeholder="Enter pincode"
                            value={pincode}
                            isInvalid={!!pincodeError}
                            onChange={(e) => { setPincode(e.target.value); setPincodeError(''); }}
                        />
                        <Button type="submit" style={{ backgroundColor: '#0b53a1', borderColor: '#0b53a1' }}>Submit</Button>
                    </Form>
                    {pincodeError && (
                        <div className="text-danger mt-2" style={{ fontSize: '13px' }}>{pincodeError}</div>
                    )}
                </Modal.Body>
            </Modal>

        </div>
    );
};

export default Cart;
