-- Update payments table to include book_consult_amount column
ALTER TABLE `payments` 
ADD COLUMN `book_consult_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `amount`;

-- Create provider_earnings table if it doesn't exist
CREATE TABLE IF NOT EXISTS `provider_earnings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_id` int(11) NOT NULL,
  `total_earnings` decimal(15,2) DEFAULT 0.00,
  `book_consult_earnings` decimal(15,2) DEFAULT 0.00,
  `total_combined_earnings` decimal(15,2) GENERATED ALWAYS AS (`total_earnings` + `book_consult_earnings`) STORED,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_id` (`provider_id`),
  FOREIGN KEY (`provider_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Update existing payment records to set book_consult_amount to 0 if NULL
UPDATE `payments` SET `book_consult_amount` = 0.00 WHERE `book_consult_amount` IS NULL;