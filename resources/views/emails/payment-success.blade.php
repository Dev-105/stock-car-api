<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - RFIFISA</title>
    <style>
        @import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #000000 0%, #0a0a0a 100%);
            margin: 0;
            padding: 20px;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: linear-gradient(135deg, #0a0a0a 0%, #000000 100%);
            border-radius: 24px;
            overflow: hidden;
            border: 2px solid rgba(212, 175, 55, 0.3);
            box-shadow: 0 25px 50px -12px rgba(212, 175, 55, 0.25);
        }
        
        /* Gold Header */
        .email-header {
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.15) 0%, rgba(255, 215, 0, 0.1) 100%);
            padding: 40px 30px;
            text-align: center;
            border-bottom: 2px solid rgba(212, 175, 55, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .email-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.1) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { transform: translate(-30%, -30%); opacity: 0.5; }
            50% { transform: translate(-50%, -50%); opacity: 1; }
        }
        
        .crown-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #D4AF37 0%, #FFD700 100%);
            border-radius: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px -5px rgba(212, 175, 55, 0.4);
        }
        
        .crown-icon svg {
            width: 32px;
            height: 32px;
            color: #000000;
        }
        
        h1 {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, #D4AF37 0%, #FFD700 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }
        
        .subtitle {
            color: rgba(212, 175, 55, 0.7);
            font-size: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        /* Content Area */
        .email-content {
            padding: 40px 30px;
        }
        
        .greeting {
            margin-bottom: 30px;
        }
        
        .greeting h2 {
            color: #FFFFFF;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .greeting p {
            color: #9CA3AF;
            line-height: 1.6;
            font-size: 16px;
        }
        
        /* Order Details Card */
        .order-card {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            margin: 30px 0;
            border: 1px solid rgba(212, 175, 55, 0.3);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
            margin-bottom: 20px;
        }
        
        .order-label {
            color: #D4AF37;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .order-number {
            color: #FFFFFF;
            font-size: 20px;
            font-weight: 700;
            font-family: monospace;
            background: rgba(212, 175, 55, 0.1);
            padding: 5px 12px;
            border-radius: 12px;
            border: 1px solid rgba(212, 175, 55, 0.3);
        }
        
        .order-detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
        }
        
        .detail-label {
            color: #9CA3AF;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .detail-label i {
            color: #D4AF37;
            font-size: 18px;
        }
        
        .detail-value {
            color: #FFFFFF;
            font-size: 18px;
            font-weight: 700;
        }
        
        .total-amount {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid rgba(212, 175, 55, 0.3);
        }
        
        .total-amount .detail-value {
            color: #D4AF37;
            font-size: 24px;
            font-weight: 800;
        }
        
        /* Success Badge */
        .success-badge {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 60px;
            padding: 12px 20px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .success-badge i {
            color: #10B981;
            font-size: 20px;
        }
        
        .success-badge span {
            color: #10B981;
            font-weight: 600;
            font-size: 14px;
        }
        
        /* Thank You Message */
        .thankyou {
            text-align: center;
            padding: 30px 0 20px;
            border-top: 1px solid rgba(212, 175, 55, 0.2);
            margin-top: 20px;
        }
        
        .thankyou p {
            color: #D4AF37;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .thankyou small {
            color: #6B7280;
            font-size: 13px;
        }
        
        /* Button */
        .btn-view-order {
            display: inline-block;
            background: linear-gradient(135deg, #D4AF37 0%, #FFD700 100%);
            color: #000000;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 60px;
            font-weight: 700;
            font-size: 14px;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 15px -3px rgba(212, 175, 55, 0.3);
        }
        
        .btn-view-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(212, 175, 55, 0.4);
        }
        
        /* Footer */
        .email-footer {
            background: rgba(0, 0, 0, 0.4);
            padding: 30px;
            text-align: center;
            border-top: 1px solid rgba(212, 175, 55, 0.2);
        }
        
        .social-links {
            margin-bottom: 20px;
        }
        
        .social-links a {
            color: #D4AF37;
            text-decoration: none;
            margin: 0 12px;
            font-size: 20px;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .social-links a:hover {
            transform: translateY(-2px);
            color: #FFD700;
        }
        
        .footer-text {
            color: #6B7280;
            font-size: 12px;
            line-height: 1.5;
        }
        
        .footer-text a {
            color: #D4AF37;
            text-decoration: none;
        }
        
        .footer-text a:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 600px) {
            .email-container {
                border-radius: 16px;
            }
            
            .email-header {
                padding: 30px 20px;
            }
            
            .email-content {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 26px;
            }
            
            .order-number {
                font-size: 16px;
            }
            
            .detail-value {
                font-size: 16px;
            }
            
            .total-amount .detail-value {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>

    <div class="email-container">
        <!-- Header with Crown Icon -->
        <div class="email-header">
            <div class="crown-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M7 2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zM9.5 5h-3a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zM5 8.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/>
                    <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zM1 8a7 7 0 1 1 14 0A7 7 0 0 1 1 8z"/>
                </svg>
            </div>
            <h1>Payment Successful</h1>
            <p class="subtitle">RFIFISA • Royal Motors</p>
        </div>
        
        <!-- Content -->
        <div class="email-content">
            
            <!-- Success Badge -->
            <div style="text-align: center;">
                <div class="success-badge">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Payment Completed Successfully</span>
                </div>
            </div>
            
            <!-- Greeting -->
            <div class="greeting">
                <h2>Dear {{ $order->user->name }},</h2>
                <p>Thank you for choosing RFIFISA. Your payment has been successfully processed. You're now the proud owner of a luxury driving experience.</p>
            </div>
            
            <!-- Order Details Card -->
            <div class="order-card">
                <div class="order-header">
                    <span class="order-label">ORDER DETAILS</span>
                    <span class="order-number">#{{ $order->id }}</span>
                </div>
                
                <div class="order-detail">
                    <span class="detail-label">
                        <i class="bi bi-calendar3"></i>
                        Order Date
                    </span>
                    <span class="detail-value">{{ $order->created_at->format('F j, Y') }}</span>
                </div>
                
                <div class="order-detail">
                    <span class="detail-label">
                        <i class="bi bi-credit-card"></i>
                        Payment Method
                    </span>
                    <span class="detail-value">PayPal • Instant Transfer</span>
                </div>
                
                <div class="order-detail">
                    <span class="detail-label">
                        <i class="bi bi-bag-check"></i>
                        Order Status
                    </span>
                    <span class="detail-value" style="color: #10B981;">Confirmed ✓</span>
                </div>
                
                <div class="order-detail total-amount">
                    <span class="detail-label">
                        <i class="bi bi-crown-fill"></i>
                        Total Amount
                    </span>
                    <span class="detail-value">{{ $order->total }} MAD</span>
                </div>
            </div>
            
            <!-- Action Button -->
            <div style="text-align: center;">
                <a href="http://localhost:5173/orders/" class="btn-view-order">
                    <i class="bi bi-eye" style="margin-right: 8px;"></i>
                    View Order Details
                </a>
            </div>
            
            <!-- Thank You Message -->
            <div class="thankyou">
                <p>✦ Thank You for Your Royal Purchase ✦</p>
                <small>A confirmation email has been sent to your inbox with all the details.</small>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="email-footer">
            <div class="social-links">
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-twitter-x"></i></a>
                <a href="#"><i class="bi bi-youtube"></i></a>
            </div>
            <div class="footer-text">
                <p>© 2025 RFIFISA MOTORS. All rights reserved.</p>
                <p style="margin-top: 8px;">
                    <a href="#">Privacy Policy</a> • 
                    <a href="#">Terms of Service</a> • 
                    <a href="#">Contact Support</a>
                </p>
                <p style="margin-top: 12px; font-size: 11px;">
                    RFIFISA • Royal Finest International For Innovative Solutions & Applications<br>
                    Luxury Electric Vehicles • Premium Service
                </p>
            </div>
        </div>
    </div>

</body>
</html>