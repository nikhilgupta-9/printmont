import React, { useState, useMemo } from 'react';
import { FiMinus, FiPlus } from 'react-icons/fi';
import { IoIosCheckmarkCircle } from 'react-icons/io';
import { MdOutlineWatchLater } from 'react-icons/md';
import { RiDeleteBin6Line } from 'react-icons/ri';
import OrderAddress from './OrderAddress';
import OrderSummary from './OrderSummary';
import { BsShieldFillCheck } from 'react-icons/bs';
import PaymentGateway from './PaymentGateway';
import { Modal, Button, Form } from 'react-bootstrap';

// --- MAIN CART COMPONENT ---

const Cart = () => {

    const initialCartItems = [
        { id: 1, name: "Printmont Rust Brown Half-Sleeves Knitted Mens Shirt", size: "M", color: "Brown", price: 549, originalPrice: 645, discount: 35, offers: 2, quantity: 1 },
        { id: 2, name: "Printmont Blue Half-Sleeves Knitted Mens Shirt", size: "L", color: "Blue", price: 699, originalPrice: 999, discount: 30, offers: 1, quantity: 1 },
        { id: 3, name: "Printmont Green T-Shirt", size: "M", color: "Green", price: 349, originalPrice: 499, discount: 20, offers: 0, quantity: 1 },
        { id: 4, name: "Printmont White Casual Shirt", size: "XL", color: "White", price: 799, originalPrice: 1199, discount: 40, offers: 3, quantity: 1 },
    ];

    const [show, setShow] = useState(false);
    const [cartItems, setCartItems] = useState(initialCartItems);
    const [pincode, setPincode] = useState("");
    const [checkoutStep, setCheckoutStep] = useState(1);

    const handleNextStep = () => {
        setCheckoutStep(prevStep => prevStep + 1);
    };

    const handlePincodeSubmit = () => {
        console.log("Entered Pincode:", pincode);
        setShow(false); // 👈 closes the modal
    };
    // Placeholder data to show in the collapsed containers
    const [addressSummary, setAddressSummary] = useState({ name: 'User Name', address: '123 Main St, City, 12345' });

    // This function will be passed to OrderAddress to capture the final data
    const handleAddressSaved = (addressData) => {
        setAddressSummary(addressData);
        handleNextStep();
    };


    // --- CollapsedStepContainer Component ---
    const CollapsedStepContainer = ({ step, title, content, children, showMobileNumber = false }) => {
        const isCurrentStep = checkoutStep === step;
        const isCompletedStep = checkoutStep > step;

        // Function to jump back to a previous step (editing)
        const handleEdit = () => {
            setCheckoutStep(step);
        };

        return (
            <div className={`card mb-3 ${isCompletedStep ? 'shadow-sm border-0' : 'bg-transparent border-0'}`}>
                <div
                    className={`p-3 text-white fs-6 fs-md-4 fs-lg-5 fw-bold rounded-top ${isCompletedStep ? 'bg-theme border-bottom' : 'bg-theme'}`}
                    style={isCompletedStep ? { cursor: 'pointer' } : {}}
                    onClick={isCompletedStep ? handleEdit : undefined}
                >
                    <span className={`px-2 me-2 ${isCompletedStep ? 'text-theme bg-white' : 'bg-white text-theme'}`}>
                        {step}
                    </span>
                    {title}
                    {isCompletedStep && (
                        <button
                            className="btn btn-sm text-uppercase fw-bold float-end "
                            onClick={handleEdit}
                            style={{ backgroundColor: 'transparent', border: 'none', color: 'white' }}
                        >
                            {content.buttonText}
                        </button>
                    )}
                </div>
                {isCurrentStep && (
                    <div className="card-body p-0 rounded-bottom">
                        {children}
                    </div>
                )}

                {isCompletedStep && (
                    <div className="p-2 bg-white d-flex align-items-center justify-content-between">
                        <small className="text-muted">{content.details}</small>
                        {/* 🌟 New Conditional Check Here 🌟 */}
                        {showMobileNumber && (
                            <small className='fw-normal'>Buyer's Mobile Number</small>
                        )}
                    </div>
                )}
            </div>
        );
    };
    // --- End CollapsedStepContainer Component ---

    const updateQuantity = (itemId, change) => {
        setCartItems(prevItems =>
            prevItems.map(item =>
                item.id === itemId
                    ? { ...item, quantity: Math.max(1, item.quantity + change) }
                    : item
            )
        );
    };

    // 3. Function to remove an item
    const removeItem = (itemId) => {
        setCartItems(prevItems => prevItems.filter(item => item.id !== itemId));
    };

    // Constants for Price Details (simulated configuration)
    const couponDiscount = 450;
    const deliveryCharge = 100; // Standard delivery charge
    const isDeliveryFree = true; // Flag to enable/disable free delivery

    const cartTotals = useMemo(() => {
        let totalOriginalPrice = 0;
        let totalDiscount = 0;
        let totalItems = 0;

        cartItems.forEach(item => {
            const discountedPrice = item.price;
            const itemPriceForTotals = item.originalPrice;

            totalOriginalPrice += itemPriceForTotals * item.quantity;
            totalDiscount += (itemPriceForTotals - discountedPrice) * item.quantity;
            totalItems += item.quantity;
        });

        const priceBeforeCoupon = totalOriginalPrice - totalDiscount;
        const finalDeliveryCharge = isDeliveryFree ? 0 : deliveryCharge;

        const totalAmount = priceBeforeCoupon - couponDiscount + finalDeliveryCharge;
        const totalSavings = totalDiscount + couponDiscount + (isDeliveryFree ? deliveryCharge : 0);

        return {
            totalItems,
            totalOriginalPrice: priceBeforeCoupon,
            totalDiscount: totalDiscount,
            couponDiscount,
            deliveryCharge: finalDeliveryCharge,
            totalAmount,
            totalSavings,
        };
    }, [cartItems, couponDiscount, isDeliveryFree, deliveryCharge]);

    // 5. CartItem Component (Unchanged)
    const CartItem = ({ item, updateQuantity, removeItem }) => (
        <>
            <div className="d-flex pt-3 ">
                {/* Product Image & Quantity Controls */}
                <div className="col-2 col-md-1 flex-shrink-0" style={{ width: '120px' }}>
                    <img src="/men_shirt/men-shirt-2.jpeg" alt={item.name} className="img-fluid" />
                    <div className="d-flex justify-content-evenly align-items-center input-group input-group-sm me-4 mt-1 border-1 rounded rounded-sm border border-dark" style={{ width: '120px', }}>
                        <button className="px-2 border-0 bg-transparent" type="button" onClick={() => updateQuantity(item.id, -1)} disabled={item.quantity <= 1} style={{ width: '40px' }}>
                            <FiMinus />
                        </button>
                        <input type="text" value={item.quantity} readOnly className="form-control text-center p-0 border-0 border-dark border-start border-end" />
                        <button className="px-2 border-0 bg-transparent" type="button" onClick={() => updateQuantity(item.id, 1)} style={{ width: '40px' }}>
                            <FiPlus />
                        </button>
                    </div>
                </div>

                {/* Product Details */}
                <div className="col-8 col-md-7 ps-3 d-flex flex-column justify-content-between ">
                    <div>
                        <p className="fw-semibold mb-1">{item.name}</p>
                        {/* Dynamic Price Display */}
                        <p className="text fs-6 fw-bold mb-1">
                            ₹{(item.price * item.quantity).toLocaleString('en-IN')}
                            <span className="text-secondary mb-0 ms-2 text-decoration-line-through">₹{(item.originalPrice * item.quantity).toLocaleString('en-IN')}</span>
                            ({item.discount}% off)
                            | {item.offers} offers applied
                        </p>
                        <p className="text-muted small mb-0">Size: {item.size}</p>
                        <p className="text-muted small mb-0">Colors: {item.color}</p>
                        <p className="text-muted small mb-0">
                            Seller: name here
                            <img src="/Asured.png" className='ms-2 ' width={60} height={'auto'} alt="Printmont Assured logo" />
                        </p>
                    </div>

                    {/* Action Buttons */}
                    <div className="d-flex align-items-center small mt-2">
                        <div className='d-none d-lg-flex'>
                            <button className="border-0 bg-transparent fs-6 text-dark fw-normal p-0 me-3" >SAVE FOR LATER</button>
                            <button className="border-0 bg-transparent fs-6 fw-normal p-0 me-3 product" onClick={() => removeItem(item.id)}>
                                REMOVE
                            </button>
                        </div>
                        <span className="text-black ms-auto small d-none d-md-flex rounded bg-gray justify-content-center align-items-center"><IoIosCheckmarkCircle size={18} className='text-success' /> Customization Saved.</span>
                    </div>
                </div>

                {/* Delivery Details */}
                <div className="text-start d-none d-md-block border-start ms-2 ps-3">
                    <p className="text-uppercase text-dark fw-semibold mb-1 small fs-6">Delivery On</p>
                    <p className="small mb-0 mt-4"><span className='fw-bold text-dark fs-6'>24</span><sup>th</sup> Oct, Friday 2025</p>
                    <p className="small text-secondary mb-0">Delivery charges <span className="fw-bold text-success">FREE</span></p>
                    <p className="small text-secondary">Standard Delivery.</p>
                </div>

            </div>
            <div className='d-flex d-lg-none justify-content-evenly align-items-center border border-dark mb-3 w-100 w-md-75'>
                <button className="border-0 small fw-semibold p-0 mx-0 text-dark fw-normal p-0 px-0 text-center d-flex align-items-center justify-content-center gap-1 w-50 py-1 border-end" ><MdOutlineWatchLater size={18} />SAVE FOR LATER</button>
                <button className="border-0 small fw-semibold p-0 mx-0 text-dark fw-normal p-0 px-1 text-center d-flex align-items-center justify-content-center gap-1 w-50 py-1 border-start border-dark" onClick={() => removeItem(item.id)}>
                    <RiDeleteBin6Line size={18} />REMOVE
                </button>
            </div>
        </>
    );

    // 6. PriceDetails Component (Unchanged)
    const PriceDetails = ({ totals, isDeliveryFree, standardDeliveryCharge }) => (
        <div className="card shadow-sm border-0">
            <div className="card-body pt-3">
                <h5 className="card-title text-uppercase fw-bold pb-2 border-bottom">Price Details.</h5>
                <table className="table table-borderless table-sm mt-2 mb-3 small">
                    <tbody>
                        <tr>
                            <td>Price ({totals.totalItems} items)</td>
                            <td className="text-end">₹{totals.totalOriginalPrice.toLocaleString('en-IN')}</td>
                        </tr>
                        <tr>
                            <td>Discount</td>
                            <td className="text-end text-success">-₹{(totals.totalDiscount).toLocaleString('en-IN')}</td>
                        </tr>
                        <tr>
                            <td>Coupon Applied</td>
                            <td className="text-end text-success">-₹{totals.couponDiscount.toLocaleString('en-IN')}</td>
                        </tr>
                        <tr>
                            <td>Delivery Charges</td>
                            <td className="text-end">
                                {isDeliveryFree ? (
                                    <><span className="text-decoration-line-through text-secondary">₹{standardDeliveryCharge}</span> <span className="text-success">Free</span></>
                                ) : (
                                    `₹${totals.deliveryCharge.toLocaleString('en-IN')}`
                                )}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div className="d-flex justify-content-between fw-bold pt-2 mt-2 border-top border-dashed">
                    <span>Total Amount</span>
                    <span>₹{Math.max(0, totals.totalAmount).toLocaleString('en-IN')}</span>
                </div>

                {/* <div className="input-group mt-3">
                    <input type="text" placeholder="Have a Discount Coupon?" className="form-control form-control-sm" />
                    <button className="rounded-end small px-1 border-0 text-uppercase bg-theme" type="button">Applied</button>
                </div> */}
                <p className="text-center text-success fw-semibold mt-3 small">
                    You will save ₹{totals.totalSavings.toLocaleString('en-IN')} on this order
                </p>
            </div>
        </div>
    );


    // 7. Main Render
    return (
        <>
            <div className="container-fluid light-bg-theme min-vh-100 py-2 py-md-4 mt-2 mt-lg-4">

                <div className="container p-0">
                    <div className="row g-2">
                        {/* Left Column (Main Content) */}
                        <div className="col-12 col-lg-9 order-md-1">

                            {/* 1. CART ITEMS SECTION */}
                            <CollapsedStepContainer
                                step={1}
                                title="MY SHOPPING BAG"
                                showMobileNumber={true} // 👈 Added this prop
                                content={{
                                    details: `${cartTotals.totalItems} items | Total: ₹${cartTotals.totalAmount.toLocaleString('en-IN')}`,
                                    buttonText: 'VIEW BAG'
                                }}
                            >
                                <div className="card bg-white border-0">
                                    {/* Delivery Address/Pincode section */}
                                    <div className="d-flex justify-content-between align-items-center mb-2 pb-3 border-bottom rounded-top bg-white p-3 border-0">
                                        <p className="fw-semibold mb-0">From Saved Address</p>
                                        <div className="input-group d-none d-lg-flex justify-content-end" style={{ maxWidth: '250px' }}>
                                            <button className="btn btn-outline-secondary btn-sm" type="button">Enter Delivery Pincode</button>
                                        </div>
                                        <div className='d-flex d-lg-none'>
                                            <Button variant="secondary" className="px-3 py-2" onClick={() => setShow(true)}>
                                                Pincode
                                            </Button>

                                            <Modal show={show} onHide={() => setShow(false)} size="sm" aria-labelledby="example-modal-sizes-title-sm" centered>
                                                <Modal.Header closeButton>
                                                    <Modal.Title id="example-modal-sizes-title-sm">Enter Pincode</Modal.Title>
                                                </Modal.Header>
                                                <Modal.Body>
                                                    <Form className='d-flex justify-content-center align-items-center gap-2'>
                                                        <Form.Control type="text" placeholder="Enter pincode" className="p-1 rounded shadow border-0 custom-bg" value={pincode} onChange={(e) => setPincode(e.target.value)} />
                                                        <Button variant="primary" className="submit" onClick={handlePincodeSubmit}>
                                                            Submit
                                                        </Button>
                                                    </Form>
                                                </Modal.Body>
                                            </Modal>
                                        </div>
                                    </div>

                                    {/* Cart Item List */}
                                    <div className="d-grid gap-3 p-3 bg-white">
                                        {cartItems.map(item =>
                                            <CartItem
                                                key={item.id}
                                                item={item}
                                                updateQuantity={updateQuantity}
                                                removeItem={removeItem}
                                            />
                                        )}

                                    </div>

                                    {/* **REMOVED ORIGINAL PLACE ORDER BUTTON** */}
                                    <div className="d-flex justify-content-end p-3 mt-2 bg-white border-top d-none d-lg-flex">
                                        <button
                                            className="fw-bold text-uppercase px-4 py-2 rounded border-0 bg-theme text-white"
                                            onClick={handleNextStep} // Moves to Step 2: Address
                                        >
                                            Place Order
                                        </button>
                                    </div>

                                </div>
                            </CollapsedStepContainer>

                            {/* 2. ORDER ADDRESS SECTION */}
                            {checkoutStep >= 2 && (
                                <CollapsedStepContainer
                                    step={2}
                                    title="DELIVERY ADDRESS"
                                    content={{
                                        // FIX: Added conditional check to prevent TypeError
                                        details: addressSummary
                                            ? `${addressSummary.name} (${addressSummary.addressType}) — ${addressSummary.address}, ${addressSummary.city}, ${addressSummary.state}, ${addressSummary.pincode} | Mobile: ${addressSummary.mobile}`
                                            : 'Please enter your delivery address details.',
                                        buttonText: 'CHANGE'
                                    }}
                                >
                                    {/* OrderAddress receives the specialized handler */}
                                    <OrderAddress onAddressSaved={handleAddressSaved} />
                                </CollapsedStepContainer>
                            )}

                            {/* 3. ORDER SUMMARY SECTION */}
                            {checkoutStep >= 3 && (
                                <CollapsedStepContainer
                                    step={3}
                                    title="ORDER SUMMARY"
                                    content={{
                                        details: `Price: ₹${cartTotals.totalOriginalPrice.toLocaleString('en-IN')} | Discount: ₹${cartTotals.totalDiscount.toLocaleString('en-IN')}`,
                                        buttonText: 'SEE DETAILS'
                                    }}
                                >
                                    {/* Move to Payment on Continue */}
                                    <OrderSummary onContinue={handleNextStep} />
                                </CollapsedStepContainer>
                            )}

                            {/* 4. PAYMENT GATEWAY SECTION */}
                            {checkoutStep >= 4 && (
                                <CollapsedStepContainer
                                    step={4}
                                    title="PAYMENT"
                                    content={{
                                        details: 'Choose your preferred payment method and complete your order.',
                                        buttonText: 'EDIT'
                                    }}
                                >
                                    <PaymentGateway />
                                </CollapsedStepContainer>
                            )}


                            {/* Saved For Later Section (Moved outside the step components) */}
                            <div className="card p-3 shadow-sm mt-4 border-0 rounded-0 rounded-bottom">
                                <h5 className="fw-semibold border-bottom pb-2 mb-0">Saved For Later (5)</h5>
                            </div>
                            {/* This ensures the content doesn't get hidden behind the fixed footer */}
                            {checkoutStep === 1 && <div className="d-lg-none" style={{ height: '70px' }}></div>}

                        </div>

                        {/* Right Column (Price Details) */}
                        <div className="col-12 col-lg-3 order-md-2 sticky-lg-top z-1 " >
                            <div className="sticky-lg-top" style={{ top: '4.4rem' }}>
                                <div className='bg-white rounded'>
                                    <div className="text-center mb-2 d-none d-md-block">
                                        <h6 className="text-uppercase fw-bold pt-4">Your Orders</h6>
                                    </div>
                                    <PriceDetails totals={cartTotals} isDeliveryFree={isDeliveryFree} standardDeliveryCharge={deliveryCharge} />

                                </div>
                                <div className='py-3 my-2 bg-white px-1'>
                                    <img src="/printmont cart coins.png" width={'100%'} alt="" />
                                </div>
                                <div>
                                    <div className='d-flex align-items-center'>
                                        <span className='text-brown text-center'><BsShieldFillCheck size={32} /></span>
                                        <small>100% safe and secure Payments. Easy return. 100% Authentic products.</small>
                                    </div>
                                    <img src="/payment-method.svg" alt="payment-methods" width={'100%'} />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* **NEW: FIXED BOTTOM BUTTON BAR FOR SMALL SCREENS** */}
            {checkoutStep === 1 && (
                <div className="d-lg-none fixed-bottom bg-white shadow-lg  z-3">
                    <div className="d-flex justify-content-between align-items-center">

                        <button
                            className="fw-bold text-uppercase px-0 py-3 border-0 bg-theme text-white w-100"
                            onClick={handleNextStep} // Moves to Step 2: Address
                        >
                            Place Order
                        </button>
                    </div>
                </div>
            )}
        </>
    );
};

export default Cart;