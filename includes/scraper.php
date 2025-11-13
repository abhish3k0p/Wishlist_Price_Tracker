<?php
function scrape_product(string $url): array {
    $html = http_get($url);
    if ($html === null) {
        return [ 'name'=>'', 'image_url'=>'', 'store_name'=>parse_url($url, PHP_URL_HOST) ?: '', 'current_price'=>0.0 ];
    }
    
    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    $xp = new DOMXPath($doc);
    $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
    if (str_starts_with($host, 'www.')) { $host = substr($host, 4); }
    
    // 1. First try universal Schema.org structured data (works on most major e-commerce sites)
    $universalResult = scrape_universal($xp, $url);
    if ($universalResult['name'] || $universalResult['current_price'] > 0) {
        return $universalResult;
    }

    // 2. If no luck, try site-specific scrapers for better accuracy
    $siteSpecificResult = [];
    if (strpos($host, 'amazon.') !== false) {
        $siteSpecificResult = scrape_amazon($xp, $url);
    } elseif (strpos($host, 'ebay.') !== false) {
        $siteSpecificResult = scrape_ebay($xp, $url);
    } elseif (strpos($host, 'bestbuy.') !== false) {
        $siteSpecificResult = scrape_bestbuy($xp, $url);
    } elseif (strpos($host, 'flipkart.') !== false) {
        $siteSpecificResult = scrape_flipkart($xp, $url);
    } elseif (strpos($host, 'myntra.') !== false) {
        $siteSpecificResult = scrape_myntra($xp, $url);
    }
    
    // 3. If we have a valid result from site-specific scraper, use it
    if (!empty($siteSpecificResult['name']) || $siteSpecificResult['current_price'] > 0) {
        return $siteSpecificResult;
    }
    
    // 4. If all else fails, try common patterns
    return scrape_common_patterns($xp, $url);

    $title = text_first([
        $xp->query('//meta[@property="og:title"]/@content'),
        $xp->query('//meta[@name="twitter:title"]/@content'),
        $xp->query('//h1'),
        $xp->query('//title'),
    ]);
    $image = text_first([
        $xp->query('//meta[@property="og:image"]/@content'),
        $xp->query('//img[@id="landingImage"]/@src'),
        $xp->query('//img[@class][@src]/@src'),
    ]);
    $store = parse_url($url, PHP_URL_HOST) ?: '';

    $priceText = text_first([
        $xp->query('//*[@id="priceblock_ourprice" or @id="priceblock_dealprice"]'),
        $xp->query('//*[contains(@class, "price") or contains(@class, "Price") or contains(@class, "amount")]'),
        $xp->query('//meta[@property="product:price:amount"]/@content'),
        $xp->query('//*[@data-price or @data-amount]/@data-price'),
    ]);
    $price = parse_price($priceText);

    return [
        'name' => trim((string)$title),
        'image_url' => trim((string)$image),
        'store_name' => $store,
        'current_price' => $price,
    ];
}

