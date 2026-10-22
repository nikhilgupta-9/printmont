import React, { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import './components/home/home.css'
import './utils/fetchInterceptor.js'
import App from './App.jsx'
import ErrorBoundary from './components/common/ErrorBoundary'
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min';

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <ErrorBoundary>
      <App />
    </ErrorBoundary>
  </StrictMode>,
)

