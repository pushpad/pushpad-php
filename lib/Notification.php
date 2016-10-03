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
  public $icon_url;
  public $ttl;

  function __construct($options = array()) {
    if (isset($options['body'])) $this->body = $options['body'];
    if (isset($options['title'])) $this->title = $options['title'];
    if (isset($options['target_url'])) $this->target_url = $options['target_url'];
    if (isset($options['icon_url'])) $this->icon_url = $options['icon_url'];
    if (isset($options['ttl'])) $this->ttl = $options['ttl'];
  }

  public function broadcast($options = array()) {
    return $this->deliver($this->req_body(null, $options['tags']), $options);
  }

  public function deliver_to($uids, $options = array()) {
    if (!isset($uids)) {
      $uids = array(); // prevent broadcasting
    }
    return $this->deliver($this->req_body($uids, $options['tags']), $options);
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
    if ($res === false) throw new NotificationDeliveryError("cURL request error: " . curl_error($req));
    $status_code = curl_getinfo($req, CURLINFO_HTTP_CODE);
    curl_close($req);
    if ($status_code != '201') throw new NotificationDeliveryError("Response $status_code: $res");
    return json_decode($res, true);
  }

  private function req_headers() {
    if (!isset(Pushpad::$auth_token)) throw new \Exception('You must set Pushpad\Pushpad::$auth_token');
    return array(
      'Authorization: Token token="' . Pushpad::$auth_token . '"',
      'Content-Type: application/json;charset=UTF-8',
      'Accept: application/json'
    );
  }

  private function req_body($uids = null, $tags = null) {
    $body = array(
      'notification' => array(
        'body' => $this->body,
        'title' => $this->title,
        'target_url' => $this->target_url,
        'icon_url' => $this->icon_url,
        'ttl' => $this->ttl
      )
    );
    if (isset($uids)) $body['uids'] = $uids;
    if (isset($tags)) $body['tags'] = $tags;
    $json = json_encode($body);
    if ($json == false)
      throw new \Exception('An error occurred while encoding the following request into JSON: ' . var_export($body, true));
    return $json;
  }
}
