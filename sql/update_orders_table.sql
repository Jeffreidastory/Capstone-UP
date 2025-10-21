-- Add delivery_time and completion_time columns to orders table
ALTER TABLE orders 
ADD COLUMN delivery_time TIMESTAMP NULL DEFAULT NULL COMMENT 'When the order status changed to out for delivery',
ADD COLUMN completion_time TIMESTAMP NULL DEFAULT NULL COMMENT 'When the order was completed';