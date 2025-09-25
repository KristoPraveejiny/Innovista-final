-- STEP-BY-STEP SQL FIX FOR REVIEWS TABLE
-- Run these commands one by one in phpMyAdmin SQL tab

-- Step 1: First, let's add AUTO_INCREMENT to the id column
ALTER TABLE `reviews` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Step 2: Add PRIMARY KEY if it doesn't exist (this might give an error if it already exists, that's okay)
ALTER TABLE `reviews` ADD PRIMARY KEY (`id`);

-- Step 3: Set the AUTO_INCREMENT starting value
ALTER TABLE `reviews` AUTO_INCREMENT = 1;

-- Step 4: Check if there are any existing reviews with id = 0 and fix them
UPDATE `reviews` SET `id` = NULL WHERE `id` = 0;

-- Step 5: Verify the fix by checking table structure
-- DESCRIBE `reviews`;

-- ALTERNATIVE: If you get errors, you can also recreate the table with correct structure:
-- (Only use this if the above doesn't work)

/*
-- Backup existing data first
CREATE TABLE reviews_backup AS SELECT * FROM reviews;

-- Drop and recreate table
DROP TABLE reviews;

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Restore data (this will auto-assign proper IDs)
INSERT INTO reviews (quotation_id, customer_id, provider_id, rating, comment, created_at)
SELECT quotation_id, customer_id, provider_id, rating, comment, created_at FROM reviews_backup;

-- Drop backup table
DROP TABLE reviews_backup;
*/