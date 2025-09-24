-- Fix reviews table structure - Add PRIMARY KEY and AUTO_INCREMENT
-- This will fix the issue where review IDs are all 0

-- First, let's fix the table structure
ALTER TABLE `reviews` 
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Add PRIMARY KEY if it doesn't exist
ALTER TABLE `reviews` 
ADD PRIMARY KEY (`id`);

-- Set AUTO_INCREMENT starting value (adjust if needed based on existing data)
ALTER TABLE `reviews` 
AUTO_INCREMENT = 1;

-- Optional: If you have existing reviews with id = 0, you might want to update them
-- UPDATE `reviews` SET `id` = NULL WHERE `id` = 0;
-- This will force MySQL to assign proper AUTO_INCREMENT values

-- Verify the fix by checking table structure
-- DESCRIBE `reviews`;