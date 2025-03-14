<?php

$original_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "http://localhost/PHP_Journey/otp.php?otp1=1&otp2=2";

// Parse the URL
$url_components = parse_url($original_url);

// Error handling if parse_url fails
if ($url_components === false) {
  echo "Error: Could not parse the URL.";
  exit; // Stop further processing
}

// Check if 'query' exists
$query_params = [];
if (isset($url_components['query'])) {
    parse_str($url_components['query'], $query_params);
}

// Modify Query Parameters (Example)
$query_params['new_param'] = 'new_value'; // Add or modify a parameter
$query_params['another_param'] = 'another_value';

// Build the new query string
$new_query_string = http_build_query($query_params);

// Build the New URL
$new_scheme = isset($url_components['scheme']) ? $url_components['scheme'] : 'http'; // Fallback to http
$new_host = "samplesample.com"; // Replace as needed
$new_path = "/new_path/otp.php"; // Replace as needed

$new_url = $new_scheme . "://" . $new_host . $new_path;

if (!empty($new_query_string)) {
    $new_url .= "?" . $new_query_string;
}

// Display the Original URL and New URL
echo "Original URL: " . $original_url . "<br>";
echo "New URL: " . htmlspecialchars($new_url) . "<br>";

// Add HTML for the form
?>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
  <input type="hidden" name="new_url" value="<?php echo htmlspecialchars($new_url); ?>">
  <button type="submit">Submit New URL</button>
</form>
<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submitted_url = $_POST['new_url'];
    echo "<br>Submitted New URL: " . htmlspecialchars($submitted_url);
}
?>