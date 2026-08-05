import React, { useState, useEffect } from 'react';
import './orders.css';
import BuyerDetails from './BuyerDetails';
import OrderAddress from './OrderAddress';
import OrderSummary from './OrderSummary';
import PaymentGateway from './PaymentGateway';
import { Modal, Button, Form } from 'react-bootstrap';

// New Shared Components
import MobileCheckoutHeader from './MobileCheckoutHeader';
import CheckoutStepper from './CheckoutStepper';
import CollapsedStepPanel from './CollapsedStepPanel';
import CartProductCard from './CartProductCard';
import PriceDetails from './PriceDetails';
import TrustBar from './TrustBar';
import PrintmontCoinsPromo from './PrintmontCoinsPromo';
import CouponSection from './CouponSection';

import { useCheckout } from '../../context/CheckoutContext';
import { useAuth } from '../../context/AuthContext';
import { useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';

const Cart = () => {
    const { user } = useAuth();
    const navigate = useNavigate();

    const {
        checkoutStep, setCheckoutStep,
        cartItems, isLoading,
        savedItems,
        couponApplied, setCouponApplied,
        useCashCoins,
        cartTotals,
        buyerDetails,
        address
    } = useCheckout();

    const [show, setShow] = useState(false);
    const [pincode, setPincode] = useState("");

    // Always reset to Step 1 (Cart Products List) when user opens /cart
    useEffect(() => {
        setCheckoutStep(1);
    }, [setCheckoutStep]);

    const handleNextStep = () => {
        setCheckoutStep(prevStep => prevStep + 1);
        window.scrollTo(0, 0);
    };

    const handlePlaceOrder = () => {
        if (!user) {
            toast.error("Please login to proceed with your order!");
            navigate('/login?redirect=/cart');
            return;
        }
        handleNextStep();
    };

    const handlePincodeSubmit = () => {
        setShow(false);
    };

    const handleAddressSaved = (addressData) => {
        handleNextStep();
    };

    // Derived state for headers
    const headerTitle = checkoutStep === 1 ? "My Carts" : 
                        checkoutStep === 2 ? "Buyer Details" : 
                        checkoutStep === 3 ? "Add Delivery Address" : 
                        checkoutStep === 4 ? `Order Summary (${cartTotals.totalItems})` : 
                        "Payment";

    if (isLoading) {
        return <div className="text-center mt-5">Loading Cart...</div>;
    }

    return (
        <div className="checkout-page bg-light pb-5" style={{ minHeight: '100vh', backgroundColor: '#f1f3f6' }}>
            
            {/* Mobile Shared Elements */}
            <CheckoutStepper currentStep={checkoutStep} onStepClick={setCheckoutStep} />

            <div className="container p-0 py-md-4">
                <div className="row g-0 g-md-3">
                    
                    {/* Left Column (Main Content) */}
                    <div className="col-12 col-lg-9 checkout-main-col order-md-1">
                        
                        {/* 1. CART SCREEN */}
                        {checkoutStep === 1 && (
                            <>
                                {/* Selection Counter for Mobile */}
                                <div className="d-md-none p-2 mb-0 fw-semibold d-flex align-items-center gap-2 border-bottom" style={{ fontSize: '13px', backgroundColor: '#f8f9fa' }}>
                                    <div className="d-flex align-items-center justify-content-center text-white" style={{ width: '16px', height: '16px', backgroundColor: '#0084ff', borderRadius: '3px' }}>
                                      <span style={{lineHeight: 0, marginTop: '-2px'}}>-</span>
                                    </div>
                                    <span className="text-dark">{cartItems.length} items selected</span>
                                </div>

                                {/* Desktop Top Bar -> Responsive Top Bar */}
                                <div className="d-flex justify-content-between align-items-center bg-white p-2 p-lg-3 mb-2 mb-lg-3 border-bottom shadow-sm rounded-0">
                                    <span className="mb-0 text-secondary d-md-none" style={{fontSize: '13px'}}>From Saved Addresses</span>
                                    <h6 className="fw-bold mb-0 text-uppercase d-none d-md-block">From Saved Addresses</h6>
                                    <button className="btn btn-outline-theme fw-bold px-2 py-1 px-md-3 py-md-2 border-1 bg-white" style={{ borderColor: '#0b53a1', color: '#0b53a1', fontSize: '12px', borderRadius: '4px' }} onClick={() => setShow(true)}>Enter Delivery Pincode</button>
                                </div>

                                {/* Cart Product List */}
                                <div className="cart-products-list">
                                    {cartItems.length === 0 ? (
                                        <div className="bg-white p-5 text-center my-3 shadow-sm rounded">
                                            <h5 className="fw-bold text-dark mb-3">Your Cart is Empty</h5>
                                            <p className="text-muted mb-4">Add products to your cart to see them here.</p>
                                            <button className="btn btn-primary px-4 py-2 fw-bold" onClick={() => window.location.href = '/allproducts'}>
                                                CONTINUE SHOPPING
                                            </button>
                                        </div>
                                    ) : (
                                        cartItems.map(item => (
                                            <CartProductCard 
                                                key={item.id} 
                                                item={item} 
                                            />
                                        ))
                                    )}
                                </div>

                                {/* Mobile Price Details Section matching the image layout */}
                                {cartItems.length > 0 && (
                                <div className="d-md-none mb-4" style={{ backgroundColor: '#e0e0e0' }}>
                                    <div className="px-3 py-2 fw-bold text-uppercase" style={{ color: '#0b53a1', fontSize: '14px' }}>
                                      PRICE DETAILS.
                                    </div>
                                    <div className="px-3 pb-3">
                                        <div className="bg-white rounded-3 p-3 shadow-sm border">
                                            <PriceDetails 
                                                itemCount={cartTotals.totalItems}
                                                totalPrice={cartTotals.originalTotalPrice}
                                                discount={cartTotals.totalDiscount}
                                                couponApplied={cartTotals.couponApplied}
                                                deliveryCharges={cartTotals.deliveryCharges}
                                                cashCoinsApplied={cartTotals.cashCoinsApplied}
                                                step={checkoutStep}
                                                isMobile={true}
                                            />
                                            
                                            {/* Custom Coupon Box for Mobile Image Match */}
                                            <div className="mt-3 mb-3 d-flex border rounded-1">
                                                <input type="text" className="form-control border-0 shadow-none px-2 text-primary" placeholder="Have a Discount Coupon?" style={{fontSize:'13px'}} />
                                                <button className="btn text-white fw-bold px-3 rounded-0" style={{backgroundColor: '#7b726e', fontSize:'13px'}}>Applied</button>
                                            </div>
                                            
                                            {/* Printmont Coins */}
                                            <PrintmontCoinsPromo />
                                        </div>
                                        
                                        <div className="mt-3 bg-light rounded text-center">
                                            <TrustBar />
                                        </div>
                                    </div>
                                </div>
                                )}

                                {/* Desktop Inline Place Order */}
                                 {cartItems.length > 0 && (
                                <div className="d-none d-lg-flex justify-content-end bg-white p-3 mb-4 shadow-sm">
                                    <button 
                                        className="btn btn-theme text-white fw-bold px-5 py-2" 
                                        style={{ backgroundColor: '#0b53a1', fontSize: '16px' }}
                                        onClick={handlePlaceOrder}
                                    >
                                        PLACE ORDER
                                    </button>
                                </div>
                                )}

                                {/* Saved For Later Section */}
                                {savedItems.length > 0 && (
                                    <div className="card shadow-sm border-light rounded-0 mt-3 d-none d-md-block mb-3">
                                        <div className="card-body p-0">
                                            <div className="p-3 border-bottom">
                                                <h6 className="fw-bold fs-6 mb-0">Saved For Later ({savedItems.length})</h6>
                                            </div>
                                            <div className="cart-products-list bg-light p-3">
                                                {savedItems.map(item => (
                                                    <CartProductCard 
                                                        key={item.id} 
                                                        item={item} 
                                                        isSavedForLater={true}
                                                    />
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </>
                        )}

                        {/* Collapsed Step 1 (Visible from Step 3+) */}
                        {checkoutStep >= 3 && (
                            <CollapsedStepPanel 
                                stepNumber="1" 
                                title="Buyer name" 
                                summaryText={buyerDetails?.name || "Name not provided"}
                                subText={buyerDetails?.mobile || "Buyer Mobile no."}
                                isCompleted={true}
                                onChange={() => setCheckoutStep(2)} 
                            />
                        )}

                        {/* 2. BUYER DETAILS SECTION */}
                        {checkoutStep === 2 && (
                            <div className="card bg-white shadow-sm border-0 rounded-0 mb-3">
                                <div className="card-header text-white p-3 rounded-0 border-0 d-none d-md-flex align-items-center gap-3" style={{ backgroundColor: '#0b53a1' }}>
                                    <div className="d-flex align-items-center justify-content-center bg-white text-theme fw-bold" style={{ width: '22px', height: '22px', fontSize: '13px', color: '#0b53a1' }}>1</div>
                                    <span className="fw-bold text-uppercase" style={{ fontSize: '14px' }}>BUYER NAME</span>
                                </div>
                                <div className="card-body p-0">
                                    <BuyerDetails onContinue={handleNextStep} />
                                </div>
                            </div>
                        )}

                        {/* Collapsed Step 2 (Visible from Step 4+) */}
                        {checkoutStep >= 4 && (
                            <CollapsedStepPanel 
                                stepNumber="2" 
                                title="Delivery Details" 
                                summaryText={`${buyerDetails?.name || "Name not provided"}`}
                                subText={`${address?.addressArea || ""}, ${address?.city || ""}, ${address?.state || ""} - ${address?.pincode || ""}`}
                                isCompleted={true}
                                onChange={() => setCheckoutStep(3)}
                            />
                        )}

                        {/* 3. ORDER ADDRESS SECTION */}
                        {checkoutStep === 3 && (
                            <div className="card bg-white shadow-sm border-0 rounded-0 mb-3">
                                <div className="card-header text-white p-3 rounded-0 border-0 d-none d-md-flex align-items-center gap-3" style={{ backgroundColor: '#0b53a1' }}>
                                    <div className="d-flex align-items-center justify-content-center bg-white text-theme fw-bold" style={{ width: '22px', height: '22px', fontSize: '13px', color: '#0b53a1' }}>2</div>
                                    <span className="fw-bold text-uppercase" style={{ fontSize: '14px' }}>ADD DELIVERY ADDRESS</span>
                                </div>
                                <div className="card-body p-0">
                                    <OrderAddress onAddressSaved={handleAddressSaved} />
                                </div>
                            </div>
                        )}

                        {/* Collapsed Step 3 (Visible from Step 5+) */}
                        {checkoutStep >= 5 && (
                            <CollapsedStepPanel 
                                stepNumber="3" 
                                title="Order Summary" 
                                summaryText={`${cartTotals.totalItems} items`}
                                isCompleted={true}
                                onChange={() => setCheckoutStep(4)}
                            />
                        )}

                        {/* 4. ORDER SUMMARY SECTION */}
                        {checkoutStep === 4 && (
                            <div className="card bg-white shadow-sm border-0 rounded-0 mb-3">
                                <div className="card-header bg-theme text-white p-3 rounded-0 border-0 d-none d-md-flex align-items-center gap-2">
                                    <span className="bg-white text-theme px-2 rounded-1 fw-bold">3</span>
                                    <span className="fw-bold">ORDER SUMMARY</span>
                                </div>
                                <div className="card-body p-0 p-md-3">
                                    <OrderSummary onContinue={handleNextStep} />
                                </div>
                            </div>
                        )}

                        {/* 5. PAYMENT GATEWAY SECTION */}
                        {checkoutStep === 5 && (
                            <div className="card bg-white shadow-sm border-0 rounded-0 mb-3">
                                <div className="card-header bg-theme text-white p-3 rounded-0 border-0 d-none d-md-flex align-items-center gap-2">
                                    <span className="bg-white text-theme px-2 rounded-1 fw-bold">4</span>
                                    <span className="fw-bold">COMPLETE PAYMENT</span>
                                </div>
                                <div className="card-body p-0 p-md-3">
                                    <PaymentGateway />
                                </div>
                            </div>
                        )}

                        {/* Extra padding for mobile so content isn't hidden behind fixed bar */}
                        <div className="d-md-none" style={{ height: '80px' }}></div>

                    </div>

                    {/* Right Column (Sidebar) — Hidden when cart is empty */}
                    {cartItems.length > 0 && (
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

                            {/* Printmont Coins Promo appears from Step 2 onwards on Desktop */}
                            {checkoutStep >= 2 && (
                                <PrintmontCoinsPromo />
                            )}

                            <div className="px-0 mt-3">
                                <TrustBar />
                            </div>

                        </div>
                    </div>
                    )}

                </div>
            </div>

            {/* Mobile Fixed Bottom Bar */}
            {cartItems.length > 0 && (
            <div className="d-md-none fixed-bottom bg-white border-top shadow-lg z-3">
                {checkoutStep === 1 && (
                    <button 
                        className="btn btn-theme w-100 py-3 fw-bold text-uppercase text-white rounded-0"
                        style={{ backgroundColor: '#0b53a1', fontSize: '15px' }}
                        onClick={handlePlaceOrder}
                    >
                        Place Order
                    </button>
                )}
            </div>
            )}

            {/* Pincode Modal */}
            <Modal show={show} onHide={() => setShow(false)} size="sm" centered>
                <Modal.Header closeButton>
                    <Modal.Title className="fs-6">Enter Pincode</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form className='d-flex gap-2'>
                        <Form.Control type="text" placeholder="Enter pincode" value={pincode} onChange={(e) => setPincode(e.target.value)} />
                        <Button variant="primary" style={{ backgroundColor: '#0b53a1', borderColor: '#0b53a1' }} onClick={handlePincodeSubmit}>Submit</Button>
                    </Form>
                </Modal.Body>
            </Modal>

        </div>
    );
};

export default Cart;
