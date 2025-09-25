<?php
// Simple test script to check book_consultation table
require_once 'config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>Testing book_consultation table</h2>";
    
    // Check if table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'book_consultation'");
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "✅ Table 'book_consultation' exists<br>";
        
        // Check table structure
        $stmt = $conn->prepare("DESCRIBE book_consultation");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test a simple insert (will rollback)
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare("
                INSERT INTO book_consultation 
                (customer_id, provider_id, service_id, date, time, amount_paid) 
                VALUES (1, 1, 1, '2024-12-25', '10:00:00', 500.00)
            ");
            $stmt->execute();
            echo "<br>✅ Test insert successful (rolling back...)<br>";
            $conn->rollBack();
        } catch (PDOException $e) {
            $conn->rollBack();
            echo "<br>❌ Test insert failed: " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "❌ Table 'book_consultation' does not exist<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage();
}
?>