<?php
// Script pentru a gasi Project ID-ul Railway folosind Token-ul
$token = 'bc9314c5-24ae-4f9b-80e1-c7469e10dfb2'; // Token-ul vizibil in screenshot

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://backboard.railway.app/graphql/v2");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Content-Type: application/json"
]);

// Query GraphQL pentru a lua proiectele
$query = <<<'JSON'
{
  "query": "query { me { projects { edges { node { id name } } } } }"
}
JSON;

curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    echo "RĂSPUNS RAILWAY:\n";
    echo $result;
}
curl_close($ch);
?>