function scrape_amazon(DOMXPath $xp, string $url): array {
    // Initialize default result
    $result = [
        'name' => '',
        'image_url' => '',
        'store_name' => 'Amazon',
        'current_price' => 0.0,
        'url' => $url
    ];

    // Try to get the product title
    $title = text_first([
        // New Amazon layout
        $xp->query('//span[@id="productTitle"]'),
        $xp->query('//h1//span[contains(@id, "productTitle")]'),
        // Fallback to meta tags
        $xp->query('//meta[@property="og:title"]/@content'),
        $xp->query('//title')
    ]);
    
    // Clean up the title (remove Amazon specific text)
    if (!empty($title)) {
        $title = preg_replace('/\s*:\s*Amazon\.in.*$/', '', $title);
        $title = preg_replace('/\s*\|[^|]*$/', '', $title);
        $title = trim($title);
        $result['name'] = $title;
    }
    
    // Try to get the product image
    $image = text_first([
        // New Amazon layout
        $xp->query('//div[@id="imgTagWrapperId"]//img/@src'),
        $xp->query('//div[@id="main-image-container"]//img/@src'),
        // Old Amazon layouts
        $xp->query('//img[@id="landingImage"]/@src'),
        $xp->query('//img[@id="imgBlkFront"]/@src|//img[@id="imgBlkFront"]/@data-a-dynamic-image'),
        // Fallback to meta tags
        $xp->query('//meta[@property="og:image"]/@content'),
        $xp->query('//div[@id="img-canvas"]/img/@src')
    ]);
    
    if (!empty($image)) {
        // Handle JSON-encoded image URLs
        if (strpos($image, '{') === 0) {
            $imageData = json_decode($image, true);
            if (is_array($imageData)) {
                $image = key($imageData); // Get the first (largest) image
            }
        }
        $result['image_url'] = trim($image);
    }
    
    // Try multiple selectors for price (most specific to least specific)
    $priceSelectors = [
        // New Amazon layout (2023-2024)
        '//span[@class="a-price aok-align-center"]//span[@class="a-offscreen"]',
        '//span[contains(@class, "priceToPay")]//span[@class="a-offscreen"]',
        '//span[contains(@class, "a-price")]//span[contains(@class, "a-offscreen")]',
        '//span[@class="a-price a-text-price"]//span[contains(@class, "a-offscreen")]',
        
        // Deal price
        '//span[contains(@class, "a-price") and contains(@class, "a-text-price")]//span[contains(@class, "a-offscreen")]',
        
        // Old Amazon layouts
        '//*[@id="price_inside_buybox"]',
        '//*[@id="priceblock_ourprice"]',
        '//*[@id="priceblock_dealprice"]',
        '//*[@id="priceblock_saleprice"]',
        '//*[@id="price"]',
        '//*[@data-asin-price]/@data-asin-price',
        '//span[@class="a-price-whole"]',
        
        // Fallback to any price in the page
        '//*[contains(@class, "price") or contains(@class, "Price")]',
        '//*[contains(text(), "$") or contains(text(), "₹")]',
        '//*[contains(translate(text(), "$", "$"), "$")]',
        '//*[contains(translate(text(), "₹", "₹"), "₹")]'
    ];
    
    foreach ($priceSelectors as $selector) {
        $priceNode = $xp->query($selector);
        if ($priceNode && $priceNode->length > 0) {
            $priceText = trim($priceNode->item(0)->nodeValue);
            $price = parse_price($priceText);
            if ($price > 0) {
                $result['current_price'] = $price;
                break;
            }
        }
    }
    
    // If we still don't have a price, try to find it in JSON-LD data
    if ($result['current_price'] <= 0) {
        $jsonLdNodes = $xp->query('//script[@type="application/ld+json"]');
        if ($jsonLdNodes) {
            foreach ($jsonLdNodes as $node) {
                $json = json_decode($node->nodeValue, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Handle different JSON-LD formats
                    if (isset($json['offers']['price'])) {
                        $price = floatval($json['offers']['price']);
                        if ($price > 0) {
                            $result['current_price'] = $price;
                            break;
                        }
                    } elseif (isset($json['price'])) {
                        $price = floatval($json['price']);
                        if ($price > 0) {
                            $result['current_price'] = $price;
                            break;
                        }
                    }
                }
            }
        }
    }
    
    return $result;
}

function scrape_ebay(DOMXPath $xp, string $url): array {
    $title = text_first([
        $xp->query('//*[@id="itemTitle"]'),
        $xp->query('//meta[@property="og:title"]/@content'),
    ]);
    $image = text_first([
        $xp->query('//img[@id="icImg"]/@src'),
        $xp->query('//meta[@property="og:image"]/@content'),
    ]);
    $priceText = text_first([
        $xp->query('//*[@id="prcIsum" or @id="mm-saleDscPrc"]'),
        $xp->query('//*[@itemprop="price"]/@content'),
    ]);
    return [
        'name' => trim((string)$title),
        'image_url' => trim((string)$image),
        'store_name' => 'eBay',
        'current_price' => parse_price($priceText),
    ];
}

