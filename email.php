<?php

require_once 'config.php';
require_once 'common.php';
require_once 'logging.php';
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;


function send_email($recipients, $template, $subject, $params) {
  global $config, $twig;

  $sender_email = "F-Zero Central <" . $config['email']['sender_address'] . ">";

  $plaintext_body = $twig->load("$template.txt")->render($params);
  $html_body = $twig->load("$template.html")->render($params);

  if ($config['app']['debug']) {
    // Write emails to a text file instead of sending
    $recipients_str = implode(', ', $recipients);
    log_entry(
      'debug_emails.log',
      "{$recipients_str}\n-----\n{$subject}"
      . "\n-----\n{$html_body}\n-----\n{$plaintext_body}\n\n");
    return true;
  }

  $ses = new SesClient([
    'version' => '2010-12-01',
    'region'  => $config['email']['aws_region'],
    'credentials' => [
      'key' => $config['email']['aws_access_key_id'],
      'secret' => $config['email']['aws_secret_access_key'],
    ],
  ]);

  try {
    $result = $ses->sendEmail([
      'Destination' => ['ToAddresses' => $recipients],
      'ReplyToAddresses' => [$sender_email],
      'Source' => $sender_email,
      'Message' => [
        'Body' => [
          'Html' => [
            'Charset' => 'UTF-8',
            'Data' => $html_body,
          ],
          'Text' => [
            'Charset' => 'UTF-8',
            'Data' => $plaintext_body,
          ],
        ],
        'Subject' => [
          'Charset' => 'UTF-8',
          'Data' => $subject,
        ],
      ],
    ]);
    return true;
  } catch (AwsException $e) {
    return $e->getAwsErrorMessage();
  }
}
