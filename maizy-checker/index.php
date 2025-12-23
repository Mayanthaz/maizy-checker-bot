<?php
// Telegram Bot Configuration - USE ENVIRONMENT VARIABLES
$botToken = getenv('8263361307:AAHU26XpTylETIueN2ygGoN82E3HwAkXDOM') ?: "";
$stripeKey = getenv('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') ?: "";

// Handle browser access - show status page
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty(file_get_contents('php://input'))) {
    displayHomePage();
    exit;
}

// Handle Telegram webhook
$input = file_get_contents('php://input');
$update = json_decode($input, TRUE);

// Log the request (using error_log for Railway)
error_log(date('Y-m-d H:i:s') . " - " . substr($input, 0, 200));

// Extract message data
$chatId = isset($update["message"]["chat"]["id"]) ? $update["message"]["chat"]["id"] : null;
$userId = isset($update["message"]["from"]["id"]) ? $update["message"]["from"]["id"] : null;
$firstname = isset($update["message"]["from"]["first_name"]) ? $update["message"]["from"]["first_name"] : 'User';
$username = isset($update["message"]["from"]["username"]) ? $update["message"]["from"]["username"] : 'NoUsername';
$message = isset($update["message"]["text"]) ? $update["message"]["text"] : '';
$message_id = isset($update["message"]["message_id"]) ? $update["message"]["message_id"] : null;
$date = isset($update["message"]["date"]) ? $update["message"]["date"] : null;

// Process Telegram message - ONLY if it's a command or recent message
if ($chatId && $message) {
    // Check if message is recent (within last 2 minutes) to avoid processing old messages when bot comes online
    $currentTime = time();
    $messageTime = $date;
    $timeDifference = $currentTime - $messageTime;
    
    // Process only if:
    // 1. It's a command (starts with /) OR
    // 2. Message is recent (within 2 minutes)
    if (strpos($message, '/') === 0 || $timeDifference < 120) {
        processTelegramMessage($chatId, $userId, $firstname, $username, $message, $message_id);
    } else {
        // Log ignored old messages
        error_log("Ignored old message from @$username: $message (Time diff: {$timeDifference}s)");
    }
}

