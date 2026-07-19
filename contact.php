<?php
// includes/auth_check.php is already included in header logic essentially, but let's be safe if we need user id
session_start();
require_once 'config/database.php';

$msg_sent = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_SESSION['user_id'])) {
        $customer_id = $_SESSION['user_id'];
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']); // Maps to 'message' column

        if (!empty($message)) {
            $stmt = $pdo->prepare("INSERT INTO messages (customer_id, subject, message) VALUES (?, ?, ?)");
            $stmt->execute([$customer_id, $subject, $message]);
            $msg_sent = true;
        }
    } else {
        // Force login if trying to send message (optional, user requested "customer can do the message")
        header("Location: login.php");
        exit();
    }
}

include 'includes/header.php';
?>

<section class="py-5 bg-light">
    <div class="container text-center">
        <h1 class="display-4 fw-bold">Contact Us</h1>
        <p class="lead text-muted">Have a question? We'd love to hear from you.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm p-4">
                    <?php if($msg_sent): ?>
                        <div class="alert alert-success">Message sent successfully! The admin will review it shortly.</div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" value="<?= $_SESSION['user_name'] ?? 'Guest' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Your Email">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-dark">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>