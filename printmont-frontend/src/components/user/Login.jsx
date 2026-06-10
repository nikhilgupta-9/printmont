import React, { useState } from 'react';

const Login = () => {
  const [emailOrPhone, setEmailOrPhone] = useState('');
  const [isSignup, setIsSignup] = useState(false); // Track mode

  const handleInputChange = (e) => {
    setEmailOrPhone(e.target.value);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (isSignup) {
      alert(`Signup requested for: ${emailOrPhone}`);
      // You can add more signup logic here
    } else {
      alert(`OTP requested for: ${emailOrPhone}`);
      // Login logic here
    }
  };

  // Toggle between Login and Signup
  const toggleForm = (e) => {
    e.preventDefault();
    setIsSignup(!isSignup);
    setEmailOrPhone(''); // Reset input when switching
  };

  return (
    <div className="container-fluid bg-light p-3 d-flex justify-content-center align-items-center px-2 px-sm-4 py-5">
      <div className="row shadow-lg rounded-3 overflow-hidden w-100" style={{ maxWidth: '900px' }}>
        {/* Left Side */}
        <div className={`col-12 col-md-6 d-flex flex-column justify-content-center p-4 ${isSignup ? 'bg-success text-white' : 'bg-primary text-white'}`}>
          <h2 className="fw-bold mb-2">{isSignup ? 'Sign Up' : 'Login'}</h2>
          <p>{isSignup 
            ? 'Create your account to get access to Orders, Wishlist and Recommendations' 
            : 'Get access to your Orders, Wishlist and Recommendations'}</p>
          <img
            src="https://img.icons8.com/fluency/96/laptop.png"
            alt="Visual"
            className="mt-auto d-none d-sm-block"
          />
        </div>

        {/* Right Side (Form) */}
        <div className="col-12 col-md-6 bg-white d-flex flex-column justify-content-center p-4">
          <form onSubmit={handleSubmit}>
            <div className="mb-4">
              <input
                type="text"
                className="form-control border-0 border-bottom rounded-0 shadow-none"
                placeholder={isSignup ? "Enter Email or Mobile number" : "Enter Email/Mobile number"}
                value={emailOrPhone}
                onChange={handleInputChange}
                required
              />
            </div>

            <p className="text-muted small mb-4">
              By continuing, you agree to Printmont's{' '}
              <a href="#" className="text-primary text-decoration-none">Terms of Use</a> and{' '}
              <a href="#" className="text-primary text-decoration-none">Privacy Policy</a>.
            </p>

            <button type="submit" className={`btn w-100 fw-bold ${isSignup ? 'btn-success' : 'btn-warning'}`}>
              {isSignup ? 'Next' : 'Request OTP'}
            </button>

            <p className="text-center mt-4 text-primary">
              {isSignup ? (
                <>
                  Already have an account?{' '}
                  <a href="#" className="text-decoration-none" onClick={toggleForm}>
                    Login here
                  </a>
                </>
              ) : (
                <>
                  New to Printmont?{' '}
                  <a href="#" className="text-decoration-none" onClick={toggleForm}>
                    Create an account
                  </a>
                </>
              )}
            </p>
          </form>
        </div>
      </div>
    </div>
  );
};

export default Login;
