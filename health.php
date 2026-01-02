<?php
// Simple healthcheck endpoint for Railway
header('Content-Type: application/json');
echo json_encode(['status' => 'ok', 'version' => 'v1.0.2']);
