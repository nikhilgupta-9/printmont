import React from 'react';
import { Container, Row, Col, ListGroup } from 'react-bootstrap';
import { FaBell } from 'react-icons/fa';
import PrintmontCoin from './PrintmontCoin'; 
import './PrintmontCoin.css'; 

const NotificationPageWrapper = () => {

  const menuItems = [
    { key: 'prefs', label: 'NOTIFICATION PREFERENCES' },
    { key: 'desktop', label: 'Desktop Notifications' },
    { key: 'inapp', label: 'In-App Notifications' },
    { key: 'sms', label: 'SMS' },
    { key: 'email', label: 'Email' },
    { key: 'whatsapp', label: 'WhatsApp' },
    { key: 'coin', label: 'Printmont Coin Balance' }, 
  ];
  
  const [activeKey, setActiveKey] = React.useState('coin'); 

  return (
    <Container fluid className="pmn-notification-wrapper p-0">
      <Row className="g-0">
        
        {/* Left Column: Vertical Navigation Menu (Hides on Mobile) */}
        <Col xs={12} md={3} lg={3} className="pmn-sidebar-menu d-none d-md-block">
          <ListGroup variant="flush">
            {menuItems.map((item) => (
              <ListGroup.Item
                key={item.key}
                action
                active={activeKey === item.key}
                onClick={() => setActiveKey(item.key)}
                className={item.key === 'prefs' ? 'pmn-menu-heading' : ''}
              >
                {item.key === 'prefs' && <FaBell className="me-2" />}
                {item.label}
              </ListGroup.Item>
            ))}
          </ListGroup>
        </Col>

        {/* Right Column: Main Content (Takes full width on Mobile) */}
        <Col xs={12} md={9} lg={9} className="pmn-main-content-area">
          <div className="p-md-4 p-0">
            {/* Render PrintmontCoinBalance component */}
            <PrintmontCoin /> 
          </div>
        </Col>
      </Row>
    </Container>
  );
};

export default NotificationPageWrapper;