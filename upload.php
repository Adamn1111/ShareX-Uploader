<?php
 
$secret_key = ""; // Generate a random key, or use a password, whatever works for you. 
$path = "i/"; // This is where the images are going to end up, make sure you actually create this folder and chmod it to 711.
$domain_url = 'https://example.com'; // Set this to the domain that you're going to be uploading to
$lengthofstring = 5; // Change this to anything, the lower the number, the less images you can store. You'll likely never reach the limit at 5.

function RandomString($length)
{
    $keys = array_merge(range(0, 9), range('a', 'z'));
    $key = 1;
    for ($i = 0; $i < $length; $i++) {
        $key .= $keys[mt_rand(0, count($keys) - 1)];
    }
    return $key;
}

if (isset($_POST['secret'])) {
    if ($_POST['secret'] === $secret_key) {
        $filename = RandomString($lengthofstring);
        $target_file = $_FILES["file"]["name"]; // Your file form name is claled "file"
        $fileType = pathinfo($target_file, PATHINFO_EXTENSION);

        if (move_uploaded_file($_FILES["file"]["tmp_name"], $path . $filename . '.' . $fileType)) { // Your file form name is claled "file"
            $file_to_compress = "/" . $path . $filename . '.' . $fileType;
            $mime = mime_content_type($file_to_compress);
            $info = pathinfo($file_to_compress);
            $name = $info['basename'];
            $output = new CURLFile($file_to_compress, $mime, $name);
            $data = array(
                "files" => $output,
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://api.resmush.it/');  // Passes data through an API to reduce total filesize. Genius.
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                $result = curl_error($ch);
            }
            curl_close($ch);

            $optimized_png_url = json_decode($result)->dest;

            if (isset($optimized_png_url)) {
                file_put_contents($file, file_get_contents($optimized_png_url));
            }

            echo $domain_url . "/" . $path . $filename . '.' . $fileType;
        } else {
            echo 'File upload failed - CHMOD/Folder doesn\'t exist?';
        }
    } else {
        echo 'Invalid Secret Key'; // If key is incorrect, return
    }
} else {
    echo 'No post data recieved'; // If upload.php receives no data, return
}

?>
