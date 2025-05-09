<?php

// --- Database Configuration ---
require 'database.php';
// --- reCAPTCHA Configuration ---
// >>> YOU NEED TO REPLACE THESE WITH YOUR ACTUAL reCAPTCHA SECRET KEY <<<
// Get this from your Google reCAPTCHA admin panel for the *server-side* key
$recaptcha_secret_key = ' 6LdcSzMrAAAAAJJEKlmSfE3MGkUqghDZy28x3MOg';

// --- Connect to Database ---
// Suppress default errors for a cleaner output, handle them manually
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection manually
if ($conn->connect_error) {
    // In a production environment, log this error securely instead of displaying detailed info
    error_log("Database Connection failed: " . $conn->connect_error); // Log the technical error
    die("An error occurred connecting to the database."); // Generic message for the user
}

// Set character set to utf8mb4 for broader character support (like emojis)
$conn->set_charset("utf8mb4");

// --- Get Data from Form ---
// Use $_POST to get data submitted via POST method
// Use htmlspecialchars to prevent basic XSS on the server-side *before* using the data (though database insertion with prepared statements is the main protection)
$username = htmlspecialchars(trim($_POST['username'] ?? '')); // trim removes leading/trailing whitespace
$message = htmlspecialchars(trim($_POST['message'] ?? ''));
$recaptcha_response = $_POST['g-recaptcha-response'] ?? ''; // Name from reCAPTCHA client-side

// Get user's IP address
// Be aware that $_SERVER['REMOTE_ADDR'] might not be the *real* IP if behind proxies (like Cloudflare)
// For simple use cases, it's often sufficient.
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A'; // Fallback to 'N/A' if not available

// --- Server-side Validation ---

// 1. Validate Message
if (empty($message)) { // After trim, empty check is sufficient
    // Output error response for AJAX
    header('Content-Type: text/plain', true, 400); // Send a 400 Bad Request status
    die("Error: Message cannot be empty.");
    // For non-AJAX (standard form post), you would redirect back with an error.
    // header("Location: index.html?error=message_empty"); exit;
}

// 2. Validate Username (if required for new users - handled later based on IP check)
// We don't validate emptiness here, but based on IP existence later.

// 3. Validate reCAPTCHA
function validateRecaptcha($response, $secret_key) {
    if (empty($response)) {
        error_log("reCAPTCHA validation failed: No response provided.");
        return false; // No reCAPTCHA response provided
    }

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $secret_key,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '' // Recommended for security, pass IP
    ];

    // Use cURL for a more robust request (requires cURL extension enabled in php.ini)
    if (!function_exists('curl_init')) {
        error_log("reCAPTCHA API validation failed: cURL extension not enabled.");
         // Fallback to file_get_contents if cURL is not available (less secure/reliable)
         if (ini_get('allow_url_fopen')) {
             $options = ['http' => ['header'  => "Content-type: application/x-www-form-urlencoded\r\n",'method'  => 'POST','content' => http_build_query($data),],];
             $context  = stream_context_create($options);
             $verify_response = @file_get_contents($url, false, $context); // Use @ to suppress warnings
         } else {
              error_log("reCAPTCHA API validation failed: Neither cURL nor allow_url_fopen is enabled.");
              return false; // Cannot perform validation
         }
    } else {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Get response as string
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); // Verify SSL certificate
        curl_setopt($curl, CURLOPT_TIMEOUT, 5); // Set timeout

        $verify_response = curl_exec($curl);
        $curl_error = curl_error($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($verify_response === FALSE) {
            error_log("reCAPTCHA API connection failed (cURL error: $curl_error, HTTP code: $http_code).");
            return false; // Connection failed
        }
    }


    if ($verify_response === FALSE) {
        // This happens if neither cURL nor file_get_contents worked or network issue
        error_log("reCAPTCHA API call failed after transport attempt.");
        return false;
    }


    $captcha_data = json_decode($verify_response);

    // Check if decoding failed or success field is missing
     if ($captcha_data === null || !isset($captcha_data->success)) {
         error_log("reCAPTCHA API response invalid: " . print_r($captcha_data, true));
         return false;
     }

    // For reCAPTCHA v2, check $captcha_data->success
    // For reCAPTCHA v3, check $captcha_data->success AND $captcha_data->score (e.g., score > 0.5)
    // Adjust this check based on which reCAPTCHA type you are using.
    if ($captcha_data->success === false) {
         // Log reCAPTCHA failure details for debugging if needed
         error_log("reCAPTCHA verification failed. Response: " . print_r($captcha_data, true));
    }

    // Assuming reCAPTCHA v2 check
    return $captcha_data->success;
}

