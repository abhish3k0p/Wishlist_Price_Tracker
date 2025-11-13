-- Database schema updates for price notification system
-- Run these queries to add missing columns for the notification system

USE wishlist_tracker;

-- Add missing columns to wishlist table for tracking alerts
ALTER TABLE wishlist
ADD COLUMN alert_sent_at DATETIME NULL AFTER alert_sent,
ADD COLUMN last_alert_price DECIMAL(10,2) NULL AFTER alert_sent_at;

-- Optional: Create notifications table for logging all notifications
CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  type VARCHAR(50) NOT NULL DEFAULT 'price_alert',
  message TEXT NOT NULL,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (user_id, sent_at),
  INDEX (product_id, sent_at),
  CONSTRAINT fk_n_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_n_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;
