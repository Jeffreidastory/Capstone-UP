    -- Add expiration_batch column to stock_history table
    ALTER TABLE stock_history
    ADD COLUMN expiration_batch DATE NULL AFTER type,
    ADD INDEX idx_expiration (expiration_batch);

    -- Add cost_per_unit column to track batch costs
    ALTER TABLE stock_history
    ADD COLUMN cost_per_unit DECIMAL(10,2) NULL AFTER expiration_batch;