if (!validateRecaptcha($recaptcha_response, $recaptcha_secret_key)) {
    // Handle reCAPTCHA failure
    header('Content-Type: text/plain', true, 403); // Send a 403 Forbidden status
    die("Error: reCAPTCHA verification failed. Are you a robot?");
    // For non-AJAX: header("Location: index.html?error=recaptcha_failed"); exit;
}


// --- Database Logic (IP/User Handling) ---
$ip_login_id = null;
$user_name_to_save = ''; // This will hold the username that actually gets linked

// Use transaction for atomicity if performing multiple related writes (insert IP, insert username, insert chat)
// Although for this specific flow (check IP -> maybe insert IP & username -> insert chat), a single transaction for
// the "new IP" path is more relevant. Let's simplify and do sequential checks/inserts first.

// Check if IP already exists in ip_logins
// Use prepared statement for security
$sql_check_ip = "SELECT id FROM ip_logins WHERE ip_address = ?";
$stmt_check_ip = $conn->prepare($sql_check_ip);

if ($stmt_check_ip === false) {
     error_log("Database error preparing statement to check IP: " . $conn->error);
     header('Content-Type: text/plain', true, 500); die("An internal error occurred.");
}

$stmt_check_ip->bind_param("s", $ip_address);
$stmt_check_ip->execute();
$result_check_ip = $stmt_check_ip->get_result();