function scrape_bestbuy(DOMXPath $xp, string $url): array {
    $title = text_first([
        $xp->query('//h1[contains(@class, "heading-5")]'),
        $xp->query('//meta[@property="og:title"]/@content'),
    ]);
    $image = text_first([
        $xp->query('//meta[@property="og:image"]/@content'),
    ]);
    $priceText = text_first([
        $xp->query('//*[contains(@class, "priceView-hero-price")]/span[1]'),
        $xp->query('//meta[@itemprop="price"]/@content'),
    ]);
    return [
        'name' => trim((string)$title),
        'image_url' => trim((string)$image),
        'store_name' => 'BestBuy',
        'current_price' => parse_price($priceText),
    ];
}

/**
 * Universal scraper that works with Schema.org structured data
 */
function scrape_universal(DOMXPath $xp, string $url): array {
    $result = [
        'name' => '',
        'image_url' => '',
        'store_name' => parse_url($url, PHP_URL_HOST),
        'current_price' => 0.0,
    ];

    // 1. Try to get Schema.org/Product data (used by most major e-commerce sites)
    $jsonLd = $xp->query('//script[@type="application/ld+json"]');
    if ($jsonLd->length > 0) {
        foreach ($jsonLd as $node) {
            $data = json_decode($node->nodeValue, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Handle both array and object formats
                $schema = is_array($data) && isset($data[0]) ? $data[0] : $data;
                
                if (isset($schema['@type']) && 
                   (strpos($schema['@type'], 'Product') !== false || 
                    (is_array($schema['@type']) && in_array('Product', $schema['@type'])))) {
                    
                    // Get name
                    if (empty($result['name']) && !empty($schema['name'])) {
                        $result['name'] = $schema['name'];
                    }
                    
                    // Get price
                    if ($result['current_price'] <= 0) {
                        if (!empty($schema['offers']['price'])) {
                            $result['current_price'] = parse_price($schema['offers']['price']);
                        } elseif (!empty($schema['offers'][0]['price'])) {
                            $result['current_price'] = parse_price($schema['offers'][0]['price']);
                        } elseif (!empty($schema['price'])) {
                            $result['current_price'] = parse_price($schema['price']);
                        }
                    }
                    
                    // Get image
                    if (empty($result['image_url'])) {
                        if (!empty($schema['image'])) {
                            $result['image_url'] = is_array($schema['image']) ? $schema['image'][0] : $schema['image'];
                        } elseif (!empty($schema['image']['url'])) {
                            $result['image_url'] = $schema['image']['url'];
                        }
                    }
                    
                    // If we got all the data we need, return early
                    if (!empty($result['name']) && $result['current_price'] > 0 && !empty($result['image_url'])) {
                        return $result;
                    }
                }
            }
        }
    }
    
    // 2. Try OpenGraph and Twitter Card meta tags
    if (empty($result['name'])) {
        $result['name'] = text_first([
            $xp->query('//meta[@property="og:title"]/@content'),
            $xp->query('//meta[@name="twitter:title"]/@content')
        ]);
    }
    
    if (empty($result['image_url'])) {
        $result['image_url'] = text_first([
            $xp->query('//meta[@property="og:image"]/@content'),
            $xp->query('//meta[@name="twitter:image"]/@content')
        ]);
    }
    
    // 3. Try to find price in common locations
    if ($result['current_price'] <= 0) {
        // Look for common price patterns in the HTML
        $html = $xp->document->saveHTML();
        if (preg_match('/"price"\s*[:=]\s*[\'"](\d+(?:\.\d+)?)[\'"]/i', $html, $matches)) {
            $result['current_price'] = (float)$matches[1];
        } elseif (preg_match('/"price"\s*[:=]\s*(\d+(?:\.\d+)?)/i', $html, $matches)) {
            $result['current_price'] = (float)$matches[1];
        } elseif (preg_match('/\b(?:price|mrp|amount)[":\s]+[\'"]?(\d+(?:\.\d*)?)[\'"]?/i', $html, $matches)) {
            $result['current_price'] = (float)$matches[1];
        }
    }
    
    return $result;
}

