<?php require '../config/config.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="container">
        <div class="auth-card">
            <h1>Create Account</h1>
            <p class="subtitle">Join us today</p>

            <form action="register.php" method="post" class="form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required placeholder="John">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required placeholder="Doe">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com">
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" required placeholder="(555) 000-0000" maxlength="14">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" minlength="12" required
                        placeholder="Min. 12 characters">
                    <p class="hint">Must contain: uppercase, lowercase, number, and symbol</p>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                        placeholder="Re-enter password">
                </div>

                <button type="submit" class="btn btn-primary">Create Account</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php" class="link">Sign in here</a></p>
            </div>
        </div>
    </div>

    <script>
        const phoneInput = document.getElementById('phone');

        phoneInput.addEventListener('input', function(e) {
            // Remove all non-digit characters
            let value = e.target.value.replace(/\D/g, '');
            
            // Limit to 10 digits
            if (value.length > 10) {
                value = value.slice(0, 10);
            }

            // Format as (XXX) XXX-XXXX
            if (value.length >= 6) {
                e.target.value = '(' + value.slice(0, 3) + ') ' + value.slice(3, 6) + '-' + value.slice(6, 10);
            } else if (value.length >= 3) {
                e.target.value = '(' + value.slice(0, 3) + ') ' + value.slice(3);
            } else if (value.length > 0) {
                e.target.value = value;
            } else {
                e.target.value = '';
            }
        });

        // Prevent non-numeric input
        phoneInput.addEventListener('keypress', function(e) {
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });
    </script>
</body>

</html>