<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_clean();
header('Content-Type: application/json');

/* -------------------------
   0. SAFETY FIX (IMPORTANT)
   Prevent old Gemini "parts" memory crash
------------------------- */
if (!isset($_SESSION['chat_format'])) {
    $_SESSION['chat_history'] = [];
    $_SESSION['chat_format'] = 'groq';
}

/* -------------------------
   1. GROQ API KEY
------------------------- */
define('GROQ_API_KEY', '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');

/* -------------------------
   2. DB CONNECTION
------------------------- */
require_once('db.php');

$userMessage = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($userMessage)) {
    echo json_encode(['reply' => 'I didn’t catch that. Could you ask an eco-related question?']);
    exit();
}

$lowerMsg = strtolower($userMessage);

/* -------------------------
   3. RESET CHAT
------------------------- */
if (in_array($lowerMsg, ['clear', 'reset', 'clear history', 'clear chat'])) {
    $_SESSION['chat_history'] = [];
    echo json_encode(['reply' => '🧹 Chat memory cleared!']);
    exit();
}

/* -------------------------
   4. KEYWORD EXTRACTION
------------------------- */
$cleanMsg = preg_replace('/[^a-z0-9\s]/', '', $lowerMsg);
$words = explode(' ', $cleanMsg);

$stopWords = [
    'suggest','me','a','an','the','product','products','show','please','any',
    'get','find','recommend','i','want','to','buy','have','is','in','of','for',
    'with','good','best','eco','friendly','green','something'
];

$searchTerms = [];

foreach ($words as $word) {
    $word = trim($word);
    if (strlen($word) > 2 && !in_array($word, $stopWords)) {
        $searchTerms[] = $word;
    }
}

/* fixes */
if (strpos($lowerMsg, 'laptop') !== false) $searchTerms[] = 'laptop';
if (strpos($lowerMsg, 'fridge') !== false) $searchTerms[] = 'refrigerator';
if (strpos($lowerMsg, 'dryer') !== false) $searchTerms[] = 'dryer';
if (strpos($lowerMsg, 'washer') !== false) $searchTerms[] = 'washing';
if (strpos($lowerMsg, 'phone') !== false) $searchTerms[] = 'phone';

$searchTerms = array_unique($searchTerms);

/* -------------------------
   5. DB CONTEXT
------------------------- */
$dbContext = "";
if (!empty($searchTerms)) {
    $dbContext = getDynamicProductContext($conn, $searchTerms);
}

/* -------------------------
   6. INIT HISTORY
------------------------- */
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

/* -------------------------
   7. SYSTEM PROMPT
------------------------- */
$systemInstruction = "You are the GreenChoice Eco-Assistant. 
You help users find eco-friendly electronics (EPEAT, Energy Star).
If system inventory is provided, prioritize only those products.
If none found, suggest alternatives and eco specifications.";

/* -------------------------
   8. BUILD GROQ MESSAGES (FIXED FORMAT)
------------------------- */
$messages = [];

/* system */
$messages[] = [
    "role" => "system",
    "content" => $systemInstruction
];

/* chat history (FORCED CLEAN FORMAT ONLY) */
foreach ($_SESSION['chat_history'] as $msg) {
    if (isset($msg['role']) && isset($msg['content'])) {
        $messages[] = [
            "role" => $msg['role'],
            "content" => $msg['content']
        ];
    }
}

/* current input */
$currentInput = "";

if (!empty($dbContext)) {
    $currentInput .= "[Inventory Data]\n$dbContext\n\n";
} else {
    $currentInput .= "[Inventory Data: No matching products found]\n\n";
}

$currentInput .= "User: " . $userMessage;

$messages[] = [
    "role" => "user",
    "content" => $currentInput
];

/* -------------------------
   9. GROQ API CALL
------------------------- */
$payload = [
    "model" => "llama-3.3-70b-versatile",
    "messages" => $messages,
    "temperature" => 0.6,
    "max_tokens" => 400
];

$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer " . GROQ_API_KEY
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 20
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

/* -------------------------
   10. CURL ERROR CHECK
------------------------- */
if ($response === false) {
    echo json_encode([
        'reply' => "CURL ERROR: $error"
    ]);
    exit();
}

/* -------------------------
   11. SUCCESS RESPONSE
------------------------- */
if ($http_code === 200) {

    $result = json_decode($response, true);
    $aiReply = $result['choices'][0]['message']['content'] ?? '';

    if (!empty($aiReply)) {

        /* save properly formatted memory */
        $_SESSION['chat_history'][] = [
            "role" => "user",
            "content" => $currentInput
        ];

        $_SESSION['chat_history'][] = [
            "role" => "assistant",
            "content" => $aiReply
        ];

        /* limit memory */
        while (count($_SESSION['chat_history']) > 10) {
            array_shift($_SESSION['chat_history']);
        }

        $htmlReply = str_replace(["**", "\n"], ["<b>", "<br>"], $aiReply);
        $htmlReply = str_replace("</b><b>", "", $htmlReply);

        echo json_encode(['reply' => $htmlReply]);
        exit();
    }
}

/* -------------------------
   12. ERROR DEBUG OUTPUT
------------------------- */
echo json_encode([
    'reply' => "⚠️ Groq API Error HTTP $http_code<br><br>" . htmlspecialchars($response)
]);
exit();


/* -------------------------
   13. DB FUNCTION
------------------------- */
function getDynamicProductContext($conn, $searchTerms) {

    $context = "";

    $check = $conn->query("SHOW TABLES LIKE 'product'");
    if (!$check || $check->num_rows == 0) return "";

    $where = [];

    foreach ($searchTerms as $term) {
        $term = $conn->real_escape_string($term);
        $where[] = "Brand LIKE '%$term%'";
        $where[] = "Product_Type LIKE '%$term%'";
        $where[] = "Product_Name LIKE '%$term%'";
    }

    if (empty($where)) return "";

    $sql = "SELECT productID, Product_Name, Brand, Product_Type, EPEAT_Tier, EnergyStar_Certified
            FROM product
            WHERE " . implode(" OR ", $where) . "
            LIMIT 4";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $epeat = $row['EPEAT_Tier'] ?: 'None';
            $energy = ($row['EnergyStar_Certified']) ? 'Yes' : 'No';

            $context .= "- {$row['productID']} | {$row['Product_Name']} | {$row['Brand']} | {$row['Product_Type']} | EPEAT: $epeat | EnergyStar: $energy\n";
        }
    }

    return $context;
}
?>