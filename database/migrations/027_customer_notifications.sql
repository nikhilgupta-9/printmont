-- Migration 027: customer-facing notifications (order status, sales/offers, account) + preferences
-- user_id NULL on a notification = broadcast to every customer.

CREATE TABLE IF NOT EXISTS `customer_notifications` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    int(11)      DEFAULT NULL,
  `category`   enum('orders','offers','account','rewards') NOT NULL DEFAULT 'offers',
  `title`      varchar(255) NOT NULL,
  `message`    text         NOT NULL,
  `link`       varchar(500) DEFAULT NULL,
  `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_created` (`user_id`, `created_at`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `customer_notification_reads` (
  `user_id`         int(11)   NOT NULL,
  `notification_id` int(11)   NOT NULL,
  `read_at`         timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`, `notification_id`),
  KEY `idx_notification` (`notification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `customer_notification_preferences` (
  `user_id`  int(11)    NOT NULL,
  `category` enum('orders','offers','account','rewards') NOT NULL,
  `channel`  enum('inapp','email','sms','whatsapp')      NOT NULL,
  `enabled`  tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`, `category`, `channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TRIGGER IF EXISTS trg_orders_notify_placed;
DROP TRIGGER IF EXISTS trg_orders_notify_status;
DROP TRIGGER IF EXISTS trg_coupons_notify_new;

DELIMITER $$

CREATE TRIGGER trg_orders_notify_placed AFTER INSERT ON orders FOR EACH ROW
BEGIN
  IF NEW.user_id IS NOT NULL THEN
    INSERT INTO customer_notifications (user_id, category, title, message, link)
    VALUES (NEW.user_id, 'orders', 'Order placed successfully',
            CONCAT('Your order #', NEW.order_number, ' has been placed. We will keep you posted on every step.'),
            '/orders');
  END IF;
END$$

CREATE TRIGGER trg_orders_notify_status AFTER UPDATE ON orders FOR EACH ROW
BEGIN
  DECLARE t VARCHAR(255);
  DECLARE m TEXT;

  IF NEW.user_id IS NOT NULL AND NEW.status <> OLD.status THEN
    SET t = CASE NEW.status
      WHEN 'confirmed'             THEN 'Order confirmed'
      WHEN 'processing'            THEN 'Your order is being prepared'
      WHEN 'shipped'               THEN 'Your order has been shipped'
      WHEN 'delivered'             THEN 'Order delivered'
      WHEN 'cancelled'             THEN 'Order cancelled'
      WHEN 'refunded'              THEN 'Refund processed'
      WHEN 'return_requested'      THEN 'Return request received'
      WHEN 'returned'              THEN 'Return completed'
      WHEN 'replacement_requested' THEN 'Replacement request received'
      ELSE NULL END;
    SET m = CASE NEW.status
      WHEN 'confirmed'             THEN CONCAT('Great news! Your order #', NEW.order_number, ' is confirmed and will move to printing soon.')
      WHEN 'processing'            THEN CONCAT('Your order #', NEW.order_number, ' is now in production.')
      WHEN 'shipped'               THEN CONCAT('Your order #', NEW.order_number, ' is on its way.', IF(NEW.tracking_number IS NOT NULL AND NEW.tracking_number <> '', CONCAT(' Tracking ID: ', NEW.tracking_number, '.'), ''))
      WHEN 'delivered'             THEN CONCAT('Your order #', NEW.order_number, ' has been delivered. We hope you love it — rate your purchase to earn coins.')
      WHEN 'cancelled'             THEN CONCAT('Your order #', NEW.order_number, ' has been cancelled.')
      WHEN 'refunded'              THEN CONCAT('The refund for order #', NEW.order_number, ' has been processed.')
      WHEN 'return_requested'      THEN CONCAT('We received your return request for order #', NEW.order_number, '.')
      WHEN 'returned'              THEN CONCAT('Your return for order #', NEW.order_number, ' is complete.')
      WHEN 'replacement_requested' THEN CONCAT('We received your replacement request for order #', NEW.order_number, '.')
      ELSE NULL END;
    IF t IS NOT NULL THEN
      INSERT INTO customer_notifications (user_id, category, title, message, link)
      VALUES (NEW.user_id, 'orders', t, m, '/orders');
    END IF;
  END IF;

  IF NEW.user_id IS NOT NULL AND NEW.payment_status <> OLD.payment_status AND NEW.payment_status = 'paid' THEN
    INSERT INTO customer_notifications (user_id, category, title, message, link)
    VALUES (NEW.user_id, 'orders', 'Payment received',
            CONCAT('We received your payment for order #', NEW.order_number, '. Thank you!'), '/orders');
  END IF;
END$$

CREATE TRIGGER trg_coupons_notify_new AFTER INSERT ON coupons FOR EACH ROW
BEGIN
  IF NEW.status = 'active' THEN
    INSERT INTO customer_notifications (user_id, category, title, message, link)
    VALUES (NULL, 'offers',
            CONCAT('New offer: ', COALESCE(NULLIF(NEW.title, ''), NEW.coupon_code)),
            CONCAT('Use code ', NEW.coupon_code, ' at checkout',
                   IF(NEW.discount_value IS NOT NULL AND NEW.discount_value > 0,
                      CONCAT(' to get ', IF(COALESCE(NEW.discount_format, NEW.discount_type) = 'percentage',
                                            CONCAT(TRIM(TRAILING '.00' FROM NEW.discount_value), '% off'),
                                            CONCAT('₹', TRIM(TRAILING '.00' FROM NEW.discount_value), ' off'))),
                      ''), '.'),
            '/user/giftcard');
  END IF;
END$$

DELIMITER ;