/**
 * Fallback to common patterns when other methods fail
 */
function scrape_common_patterns(DOMXPath $xp, string $url): array {
    $result = [
        'name' => '',
        'image_url' => '',
        'store_name' => parse_url($url, PHP_URL_HOST),
        'current_price' => 0.0,
    ];
    
    // Get the HTML content for regex searches
    $html = $xp->document->saveHTML();
    
    // 1. Try to get name from common selectors
    $result['name'] = text_first([
        $xp->query('//h1'),
        $xp->query('//h2'),
        $xp->query('//meta[@property="og:title"]/@content'),
        $xp->query('//title')
    ]);
    
    // 2. Try to get image from common selectors
    $result['image_url'] = text_first([
        $xp->query('//img[contains(@class, "product") or contains(@id, "product") or contains(@class, "main")]/@src'),
        $xp->query('//img[@id="main-image"]/@src'),
        $xp->query('//img[@class="product-image"]/@src'),
        $xp->query('//meta[@property="og:image"]/@content')
    ]);
    
    // 3. Try to get price from common selectors
    $priceText = text_first([
        $xp->query('//span[contains(@class, "price") or contains(@id, "price")]'),
        $xp->query('//div[contains(@class, "price") or contains(@id, "price")]'),
        $xp->query('//*[contains(@class, "amount")]'),
        $xp->query('//*[contains(text(), "₹") or contains(text(), "$") or contains(text(), "€") or contains(text(), "£")]')
    ]);
    
    if (!empty($priceText)) {
        $result['current_price'] = parse_price($priceText);
    }
    
    // 4. If we still don't have a price, try regex patterns
    if ($result['current_price'] <= 0) {
        if (preg_match('/(?:₹|\$|€|£|Rs\.?\s*)(\d+[,\.]\d{2})/', $html, $matches)) {
            $result['current_price'] = (float)str_replace([',', '.'], '', $matches[1]) / 100;
        } elseif (preg_match('/(?:₹|\$|€|£|Rs\.?\s*)(\d+)/', $html, $matches)) {
            $result['current_price'] = (float)$matches[1];
        }
    }
    
    return $result;
}

/**
 * Site-specific scraper for Myntra
 */
