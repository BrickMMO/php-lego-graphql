<?php

// --- Configuration ---

$root_url = 'https://www.lego.com/';
$api_url = 'https://www.lego.com/api/graphql/StoresDirectory';
$cookie_file = __DIR__ . '/lego_cookies.txt'; // Ensure this file is writable!

$query = '
query {
  storesDirectory {
    id
    country
    region
    stores {
      storeId
      name
      phone
      state
      phone
      openingDate
      certified
      additionalInfo
      storeUrl
      urlKey
      isNewStore
      isComingSoon
      __typename
    }
    __typename
  }
}';

$data = array('query' => $query);
$jsonData = json_encode($data);

// Define headers for both requests
$browser_headers = array(
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
    'Accept: */*',
    'Accept-Language: en-US,en;q=0.9',
    'Referer: https://www.lego.com/',
    'Sec-Fetch-Site: same-origin'
);

// --- 1. Step 1: Get the Security Cookie (Pre-flight Check) ---

echo "Starting security check on root domain...<br>";

$ch1 = curl_init($root_url);
curl_setopt($ch1, CURLOPT_HTTPHEADER, $browser_headers);
curl_setopt($ch1, CURLOPT_COOKIEJAR, $cookie_file); // Save cookie
curl_setopt($ch1, CURLOPT_COOKIEFILE, $cookie_file); // Use cookie
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, 0); 

$response1 = curl_exec($ch1);
$error1 = curl_error($ch1);
curl_close($ch1);

if ($response1 === false) 
{
    echo "**cURL Error on Step 1:** {$error1}<br>";
    die();
}

echo "Security check completed. Cookies saved to '{$cookie_file}'.<br>";

// --- 2. Step 2: Execute the GraphQL API Call ---

echo "Executing GraphQL API call with saved cookies...<br>";

$ch2 = curl_init($api_url);

// Headers for the API call: merged browser headers + specific GraphQL headers
curl_setopt($ch2, CURLOPT_HTTPHEADER, array_merge($browser_headers, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData),
    'Origin: https://www.lego.com',
    'X-Requested-With: XMLHttpRequest', 
    'x-apollo-operation-name: storesDirectory' 
)));

// Crucially, use the same cookie file
curl_setopt($ch2, CURLOPT_COOKIEJAR, $cookie_file); 
curl_setopt($ch2, CURLOPT_COOKIEFILE, $cookie_file); 

curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);

$response2 = curl_exec($ch2);
$error2 = curl_error($ch2);
curl_close($ch2);

echo $response2;

if ($response2 === false) 
{
    echo "**cURL Error on Step 2:** {$error2}<br>";
} 
else 
{

    $decoded_response = json_decode($response2, true);
    
    if (json_last_error() === JSON_ERROR_NONE) 
    {
        echo "✅ **Success!** JSON response received.<br><br>";
        print_r($decoded_response);
    } 
    else 
    {
        echo "⚠️ **Warning!** Received a response that is not valid JSON.<br><br>";
        // Output the raw response to check for a new Cloudflare block message
        print_r($response2); 
    }

}

die();

?>


<?php

// This code used to work

// TODO
// If not logged in
// Check for key

if(!security_is_logged_in())
{
    $data = array('message' => 'Must be logged in to use this ajax call.', 'error' => false);
    return;
}

$url = 'https://www.lego.com/api/graphql/StoresDirectory';

$query = '
query {
  storesDirectory {
    id
    country
    region
    stores {
      storeId
      name
      phone
      state
      phone
      openingDate
      certified
      additionalInfo
      storeUrl
      urlKey
      isNewStore
      isComingSoon
      __typename
    }
    __typename
  }
}';

$data = array('query' => $query);
$jsonData = json_encode($data);

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
));
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

$response = curl_exec($ch);

debug_pre($response);

die();

// Code to add stores to database

if (curl_errno($ch)) 
{
    echo 'Error:' . curl_error($ch);
} 
else 
{
    $stores = json_decode($response, true);   
}

curl_close($ch);

$query = 'TRUNCATE TABLE stores';
mysqli_query($connect, $query);

$query = 'UPDATE settings SET 
  value = NOW() 
  WHERE name = "STORES_LAST_IMPORT" 
  LIMIT 1';
mysqli_query($connect, $query);

$data = array(
    'message' => 'LEGO Stores has been retrieved.',
    'error' => false, 
    'stores' => $stores
);
