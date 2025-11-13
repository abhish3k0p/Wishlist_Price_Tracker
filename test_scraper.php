<?php
require_once __DIR__ . '/includes/scraper.php';

// Test Amazon URL
$amazonUrl = 'https://www.amazon.in/Skybags-Premium-Polyester-Adjustable-Backpack/dp/B0D1G32M32/';

// Test the scraper
$product = scrape_product($amazonUrl);

echo "<h2>Scraping Results for Amazon Product</h2>";
echo "<pre>";
print_r($product);
echo "</pre>";

// Debug: Check the raw HTML
$html = http_get($amazonUrl);
if ($html === null) {
    echo "<h3>Failed to fetch the page. Check if cURL is enabled and the URL is accessible.</h3>";
} else {
    // Save a copy of the HTML for inspection
    file_put_contents('amazon_page.html', $html);
    echo "<p>Page content saved to amazon_page.html for inspection.</p>";
}

// Function to display the HTML in a readable format
function format_html($html) {
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $dom->formatOutput = true;
    return htmlspecialchars($dom->saveHTML());
}
?>

<h3>Debug Information</h3>
<p>PHP Version: " . phpversion() . "</p>
<p>cURL Enabled: " . (function_exists('curl_version') ? 'Yes' : 'No') . "</p>
<p>DOM Extension: " . (extension_loaded('dom') ? 'Loaded' : 'Not Loaded') . "</p>
