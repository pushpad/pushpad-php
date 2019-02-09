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
  public $image_url;
  public $ttl;
  public $require_interaction;
  public $urgent;
  public $custom_data;
  public $custom_metrics;
  public $actions;
  public $starred;
  public $send_at;

  function __construct($options = array()) {
    $this->body = $options['body'];
    if (isset($options['title'])) $this->title = $options['title'];
    if (isset($options['target_url'])) $this->target_url = $options['target_url'];
    if (isset($options['icon_url'])) $this->icon_url = $options['icon_url'];
    if (isset($options['image_url'])) $this->image_url = $options['image_url'];
    if (isset($options['ttl'])) $this->ttl = $options['ttl'];
    if (isset($options['require_interaction'])) $this->require_interaction = $options['require_interaction'];
    if (isset($options['urgent'])) $this->urgent = $options['urgent'];
    if (isset($options['custom_data'])) $this->custom_data = $options['custom_data'];
    if (isset($options['custom_metrics'])) $this->custom_metrics = $options['custom_metrics'];
    if (isset($options['actions'])) $this->actions = $options['actions'];
    if (isset($options['starred'])) $this->starred = $options['starred'];
    if (isset($options['send_at'])) $this->send_at = $options['send_at'];
  }

  public function broadcast($options = array()) {
    return $this->deliver($this->req_body(null, isset($options['tags']) ? $options['tags'] : null), $options);
  }

  public function deliver_to($uids, $options = array()) {
    if (!isset($uids)) {
      $uids = array(); // prevent broadcasting
    }
    return $this->deliver($this->req_body($uids, isset($options['tags']) ? $options['tags'] : null), $options);
  }

  private function deliver($req_body, $options = array()) {
    $project_id = isset($options['project_id']) ? $options['project_id'] : Pushpad::$project_id;
    if (!isset($project_id)) throw new \Exception('You must set Pushpad\Pushpad::$project_id');
    $endpoint = "https://pushpad.xyz/api/v1/projects/$project_id/notifications";
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
        'body' => $this->body
      )
    );
    if (isset($this->title)) $body['notification']['title'] = $this->title;
    if (isset($this->target_url)) $body['notification']['target_url'] = $this->target_url;
    if (isset($this->icon_url)) $body['notification']['icon_url'] = $this->icon_url;
    if (isset($this->image_url)) $body['notification']['image_url'] = $this->image_url;
    if (isset($this->ttl)) $body['notification']['ttl'] = $this->ttl;
    if (isset($this->require_interaction)) $body['notification']['require_interaction'] = $this->require_interaction;
    if (isset($this->urgent)) $body['notification']['urgent'] = $this->urgent;
    if (isset($this->custom_data)) $body['notification']['custom_data'] = $this->custom_data;
    if (isset($this->custom_metrics)) $body['notification']['custom_metrics'] = $this->custom_metrics;
    if (isset($this->actions)) $body['notification']['actions'] = $this->actions;
    if (isset($this->starred)) $body['notification']['starred'] = $this->starred;
    if (isset($this->send_at)) $body['notification']['send_at'] = gmstrftime('%Y-%m-%dT%H:%M', $this->send_at);

    if (isset($uids)) $body['uids'] = $uids;
    if (isset($tags)) $body['tags'] = $tags;
    $json = json_encode($body);
    if ($json == false)
      throw new \Exception('An error occurred while encoding the following request into JSON: ' . var_export($body, true));
    return $json;
  }
}
