-- Email Templates Table
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Email Logs Table (for tracking sent emails)
CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255) NULL,
    subject VARCHAR(500) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    sent_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES email_templates(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default templates
INSERT INTO email_templates (name, subject, body) VALUES
('Welcome Email', 'Welcome to X Business Grant!', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #1e40af; margin-top: 0;">Welcome, {{name}}!</h2>
        <p>Thank you for registering with X Business Grant. We are excited to have you on board!</p>
        <p>With X Business Grant, you can:</p>
        <ul>
            <li>Apply for business grants up to ₦5,000,000</li>
            <li>Track your application status online</li>
            <li>Get expert guidance on your business growth</li>
        </ul>
        <p>Get started by completing your profile and submitting your first grant application.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Go to Dashboard</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Application Received', 'We Received Your Grant Application - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #1e40af; margin-top: 0;">Application Received!</h2>
        <p>Dear {{name}},</p>
        <p>We have received your grant application. Your application reference number is: <strong>{{reference}}</strong></p>
        <p>Our team will review your application and get back to you within 5-7 business days.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Track Application</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Application Approved', 'Congratulations! Your Grant is Approved!', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant - Approved!</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #059669; margin-top: 0;">Congratulations, {{name}}!</h2>
        <p>We are thrilled to inform you that your grant application (Reference: <strong>{{reference}}</strong>) has been approved!</p>
        <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Approved Amount:</strong> {{amount}}</p>
        </div>
        <p>Our team will contact you shortly with details on how to receive your grant funds.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Details</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Application Rejected', 'Update on Your Grant Application', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #dc2626; margin-top: 0;">Application Update</h2>
        <p>Dear {{name}},</p>
        <p>Thank you for your interest in X Business Grant. After careful review of your application (Reference: <strong>{{reference}}</strong>), we regret to inform you that we are unable to approve your grant request at this time.</p>
        <p>This decision does not reflect on you personally. We encourage you to:</p>
        <ul>
            <li>Review your business plan and strengthen your proposal</li>
            <li>Build your credit history</li>
            <li>Gain more business experience</li>
        </ul>
        <p>You are welcome to reapply in the future. We wish you all the best in your business journey.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{apply_url}}" style="display: inline-block; background: #dc2626; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Reapply Now</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('General Announcement', 'Important Update from X Business Grant', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #1e40af; margin-top: 0;">{{subject}}</h2>
        <p>Dear {{name}},</p>
        {{message}}
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Visit Dashboard</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Application Under Review', 'Your Application is Under Review - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant - Under Review</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #f59e0b; margin-top: 0;">Application Under Review</h2>
        <p>Dear {{name}},</p>
        <p>We wanted to let you know that your grant application (Reference: <strong>{{reference}}</strong>) is now under review by our team.</p>
        <p>This is great news! Our review team is carefully evaluating all applications to ensure fair and thorough assessment.</p>
        <p>Expected timeline: 5-7 business days</p>
        <p>You will receive an email notification once the review is complete.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Dashboard</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Payment Processing', 'Your Grant Payment is Being Processed', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant - Payment</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #059669; margin-top: 0;">Payment Processing</h2>
        <p>Dear {{name}},</p>
        <p>Great news! Your grant payment for application <strong>{{reference}}</strong> is now being processed.</p>
        <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Amount:</strong> {{amount}}</p>
            <p style="margin: 10px 0 0 0;"><strong>Status:</strong> Payment Processing</p>
        </div>
        <p>Please allow 3-5 business days for the funds to reflect in your account.</p>
        <p>If you have any questions, please contact our support team.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Dashboard</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Payment Completed', 'Grant Payment Completed Successfully!', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant - Payment Complete</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #059669; margin-top: 0;">Payment Complete!</h2>
        <p>Dear {{name}},</p>
        <p>We are pleased to confirm that your grant payment has been successfully transferred!</p>
        <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Reference:</strong> {{reference}}</p>
            <p style="margin: 10px 0 0 0;"><strong>Amount Received:</strong> {{amount}}</p>
            <p style="margin: 10px 0 0 0;"><strong>Status:</strong> Completed</p>
        </div>
        <p>Congratulations on receiving your grant! We encourage you to use these funds wisely to grow your business.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Dashboard</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Document Request', 'Additional Documents Required - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #f59e0b; margin-top: 0;">Additional Documents Required</h2>
        <p>Dear {{name}},</p>
        <p>We are reviewing your grant application (Reference: <strong>{{reference}}</strong>) and need some additional documents to complete the process.</p>
        <p>Please upload the following documents:</p>
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <ul style="margin: 0; padding-left: 20px;">
                {{documents}}
            </ul>
        </div>
        <p>Once we receive these documents, we will continue with the review process.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{upload_url}}" style="display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Upload Documents</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Account Verification', 'Please Verify Your Account', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #1e40af; margin-top: 0;">Account Verification Required</h2>
        <p>Dear {{name}},</p>
        <p>Thank you for registering with X Business Grant!</p>
        <p>To ensure the security of your account and comply with regulations, we need to verify your identity.</p>
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
            <p style="font-size: 18px; margin: 0;">Your verification code:</p>
            <p style="font-size: 28px; font-weight: bold; color: #1e40af; margin: 10px 0;">{{code}}</p>
            <p style="font-size: 12px; color: #6b7280; margin: 0;">This code expires in 30 minutes</p>
        </div>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{verify_url}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Verify Account</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Password Reset', 'Reset Your Password - X Business Grant', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #1e40af; margin-top: 0;">Password Reset Request</h2>
        <p>Dear {{name}},</p>
        <p>We received a request to reset your password. Click the button below to set a new password:</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{reset_link}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Reset Password</a>
        </div>
        <p>Or copy this link: {{reset_link}}</p>
        <p style="color: #dc2626;"><strong>Note:</strong> This link expires in 1 hour. If you did not request a password reset, please ignore this email.</p>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Newsletter', 'X Business Grant Newsletter - {{subject}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #1e40af; margin-top: 0;">{{subject}}</h2>
        <p>Dear {{name}},</p>
        {{message}}
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            <h3 style="color: #1e40af;">Quick Links</h3>
            <p><a href="{{dashboard_url}}" style="color: #1e40af;">Dashboard</a> | <a href="{{apply_url}}" style="color: #1e40af;">Apply for a Grant</a> | <a href="{{contact_url}}" style="color: #1e40af;">Contact Support</a></p>
        </div>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Visit Dashboard</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
        <p>You received this email because you registered on our platform.</p>
        <p><a href="{{unsubscribe_url}}" style="color: #6b7280;">Unsubscribe</a> | <a href="{{view_in_browser_url}}" style="color: #6b7280;">View in browser</a></p>
    </div>
</div>'),
('Survey Request', 'We Value Your Feedback - X Business Grant', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #1e40af; margin-top: 0;">We Need Your Feedback!</h2>
        <p>Dear {{name}},</p>
        <p>Thank you for being part of the X Business Grant community. Your opinion matters to us!</p>
        <p>We would love to hear about your experience with our platform. Please take a few minutes to complete our survey:</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{survey_link}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Take Survey</a>
        </div>
        <p>Your feedback will help us improve our services and better serve entrepreneurs like you.</p>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Referral Program', 'Invite Friends to X Business Grant - Earn Rewards!', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant - Referral Program</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #059669; margin-top: 0;">Invite Friends & Earn Rewards!</h2>
        <p>Dear {{name}},</p>
        <p>Share the opportunity with friends and colleagues. When they successfully receive a grant, you both get rewarded!</p>
        <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
            <p style="margin: 0; font-size: 18px;"><strong>Your Referral Code:</strong></p>
            <p style="margin: 10px 0 0 0; font-size: 24px; font-weight: bold; color: #059669; letter-spacing: 4px;">{{referral_code}}</p>
        </div>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{referral_link}}" style="display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Share Now</a>
        </div>
        <p>How it works:</p>
        <ul>
            <li>Share your unique referral link/code with friends</li>
            <li>Friends apply and get approved for a grant</li>
            <li>You both receive a reward bonus!</li>
        </ul>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Interview Scheduled', 'Interview Scheduled - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #7c3aed; margin-top: 0;">Interview Scheduled</h2>
        <p>Dear {{name}},</p>
        <p>Congratulations! You have been selected for an interview regarding your grant application.</p>
        <div style="background: #f5f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Application Reference:</strong> {{reference}}</p>
            <p style="margin: 10px 0 0 0;"><strong>Date:</strong> {{date}}</p>
            <p style="margin: 10px 0 0 0;"><strong>Time:</strong> {{time}}</p>
            <p style="margin: 10px 0 0 0;"><strong>Location:</strong> {{location}}</p>
        </div>
        <p>Please confirm your attendance by clicking the button below.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{continue_url}}" style="display: inline-block; background: #7c3aed; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Confirm Attendance</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Interview Reminder', 'Interview Reminder - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #7c3aed; margin-top: 0;">Interview Reminder</h2>
        <p>Dear {{name}},</p>
        <p>This is a friendly reminder about your upcoming interview for your grant application.</p>
        <div style="background: #f5f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Application Reference:</strong> {{reference}}</p>
            <p style="margin: 10px 0 0 0;"><strong>Date:</strong> {{date}}</p>
            <p style="margin: 10px 0 0 0;"><strong>Time:</strong> {{time}}</p>
            <p style="margin: 10px 0 0 0;"><strong>Location:</strong> {{location}}</p>
        </div>
        <p>Please arrive 15 minutes early and bring all required documents.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #7c3aed; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Details</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Deadline Reminder', 'Deadline Reminder - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #f59e0b; margin-top: 0;">Deadline Reminder</h2>
        <p>Dear {{name}},</p>
        <p>This is a reminder that your application (Reference: <strong>{{reference}}</strong>) has an upcoming deadline.</p>
        <div style="background: #fffbeb; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b;">
            <p style="margin: 0; font-size: 18px;"><strong>Deadline:</strong> {{new_deadline}}</p>
        </div>
        <p>Please complete all required actions before the deadline to avoid delays in your application.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{continue_url}}" style="display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Take Action</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Incomplete Application Reminder', 'Complete Your Application - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #f59e0b; margin-top: 0;">Complete Your Application</h2>
        <p>Dear {{name}},</p>
        <p>We noticed you started but haven\'t completed your grant application yet.</p>
        <p>Your saved application (Reference: <strong>{{reference}}</strong>) is still pending submission.</p>
        <div style="background: #fffbeb; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Required Actions:</strong></p>
            <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                <li>Complete your business details</li>
                <li>Upload required documents</li>
                <li>Submit your application</li>
            </ul>
        </div>
        <p>Complete your application now to be considered for the grant.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{continue_url}}" style="display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Continue Application</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Document Approved', 'Documents Approved - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #059669; margin-top: 0;">Documents Approved</h2>
        <p>Dear {{name}},</p>
        <p>We are pleased to inform you that the documents submitted for your application (Reference: <strong>{{reference}}</strong>) have been approved!</p>
        <p>Your application will now proceed to the next stage of review.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Status</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Document Rejected', 'Documents Need Revision - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #dc2626; margin-top: 0;">Documents Need Revision</h2>
        <p>Dear {{name}},</p>
        <p>After reviewing the documents for your application (Reference: <strong>{{reference}}</strong>), we need you to resubmit the following documents:</p>
        <div style="background: #fef2f2; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc2626;">
            <ul style="margin: 0; padding-left: 20px;">
                {{documents}}
            </ul>
        </div>
        <p>Please review the requirements and upload the corrected documents using the secure portal below.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{upload_url}}" style="display: inline-block; background: #dc2626; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Reupload Documents</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Application Status Update', 'Application Status Update - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #1e40af; margin-top: 0;">Application Status Update</h2>
        <p>Dear {{name}},</p>
        <p>There has been an update to your grant application (Reference: <strong>{{reference}}</strong>).</p>
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
            <p style="margin: 0; font-size: 18px;"><strong>Current Status:</strong> {{status}}</p>
        </div>
        <p>Please log in to your dashboard to view more details.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Dashboard</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Document Request - Initial Submission', 'Action Required: Submit Documents for {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #b45309; margin-top: 0;">Documents Required</h2>
        <p>Dear {{name}},</p>
        <p>Thank you for your grant application (Reference: <strong>{{reference}}</strong>). To proceed with the review, we need you to submit the following required documents:</p>
        <div style="background: #fffbeb; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b;">
            <ul style="margin: 0; padding-left: 20px;">
                {{documents}}
            </ul>
        </div>
        <p>Please upload all documents using the secure portal below. Make sure each file is clear and legible.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{upload_url}}" style="display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Submit Documents</a>
        </div>
        <p>If you have any questions, contact us at <a href="mailto:support@xbusinessgrant.ng" style="color: #1e40af;">support@xbusinessgrant.ng</a>.</p>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Document Request - Follow-up', 'Follow-up: Documents Still Required for {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #d97706 0%, #b91c1c 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #b45309; margin-top: 0;">Documents Still Required</h2>
        <p>Dear {{name}},</p>
        <p>This is a follow-up regarding your grant application (Reference: <strong>{{reference}}</strong>). We are still waiting for the following documents:</p>
        <div style="background: #fffbeb; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b;">
            <ul style="margin: 0; padding-left: 20px;">
                {{documents}}
            </ul>
        </div>
        <p>Please submit these documents as soon as possible to avoid delays in processing your application.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{upload_url}}" style="display: inline-block; background: #d97706; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Upload Remaining Documents</a>
        </div>
        <p>Need help? Visit your <a href="{{dashboard_url}}" style="color: #1e40af;">dashboard</a> or contact support.</p>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Document Request - Final Reminder', 'Final Reminder: Documents Due for {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #b91c1c 0%, #991c1c 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #b91c1c; margin-top: 0;">Final Document Reminder</h2>
        <p>Dear {{name}},</p>
        <p>This is your final reminder. Your grant application (Reference: <strong>{{reference}}</strong>) requires the following documents, and the deadline is <strong>{{new_deadline}}</strong>:</p>
        <div style="background: #fef2f2; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc2626;">
            <ul style="margin: 0; padding-left: 20px;">
                {{documents}}
            </ul>
        </div>
        <p>If we do not receive these documents by the deadline, your application may be closed.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{upload_url}}" style="display: inline-block; background: #b91c1c; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Submit Before Deadline</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Document Request - Partial Submission', 'Partial Documents Received - More Needed for {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #b45309; margin-top: 0;">Additional Documents Needed</h2>
        <p>Dear {{name}},</p>
        <p>Thank you for submitting some documents for your application (Reference: <strong>{{reference}}</strong>). We have received part of your submission, but the following documents are still missing:</p>
        <div style="background: #fffbeb; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b;">
            <ul style="margin: 0; padding-left: 20px;">
                {{documents}}
            </ul>
        </div>
        <p>Please upload the remaining documents using the secure portal below.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{upload_url}}" style="display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Upload Missing Documents</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Document Request - Expired Link', 'Upload Link Expired - Resubmit Documents for {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #d97706 0%, #b91c1c 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #b45309; margin-top: 0;">Upload Link Expired</h2>
        <p>Dear {{name}},</p>
        <p>Your previous document upload link for application (Reference: <strong>{{reference}}</strong>) has expired. Please request a new upload link to submit the following documents:</p>
        <div style="background: #fffbeb; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b;">
            <ul style="margin: 0; padding-left: 20px;">
                {{documents}}
            </ul>
        </div>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #d97706; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Request New Upload Link</a>
        </div>
        <p>You can also visit your dashboard to generate a new upload link at any time.</p>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Application Submitted', 'Application Submitted Successfully - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #047857; margin-top: 0;">Application Submitted!</h2>
        <p>Dear {{name}},</p>
        <p>Your grant application has been successfully submitted. Your reference number is: <strong>{{reference}}</strong></p>
        <p>Our team will review your application and contact you within 5-7 business days. You will receive email updates on your application status.</p>
        <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Status:</strong> Submitted</p>
            <p style="margin: 5px 0 0 0;"><strong>Expected Review:</strong> 5-7 business days</p>
        </div>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Application</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Application In Review', 'Your Application is Now in Review - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #1e40af; margin-top: 0;">Application In Review</h2>
        <p>Dear {{name}},</p>
        <p>Good news! Your grant application (Reference: <strong>{{reference}}</strong>) is now under review by our evaluation team.</p>
        <p>Our reviewers are carefully assessing your submission. This process typically takes 5-7 business days.</p>
        <div style="background: #eff6ff; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Current Status:</strong> {{status}}</p>
            <p style="margin: 5px 0 0 0;"><strong>Review Deadline:</strong> {{new_deadline}}</p>
        </div>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Track Progress</a>
        </div>
        <p>You will receive another email once the review is complete.</p>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Application Approved', 'Congratulations! Your Grant is Approved - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant - Approved!</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #059669; margin-top: 0;">Congratulations, {{name}}!</h2>
        <p>We are delighted to inform you that your grant application (Reference: <strong>{{reference}}</strong>) has been approved!</p>
        <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Approved Amount:</strong> {{amount}}</p>
        </div>
        <p>Our team will contact you shortly with details on how to receive your grant funds.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Approval Details</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Application Rejected', 'Application Update - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #dc2626; margin-top: 0;">Application Update</h2>
        <p>Dear {{name}},</p>
        <p>After careful review of your grant application (Reference: <strong>{{reference}}</strong>), we regret to inform you that we are unable to approve your request at this time.</p>
        <p>This decision does not reflect on your business potential. We encourage you to:</p>
        <ul style="padding-left: 20px;">
            <li>Review and strengthen your business plan</li>
            <li>Build your credit history</li>
            <li>Gather additional supporting documents</li>
        </ul>
        <p>You are welcome to reapply in the future. We wish you success in your business journey.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{apply_url}}" style="display: inline-block; background: #dc2626; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Reapply Now</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Payment Initiated', 'Payment Initiated for {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant - Payment</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #047857; margin-top: 0;">Payment Initiated</h2>
        <p>Dear {{name}},</p>
        <p>Great news! Your grant payment for application (Reference: <strong>{{reference}}</strong>) has been initiated.</p>
        <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Amount:</strong> {{amount}}</p>
            <p style="margin: 5px 0 0 0;"><strong>Status:</strong> Payment Processing</p>
        </div>
        <p>Please allow 3-5 business days for the funds to reflect in your account.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Payment Details</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Payment Received', 'Payment Received - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant - Payment Complete</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #047857; margin-top: 0;">Payment Received!</h2>
        <p>Dear {{name}},</p>
        <p>We are pleased to confirm that your grant payment has been successfully transferred to your account.</p>
        <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Reference:</strong> {{reference}}</p>
            <p style="margin: 5px 0 0 0;"><strong>Amount Received:</strong> {{amount}}</p>
            <p style="margin: 5px 0 0 0;"><strong>Status:</strong> Completed</p>
        </div>
        <p>Congratulations on receiving your grant! We encourage you to use these funds wisely to grow your business.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Receipt</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Interview Invitation', 'Interview Invitation - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #7c3aed; margin-top: 0;">Interview Invitation</h2>
        <p>Dear {{name}},</p>
        <p>Congratulations! You have been selected for an interview regarding your grant application (Reference: <strong>{{reference}}</strong>).</p>
        <div style="background: #f5f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Date:</strong> {{date}}</p>
            <p style="margin: 5px 0 0 0;"><strong>Time:</strong> {{time}}</p>
            <p style="margin: 5px 0 0 0;"><strong>Location:</strong> {{location}}</p>
        </div>
        <p>Please confirm your attendance by clicking the button below.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{continue_url}}" style="display: inline-block; background: #7c3aed; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Confirm Interview</a>
        </div>
        <p>Please arrive 15 minutes early and bring all required documents.</p>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Interview Confirmation', 'Interview Confirmed - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #047857; margin-top: 0;">Interview Confirmed</h2>
        <p>Dear {{name}},</p>
        <p>Your interview for grant application (Reference: <strong>{{reference}}</strong>) has been confirmed.</p>
        <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Date:</strong> {{date}}</p>
            <p style="margin: 5px 0 0 0;"><strong>Time:</strong> {{time}}</p>
            <p style="margin: 5px 0 0 0;"><strong>Location:</strong> {{location}}</p>
        </div>
        <p>Please remember to bring all required documents and arrive 15 minutes early.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Interview Details</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Deadline Extension', 'Deadline Extended - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #1e40af; margin-top: 0;">Deadline Extended</h2>
        <p>Dear {{name}},</p>
        <p>Good news! The deadline for your grant application (Reference: <strong>{{reference}}</strong>) has been extended.</p>
        <div style="background: #eff6ff; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
            <p style="margin: 0; font-size: 18px;"><strong>New Deadline:</strong> {{new_deadline}}</p>
        </div>
        <p>This extension gives you more time to complete the required actions. Please use the link below to continue your application.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{continue_url}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Continue Application</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Account Created', 'Welcome! Your Account is Ready - {{name}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #047857; margin-top: 0;">Welcome, {{name}}!</h2>
        <p>Your account has been successfully created with X Business Grant.</p>
        <p>You can now apply for grants, track your applications, and manage your profile all from your dashboard.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Complete Your Profile</a>
        </div>
        <p>To get started, we recommend completing your profile and submitting your first grant application.</p>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Profile Update Required', 'Profile Update Required - {{name}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #b45309; margin-top: 0;">Profile Update Required</h2>
        <p>Dear {{name}},</p>
        <p>To continue processing your grant application, we need you to update your profile with the following information:</p>
        <div style="background: #fffbeb; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b;">
            <ul style="margin: 0; padding-left: 20px;">
                {{documents}}
            </ul>
        </div>
        <p>Please update your profile as soon as possible to avoid delays.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Update Profile</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Document Verification in Progress', 'Document Verification in Progress - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #1e40af; margin-top: 0;">Verification in Progress</h2>
        <p>Dear {{name}},</p>
        <p>We are currently verifying the documents submitted for your application (Reference: <strong>{{reference}}</strong>).</p>
        <p>The following documents are being reviewed:</p>
        <div style="background: #eff6ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #1e40af;">
            <ul style="margin: 0; padding-left: 20px;">
                {{documents}}
            </ul>
        </div>
        <p>This process typically takes 2-3 business days. You will receive another email once verification is complete.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Status</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>'),
('Grant Disbursement', 'Grant Disbursement Initiated - {{reference}}', '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant - Disbursement</h1>
    </div>
    <div style="padding: 40px 30px; background: #ffffff;">
        <h2 style="color: #047857; margin-top: 0;">Grant Disbursement Initiated</h2>
        <p>Dear {{name}},</p>
        <p>Your grant payment for application (Reference: <strong>{{reference}}</strong>) is being processed for disbursement.</p>
        <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Amount:</strong> {{amount}}</p>
            <p style="margin: 5px 0 0 0;"><strong>Status:</strong> Disbursement in Progress</p>
        </div>
        <p>The funds will be transferred to your designated account. Please allow 3-5 business days for the transaction to complete.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{dashboard_url}}" style="display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Disbursement Details</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p>
    </div>
    <div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;">
        <p>&copy; {{year}} X Business Grant. All rights reserved.</p>
    </div>
</div>');
