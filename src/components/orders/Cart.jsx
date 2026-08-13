import React, { useState, useEffect } from 'react';
import './orders.css';
import OrderAddress from './OrderAddress';
import OrderSummary from './OrderSummary';
import PaymentGateway from './PaymentGateway';
import { Modal, Button, Form } from 'react-bootstrap';

import CheckoutStepper from './CheckoutStepper';
import CartProductCard from './CartProductCard';
import PriceDetails from './PriceDetails';
import TrustBar from './TrustBar';
import PrintmontCoinsPromo from './PrintmontCoinsPromo';

import { useCheckout } from '../../context/CheckoutContext';
import { useAuth } from '../../context/AuthContext';
import { useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';

/**
 * Checkout is the cart plus three steps:
 *   1  cart          (no stepper)
 *   2  Address       -> stepper node 1
 *   3  Order Summary -> stepper node 2
 *   4  Payment       -> stepper node 3
 */
const STEP_CART = 1;
const STEP_ADDRESS = 2;
const STEP_SUMMARY = 3;
const STEP_PAYMENT = 4;

const Cart = () => {
    const { user } = useAuth();
    const navigate = useNavigate();

    const {
        checkoutStep, setCheckoutStep,
        cartItems, isLoading,
        savedItems,
        cartTotals,
    } = useCheckout();

    const [show, setShow] = useState(false);
    const [pincode, setPincode] = useState("");

    // Opening /cart always starts at the cart itself, never mid-checkout.
    useEffect(() => {
        setCheckoutStep(STEP_CART);
    }, [setCheckoutStep]);

    const goToStep = (step) => {
        setCheckoutStep(step);
        window.scrollTo(0, 0);
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

    const isCart = checkoutStep === STEP_CART;
    const hasItems = cartItems.length > 0;

    return (
        <div className="checkout-page pb-5" style={{ minHeight: '100vh', backgroundColor: '#f1f3f6' }}>

            <CheckoutStepper currentStep={checkoutStep} onStepClick={goToStep} />

            <div className="container p-0 py-md-4">
                <div className="row g-0 g-md-3">

                    <div className="col-12 col-lg-9 checkout-main-col order-md-1">

                        {/* ---------- CART ---------- */}
                        {isCart && (
                            <>
                                <div className="d-flex justify-content-between align-items-center bg-white p-2 p-lg-3 mb-2 border-bottom shadow-sm">
                                    <span className="mb-0 text-secondary d-md-none" style={{ fontSize: '13px' }}>From Saved Addresses</span>
                                    <h6 className="fw-bold mb-0 text-uppercase d-none d-md-block">From Saved Addresses</h6>
                                    <button
                                        className="btn fw-bold px-2 py-1 px-md-3 py-md-2 bg-white"
                                        style={{ border: '1px solid #0b53a1', color: '#0b53a1', fontSize: '12px', borderRadius: '4px' }}
                                        onClick={() => setShow(true)}
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
                                                    step={STEP_CART}
                                                    isMobile
                                                />

                                                <div className="mt-3 mb-3 d-flex border rounded-1">
                                                    <input type="text" className="form-control border-0 shadow-none px-2" placeholder="Have a Discount Coupon?" style={{ fontSize: '13px', color: '#1a73e8' }} />
                                                    <button className="btn text-white fw-bold px-3 rounded-0" style={{ backgroundColor: '#7b726e', fontSize: '13px' }}>Applied</button>
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

                                {savedItems.length > 0 && (
                                    <div className="card shadow-sm border-light rounded-0 mt-3 d-none d-md-block mb-3">
                                        <div className="card-body p-0">
                                            <div className="p-3 border-bottom">
                                                <h6 className="fw-bold fs-6 mb-0">Saved For Later ({savedItems.length})</h6>
                                            </div>
                                            <div className="cart-products-list bg-light p-3">
                                                {savedItems.map(item => (
                                                    <CartProductCard key={item.id} item={item} isSavedForLater />
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </>
                        )}

                        {/* ---------- 1. ADDRESS ---------- */}
                        {checkoutStep === STEP_ADDRESS && (
                            <OrderAddress onAddressSaved={() => goToStep(STEP_SUMMARY)} />
                        )}

                        {/* ---------- 2. ORDER SUMMARY ---------- */}
                        {checkoutStep === STEP_SUMMARY && (
                            <OrderSummary
                                onContinue={() => goToStep(STEP_PAYMENT)}
                                onChangeAddress={() => goToStep(STEP_ADDRESS)}
                            />
                        )}

                        {/* ---------- 3. PAYMENT ---------- */}
                        {checkoutStep === STEP_PAYMENT && (
                            <PaymentGateway onPaymentSuccess={() => navigate('/orders')} />
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
                                    step={checkoutStep}
                                />

                                {checkoutStep >= STEP_SUMMARY && <PrintmontCoinsPromo />}

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

            <Modal show={show} onHide={() => setShow(false)} size="sm" centered>
                <Modal.Header closeButton>
                    <Modal.Title className="fs-6">Enter Pincode</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form className="d-flex gap-2" onSubmit={(e) => { e.preventDefault(); setShow(false); }}>
                        <Form.Control type="text" placeholder="Enter pincode" value={pincode} onChange={(e) => setPincode(e.target.value)} />
                        <Button type="submit" style={{ backgroundColor: '#0b53a1', borderColor: '#0b53a1' }}>Submit</Button>
                    </Form>
                </Modal.Body>
            </Modal>

        </div>
    );
};

export default Cart;