function scrape_myntra(DOMXPath $xp, string $url): array {
    $result = [
        'name' => '',
        'image_url' => '',
        'store_name' => 'Myntra',
        'current_price' => 0.0,
    ];

    // Get the HTML content to search for JavaScript variables
    $html = $xp->document->saveHTML();
    
    // Extract product title
    $title = text_first([
        $xp->query('//h1[contains(@class, "pdp-title")]'),
        $xp->query('//h1[contains(@class, "pdp-name")]'),
        $xp->query('//meta[@property="og:title"]/@content'),
        $xp->query('//h1'),
        $xp->query('//title')
    ]);
    
    // Clean up the title (remove Myntra specific text)
    if (!empty($title)) {
        $title = preg_replace('/\s*\|\s*Online Shopping.*$/i', '', $title);
        $title = preg_replace('/\s*\|?\s*Myntra.*$/i', '', $title);
        $title = trim($title);
        $result['name'] = $title;
    }
    
    // Extract product image
    $image = text_first([
        $xp->query('//div[contains(@class, "image-grid-image")]//img/@src'),
        $xp->query('//div[contains(@class, "pdp-images")]//img/@src'),
        $xp->query('//meta[@property="og:image"]/@content'),
        $xp->query('//img[contains(@class, "pdp-image")]/@src'),
        $xp->query('//img[contains(@class, "image-grid-image")]/@src')
    ]);
    
    if (!empty($image)) {
        // Ensure we have a complete URL
        if (strpos($image, 'http') !== 0) {
            $image = 'https:' . ltrim($image, ':');
        }
        $result['image_url'] = trim($image);
    }
    
    // First, try to extract price from the window.__myx.pdpData object in JavaScript
    if (preg_match('/window\.__myx\s*=\s*(\{.*?\});/s', $html, $matches)) {
        try {
            $data = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE && isset($data['pdpData'])) {
                $pdpData = $data['pdpData'];
                
                // Try to get the price from various possible locations in the data
                if (isset($pdpData['mrp'])) {
                    $result['current_price'] = (float)$pdpData['mrp'];
                } elseif (isset($pdpData['price'])) {
                    $result['current_price'] = (float)$pdpData['price'];
                }
                
                // If we still don't have a price, look for it in the offers array
                if (empty($result['current_price']) && !empty($pdpData['offers'])) {
                    foreach ($pdpData['offers'] as $offer) {
                        if (isset($offer['price'])) {
                            $result['current_price'] = (float)$offer['price'];
                            break;
                        }
                    }
                }
                
                // If we found a price, return early
                if (!empty($result['current_price'])) {
                    return $result;
                }
            }
        } catch (Exception $e) {
            // Continue with other methods if JSON parsing fails
        }
    }
    
    // Try to find price in the page's JavaScript variables
    if (preg_match('/"price"\s*:\s*\{\s*"discounted"\s*:\s*(\d+(?:\.\d+)?)/', $html, $matches)) {
        $result['current_price'] = (float)$matches[1];
    } elseif (preg_match('/"mrp"\s*:\s*(\d+(?:\.\d+)?)/', $html, $matches)) {
        $result['current_price'] = (float)$matches[1];
    } elseif (preg_match('/"current_price"\s*:\s*(\d+(?:\.\d+)?)/', $html, $matches)) {
        $result['current_price'] = (float)$matches[1];
    }
    
    // If we still don't have a price, try traditional XPath selectors
    if ($result['current_price'] <= 0) {
        $priceSelectors = [
            // Current price
            '//span[contains(@class, "pdp-price")]//strong',
            '//div[contains(@class, "pdp-price-info")]//span[contains(@class, "pdp-price")]',
            '//div[contains(@class, "pdp-price")]//span[contains(@class, "pdp-price")]',
            '//span[contains(@class, "pdp-price")]',
            
            // Discounted price
            '//div[contains(@class, "pdp-discount")]//span[contains(@class, "pdp-price")]',
            '//div[contains(@class, "pdp-discount")]',
            
            // Fallback to any price in the page
            '//*[contains(@class, "pdp-price")]',
            '//*[contains(text(), "₹")]',
            '//*[contains(translate(text(), "₹", "₹"), "₹")]',
            
            // JSON-LD data
            '//script[@type="application/ld+json"]'
        ];
        
        foreach ($priceSelectors as $selector) {
            $priceNode = $xp->query($selector);
            if ($priceNode && $priceNode->length > 0) {
                // Handle JSON-LD data separately
                if ($selector === '//script[@type="application/ld+json"]') {
                    foreach ($priceNode as $node) {
                        $json = json_decode($node->nodeValue, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            if (isset($json['offers']['price'])) {
                                $price = parse_price($json['offers']['price']);
                                if ($price > 0) {
                                    $result['current_price'] = $price;
                                    break 2;
                                }
                            } elseif (isset($json['price'])) {
                                $price = parse_price($json['price']);
                                if ($price > 0) {
                                    $result['current_price'] = $price;
                                    break 2;
                                }
                            }
                        }
                    }
                } else {
                    $priceText = trim($priceNode->item(0)->nodeValue);
                    $price = parse_price($priceText);
                    if ($price > 0) {
                        $result['current_price'] = $price;
                        break;
                    }
                }
            }
        }
    }
    
    return $result;
}

