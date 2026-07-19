<?php
class ImageKitUploader
{
    private $privateKey;
    private $urlEndpoint;
    private $uploadEndpoint = "https://upload.imagekit.io/api/v1/files/upload";

    public function __construct()
    {
        // Credentials provided by user
        $this->privateKey = 'private_I9Fru/ehFXiHcqy2zj/47YZlYAk=';
        $this->urlEndpoint = 'https://ik.imagekit.io/Sohan23';
    }

    public function upload($fileTmpPath, $fileName)
    {
        $fileData = file_get_contents($fileTmpPath);
        $base64Data = base64_encode($fileData);

        $postFields = [
            'file' => $base64Data,
            'fileName' => $fileName,
            'useUniqueFileName' => 'true'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->uploadEndpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Basic Auth using Private Key
        curl_setopt($ch, CURLOPT_USERPWD, $this->privateKey . ":");

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['success' => false, 'error' => "cURL Error: $err"];
        }

        $result = json_decode($response, true);

        if (isset($result['url'])) {
            return ['success' => true, 'url' => $result['url']];
        } else {
            return ['success' => false, 'error' => $result['message'] ?? 'Unknown API error'];
        }
    }
}
?>