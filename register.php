<?php
session_start();
require_once 'connect.php';
require_once 'Session.php';

require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

// Function to check for common passwords
function isCommonPassword($password) {
    $commonPasswords = [
        '123456', 'password', 'qwerty', 'admin123', 'password123',
        '12345678', '123456789', 'abc123', '1234567', 'password1'
    ];
    return in_array(strtolower($password), $commonPasswords);
}

// Function to check for consecutive/repeated characters
function hasConsecutiveRepeatedChars($password) {
    // Check for repeated characters (more than 3 times)
    if (preg_match('/(.)\1{3,}/', $password)) {
        return true;
    }
    // Check for sequential numbers or letters (more than 4 in sequence)
    if (preg_match('/(0123|1234|2345|3456|4567|5678|6789|abcd|bcde|cdef|defg|efgh|fghi|ghij|hijk|ijkl|jklm|klmn|lmno|mnop|nopq|opqr|pqrs|qrst|rstu|stuv|tuvw|uvwx|vwxy|wxyz)/i', $password)) {
        return true;
    }
    return false;
}

// Function to generate OTP
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

if(isset($_POST["submit"])) {
    $firstName = mysqli_real_escape_string($conn, $_POST["firstName"]);
    $lastName = mysqli_real_escape_string($conn, $_POST["lastName"]);
    $username = mysqli_real_escape_string($conn, $_POST["username"]);
    $email = mysqli_real_escape_string($conn, $_POST["email"]);
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirmPassword"];
    
    // Password validation
    $passwordErrors = [];
    
    // Length check
    if (strlen($password) < 8) {
        $passwordErrors[] = "Password must be at least 8 characters long";
    }
    
    // Character mix check
    if (!preg_match('/[A-Z]/', $password)) {
        $passwordErrors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $passwordErrors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $passwordErrors[] = "Password must contain at least one number";
    }
    if (!preg_match('/[!@#$%^&*]/', $password)) {
        $passwordErrors[] = "Password must contain at least one special character (!@#$%^&*)";
    }
    
    // Common password check
    if (isCommonPassword($password)) {
        $passwordErrors[] = "This password is too common. Please choose a stronger password";
    }
    
    // Consecutive/Repeated characters check
    if (hasConsecutiveRepeatedChars($password)) {
        $passwordErrors[] = "Password cannot contain repeated or sequential characters";
    }
    
    if (!empty($passwordErrors)) {
        $error = "Password Requirements:<br>" . implode("<br>", $passwordErrors);
    }
    
    // Validate password match
    if($password !== $confirmPassword) {
        $error = "Passwords do not match";
    } else {
        // Check if username exists
        $checkUsername = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $checkUsername->bind_param("s", $username);
        $checkUsername->execute();
        if($checkUsername->get_result()->num_rows > 0) {
            $error = "Username already exists";
        } else {
            // Check if email exists
            $checkEmail = $conn->prepare("SELECT id FROM users WHERE Email = ?");
            $checkEmail->bind_param("s", $email);
            $checkEmail->execute();
            if($checkEmail->get_result()->num_rows > 0) {
                $error = "Email already registered";
            } else {
                // Hash password with proper security
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user with default role_id for customers (4)
                $stmt = $conn->prepare("INSERT INTO users (firstName, lastName, username, Email, Password, role_id) VALUES (?, ?, ?, ?, ?, 4)");
                $stmt->bind_param("sssss", $firstName, $lastName, $username, $email, $hashedPassword);
                
                // Generate and store OTP
                $otp = generateOTP();
                
                // Store OTP in database
                $otpStmt = $conn->prepare("INSERT INTO otp_codes (email, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
                $otpStmt->bind_param("ss", $email, $otp);
                
                if($otpStmt->execute()) {
                    // Store user data in session temporarily
                    $_SESSION['temp_user_data'] = [
                        'firstName' => $firstName,
                        'lastName' => $lastName,
                        'username' => $username,
                        'email' => $email,
                        'password' => $hashedPassword
                    ];
                    $_SESSION['temp_email'] = $email;

                    // Send OTP email
                    $mail = new PHPMailer(true);
                    
                    try {
                        // Server settings
                        $mail->SMTPDebug = 0;  // Disable debug output for production
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'jffrsnfeliciano0000@gmail.com'; // System email
                        $mail->Password = 'spvw ying kumg wwms'; // App password for Gmail SMTP
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;
                        $mail->CharSet = 'UTF-8';
                        
                        // Set additional parameters
                        $mail->Timeout = 60; // Set timeout to 60 seconds
                        $mail->AuthType = 'LOGIN';
                        $mail->SMTPKeepAlive = true; // Keep the connection alive
                        
                        // Set the mailer to throw exceptions on error
                        $mail->SMTPOptions = array(
                            'ssl' => array(
                                'verify_peer' => true,
                                'verify_peer_name' => true,
                                'allow_self_signed' => false
                            )
                        );

                        $mail->setFrom('jffrsnfeliciano0000@gmail.com', 'K-Food Delight');
                        $mail->addAddress($email);

                        $mail->isHTML(true);
                        $mail->Subject = 'Email Verification - K-Food Delight';
                        $mail->Body = "Your verification code is: <b>{$otp}</b><br>This code will expire in 10 minutes.";

                        $mail->send();
                        $success = "Please check your email for verification code.";
                        // Set flag to show OTP form
                        $showOTPForm = true;
                    } catch (Exception $e) {
                        // Log the error for debugging
                        error_log("Mailer Error: " . $e->getMessage());
                        
                        // Set a user-friendly error message
                        if (strpos($e->getMessage(), 'authenticate') !== false) {
                            $error = "Email system temporarily unavailable. Please try again later.";
                        } else {
                            $error = "Failed to send verification code. Please try again.";
                        }
                        
                        // Clear any partial progress
                        if (isset($otpStmt)) {
                            $conn->query("DELETE FROM otp_codes WHERE email = '" . $conn->real_escape_string($email) . "' AND verified = 0");
                        }
                    }
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - K-Food Delight</title>
    <link rel="stylesheet" href="new-login-style.css">
    <style>
        /* OTP Form Container */
        .otp-form {
            max-width: 720px;
            width: 98%;
            margin: 1.5rem auto;
            text-align: center;
            padding: 2rem;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        /* OTP Header */
        .otp-header {
            margin-bottom: 1.5rem;
        }

        .otp-header h2 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 0.8rem;
            font-weight: 600;
        }

        .otp-header p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .email-display {
            background: #f8f9fa;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            color: #ff6b6b;
            font-weight: 500;
            margin: 1.5rem auto;
            word-break: break-all;
            font-size: 1.1rem;
            border: 1px solid rgba(0,0,0,0.1);
            max-width: 90%;
            overflow-wrap: break-word;
        }

        /* OTP Input Group */
        .otp-input-group {
            display: flex;
            gap: 0.4rem;
            justify-content: center;
            margin: 1.5rem auto;
            flex-wrap: nowrap;
            padding: 0;
            width: 95%;
            max-width: 480px;
        }

        @media (max-width: 480px) {
            .otp-input-group {
                gap: 0.6rem;
            }
        }

        .otp-input {
            width: calc(16% - 4px);
            min-width: 40px;
            height: 45px;
            font-size: 1.5rem;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: #f8f9fa;
            transition: all 0.3s ease;
            font-weight: 600;
            -webkit-appearance: none;
            -moz-appearance: textfield;
            margin: 0;
            padding: 0;
        }

        @media (max-width: 480px) {
            .otp-input-group {
                width: 100%;
                gap: 0.3rem;
            }
            .otp-input {
                height: 40px;
                font-size: 1.2rem;
                border-radius: 6px;
            }
            .otp-form {
                padding: 1.5rem 1rem;
            }
        }

        .otp-input::-webkit-outer-spin-button,
        .otp-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .otp-input:focus {
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.15);
            outline: none;
            background: #fff;
        }

        .otp-input.filled {
            border-color: #ff6b6b;
            background: #fff5f5;
            transform: translateY(-2px);
        }

        .otp-input:focus {
            border-color: #ff6b6b;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }

        /* OTP Footer */
        .otp-footer {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(0,0,0,0.08);
        }

        .verify-btn, 
        button[type="submit"].auth-button {
            background: linear-gradient(45deg, #ff6b6b, #ff8787);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .verify-btn:hover,
        button[type="submit"].auth-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.25);
            background: linear-gradient(45deg, #ff5252, #ff7676);
        }

        .verify-btn:active,
        button[type="submit"].auth-button:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(255, 107, 107, 0.2);
        }

        .verify-btn:disabled,
        button[type="submit"].auth-button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .resend-btn {
            background: none;
            border: none;
            color: #ff6b6b;
            font-size: 1rem;
            cursor: pointer;
            padding: 8px 16px;
            text-decoration: underline;
            transition: all 0.3s ease;
        }

        .resend-btn:hover {
            color: #ff5252;
            text-decoration: none;
        }

        .resend-btn:disabled {
            color: #999;
            cursor: not-allowed;
            text-decoration: none;
        }

        .timer {
            color: #666;
            font-size: 1rem;
            margin-top: 1rem;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 8px;
            display: inline-block;
        }

        .timer span {
            color: #ff6b6b;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .resend-btn {
            background: none;
            border: none;
            color: #ff6b6b;
            cursor: pointer;
            font-size: inherit;
            padding: 0;
            text-decoration: underline;
        }

        .resend-btn:disabled {
            color: #999;
            cursor: not-allowed;
            text-decoration: none;
        }

        /* Verification Messages */
        .verification-message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .verification-message.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .verification-message.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .verification-message i {
            font-size: 1.2rem;
        }

        .timer {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <img src="images/logo.png" alt="K-Food Delight">
            <h1>Create Account</h1>
            <p>Join K-Food Delight today</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="registerForm" <?php echo isset($showOTPForm) ? 'style="display: none;"' : ''; ?>>
            <div class="name-group">
                <div class="form-group">
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" name="firstName" required
                           value="<?php echo isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" name="lastName" required
                           value="<?php echo isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-input">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="toggle-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="confirmPassword">Confirm Password</label>
                <div class="password-input">
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                    <button type="button" class="toggle-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="terms">
                <label class="remember-me">
                    <input type="checkbox" name="terms" id="terms" required>
                    <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
                </label>
            </div>

            <button type="submit" name="submit" class="auth-button">Create Account</button>

            <div class="auth-footer">
                <p>Already have an account? <a href="loginpage.php">Sign In</a></p>
            </div>
        </form>

        <!-- OTP Verification Form -->
        <form method="POST" id="otpForm" class="otp-form" <?php echo !isset($showOTPForm) ? 'style="display: none;"' : ''; ?>>
            <div class="otp-header">
                <h2>Email Verification</h2>
                <p>Please enter the verification code sent to your email</p>
                <p class="email-display"><?php echo isset($_SESSION['temp_email']) ? htmlspecialchars($_SESSION['temp_email']) : ''; ?></p>
            </div>

            <div class="otp-input-group">
                <input type="text" maxlength="1" class="otp-input" data-index="1">
                <input type="text" maxlength="1" class="otp-input" data-index="2">
                <input type="text" maxlength="1" class="otp-input" data-index="3">
                <input type="text" maxlength="1" class="otp-input" data-index="4">
                <input type="text" maxlength="1" class="otp-input" data-index="5">
                <input type="text" maxlength="1" class="otp-input" data-index="6">
            </div>

            <button type="button" id="verifyOTPBtn" class="auth-button">Verify Code</button>

            <div class="otp-footer">
                <p>Didn't receive the code? 
                    <button type="button" id="resendOTP" class="resend-btn">Resend Code</button>
                </p>
                <p class="timer">Resend available in <span id="countdown">02:00</span></p>
            </div>
        </form>
    </div>

    <script>
        // OTP Handling
        document.querySelectorAll('.otp-input').forEach(input => {
            // Handle input events
            input.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
                
                if (this.value) {
                    this.classList.add('filled');
                } else {
                    this.classList.remove('filled');
                }

                if (this.value.length === 1) {
                    const index = parseInt(this.getAttribute('data-index'));
                    const nextInput = document.querySelector(`.otp-input[data-index="${index + 1}"]`);
                    if (nextInput) {
                        nextInput.focus();
                    }
                }
            });

            // Handle keyboard events
            input.addEventListener('keyup', function(e) {
                const index = parseInt(this.getAttribute('data-index'));
                
                // Move to next input if current is filled
                if (this.value.length === 1 && index < 6) {
                    const nextInput = document.querySelector(`.otp-input[data-index="${index + 1}"]`);
                    if (nextInput) nextInput.focus();
                }
                
                // Handle backspace
                if (e.key === 'Backspace' && index > 1) {
                    const prevInput = document.querySelector(`.otp-input[data-index="${index - 1}"]`);
                    if (prevInput) prevInput.focus();
                }
            });
        });

        // Verify OTP
        document.getElementById('verifyOTPBtn')?.addEventListener('click', function() {
            const otpInputs = document.querySelectorAll('.otp-input');
            const otp = Array.from(otpInputs).map(input => input.value).join('');
            
            if (otp.length !== 6) {
                alert('Please enter the complete verification code.');
                return;
            }

            fetch('verify_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `verify_otp=1&otp=${otp}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Registration successful!');
                    window.location.href = 'loginpage.php';
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
            });
        });

        // Resend OTP
        let countdown;
        function startCountdown() {
            const resendBtn = document.getElementById('resendOTP');
            const timerSpan = document.getElementById('countdown');
            let timeLeft = 120; // 2 minutes

            resendBtn.disabled = true;
            
            countdown = setInterval(() => {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerSpan.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    resendBtn.disabled = false;
                    document.querySelector('.timer').style.display = 'none';
                }
                timeLeft--;
            }, 1000);
        }

        document.getElementById('resendOTP')?.addEventListener('click', function() {
            fetch('verify_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'resend_otp=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('New verification code sent!');
                    document.querySelector('.timer').style.display = 'block';
                    startCountdown();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                alert('An error occurred while resending the code.');
            });
        });

        // Start countdown when OTP form is shown
        if (document.getElementById('otpForm')?.style.display !== 'none') {
            startCountdown();
        }

        // Password visibility toggle
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        });

        // Common passwords list
        const commonPasswords = [
            '123456', 'password', 'qwerty', 'admin123', 'password123',
            '12345678', '123456789', 'abc123', '1234567', 'password1'
        ];

        // Function to check for consecutive/repeated characters
        function hasConsecutiveRepeatedChars(password) {
            // Check for repeated characters (more than 3 times)
            if (/(.)\1{3,}/.test(password)) {
                return true;
            }
            // Check for sequential numbers or letters (more than 4 in sequence)
            if (/(0123|1234|2345|3456|4567|5678|6789|abcd|bcde|cdef|defg|efgh|fghi|ghij|hijk|ijkl|jklm|klmn|lmno|mnop|nopq|opqr|pqrs|qrst|rstu|stuv|tuvw|uvwx|vwxy|wxyz)/i.test(password)) {
                return true;
            }
            return false;
        }

        // Function to validate password
        function validatePassword(password) {
            const errors = [];
            
            // Length check
            if (password.length < 8) {
                errors.push("Password must be at least 8 characters long");
            }
            
            // Character mix check
            if (!/[A-Z]/.test(password)) {
                errors.push("Must contain at least one uppercase letter");
            }
            if (!/[a-z]/.test(password)) {
                errors.push("Must contain at least one lowercase letter");
            }
            if (!/[0-9]/.test(password)) {
                errors.push("Must contain at least one number");
            }
            if (!/[!@#$%^&*]/.test(password)) {
                errors.push("Must contain at least one special character (!@#$%^&*)");
            }
            
            // Common password check
            if (commonPasswords.includes(password.toLowerCase())) {
                errors.push("This password is too common");
            }
            
            // Consecutive/Repeated characters check
            if (hasConsecutiveRepeatedChars(password)) {
                errors.push("Cannot contain repeated or sequential characters");
            }
            
            return errors;
        }

        // Real-time password validation
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const errors = validatePassword(password);
            
            // Create or get the error message container
            let errorDiv = this.parentElement.querySelector('.password-errors');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'password-errors';
                errorDiv.style.color = '#ff4444';
                errorDiv.style.fontSize = '12px';
                errorDiv.style.marginTop = '5px';
                this.parentElement.appendChild(errorDiv);
            }
            
            if (errors.length > 0) {
                errorDiv.innerHTML = errors.join('<br>');
            } else {
                errorDiv.innerHTML = '<span style="color: #4CAF50">Password meets all requirements ✓</span>';
            }
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const terms = document.getElementById('terms');
            const errors = validatePassword(password);

            if (errors.length > 0) {
                e.preventDefault();
                alert('Please fix the password requirements:\n\n' + errors.join('\n'));
                return;
            }

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                return;
            }

            if (!terms.checked) {
                e.preventDefault();
                alert('Please agree to the Terms of Service and Privacy Policy');
            }
        });
    </script>
</body>
</html>