<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

set_time_limit(300);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Price Comparison</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background: linear-gradient(to right, #ff7e5f, #feb47b);
            color: white;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 10px;
            align-items: center;
        }
        .search-box {
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: inline-block;
        }
        table {
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.9);
            color: black;
            border-radius: 10px;
        }
        table, th, td {
            border: 1px solid black;
            padding: 10px;
        }
        th {
            background-color: #ff6b6b;
            color: white;
        }
        .platform-logo {
            width: 200px;
            margin: 20px auto;
        }
        .strike {
            text-decoration: line-through;
            color: gray;
        }
    </style>
</head>
<body>
<div class="navbar">
    <span>Welcome, <?= htmlspecialchars($_SESSION['username']); ?>!</span>
    <a href="logout.php">Logout</a>
</div>
<h1>🔍 Product Price Comparison</h1>
<form class="search-box" method="GET">
    <input type="text" name="query" placeholder="Search for a product..." required>
    <button type="submit">Search</button>
</form>
<div class="results-container">
<?php
if (isset($_GET['query'])) {
    $query = escapeshellarg($_GET['query']);
    echo "<h2>Results for: " . htmlspecialchars($_GET['query']) . "</h2>";

    $logos = [
        "amazon" => "https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg",
        "flipkart" => "https://1000logos.net/wp-content/uploads/2021/02/Flipkart-logo.png",
        "snapdeal" => "https://tse4.mm.bing.net/th?id=OIP.AVMVDTj1DfrBfGRLzkrg4QHaDC&pid=Api&P=0&h=180",
        "meesho" => "https://upload.wikimedia.org/wikipedia/commons/6/62/Meesho_Logo_Full.png"
    ];

    $command = "node amazon_flipkart_scraper.js " . $query;
    $output = shell_exec($command . " 2>&1");
    // echo"$output";
    $data = json_decode($output, true);
    
    if (!$data) {
        echo "<p>❌ Error: No valid data received. Check your Node.js script.</p>";
        echo "<pre>$output</pre>";
    } else {
        foreach ($data as $platform => $products) {
            usort($products, function ($a, $b) {
                return intval(preg_replace('/[^0-9]/', '', $a['price'])) - intval(preg_replace('/[^0-9]/', '', $b['price']));
            });
            
            if (!empty($products)) {
                echo "<img src='" . $logos[$platform] . "' class='platform-logo'>";
                echo "<table>
                        <tr>
                            <th>Title</th>
                            <th>Original Price</th>
                            <th>Discounted Price</th>
                            <th>Discount</th>
                            <th>Link</th>
                        </tr>";
                foreach ($products as $product) {
                    $originalPrice = isset($product['original_price']) ? intval(preg_replace('/[^0-9]/', '', $product['original_price'])) : 0;
                    $discountedPrice = intval(preg_replace('/[^0-9]/', '', $product['price']));
                    
                    $discountPercentage = $originalPrice > 0 ? round((($originalPrice - $discountedPrice) / $originalPrice) * 100, 2) : 0;
                    
                    echo "<tr>
                            <td>" . htmlspecialchars($product['title']) . "</td>
                            <td>" . ($originalPrice > 0 ? "<span class='strike'>₹$originalPrice</span>" : "N/A") . "</td>
                            <td>" . htmlspecialchars($product['price']) . "</td>
                            <td>" . ($discountPercentage > 0 ? "$discountPercentage% Off" : "No Discount") . "</td>
                            <td><a href='" . htmlspecialchars($product['link']) . "' target='_blank'>Click Here</a></td>
                          </tr>";
                }
                echo "</table>";
            }
        }
    }
}
?>
</div>
</body>
</html>
