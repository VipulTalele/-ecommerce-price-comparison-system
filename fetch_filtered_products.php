<?php
if (!isset($_GET['query'])) {
    echo "Please provide a search query.";
    exit;
}

$query = $_GET['query'];
$minPrice = isset($_GET['minPrice']) ? (float)$_GET['minPrice'] : 0;
$maxPrice = isset($_GET['maxPrice']) ? (float)$_GET['maxPrice'] : PHP_INT_MAX;

// Run your scraper script
$command = escapeshellcmd("node amazon_flipkart_scraper.js \"$query\" $minPrice $maxPrice");
$output = shell_exec($command);

if (!$output) {
    echo "Error fetching products.";
    exit;
}

$data = json_decode($output, true);

// Merge all products into a single array
$allProducts = array_merge(
    $data['amazon'] ?? [],
    $data['flipkart'] ?? [],
    $data['snapdeal'] ?? [],
    $data['meesho'] ?? []
);

// Sort products by price in ascending order
usort($allProducts, function ($a, $b) {
    return floatval(preg_replace('/[^\d.]/', '', $a['price'])) - floatval(preg_replace('/[^\d.]/', '', $b['price']));
});

echo "<table border='1'>";
echo "<tr><th>Title</th><th>Price</th><th>Link</th></tr>";

foreach ($allProducts as $product) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($product['title']) . "</td>";
    echo "<td>" . htmlspecialchars($product['price']) . "</td>";
    echo "<td><a href='" . htmlspecialchars($product['link']) . "' target='_blank'>Click Here</a></td>";
    echo "</tr>";
}

echo "</table>";
?>
