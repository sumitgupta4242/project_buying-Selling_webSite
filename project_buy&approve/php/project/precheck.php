<?php
// php/project/precheck.php
require_once '../config.php';

// IMPORTANT: Your API key is now safe on the server.
$apiKey = 'AIzaSyB67kin1fVLZ4B-BBH_iBmq330o4qoALH8'; // <--- MAKE SURE YOUR API KEY IS PASTED HERE

$data = json_decode(file_get_contents('php://input'), true);
$promptBody = $data['prompt'] ?? '';

if (empty($promptBody)) {
    echo json_encode(['error' => 'No prompt provided.']);
    exit;
}
if (empty($apiKey) || $apiKey === 'YOUR_GEMINI_API_KEY') {
    http_response_code(500);
    echo json_encode(['error' => 'API Key is not configured on the server.']);
    exit;
}

// --- NEW AND IMPROVED PROMPT ---
$system_prompt = <<<PROMPT
You are a strict AI assistant that reviews project submissions.
Your entire response MUST BE ONLY a single, valid JSON object and nothing else.
The JSON object must have a key "overallStatus" ('pass' or 'fail') and a key "feedbackItems" which is an array of objects.
Each object in the "feedbackItems" array MUST have exactly three keys: "field", "status" ('pass' or 'fail'), and "comment".

Here is a perfect example of the required output format:
{
  "overallStatus": "fail",
  "feedbackItems": [
    {
      "field": "Project Title",
      "status": "pass",
      "comment": "The title is clear and professional."
    },
    {
      "field": "Project Description",
      "status": "fail",
      "comment": "The description is too short, please elaborate on the technologies used."
    }
  ]
}

Now, analyze the following project data based on these rules:
1.  **Project Title** (CRITICAL): Must be professional and descriptive.
2.  **Project Description** (CRITICAL): Must be at least 25 words and clearly explain the project's purpose.
PROMPT;


$finalPrompt = $system_prompt . "\n\n--- PROJECT DATA TO ANALYZE ---\n" . $promptBody;

$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key={$apiKey}";
$postData = json_encode(['contents' => [['parts' => [['text' => $finalPrompt]]]]]);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode != 200) {
    http_response_code($httpcode);
    echo $response;
} else {
    echo $response;
}
?>