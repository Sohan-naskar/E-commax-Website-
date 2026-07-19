<?php
// forget_account.php
if (isset($_GET['email'])) {
    $email_to_remove = $_GET['email'];
    $cookie_name = 'ecommax_saved_accounts';

    if (isset($_COOKIE[$cookie_name])) {
        $accounts = json_decode($_COOKIE[$cookie_name], true);
        if (is_array($accounts)) {
            // Filter out the email
            $accounts = array_filter($accounts, function ($acc) use ($email_to_remove) {
                return isset($acc['email']) && $acc['email'] !== $email_to_remove;
            });

            // Re-index array
            $accounts = array_values($accounts);

            // Update cookie
            setcookie($cookie_name, json_encode($accounts), time() + (86400 * 30), "/");
        }
    }
}

// Redirect back to the previous page if possible, else index
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header("Location: $redirect_url");
exit();
?>