<?php
require_once __DIR__ . '/../vendor/autoload.php';
use GuzzleHttp\Client;

function api_fetch_price(string $url): ?float {
    $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
    if ($host === '') return null;

    // Normalize host (strip www.)
    if (str_starts_with($host, 'www.')) { $host = substr($host, 4); }

    // Placeholder routing for major stores; integrate real APIs with keys later
    if (strpos($host, 'amazon.') !== false) {
        return null;
    }
    if (strpos($host, 'ebay.') !== false) {
        return null;
    }
    if (strpos($host, 'bestbuy.') !== false || strpos($host, 'bestbuyapis') !== false) {
        return null;
    }

    // Generic fallback: attempt to extract price from JSON-LD Offer blocks quickly
    try {
        $client = new Client([
            'timeout' => 15,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate',
            ],
            'allow_redirects' => [ 'max' => 5, 'strict' => false, 'referer' => true ],
            'verify' => false,
        ]);
        $res = $client->get($url);
        if ($res->getStatusCode() >= 400) { return null; }
        $body = (string)$res->getBody();
        if ($body === '') { return null; }
        $price = api_extract_price_from_jsonld($body);
        return $price > 0 ? $price : null;
    } catch (\Throwable $e) {
        return null;
    }
}

function api_extract_price_from_jsonld(string $html): float {
    // Extract <script type="application/ld+json"> JSON-LD and parse Offer price fields
    $price = 0.0;
    if (preg_match_all('#<script[^>]+type=\"application/ld\+json\"[^>]*>(.*?)</script>#is', $html, $m)) {
        foreach ($m[1] as $block) {
            $json = trim(html_entity_decode($block));
            $data = json_decode($json, true);
            if (!is_array($data)) { continue; }
            // Some pages wrap JSON-LD in arrays
            $candidates = isset($data['@graph']) && is_array($data['@graph']) ? $data['@graph'] : (is_array($data) ? [$data] : []);
            foreach ($candidates as $node) {
                if (!is_array($node)) continue;
                // Direct Offer
                if (($node['@type'] ?? '') === 'Offer') {
                    $p = (float)($node['price'] ?? 0);
                    if ($p > 0) return $p;
                }
                // Product with offers
                if (($node['@type'] ?? '') === 'Product' && !empty($node['offers'])) {
                    $offers = $node['offers'];
                    if (isset($offers['@type']) && $offers['@type'] === 'Offer') {
                        $p = (float)($offers['price'] ?? 0);
                        if ($p > 0) return $p;
                    } elseif (is_array($offers)) {
                        foreach ($offers as $of) {
                            if (is_array($of) && ($of['@type'] ?? '') === 'Offer') {
                                $p = (float)($of['price'] ?? 0);
                                if ($p > 0) return $p;
                            }
                        }
                    }
                }
            }
        }
    }
    return $price;
}