if ($result_check_ip->num_rows > 0) {
    // IP exists, get the existing ip_login_id
    $row = $result_check_ip->fetch_assoc();
    $ip_login_id = $row['id'];

    // As per requirement: If IP exists, use the saved username linked to this login ID.
    // Query usernames table based on the ip_login_id (using the 'id' foreign key)
    $sql_get_username = "SELECT user_name FROM usernames WHERE id = ?";
    $stmt_get_username = $conn->prepare($sql_get_username);

    if ($stmt_get_username === false) {
        error_log("Database error preparing statement to get username: " . $conn->error);
        header('Content-Type: text/plain', true, 500); die("An internal error occurred.");
    }

    $stmt_get_username->bind_param("i", $ip_login_id);
    $stmt_get_username->execute();
    $result_get_username = $stmt_get_username->get_result();

    if ($result_get_username->num_rows > 0) {
        // Found a username linked to this specific login ID
        $user_row = $result_get_username->fetch_assoc();
        $user_name_to_save = $user_row['user_name'];
        // Note: The username submitted via the form ($username) is ignored here as per logic.
        // You might want to send this saved username back to the client via AJAX
        // to pre-fill the field on page load, which would be handled in index.js's
        // DOMContentLoaded, perhaps by another PHP script.
    } else {
         // *** PROBLEM SCENARIO BASED ON SCHEMA/LOGIC ***
         // IP exists, but no username is linked to this ip_login_id.
         // This indicates an inconsistency based on the workflow (username should be saved
         // when the IP login entry is created).
         error_log("IP login ID found ($ip_login_id) but no linked username in 'usernames' table.");
         // Output an error.
         header('Content-Type: text/plain', true, 500); die("Error: User data inconsistency detected.");
    }
    $stmt_get_username->close();

} else {
    // IP does NOT exist, this is a new login
    // 1. Validate Username (required for new IPs)
    if (empty($username)) {
         header('Content-Type: text/plain', true, 400); die("Error: Username cannot be empty for a new user.");
    }
    $user_name_to_save = $username; // Use the username provided by the user

    // Start a transaction for atomicity for the new user creation steps
    $conn->begin_transaction();
    $transaction_failed = false;

    try {
        // 2. Insert new IP into ip_logins
        $sql_insert_ip = "INSERT INTO ip_logins (ip_address) VALUES (?)";
        $stmt_insert_ip = $conn->prepare($sql_insert_ip);

        if ($stmt_insert_ip === false) {
             throw new Exception("Database error preparing insert IP statement: " . $conn->error);
        }
        $stmt_insert_ip->bind_param("s", $ip_address);
        if (!$stmt_insert_ip->execute()) {
             throw new Exception("Database error inserting IP login: " . $stmt_insert_ip->error);
        }
        $ip_login_id = $conn->insert_id; // Get the ID of the newly inserted row
        $stmt_insert_ip->close();

        // 3. Insert username into usernames, linked to the new ip_login_id
        // *** POTENTIAL FAILURE POINT DUE TO SCHEMA ***
        // This will fail if the provided $username already exists in the 'usernames' table globally
        // because 'user_name' is the PRIMARY KEY.
        $sql_insert_username = "INSERT INTO usernames (user_name, id) VALUES (?, ?)";
        $stmt_insert_username = $conn->prepare($sql_insert_username);

        if ($stmt_insert_username === false) {
             throw new Exception("Database error preparing insert username statement: " . $conn->error);
        }
        $stmt_insert_username->bind_param("si", $user_name_to_save, $ip_login_id);
        if (!$stmt_insert_username->execute()) {
             // Check if it's a duplicate entry error (e.g., username already taken)
             if ($conn->errno == 1062) { // MySQL error code for duplicate entry for a primary key
                 // Rollback the transaction because the username insertion failed
                 $conn->rollback();
                 // Send a specific error message back to the user
                 header('Content-Type: text/plain', true, 409); // 409 Conflict status
                 die("Error: Username '" . htmlspecialchars($user_name_to_save) . "' is already taken or used with another IP session based on the current database structure.");
             } else {
                 // Other database error during username insert
                 throw new Exception("Database error inserting username: " . $stmt_insert_username->error);
             }
        }
        $stmt_insert_username->close();

        // If we reached here, both IP and username inserts were successful within the transaction
        // We will commit the transaction later if chat insertion also succeeds.

    } catch (Exception $e) {
        // Catch any exceptions during the transaction and roll back
        $conn->rollback();
        $transaction_failed = true;
        error_log("Transaction failed during new user creation: " . $e->getMessage());
        header('Content-Type: text/plain', true, 500); die("An internal error occurred during user setup.");
    }

    // If the transaction failed (e.g., username already exists), stop here
    if ($transaction_failed) {
        $stmt_check_ip->close(); // Close the initial check stmt
        $conn->close(); // Close connection
        exit; // Stop script execution
    }

} // End of else (IP does NOT exist)

$stmt_check_ip->close(); // Close the initial check statement