function displayHomePage() {
    global $botToken, $stripeKey;

    // Test bot token
    $testUrl = "https://api.telegram.org/bot$botToken/getMe";
    $testResponse = @file_get_contents($testUrl);
    $testData = json_decode($testResponse, true);

    // Test Stripe key
    $stripeTest = testStripeKeyInternal($stripeKey);

    $botStatus = $testData['ok'] ? "✅ VALID" : "❌ INVALID";
    $botName = $testData['ok'] ? "@" . $testData['result']['username'] : "Unknown";
    $stripeStatus = $stripeTest['valid'] ? "✅ LIVE" : "❌ INVALID";

    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>MAIZY CHECKER</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 20px;
            }
            .container { 
                max-width: 800px; 
                margin: 0 auto; 
                background: white; 
                padding: 30px; 
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            }
            .header { 
                text-align: center; 
                margin-bottom: 30px;
            }
            .header h1 { 
                color: #333; 
                font-size: 2.5em;
                margin-bottom: 10px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .status-card { 
                padding: 20px; 
                margin: 15px 0; 
                border-radius: 10px;
                border-left: 5px solid;
            }
            .success { 
                background: #d4edda; 
                color: #155724;
                border-left-color: #28a745;
            }
            .error { 
                background: #f8d7da; 
                color: #721c24;
                border-left-color: #dc3545;
            }
            .info { 
                background: #d1ecf1; 
                color: #0c5460;
                border-left-color: #17a2b8;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🤖 MAIZY CHECKER</h1>
                <p>Professional Card Checking Bot</p>
            </div>

            <div class="status-card ' . ($testData['ok'] ? 'success' : 'error') . '">
                <h3>🤖 Bot Status: ' . $botStatus . '</h3>
                <p><strong>Bot Name:</strong> ' . $botName . '</p>
                <p><strong>Host:</strong> Railway.app ($20 Plan)</p>
            </div>

            <div class="status-card ' . ($stripeTest['valid'] ? 'success' : 'error') . '">
                <h3>💳 Stripe Status: ' . $stripeStatus . '</h3>
                <p><strong>Key:</strong> ' . substr($stripeKey, 0, 12) . '...' . substr($stripeKey, -4) . '</p>';

    if (!$stripeTest['valid']) {
        echo '<p><strong>Error:</strong> ' . $stripeTest['message'] . '</p>';
    }

    echo '</div>

            <div class="status-card info">
                <h3>📋 Available Commands</h3>
                <p><strong>/start</strong> - Welcome message</p>
                <p><strong>/cmds</strong> - Commands list</p>
                <p><strong>/chk</strong> - Check cards with Stripe</p>
                <p><strong>/bin</strong> - BIN lookup</p>
                <p><strong>/sk</strong> - Stripe key check</p>
            </div>
            
            <div class="status-card info">
                <h3>🌐 Webhook Info</h3>
                <p><strong>URL:</strong> ' . $_SERVER['HTTP_HOST'] . '</p>
                <p><strong>Status:</strong> Live on Railway</p>
            </div>
        </div>
    </body>
    </html>';
}

// KEEP ALL YOUR EXISTING FUNCTIONS HERE (processTelegramMessage, validateCardBasic, getBinInfo, checkCardWithStripe, etc.)
// Just copy all your functions from the previous code, but REMOVE file_put_contents lines
// Replace them with error_log() instead

function processTelegramMessage($chatId, $userId, $firstname, $username, $message, $message_id) {
    $botToken = $GLOBALS['botToken'];

    // Log user activity (using error_log for Railway)
    error_log(date('Y-m-d H:i:s') . " - User: $userId (@$username) - Message: $message");

    // Process commands
    switch (true) {
        case strpos($message, '/start') === 0:
            $welcomeMessage = "✨ <b>🏁 MAIZY CHECKER</b> ✨

🎉 <b>Welcome, $firstname!</b>

🔧 <b>Professional Card Checking Bot</b>

📊 <b>User Information:</b>
   👤 <b>Name:</b> $firstname
   🆔 <b>User ID:</b> <code>$userId</code>
   🔗 <b>Username:</b> @$username

🚀 <b>Available Features:</b>
   ✅ Real Card Checking with Stripe
   ✅ BIN Lookup System
   ✅ Stripe Key Verification

📜 Use <code>/cmds</code> to see all commands!

🔒 <i>Secure • Fast • Reliable • Hosted on Railway</i>";
            sendTelegramMessage($chatId, $welcomeMessage, $message_id);
            break;

        // KEEP ALL OTHER CASES THE SAME AS BEFORE
        // ... rest of your switch cases ...
        
        default:
            // Only respond to commands (messages starting with /)
            if (strpos($message, '/') === 0) {
                $errorMessage = "❓ <b>🚫 UNKNOWN COMMAND</b> ❓

💡 <b>Use</b> <code>/cmds</code> <b>to see available commands</b>

🎯 <b>All features are available for everyone!</b>

🔧 <i>MAIZY CHECKER • Professional Bot • Railway Hosted</i>";
                sendTelegramMessage($chatId, $errorMessage, $message_id);
            }
            // Ignore regular messages that don't start with /
            break;
    }
}

// COPY ALL YOUR OTHER FUNCTIONS EXACTLY AS BEFORE, but:
// Replace file_put_contents() with error_log()

function sendTelegramMessage($chatId, $message, $reply_to_message_id = null) {
    $botToken = $GLOBALS['botToken'];
    $url = "https://api.telegram.org/bot$botToken/sendMessage";

    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    if ($reply_to_message_id) {
        $data['reply_to_message_id'] = $reply_to_message_id;
    }

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
        ],
    ];

    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);

    // Use error_log instead of file_put_contents
    error_log(date('Y-m-d H:i:s') . " - Sent to: $chatId - Message: " . substr($message, 0, 100));

    return $result;
}

// ADD ALL YOUR OTHER FUNCTIONS HERE (validateCardBasic, getBinInfo, checkCardWithStripe, analyzeStripeError, etc.)
// Just copy them from your original code

exit;
?>