function scrape_flipkart(DOMXPath $xp, string $url): array {
    $title = text_first([
        $xp->query('//meta[@property="og:title"]/@content'),
        $xp->query('//h1'),
        $xp->query('//title'),
    ]);
    $image = text_first([
        $xp->query('//meta[@property="og:image"]/@content'),
        $xp->query('//img[@class][@src]/@src'),
    ]);
    // Common Flipkart price selectors (old and new)
    $priceText = text_first([
        $xp->query('//*[contains(@class, "_30jeq3") and contains(@class, "_16Jk6d")]'),
        $xp->query('//*[contains(@class, "_30jeq3")]'),
        $xp->query('//*[contains(@class, "Nx9bqj")]'),
        $xp->query('//*[contains(@class, "CxhGGd")]'),
        $xp->query('//meta[@property="product:price:amount"]/@content'),
    ]);
    return [
        'name' => trim((string)$title),
        'image_url' => trim((string)$image),
        'store_name' => 'Flipkart',
        'current_price' => parse_price($priceText),
    ];
}

function http_get(string $url): ?string {
    // List of user agents to rotate
    $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0'
    ];
    
    $maxRetries = 3;
    $attempt = 0;
    $result = null;
    $httpCode = 0;
    
    while ($attempt < $maxRetries) {
        // Select a random user agent
        $userAgent = $userAgents[array_rand($userAgents)];
        
        // Set common headers to mimic a real browser
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Upgrade-Insecure-Requests: 1',
            'User-Agent: ' . $userAgent,
        ];
        
        // Add Amazon-specific headers if the URL is from Amazon
        if (strpos($url, 'amazon.') !== false) {
            $headers = array_merge($headers, [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding: gzip, deflate, br',
                'Connection: keep-alive',
                'DNT: 1',
                'Sec-Fetch-Dest: document',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-Site: none',
                'Sec-Fetch-User: ?1',
                'TE: trailers',
            ]);
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => 'gzip, deflate',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_COOKIEJAR => sys_get_temp_dir() . '/cookies.txt',
            CURLOPT_COOKIEFILE => sys_get_temp_dir() . '/cookies.txt',
            CURLOPT_REFERER => 'https://www.google.com/',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        
        // Check if we got redirected to a captcha or bot detection page
        if (strpos($effectiveUrl, 'amazon.com/errors/validatecaptcha') !== false || 
            strpos($effectiveUrl, 'amazon.com/ap/signin') !== false ||
            strpos($result, 'Enter the characters you see below') !== false ||
            strpos($result, 'Robot Check') !== false) {
            
            error_log("Amazon bot detection triggered. Attempt: " . ($attempt + 1));
            $attempt++;
            usleep(500000 * $attempt); // Wait before retry
            continue;
        }
        
        // If we got a successful response, break the retry loop
        if ($httpCode === 200 && $result) {
            break;
        }
        
        $attempt++;
        usleep(1000000 * $attempt); // Wait before retry
    }
    
    curl_close($ch);
    
    // Log errors for debugging
    if ($httpCode !== 200) {
        error_log("HTTP request failed for $url with status code: $httpCode");
    }
    
    return $httpCode === 200 ? $result : null;
}

function text_first(array $nodeLists): string {
    foreach ($nodeLists as $nl) {
        if ($nl && $nl->length) {
            $node = $nl->item(0);
            if ($node) return $node->nodeValue ?? '';
        }
    }
    return '';
}

function parse_price(?string $text): float {
    if (!$text) return 0.0;
    $t = trim($text);
    // Remove currency symbols and non-breaking spaces
    $t = str_replace(["\xc2\xa0", '₹', 'Rs.', 'rs.', 'INR', '$', '€', '£', '*'], ' ', $t);
    // Find all numeric candidates: with thousand separators or plain
    if (preg_match_all('/\b[0-9]{1,3}(?:,[0-9]{2,3})+(?:\.[0-9]{1,2})?|\b[0-9]+(?:\.[0-9]{1,2})?/u', $t, $all)) {
        $best = 0.0;
        foreach ($all[0] as $cand) {
            $val = (float)str_replace([','], [''], $cand);
            if ($val > $best) { $best = $val; }
        }
        if ($best > 0) return $best;
    }
    return 0.0;
}
