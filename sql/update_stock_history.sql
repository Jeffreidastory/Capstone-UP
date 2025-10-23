-- Drop the existing foreign key constraint
ALTER TABLE stock_history
DROP FOREIGN KEY stock_history_ibfk_1;

-- Add the new foreign key constraint referencing new_products
ALTER TABLE stock_history
ADD CONSTRAINT stock_history_ibfk_1
FOREIGN KEY (product_id) REFERENCES new_products(id)
ON DELETE CASCADE ON UPDATE CASCADE;