// --- Insert Chat Message ---
// We now have a valid $ip_login_id and $user_name_to_save
if ($ip_login_id && $user_name_to_save) {

    // The message is already htmlspecialchars'd from retrieval

    $sql_insert_chat = "INSERT INTO chats (id, user_name, message) VALUES (?, ?, ?)";
    $stmt_insert_chat = $conn->prepare($sql_insert_chat);

    if ($stmt_insert_chat === false) {
        // If we are inside a transaction, we should roll back here as well
        if ($conn->in_transaction) $conn->rollback();
        error_log("Database error preparing chat insert statement: " . $conn->error);
        header('Content-Type: text/plain', true, 500); die("An internal error occurred.");
    }

    // Bind parameters: i for integer (id), s for string (user_name, message)
    $stmt_insert_chat->bind_param("iss", $ip_login_id, $user_name_to_save, $message);

    if ($stmt_insert_chat->execute()) {
        // --- Success ---
        // If a transaction was started for a new user, commit it now
        if ($conn->in_transaction) {
            $conn->commit();
        }

        // Output a success message/status for the AJAX call to receive
        header('Content-Type: text/plain'); // Simple text response
        echo "success"; // Or a more structured response like JSON: echo json_encode(["status" => "success"]);

    } else {
        // Error inserting chat message
        // Rollback the transaction if it was started
        if ($conn->in_transaction) $conn->rollback();
        error_log("Database error inserting chat: " . $stmt_insert_chat->error);
        header('Content-Type: text/plain', true, 500); die("Error sending message: Could not save chat.");
    }

    $stmt_insert_chat->close();

} else {
    // This else block should theoretically not be reached if previous logic is sound,
    // but it's a safeguard.
    if ($conn->in_transaction) $conn->rollback(); // Rollback if transaction was somehow pending
    error_log("Internal error: ip_login_id or user_name_to_save is missing after processing.");
    header('Content-Type: text/plain', true, 500); die("An internal error occurred.");
}


// --- Close Database Connection ---
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HackNo Internet Tips</title>
    <link rel="stylesheet" href="style.css">
    <!-- Favicon - linking directly to SVG like this is possible but less common than .ico or .png -->
    <link rel="icon" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/svgs/solid/shield-halved.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- Google reCAPTCHA API script -->
    <!-- Replace YOUR_RECAPTCHA_SITE_KEY in the div below -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <!-- Ensure this path is correct: <script src="index.js" defer></script> -->
    <script src="index.js" defer></script>
