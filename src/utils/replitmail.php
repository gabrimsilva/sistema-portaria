<?php
// Blueprint for sending emails using OpenInt mail service in Agent Stack workflows
// This utility handles email sending with minimal user input

class ReplitMail {
    private $authToken;
    
    public function __construct() {
        $this->authToken = $this->getAuthToken();
    }
    
    private function getAuthToken(): string {
        $xReplitToken = null;
        
        if (isset($_ENV['REPL_IDENTITY'])) {
            $xReplitToken = "repl " . $_ENV['REPL_IDENTITY'];
        } elseif (isset($_ENV['WEB_REPL_RENEWAL'])) {
            $xReplitToken = "depl " . $_ENV['WEB_REPL_RENEWAL'];
        }
        
        if (!$xReplitToken) {
            throw new Exception("No authentication token found. Please set REPL_IDENTITY or ensure you're running in Replit environment.");
        }
        
        return $xReplitToken;
    }
    
    public function sendEmail(array $message): array {
        // Validate required fields
        if (empty($message['to'])) {
            throw new InvalidArgumentException("Recipient email address is required");
        }
        
        if (empty($message['subject'])) {
            throw new InvalidArgumentException("Email subject is required");
        }
        
        if (empty($message['text']) && empty($message['html'])) {
            throw new InvalidArgumentException("Email content (text or html) is required");
        }
        
        // Prepare request data
        $requestData = [
            'to' => $message['to'],
            'subject' => $message['subject']
        ];
        
        if (!empty($message['cc'])) {
            $requestData['cc'] = $message['cc'];
        }
        
        if (!empty($message['text'])) {
            $requestData['text'] = $message['text'];
        }
        
        if (!empty($message['html'])) {
            $requestData['html'] = $message['html'];
        }
        
        if (!empty($message['attachments'])) {
            $requestData['attachments'] = $message['attachments'];
        }
        
        // Send email via OpenInt API
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://connectors.replit.com/api/v2/mailer/send",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($requestData),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "X_REPLIT_TOKEN: " . $this->authToken,
            ],
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode !== 200) {
            $errorMessage = isset($responseData['message']) ? $responseData['message'] : 'Failed to send email';
            throw new Exception($errorMessage);
        }
        
        return $responseData;
    }
}

// Helper function for easy usage
function sendEmail(array $message): array {
    $mailer = new ReplitMail();
    return $mailer->sendEmail($message);
}