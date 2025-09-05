
<?php
$searchQuery = isset($_GET['q']) ? escapeshellarg($_GET['q']) : '';
if ($searchQuery) {
    $command = "node amazon_flipkart_scraper.js $searchQuery";
    $output = shell_exec($command);
    header('Content-Type: application/json');
    echo $output;
} else {
    echo json_encode(["error" => "No search query provided"]);
}
?>
