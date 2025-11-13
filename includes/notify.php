<?php

require __DIR__ . '/../vendor/autoload.php';
use SendGrid\Mail\Mail;
use SendGrid as SendGridClient;

/**
 * Send a price drop notification email using Twilio SendGrid
 *
 * @param array $user User details (must contain 'email' and 'name')
 * @param array $product Product details (must contain 'name', 'url')
 * @param float $oldPrice Old price
 * @param float $newPrice New price
 * @return bool True if email was sent successfully, false otherwise
 */
function sendPriceDropNotification(array $user, array $product, float $oldPrice, float $newPrice): bool {
    $email = new Mail();
    $email->setFrom(
        getenv('TWILIO_SENDGRID_FROM_EMAIL'),
        getenv('TWILIO_SENDGRID_FROM_NAME')
    );
    $email->setSubject('Price Drop Alert!');
    $email->addTo($user['email'], $user['name'] ?? '');

    // Create the email content
    $htmlContent = getEmailTemplate('price_drop', [
        'name' => $user['name'],
        'product_name' => $product['name'],
        'product_url' => $product['url'] ?? '#',
        'old_price' => number_format($oldPrice, 2),
        'new_price' => number_format($newPrice, 2),
        'app_name' => getenv('APP_NAME'),
        'app_url' => getenv('APP_URL')
    ]);

    $email->addContent("text/html", $htmlContent);

    // Send the email
    $sendgrid = new SendGridClient(getenv('TWILIO_SENDGRID_API_KEY'));

    try {
        $response = $sendgrid->send($email);
        return $response->statusCode() >= 200 && $response->statusCode() < 300;
    } catch (Exception $e) {
        error_log('SendGrid Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get email template with variables replaced
 *
 * @param string $templateName Name of the template file (without .html)
 * @param array $variables Associative array of variables to replace
 * @return string Processed HTML content
 */
function getEmailTemplate(string $templateName, array $variables = []): string {
    $templatePath = __DIR__ . "/../emails/{$templateName}.html";

    if (!file_exists($templatePath)) {
        throw new Exception("Email template not found: {$templateName}");
    }

    $content = file_get_contents($templatePath);

    // Replace variables in the template
    foreach ($variables as $key => $value) {
        $content = str_replace("{{{$key}}}", htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $content);
    }

    return $content;
}
