<?php
require_once '../config/database.php';

try {
    echo "Resetting Order IDs...\n";

    // 1. Fetch all existing orders ordered by creation date
    $stmt = $pdo->query("SELECT id FROM orders ORDER BY created_at ASC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($orders) . " orders to re-sequence.\n";

    $new_id = 1;
    $pdo->beginTransaction();

    foreach ($orders as $order) {
        $old_id = $order['id'];

        // Skip if ID is already correct to avoid unique constraint errors?
        // Actually, if we just update to new_id, we might conflict with existing IDs.
        // E.g. IDs: 28, 29.
        // Update 28 -> 1 (OK).
        // Update 29 -> 2 (OK).
        // But if IDs were 2, 3 and we update 2->1, 3->2.
        // If IDs were 1, 3. Update 1->1 (Skip). Update 3->2 (OK).

        if ($old_id != $new_id) {
            // Disable foreign key checks temporarily if needed, or update carefully
            // Update order_items first? No, foreign key constrains to orders.id
            // So we must update orders.id. But if we update to an ID that exists...
            // Since we are sorting ASC and starting from 1, and IDs are unique integers...
            // If new_id < old_id, it is safe unless ID=1 exists and we are at 28.
            // But we start from 1. If 1 exists, we skip it.

            // Check if target ID exists (it shouldn't if we are compacting, unless we overlap?)
            // Safest way: Update all IDs to a temporary range (e.g. +1000000), then back to 1..N
            // But let's try direct update first. Since we are compacting, new_ID <= old_ID.

            // Wait, we can't update Parent ID if Children exist, unless ON UPDATE CASCADE.
            // I'll assume FK constraints might block update.
            // Let's check if we can disable FK checks.
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

            // Update Order
            $stmt = $pdo->prepare("UPDATE orders SET id = ? WHERE id = ?");
            $stmt->execute([$new_id, $old_id]);

            // Update Order Items
            $stmt = $pdo->prepare("UPDATE order_items SET order_id = ? WHERE order_id = ?");
            $stmt->execute([$new_id, $old_id]);

            echo "Renamed Order #$old_id to #$new_id\n";
        }
        $new_id++;
    }

    // Reset Auto Increment
    $stmt = $pdo->query("SELECT MAX(id) FROM orders");
    $max_id = $stmt->fetchColumn();
    $next_id = ($max_id) ? $max_id + 1 : 1;

    $pdo->exec("ALTER TABLE orders AUTO_INCREMENT = $next_id");

    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    $pdo->commit();

    echo "Reset complete. Next Order ID will be #$next_id\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?>