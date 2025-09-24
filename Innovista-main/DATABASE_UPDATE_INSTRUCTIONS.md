# Database Update Instructions

## IMPORTANT: Run this SQL in your database first!

Before testing the new consultation booking functionality, you need to update your database structure:

1. Open phpMyAdmin or your MySQL client
2. Select your `innovista` database
3. Run the SQL commands from: `sql/update_payment_earnings_structure.sql`

Or copy and paste these commands directly:

```sql
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
```

## What's New:

1. **Payments Table**: Now has a separate `book_consult_amount` column to track consultation fees separately from regular project payments.

2. **Provider Earnings Table**: New table that tracks:
   - `total_earnings`: Regular project earnings
   - `book_consult_earnings`: Consultation booking earnings
   - `total_combined_earnings`: Automatically calculated total of both

3. **Provider Dashboard**: Now shows a breakdown:
   - Total Combined Earnings (main display)
   - Projects earnings breakdown
   - Consultation earnings breakdown

4. **Consultation Booking**: Now properly records consultation payments in the separate column and updates provider consultation earnings.

## Testing:
After running the SQL, try booking a consultation - it should now properly track consultation earnings separately from project earnings!