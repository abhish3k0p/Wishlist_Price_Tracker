<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/settings.php';

use SendGrid\Mail\Mail;
use SendGrid as SendGridClient;

function getSendGridClient() {
    $apiKey = setting('TWILIO_SENDGRID_API_KEY');
    if (empty($apiKey)) {
        throw new Exception('SendGrid API key is not configured');
    }
    return new SendGridClient($apiKey);
}

function sendPriceAlert(array $user, array $product, float $oldPrice, float $newPrice): bool {
    try {
        $fromEmail = setting('TWILIO_SENDGRID_FROM_EMAIL', 'abhi2k0p@gmail.com');
        $fromName = setting('TWILIO_SENDGRID_FROM_NAME', 'Wishlist Price Tracker');
        
        // Load email template
        $tpl = file_get_contents(__DIR__ . '/../emails/price_drop.html');
        if ($tpl === false) {
            throw new Exception('Could not load email template');
        }
        
        // Replace placeholders
        $body = strtr($tpl, [
            '{{product_name}}' => htmlspecialchars($product['name'] ?? 'Product'),
            '{{old_price}}' => number_format($oldPrice, 2),
            '{{new_price}}' => number_format($newPrice, 2),
            '{{product_url}}' => $product['url'] ?? '#'
        ]);
        
        // Create email
        $email = new Mail();
        $email->setFrom($fromEmail, $fromName);
        $email->setSubject('Price Drop Alert: ' . ($product['name'] ?? 'Product'));
        $email->addTo($user['email'], $user['name'] ?? '');
        $email->addContent("text/plain", 'Price dropped for ' . ($product['name'] ?? 'Product'));
        $email->addContent("text/html", $body);
        
        // Send email
        $sendgrid = getSendGridClient();
        $response = $sendgrid->send($email);
        
        // Check response status code
        if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
            return true;
        } else {
            error_log('SendGrid API Error: ' . $response->body());
            return false;
        }
        
    } catch (\Throwable $e) {
        error_log('Error in sendPriceAlert: ' . $e->getMessage());
        return false;
    }
}

function sendWelcomeEmail(array $user): bool {
    try {
        $fromEmail = setting('TWILIO_SENDGRID_FROM_EMAIL', 'noreply@example.com');
        $fromName = setting('TWILIO_SENDGRID_FROM_NAME', 'Wishlist Price Tracker');
        
        // Load email template
        $tpl = file_get_contents(__DIR__ . '/../emails/welcome.html');
        if ($tpl === false) {
            throw new Exception('Could not load welcome email template');
        }
        
        // Replace placeholders
        $body = strtr($tpl, [
            '{{name}}' => htmlspecialchars($user['name'] ?? 'User'),
        ]);
        
        // Create email
        $email = new Mail();
        $email->setFrom($fromEmail, $fromName);
        $email->setSubject('Welcome to Wishlist Price Tracker');
        $email->addTo($user['email'], $user['name'] ?? '');
        $email->addContent("text/plain", 'Welcome to Wishlist Price Tracker');
        $email->addContent("text/html", $body);
        
        // Send email
        $sendgrid = getSendGridClient();
        $response = $sendgrid->send($email);
        
        return $response->statusCode() >= 200 && $response->statusCode() < 300;
        
    } catch (\Throwable $e) {
        error_log('Error in sendWelcomeEmail: ' . $e->getMessage());
        return false;
    }
}

function sendPasswordReset(array $user, string $token): bool {
    try {
        $fromEmail = setting('TWILIO_SENDGRID_FROM_EMAIL', 'noreply@example.com');
        $fromName = setting('TWILIO_SENDGRID_FROM_NAME', 'Wishlist Price Tracker');
        
        // Generate reset link
        $resetLink = get_base_url() . '/reset-password.php?token=' . urlencode($token);
        
        // Load email template
        $tpl = file_get_contents(__DIR__ . '/../emails/password_reset.html');
        if ($tpl === false) {
            throw new Exception('Could not load password reset email template');
        }
        
        // Replace placeholders
        $body = strtr($tpl, [
            '{{name}}' => htmlspecialchars($user['name'] ?? 'User'),
            '{{reset_link}}' => $resetLink,
        ]);
        
        // Create email
        $email = new Mail();
        $email->setFrom($fromEmail, $fromName);
        $email->setSubject('Password Reset Request');
        $email->addTo($user['email'], $user['name'] ?? '');
        $email->addContent("text/plain", 'Click here to reset your password: ' . $resetLink);
        $email->addContent("text/html", $body);
        
        // Send email
        $sendgrid = getSendGridClient();
        $response = $sendgrid->send($email);
        
        return $response->statusCode() >= 200 && $response->statusCode() < 300;
        
    } catch (\Throwable $e) {
        error_log('Error in sendPasswordReset: ' . $e->getMessage());
        return false;
    }
}

