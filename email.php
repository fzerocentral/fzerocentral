<?php

require_once 'config.php';
require_once 'common.php';
require_once 'logging.php';
use \Mailjet\Resources;


function send_email($recipient_addresses, $template, $subject, $params) {
  global $config, $twig;

  $plaintext_body = $twig->load("$template.txt")->render($params);
  $html_body = $twig->load("$template.html")->render($params);

  if ($config['app']['debug']) {
    // Write emails to a text file instead of sending
    $recipients_str = implode(', ', $recipient_addresses);
    log_entry(
      'debug_emails.log',
      "{$recipients_str}\n-----\n{$subject}"
      . "\n-----\n{$html_body}\n-----\n{$plaintext_body}\n\n");
    return true;
  }

  // https://github.com/mailjet/mailjet-apiv3-php?tab=readme-ov-file#client--call-configuration-specifics
  $mj = new \Mailjet\Client(
    $config['email']['mj_api_public_key'],
    $config['email']['mj_api_private_key'],
    true,
    ['version' => 'v3.1'],
  );

  // https://dev.mailjet.com/email/guides/getting-started/#send-your-first-email
  // https://dev.mailjet.com/email/reference/send-emails/
  $recipients = array_map(
    function($addr) {
      return ['Email' => $addr];
    },
    $recipient_addresses,
  );
  $api_request_body = [
    'Messages' => [
      [
        'From' => [
          'Email' => $config['email']['sender_address'],
          'Name' => "F-Zero Central"
        ],
        'To' => $recipients,
        'Subject' => $subject,
        'TextPart' => $plaintext_body,
        'HTMLPart' => $html_body,
      ]
    ]
  ];

  // Send
  $response = $mj->post(Resources::$Email, ['body' => $api_request_body]);

  if (!$response->success()) {
    $data = $response->getData();
    $error_code = $data['Messages'][0]['Errors'][0]['ErrorCode'];

    // Error codes at the bottom here:
    // https://dev.mailjet.com/email/guides/send-api-v31/
    die(
      "There was a problem with sending the email: Error code " . $error_code);
  }
}
