<?php
$apiUsername = 'yJjkh0gTQUWEO2WDn6kD';
$apiPassword = 'OqDzPgD4pdqlvCPiBGpejpBc5GO22MAafI7CwQ09';
$credentials = $apiUsername . ':' . $apiPassword;
$encodedCredentials = base64_encode($credentials);
$basicAuthToken = 'Basic ' . $encodedCredentials;

$message = '';

if (isset($_POST['submit'])) {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_INT);
    $phone_number = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING);
    $external_reference = filter_input(INPUT_POST, 'external_reference', FILTER_SANITIZE_STRING);

    $paymentData = array(
        'amount' => intval($amount),
        'phone_number' => $phone_number,
        'channel_id' => 2221,
        'provider' => 'm-pesa',
        'external_reference' => $external_reference,
        'callback_url' => 'https://aibuilder.onrender.com/callback'
    );

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://backend.payhero.co.ke/api/v2/payments',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($paymentData),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: ' . $basicAuthToken
        ),
    ));
    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);

    if ($error) {
        $message = 'Failed to initiate STK Push: ' . htmlspecialchars($error);
    } else {
        $result = json_decode($response, true);
        if ($result && isset($result['success']) && $result['success'] === true) {
            $message = "M-Pesa STK Push initiated, check your phone";
            $reference = $result['reference'];
        } else {
            $message = "STK Push failed to initiate: " . htmlspecialchars($response);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Processing - AI Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
        }
        .payment-form {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            background-color: #ffffff;
        }
        .form-header {
            margin-bottom: 30px;
            text-align: center;
            background-color: #00A651;
            color: #ffffff;
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }
        .form-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        .form-body {
            padding: 20px;
        }
        .form-footer {
            margin-top: 20px;
            text-align: center;
        }
        .payment-status {
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: center;
        }
        .error {
            color: #dc3545;
        }
        #loader {
            display: none;
            text-align: center;
            margin: 20px 0;
        }
        .spinner {
            display: inline-block;
            width: 50px;
            height: 50px;
            border: 4px solid transparent;
            border-radius: 50%;
            background: linear-gradient(white, white) padding-box,
                        linear-gradient(45deg, #00A651, #ffffff) border-box;
            box-shadow: 0 0 15px rgba(0, 166, 81, 0.2),
                        inset 0 0 10px rgba(0, 166, 81, 0.1);
            animation: spin 1.2s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
            position: relative;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .btn-primary {
            background-color: #00A651;
            border-color: #00A651;
        }
        .btn-primary:hover {
            background-color: #008c44;
            border-color: #008c44;
        }
        .warning-message {
            color: #dc3545;
            font-weight: bold;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="payment-form">
            <div class="form-header">
                <h2>Lipa na M-PESA</h2>
            </div>
            <div class="form-body">
                <p class="text-center text-muted mb-4">Pay Ksh 36 to download your cover letter</p>
                <?php if ($message): ?>
                    <div class="alert alert-info text-center"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <form id="paymentForm" class="needs-validation" method="POST" novalidate>
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="form-floating mb-3">
                                <input type="tel" class="form-control" id="phoneNumber" name="phone_number" placeholder="Enter phone number" pattern="^07\d{8}$" value="0706625195" required>
                                <label for="phoneNumber"><i class="bi bi-phone-fill"></i> M-PESA Phone Number</label>
                                <div class="invalid-feedback">Please enter a valid M-Pesa phone number starting with 07 (e.g., 0712345678)</div>
                                <div class="form-text text-muted"><i class="bi bi-info-circle-fill"></i> Enter your M-Pesa registered phone number</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating mb-3">
                                <input type="number" class="form-control" id="amount" name="amount" value="36" readonly required>
                                <label for="amount"><i class="bi bi-currency-exchange"></i> Amount (Ksh)</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="externalReference" name="external_reference" value="<?php echo 'REF_' . time() . '_' . bin2hex(random_bytes(4)); ?>" readonly required>
                                <label for="externalReference"><i class="bi bi-receipt"></i> External Reference</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg position-relative" name="submit" id="submitBtn">
                                    <i class="bi bi-credit-card-fill me-2"></i>
                                    Pay Now Ksh 36
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <div id="loader">
                    <div class="spinner"></div>
                    <p class="mt-3">Processing payment. Please check your phone for the M-Pesa prompt...</p>
                    <p class="warning-message">Please do not leave this page until you receive a successful payment confirmation.</p>
                </div>
                <div class="payment-status" id="paymentStatus"></div>
            </div>
            <div class="form-footer">
                <p class="text-muted small">Powered by <a href="https://payherokenya.com" target="_blank">Pay Hero Kenya</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script>
        const paymentConfig = {
            successURL: 'https://thecvarchitect.github.io/Aibuilder/p/cover-letter-download.html',
            failedURL: 'https://thecvarchitect.github.io/Aibuilder/index.html'
        };

        const coverLetterData = localStorage.getItem('coverLetterData');
        if (!coverLetterData) {
            Swal.fire({
                title: 'No Cover Letter Found',
                text: 'Please generate a cover letter before proceeding to payment.',
                icon: 'warning',
                confirmButtonText: 'Go Back'
            }).then(() => {
                window.location.href = paymentConfig.failedURL;
            });
        }

        (function () {
            'use strict';
            const form = document.getElementById('paymentForm');
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        })();

        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            document.getElementById('loader').style.display = 'block';
        });

        <?php if (isset($reference)): ?>
            startPaymentStatusCheck('<?php echo $reference; ?>');
        <?php endif; ?>

        async function startPaymentStatusCheck(reference) {
            let attempts = 0;
            const maxAttempts = 90;
            const interval = 1000;
            const statusElement = document.getElementById('paymentStatus');

            try {
                const intervalId = setInterval(async () => {
                    attempts++;
                    try {
                        const response = await fetch(`https://aibuilder.onrender.com/api/transaction-status?reference=${reference}`, {
                            mode: 'cors',
                            headers: { 'Accept': 'application/json' }
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status} - ${response.statusText}`);
                        }

                        const statusResult = await response.json();
                        if (!statusResult || typeof statusResult !== 'object') {
                            throw new Error('Invalid response format');
                        }

                        console.log('Payment status:', statusResult);

                        if (statusResult.success === false) {
                            throw new Error(statusResult.error || 'Failed to check payment status');
                        }

                        if (statusResult.success) {
                            if (statusResult.status === 'COMPLETED') {
                                clearInterval(intervalId);
                                statusElement.className = 'payment-status alert alert-success';
                                statusElement.textContent = 'Payment successful! Redirecting...';
                                statusElement.style.display = 'block';
                                statusElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                setTimeout(() => window.location.href = paymentConfig.successURL, 2000);
                                return;
                            } else if (statusResult.status === 'FAILED') {
                                throw new Error('Payment failed');
                            } else {
                                statusElement.className = 'payment-status alert alert-info';
                                statusElement.textContent = `Payment status: ${statusResult.status}`;
                                statusElement.style.display = 'block';
                            }
                        }

                        if (attempts >= maxAttempts) {
                            throw new Error('Payment processing timed out');
                        }
                    } catch (error) {
                        clearInterval(intervalId);
                        console.error('Payment status check error:', error);
                        statusElement.className = 'payment-status error';
                        statusElement.textContent = error.message === 'Payment processing timed out'
                            ? 'Payment processing failed or timed out.'
                            : error.message.includes('HTTP error')
                            ? `Server error: ${error.message}`
                            : 'Payment failed. Please try again.';
                        statusElement.style.display = 'block';
                        statusElement.scrollIntoView({ behavior: 'smooth', block: 'center' });

                        const submitBtn = document.getElementById('submitBtn');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-credit-card-fill me-2"></i> Pay Now Ksh 36';
                        document.getElementById('loader').style.display = 'none';

                        setTimeout(() => window.location.href = paymentConfig.failedURL, 3000);
                    }
                }, interval);
            } catch (error) {
                console.error('Payment check failed to start:', error);
                statusElement.className = 'payment-status error';
                statusElement.textContent = 'Failed to start payment check due to an error';
                statusElement.style.display = 'block';
                statusElement.scrollIntoView({ behavior: 'smooth', block: 'center' });

                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-credit-card-fill me-2"></i> Pay Now Ksh 36';
                document.getElementById('loader').style.display = 'none';
            }
        }
    </script>
</body>
</html>