</head>
<body>
    <div class="main-container">
        <div class="left-container">
            <div class="head-container">
                <!-- Font Awesome icon used correctly -->
                <i class="fa-solid fa-shield-halved"></i>
                <div class="logo">
                    <div class="logo-title">
                        HackNo
                    </div>
                    <div class="logo-subtitle">
                        "Not Today, Hackers!"
                    </div>
                </div>
            </div>
            <div class="tab-container">
                <div class="tabs">
                    <!-- Initial 'active' class here will be overridden by JS on load -->
                    <div class="tab active" id="tab-home">
                        <i class="fa-solid fa-house"></i>
                        <div class="tabs-labels">Home</div>
                    </div>
                    <div class="tab" id="tab-passwords">
                        <i class="fa-solid fa-key"></i>
                        <div class="tabs-labels">Password Security</div>
                    </div>
                    <div class="tab" id="tab-connectivity">
                        <i class="fa-solid fa-users"></i>
                        <div class="tabs-labels">Internet Interaction</div>
                    </div>
                    <div class="tab" id="tab-cloud">
                        <i class="fa-solid fa-cloud"></i>
                        <div class="tabs-labels">Cloud Security</div>
                    </div>
                    <div class="tab" id="tab-contact">
                        <i class="fa-solid fa-comment"></i>
                        <div class="tabs-labels">Chat</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="right-container">
            <div class="box-container">
                <!-- Initial 'active' class here will be overridden by JS on load -->
                <div class="box active" id="box-home">
                    <div class="content-container">
                        <!-- Hero Section -->
                        <div class="content-box">
                            <div class="content-title">
                                Stay Safe Online: Simple Cybersecurity Tips for Everyday Use
                            </div>
                            <div class="content-subtitle">
                                Protect your data, privacy, and digital life with these easy-to-follow guidelines.
                            </div>
                        </div>

                        <!-- Password Security -->
                        <div class="content-box target" onclick="gotoTab('box-passwords');">
                            <div class="content-title">
                                🔐 Password Security
                            </div>
                            <div class="content-subtitle">
                                Use strong, unique passwords and enable multi-factor authentication to keep your accounts secure.
                            </div>
                        </div>

                        <!-- Internet Interaction -->
                        <div class="content-box target" onclick="gotoTab('box-connectivity');">
                            <div class="content-title">
                                🌐 Internet Interaction
                            </div>
                            <div class="content-subtitle">
                                Be cautious of suspicious links, avoid public Wi-Fi for sensitive tasks, and verify URLs before clicking.
                            </div>
                        </div>

                        <!-- Cloud Security -->
                        <div class="content-box target" onclick="gotoTab('box-cloud');">
                            <div class="content-title">
                                ☁️ Cloud Security
                            </div>
                            <div class="content-subtitle">
                                Choose trusted cloud services, enable encryption, and manage file access permissions carefully.
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="content-footer">
                            © 2023 HackNo. All rights reserved.
                        </div>
                    </div>
                    <div class="background-mascot">
                        <img src="image/image3.png" alt="IMAGE">
                    </div>
                </div>
                <div class="box" id="box-passwords">
                        <div class="content-container">
                            <!-- Introduction -->
                            <div class="content-box">
                                <div class="content-title">
                                    Stay Safe Online: Password Security Matters
                                </div>
                                <div class="content-subtitle">
                                    Passwords are the first line of defense for your digital life. Learn how to create, manage, and protect your passwords effectively.
                                </div>
                            </div>

                            <!-- Password Creation Tips -->
                            <div class="content-box">
                                <div class="content-title">
                                    🔑 Create Strong Passwords
                                </div>
                                <div class="content-subtitle">
                                    Use at least 12 characters with a mix of uppercase, lowercase, numbers, and symbols. Avoid using names, birthdays, or common words.
                                </div>
                            </div>

                            <!-- Password Management -->
                            <div class="content-box">
                                <div class="content-title">
                                    🧠 Manage Passwords Wisely
                                </div>
                                <div class="content-subtitle">
                                    Never reuse passwords across sites. Use a trusted password manager to generate and store your passwords securely.
                                </div>
                            </div>

                            <!-- Extra Protection Tips -->
                            <div class="content-box">
                                <div class="content-title">
                                    🛡️ Enhance Password Security
                                </div>
                                <div class="content-subtitle">
                                    Enable multi-factor authentication (MFA), change compromised passwords immediately, and avoid saving passwords in browsers.
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="content-footer">
                                © 2023 HackNo. All rights reserved.
                            </div>
                        </div>

                    <div class="background-mascot">
                        <img src="image/image2.png" alt="IMAGE">
                    </div>
                </div>
                <div class="box" id="box-connectivity">
                    <div class="content-container">
                        <!-- Introduction -->
                        <div class="content-box">
                            <div class="content-title">
                                Stay Safe Online: Smart Internet Interaction Tips
                            </div>
                            <div class="content-subtitle">
                                The internet is full of opportunities—and risks. Learn how to browse, communicate, and interact safely in the digital world.
                            </div>
                        </div>

                        <!-- Think Before You Click -->
                        <div class="content-box">
                            <div class="content-title">
                                ⚠️ Think Before You Click
                            </div>
                            <div class="content-subtitle">
                                Avoid clicking suspicious links, pop-ups, or email attachments from unknown sources. Always verify before you interact.
                            </div>
                        </div>

                        <!-- Safe Browsing Practices -->
                        <div class="content-box">
                            <div class="content-title">
                                🌐 Practice Safe Browsing
                            </div>
                            <div class="content-subtitle">
                                Use secure websites (look for "https://"), avoid illegal downloads, and steer clear of shady websites to protect your device and data.
                            </div>
                        </div>

                        <!-- Public Wi-Fi Awareness -->
                        <div class="content-box">
                            <div class="content-title">
                                📶 Be Cautious with Public Wi-Fi
                            </div>
                            <div class="content-subtitle">
                                Avoid accessing sensitive accounts on public Wi-Wi. If needed, use a VPN to encrypt your connection and keep your data private.
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="content-footer">
                            © 2023 HackNo. All rights reserved.
                        </div>
                    </div>
                    <div class="background-mascot">
                        <img src="image/image1.png" alt="IMAGE">
                    </div>
                </div>
                <div class="box" id="box-cloud">
                    <div class="content-container">
                        <!-- Introduction -->
                        <div class="content-box">
                            <div class="content-title">
                                Stay Safe Online: Cloud Security Essentials
                            </div>
                            <div class="content-subtitle">
                                Cloud storage makes life easier—but it also requires smart security practices. Learn how to protect your data in the cloud.
                            </div>
                        </div>

                        <!-- Choose Trusted Providers -->
                        <div class="content-box">
                            <div class="content-title">
                                ☁️ Use Trusted Cloud Services
                            </div>
                            <div class="content-subtitle">
                                Stick to reputable providers like Google Drive, OneDrive, or Dropbox. Check their privacy policies and security features before uploading files.
                            </div>
                        </div>
                        <!-- Enable Encryption -->
                        <div class="content-box">
                            <div class="content-title">
                                🔒 Enable Encryption
                            </div>
                            <div class="content-subtitle">
                                Use services that offer encryption for your data both in transit and at rest. For extra protection, encrypt files before uploading.
                            </div>
                        </div>

                        <!-- Manage Access Carefully -->
                        <div class="content-box">
                            <div class="content-title">
                                👥 Control Who Has Access
                            </div>
                            <div class="content-subtitle">
                                Regularly review shared links and permissions. Remove access when no longer needed to prevent unauthorized file viewing or editing.
                            </div>
                        </div>
                        <!-- Footer -->
                        <div class="content-footer">
                            © 2023 HackNo. All rights reserved.
                        </div>
                    </div>
                    <div class="background-mascot">
                        <img src="image/image4.png" alt="IMAGE">
                    </div>
                </div>
                <div class="box" id="box-contact">
                    <div class="feedback-container">
                        <div class="live-feedback">
                            <!-- Added ID to the iframe -->
                            <iframe src="chat.html" frameborder="0" width="100%" height="100%" id="chatIframe"></iframe>
                        </div>
                        <div class="c">
                            <div class="c-box">
                                <div class="c-title">
                                    Share your thoughts and interact with fellow visitors using the Global chat
                                </div>
                            </div>
                        </div>
                        <div class="a-f-container">
                             <!-- Added the form element -->
                            <!-- Make sure action points to your PHP file -->
                            <form id="chatForm" action="process_chat.php" method="POST">
                                <div class="chat-input-container">
                                    <div class="chat-box">
                                        <label for="usernameInput" class="visually-hidden">Username:</label>
                                        <input type="text" name="username" id="usernameInput" placeholder="Your Username" required>

                                        <label for="messageInput" class="visually-hidden">Message:</label>
                                        <textarea name="message" id="messageInput" placeholder="Enter your chat here" rows="3" required></textarea>

                                        <!-- Placeholder for reCAPTCHA -->
                                        <!-- Add the class "g-recaptcha" and data-sitekey -->
                                        <!-- Replace YOUR_RECAPTCHA_SITE_KEY with your actual site key -->
                                        <div id="recaptchaPlaceholder" class="g-recaptcha" data-sitekey="6LdcSzMrAAAAACQkHLEM745outL0-5cPo8CiGubW">
                                             <!-- reCAPTCHA widget will render here -->
                                        </div>

                                        <!-- Submit button is inside the form -->
                                        <button type="submit">Send Message</button>
                                    </div>
                                </div>
                            </form> <!-- End of form -->
                        </div>
                    </div>
                    <div class="background-mascot">
                        <img src="image/image5.png" alt="IMAGE">
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>