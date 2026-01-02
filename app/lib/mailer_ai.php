<?php

function ai_send_email(string $to, string $subject, string $body): array
{
    // Uses the existing mailer library
    if (!function_exists('send_mail')) {
        return ['ok' => false, 'error' => 'Mailer not loaded'];
    }
    // Logging for safety
    error_log("AI Sending Email to: $to | Subject: $subject");

    $ok = send_mail($to, $subject, $body);
    return ['ok' => $ok, 'status' => $ok ? 'Sent successfully' : 'Failed to send'];
}

function ai_check_email(int $limit = 5): array
{
    if (!function_exists('imap_open')) {
        return ['ok' => false, 'error' => 'IMAP extension not enabled on server'];
    }

    global $CONFIG;
    $imap = $CONFIG['mail']['imap'] ?? [];
    if (empty($imap['enabled'])) {
        return ['ok' => false, 'error' => 'IMAP disabled in config'];
    }

    $host = '{' . $imap['host'] . ':' . $imap['port'] . '/imap/ssl}INBOX';
    $mbox = @imap_open($host, $imap['username'], $imap['password']);

    if (!$mbox) {
        return ['ok' => false, 'error' => 'Could not connect to mailbox: ' . imap_last_error()];
    }

    $num = imap_num_msg($mbox);
    if ($num == 0) {
        imap_close($mbox);
        return ['ok' => true, 'emails' => []];
    }

    $emails = [];
    // Fetch last $limit emails (from $num down to $num-$limit)
    for ($i = $num; $i > max($num - $limit, 0); $i--) {
        $header = imap_headerinfo($mbox, $i);
        $body = imap_fetchbody($mbox, $i, 1); // Simple fetch, might need decoding

        $from = $header->from[0]->mailbox . "@" . $header->from[0]->host;
        $subject = $header->subject ?? '(No Subject)';
        $date = $header->date;

        // Basic body decoding
        $body = trim(substr($body, 0, 500)); // Limit body size

        $emails[] = [
            'id' => $i,
            'from' => $from,
            'subject' => $subject,
            'date' => $date,
            'body_preview' => $body
        ];
    }

    imap_close($mbox);
    return ['ok' => true, 'emails' => $emails];
}
