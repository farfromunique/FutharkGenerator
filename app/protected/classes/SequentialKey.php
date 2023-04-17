<?php

namespace ACWPD\Helpers;

class SequentialKey {

  private static $alphabet = '-0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';
  public static function generate(): string {
    $alphabet = self::$alphabet;
    $d = new \DateTimeImmutable();
    $now = $d->format('U') . $d->format('v');

    $timeStampChars = array(0, 1, 2, 3, 4, 5, 6, 7);
    $lastRandChars = [];
    for ($i = 7; $i >= 0; $i--) {
      $timeStampChars[$i] = \substr($alphabet, $now % 64, 1);
      $now = \floor($now / 64);
    }
    $id = \implode('', $timeStampChars);


    for ($i = 0; $i < 12; $i++) {
      $lastRandChars[$i] = \floor(\rand(0, 63));
    }

    for ($i = 0; $i < 12; $i++) {
      $id .= \substr($alphabet, $lastRandChars[$i], 1);
    }

    return $id;
  }
}
