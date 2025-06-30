<?php
require_once 'config/config.php';
require_once 'config/db.php';

class MSGraphHelper {
    private $accessToken;
    private $config;
    private $userId = 'certificados@norteamericanoconcepcion.cl'; // El correo desde el que se enviarán los emails

    public function __construct() {
        $this->config = require 'config.php';
        $this->accessToken = $this->getAccessToken();
    }

    private function getAccessToken() {
        $accessToken = $this->getAccessTokenFromFile();
        if (!$accessToken) {
            $accessToken = $this->getNewAccessToken();
        }
        return $accessToken;
    }

    private function getNewAccessToken() {
        $tenantId = $this->config['tenantId'];
        $clientId = $this->config['clientId'];
        $clientSecret = $this->config['clientSecret'];

        $tokenUrl = "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token";
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => 'https://graph.microsoft.com/.default'
        ];

        $curl = curl_init($tokenUrl);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode == 200) {
            $response = json_decode($response, true);
            if (isset($response['access_token'])) {
                file_put_contents('access_token.txt', $response['access_token']);
                return $response['access_token'];
            }
        }
        return null;
    }

    private function getAccessTokenFromFile() {
        $filePath = 'access_token.txt';
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        }
        return null;
    }

    public function sendMail($toEmail, $subject, $body, $attachmentContent, $attachmentName) {
        $url = "https://graph.microsoft.com/v1.0/users/{$this->config['userId']}/sendMail";
        
        $messageBody = [
            'message' => [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'Text',
                    'content' => $body
                ],
                'toRecipients' => [
                    [
                        'emailAddress' => [
                            'address' => $toEmail
                        ]
                    ]
                ],
                'attachments' => [
                    [
                        '@odata.type' => '#microsoft.graph.fileAttachment',
                        'name' => $attachmentName,
                        'contentType' => 'application/pdf',
                        'contentBytes' => base64_encode($attachmentContent)
                    ]
                ]
            ],
            'saveToSentItems' => 'true'
        ];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$this->accessToken}",
            "Content-Type: application/json"
        ]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($messageBody));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode == 202) {
            return true;
        } else {
            error_log("Error sending email: " . $response);
            return false;
        }
    }
}

// Ruta al archivo de token
$tokenFile = 'access_token.txt';

// Verifica si el archivo existe y lo elimina
if (file_exists($tokenFile)) {
    unlink($tokenFile);
}