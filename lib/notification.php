<?php

namespace Pushpad;

class NotificationDeliveryError extends \Exception {
  function __construct($message) {
    parent::__construct($message);
  }
}

class Notification {
  public $body;
  public $title;
  public $target_url;

  function __construct($options = array()) {
    if (isset($options['body'])) $this->body = $options['body'];
    if (isset($options['title'])) $this->title = $options['title'];
    if (isset($options['target_url'])) $this->target_url = $options['target_url'];
  }

  public function broadcast($options = array()) {
    $this->deliver($this->req_body(), $options);
  }

  public function deliver_to($uids, $options = array()) {
    $this->deliver($this->req_body($uids), $options);
  }

  private function deliver($req_body, $options = array()) {
    $project_id = isset($options['project_id']) ? $options['project_id'] : Pushpad::$project_id;
    if (!isset($project_id)) throw new \Exception('You must set Pushpad\Pushpad::$project_id');
    $endpoint = "https://pushpad.xyz/projects/$project_id/notifications";
    $req = curl_init($endpoint);
    curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($req, CURLOPT_POSTFIELDS, $req_body);
    curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($req, CURLOPT_HTTPHEADER, $this->req_headers());
    $res = curl_exec($req);
    if ($res == false) throw new NotificationDeliveryError("cURL request error");
    $status_code = curl_getinfo($req, CURLINFO_HTTP_CODE);
    curl_close($req);
    list($header, $body) = explode("\r\n\r\n", $res, 2);
    if ($status_code != '201') throw new NotificationDeliveryError("Response $status_code: $body");
    return json_decode($body);
  }

  private function req_headers() {
    if (!isset(Pushpad::$auth_token)) throw new \Exception('You must set Pushpad\Pushpad::$auth_token');
    return array(
      'Authorization: Token token="' . Pushpad::$auth_token . '"',
      'Content-Type: application/json;charset=UTF-8',
      'Accept: application/json'
    );
  }

  private function req_body($uids = null) {
    $body = array(
      'notification' => array(
        'body' => $this->body,
        'title' => $this->title,
        'target_url' => $this->target_url
      )
    );
    if (isset($uids)) $body['uids'] = $uids;
    return json_encode($body);
  }
}
