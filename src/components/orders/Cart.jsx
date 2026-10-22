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
import checkPincode from '../../utils/checkPincode';

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
        deliveryInfo, setDeliveryInfo,
    } = useCheckout();

    const [show, setShow] = useState(false);
    const [showSaved, setShowSaved] = useState(false);

    const [pincode, setPincode] = useState(deliveryPincode);
    const [pincodeError, setPincodeError] = useState("");
    const [checkingPincode, setCheckingPincode] = useState(false);

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

    // Checks delivery to the pincode on the server before using it
    const handlePincodeSubmit = async (e) => {
        e.preventDefault();
        const entered = pincode.trim();
        if (!/^[1-9]\d{5}$/.test(entered)) {
            setPincodeError("Enter a valid 6-digit pincode");
            return;
        }
        setCheckingPincode(true);
        const result = await checkPincode(entered);
        setCheckingPincode(false);
        if (!result.deliverable) {
            setPincodeError(result.message || "Delivery is not available to this pincode.");
            return;
        }
        setDeliveryPincode(entered);
        setDeliveryInfo(result);
        toast.success(`Delivery available to ${result.city_name}${result.delivery_by_label ? ` by ${result.delivery_by_label}` : ''}`);
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
        return (
            <div className="text-center py-5" style={{ minHeight: '60vh' }}>
                <div className="spinner-border text-primary" role="status"></div>
                <div className="mt-3 text-muted">Loading Cart...</div>
            </div>
        );
    }

    const isCart = checkoutStep === SCREEN_CART;
    const hasItems = cartItems.length > 0;

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
        <div className="checkout-page pb-md-5 pb-0" style={{ minHeight: '100vh', backgroundColor: '#f1f3f6' }}>

            <div className="container p-0 py-md-4">
                <div className="row g-0 g-md-3">

                    <div className="col-12 col-lg-9 checkout-main-col order-md-1">

                        {/* ---------- CART ---------- */}
                        {isCart && (
                            <>
                                {/* Delivery Address / Pincode Bar */}
                                <div className="d-flex justify-content-between align-items-center bg-white p-2 p-md-3 mb-2 border-bottom shadow-xs">
                                    {deliveryPincode && deliveryInfo ? (
                                        <div className="mb-0 me-2" style={{ fontSize: '13px', minWidth: 0 }}>
                                            <div className="text-dark text-truncate">
                                                Deliver to: <strong>{deliveryInfo.city_name}, {deliveryInfo.state_name} - {deliveryPincode}</strong>
                                            </div>
                                            <div className="text-secondary" style={{ fontSize: '12px' }}>
                                                {deliveryInfo.delivery_by_label ? `Delivery by ${deliveryInfo.delivery_by_label}` : deliveryInfo.standard_delivery_days}
                                                {deliveryInfo.cod_available ? ' • Cash on Delivery available' : ''}
                                            </div>
                                        </div>
                                    ) : (
                                        <span className="mb-0 text-secondary fw-normal" style={{ fontSize: '13px' }}>
                                            Check delivery to your pincode
                                        </span>
                                    )}
                                    <button
                                        className="btn fw-semibold px-2 py-1 px-md-3 py-md-2 bg-white flex-shrink-0"
                                        style={{ border: '1px solid #0b53a1', color: '#0b53a1', fontSize: '12px', borderRadius: '3px' }}
                                        onClick={openPincodeModal}
                                    >
                                        {deliveryPincode && deliveryInfo ? 'Change' : 'Enter Delivery Pincode'}
                                    </button>
                                </div>

                                {/* Items Selected Bar (Mobile View) */}
                                {hasItems && (
                                    <div className="d-md-none p-2 px-3 fw-semibold d-flex align-items-center gap-2 border-bottom bg-white" style={{ fontSize: '13px' }}>
                                        <div 
                                            className="d-inline-flex align-items-center justify-content-center text-white rounded-1 shadow-xs"
                                            style={{ 
                                                width: '18px', 
                                                height: '18px', 
                                                backgroundColor: '#0084ff',
                                                fontSize: '12px',
                                                fontWeight: 'bold',
                                                lineHeight: 1
                                            }}
                                        >
                                            −
                                        </div>
                                        <span className="text-dark">
                                            {cartTotals.totalItems || cartItems.length}/{cartItems.length} items selected
                                        </span>
                                    </div>
                                )}

                                {/* Cart Products List */}
                                <div className="cart-products-list">
                                    {!hasItems ? (
                                        <div className="bg-white p-5 text-center my-3 shadow-sm rounded">
                                            <h5 className="fw-bold text-dark mb-3">Your Cart is Empty</h5>
                                            <p className="text-muted mb-4">Add products to your cart to see them here.</p>
                                            <button className="btn checkout-cta rounded-pill px-4" onClick={() => navigate('/allproducts')}>
                                                Continue Shopping
                                            </button>
                                        </div>
                                    ) : (
                                        cartItems.map(item => <CartProductCard key={item.id} item={item} />)
                                    )}
                                </div>

                                {/* Mobile Price Details & Promo Card (Exact User Layout) */}
                                {hasItems && (
                                    <div className="checkout-panel-band d-md-none mb-0">
                                        <div className="px-3 py-2 fw-bold text-uppercase" style={{ color: '#0b53a1', fontSize: '14px', letterSpacing: '0.3px' }}>
                                            PRICE DETAILS.
                                        </div>
                                        <div className="px-3 pb-3">
                                            {/* Price Details White Card */}
                                            <div className="bg-white rounded-3 p-3 shadow-xs border mb-3">
                                                <PriceDetails
                                                    itemCount={cartTotals.totalItems || cartItems.length}
                                                    totalPrice={cartTotals.originalTotalPrice}
                                                    discount={cartTotals.totalDiscount}
                                                    couponApplied={cartTotals.couponApplied}
                                                    deliveryCharges={cartTotals.deliveryCharges}
                                                    cashCoinsApplied={cartTotals.cashCoinsApplied}
                                                    isCart
                                                    isMobile
                                                />
                                            </div>

                                            {/* Printmont Coins Promo (Outside Price Details) */}
                                            <PrintmontCoinsPromo />

                                            {/* Trust & Safe Payments (Outside Price Details) */}
                                            <div className="mt-2 text-center">
                                                <TrustBar />
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Desktop Place Order Button */}
                                {hasItems && (
                                    <div className="d-none d-lg-flex justify-content-end bg-white p-3 mb-4 shadow-sm border">
                                        <button className="btn checkout-cta px-4" onClick={handlePlaceOrder}>Place Order</button>
                                    </div>
                                )}

                                {/* Saved For Later Section */}
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

                                {/* DESKTOP COLLAPSED STEPS (Steps 1 & 2) */}
                                {checkoutStep >= STEP_SUMMARY && (
                                    <div className="d-none d-md-block">
                                        {/* Step 1: Buyer Name */}
                                        <CheckoutStepBar
                                            number="1"
                                            title={buyerName}
                                            plainTitle
                                            done={true}
                                            aside={buyerMobile}
                                        />

                                        {/* Step 2: Delivery Details */}
                                        <CheckoutStepBar
                                            number="2"
                                            title="DELIVERY DETAILS"
                                            done={true}
                                            onChange={() => goToStep(STEP_ADDRESS)}
                                        >
                                            {savedAddressLine}
                                        </CheckoutStepBar>
                                    </div>
                                )}

                                {/* 1 — Address step */}
                                {checkoutStep === STEP_ADDRESS && (
                                    <OrderAddress onAddressSaved={() => goToStep(STEP_SUMMARY)} />
                                )}

                                {/* 2 — Order Summary step */}
                                {checkoutStep === STEP_SUMMARY && (
                                    <>
                                        <div className="d-none d-md-block">
                                            <CheckoutStepBar number="3" title="ORDER SUMMARY" active />
                                        </div>
                                        <OrderSummary onContinue={() => goToStep(STEP_PAYMENT)} />
                                    </>
                                )}

                                {/* 3 — Payment step */}
                                {checkoutStep === STEP_PAYMENT && (
                                    <>
                                        <div className="d-none d-md-block">
                                            <CheckoutStepBar
                                                number="3"
                                                title="ORDER SUMMARY"
                                                done={true}
                                                onChange={() => goToStep(STEP_SUMMARY)}
                                            />
                                            <CheckoutStepBar number="4" title="COMPLETE PAYMENT" active />
                                        </div>
                                        <PaymentGateway onPaymentSuccess={(result) => navigate(result?.orderNumber || result?.orderId ? `/order/${result.orderNumber || result.orderId}` : '/orders', { replace: true })} />
                                    </>
                                )}

                            </div>
                        )}

                        {/* Clears the fixed bottom bar on mobile. */}
                        <div className="d-md-none" style={{ height: '70px' }} />

                    </div>

                    {/* Desktop sidebar - Kept 100% intact */}
                    {hasItems && (
                        <div className="col-12 col-lg-3 checkout-sidebar-col order-md-2 d-none d-lg-block">
                            <div className="checkout-sidebar-sticky">
                                <PriceDetails
                                    itemCount={cartTotals.totalItems || cartItems.length}
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

            {/* Mobile Fixed Bottom Bar (Exact Full-Width Place Order Button in Blue) */}
            {isCart && hasItems && (
                <div 
                    className="d-md-none position-fixed bottom-0 start-0 w-100 bg-white border-top shadow-lg"
                    style={{ zIndex: 1040 }}
                >
                    <div className="">
                        <button 
                            type="button"
                            className="btn w-100 py-3 text-white fw-bold text-uppercase rounded-0 shadow-sm" 
                            style={{ 
                                backgroundColor: '#0b53a1', 
                                borderColor: '#0b53a1', 
                                fontSize: '15px', 
                                letterSpacing: '0.6px' 
                            }}
                            onClick={handlePlaceOrder}
                        >
                            PLACE ORDER
                        </button>
                    </div>
                </div>
            )}

            {/* Pincode Modal */}
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
                            onChange={(e) => { setPincode(e.target.value.replace(/\D/g, '')); setPincodeError(''); }}
                            autoFocus
                        />
                        <Button type="submit" disabled={checkingPincode} style={{ backgroundColor: '#0b53a1', borderColor: '#0b53a1' }}>
                            {checkingPincode ? 'Checking…' : 'Check'}
                        </Button>
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
