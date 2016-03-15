<?php

namespace Pushpad;

class Pushpad {
  public static $auth_token;
  public static $project_id;

  public static function signature_for($data) {
    if (!isset(self::$auth_token)) throw new \Exception('You must set Pushpad\Pushpad::$auth_token');
    return hash_hmac('sha1', $data, self::$auth_token);
  }

  public static function path($options = array()) {
    $project_id = isset($options['project_id']) ? $options['project_id'] : self::$project_id;
    if (!isset($project_id)) throw new \Exception('You must set Pushpad\Pushpad::$project_id');
    return "https://pushpad.xyz/projects/$project_id/subscription/edit";
  }

  public static function path_for($uid, $options = array()) {
    $uid_signature = self::signature_for($uid);
    return self::path($options) . "?uid=$uid&uid_signature=$uid_signature";
  }
}
