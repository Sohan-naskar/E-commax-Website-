# Admin Data Fix Script
I will create `admin/fix_data.php` to clean up inconsistencies in `orders`.

## Logic
Delete orders that are missing related foreign keys which causes them to be hidden from the JOIN query.

```sql
DELETE o
FROM orders o
LEFT JOIN customers c ON o.customer_id = c.id
LEFT JOIN order_items oi ON o.id = oi.order_id
LEFT JOIN products p ON oi.product_id = p.id
WHERE c.id IS NULL
OR oi.id IS NULL
OR p.id IS NULL;
```

```php
<?php
require_once '../config/database.php';

try {
    echo "Cleaning up orders...\n";

    // Delete orders with invalid customers
    $stmt = $pdo->exec("
        DELETE o FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        WHERE c.id IS NULL
    ");
    echo "Deleted orders with missing customers: $stmt\n";

    // Delete orders with invalid order_items (products removed)
    // Note: This needs to check if ALL items in an order are invalid, or ANY.
    // The view joins order_items and products. If ANY item is missing product, that row is hidden?
    // The view uses joins: o -> oi -> p.
    // Actually, distinct orders are grouped.
    // If an order has 2 items, and 1 product is missing, the other item might show up?
    // But if GROUP BY o.id is used, and the join filters out rows...
    // If an order has NO valid items (all products deleted), it won't show.

    // Let's first delete order_items regarding missing products
    $stmt = $pdo->exec("
        DELETE oi FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE p.id IS NULL
    ");
    echo "Deleted order_items for missing products: $stmt\n";

    // Now delete orders that have NO items left
    $stmt = $pdo->exec("
        DELETE o FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE oi.id IS NULL
    ");
    echo "Deleted empty orders: $stmt\n";

    echo "Cleanup complete.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
```