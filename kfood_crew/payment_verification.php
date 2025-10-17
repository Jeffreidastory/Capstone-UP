<?php
session_start();
require_once "../connect.php";

if (!isset($_SESSION['crew_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Processing - Crew Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payment-verification-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            width: 90%;
            max-width: 600px;
            border-radius: 8px;
            position: relative;
        }

        .modal-close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .payment-details {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .payment-proof {
            margin: 20px 0;
            text-align: center;
        }

        .payment-proof img {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .verification-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn-approve {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-reject {
            background: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-approve:hover {
            background: #45a049;
        }

        .btn-reject:hover {
            background: #da190b;
        }

        .verification-notes {
            margin-top: 15px;
        }

        .verification-notes textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 80px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>

<!-- Payment Verification Modal -->
<div id="paymentVerificationModal" class="payment-verification-modal">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h2>Verify GCash Payment</h2>
        
        <div class="payment-details">
            <p><strong>Order ID:</strong> <span id="orderIdDisplay"></span></p>
            <p><strong>Customer:</strong> <span id="customerName"></span></p>
            <p><strong>Amount:</strong> ₱<span id="paymentAmount"></span></p>
            <p><strong>Reference Number:</strong> <span id="referenceNumber"></span></p>
        </div>

        <div class="payment-proof">
            <h3>Payment Screenshot</h3>
            <img id="paymentProofImage" src="" alt="Payment Proof">
        </div>

        <div class="verification-notes">
            <label for="verificationNotes">Verification Notes:</label>
            <textarea id="verificationNotes" placeholder="Add notes about the verification (optional)"></textarea>
        </div>

        <div class="verification-actions">
            <button class="btn-reject" onclick="verifyPayment('invalid')">
                <i class="fas fa-times"></i> Reject
            </button>
            <button class="btn-approve" onclick="verifyPayment('verified')">
                <i class="fas fa-check"></i> Approve
            </button>
        </div>
    </div>
</div>

<script>
// Function to show payment verification modal
function showPaymentVerification(orderId) {
    // Fetch payment details
    fetch(`get_payment_details.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const payment = data.payment;
                document.getElementById('orderIdDisplay').textContent = orderId;
                document.getElementById('customerName').textContent = payment.customer_name;
                document.getElementById('paymentAmount').textContent = parseFloat(payment.total_price).toFixed(2);
                document.getElementById('referenceNumber').textContent = payment.reference_number;
                document.getElementById('paymentProofImage').src = '../uploaded_img/payment_proofs/' + payment.payment_proof;
                document.getElementById('paymentVerificationModal').style.display = 'block';
            } else {
                alert('Error loading payment details: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
}

// Function to verify payment
function verifyPayment(status) {
    const orderId = document.getElementById('orderIdDisplay').textContent;
    const notes = document.getElementById('verificationNotes').value;
    
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('status', status);
    formData.append('remarks', notes);

    fetch('verify_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            document.getElementById('paymentVerificationModal').style.display = 'none';
            // Refresh the orders list
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Close modal when clicking the X button
document.querySelector('.modal-close').addEventListener('click', () => {
    document.getElementById('paymentVerificationModal').style.display = 'none';
});

// Close modal when clicking outside
window.addEventListener('click', (event) => {
    const modal = document.getElementById('paymentVerificationModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});
</script>

</body>
</html>