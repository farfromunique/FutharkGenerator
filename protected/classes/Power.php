<?php

namespace ACWPD\Futhark;

use ErrorException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Aws\DynamoDb\Marshaler;

class Power {
  protected ContainerInterface $container;
  protected const validSelectors = ['type', 'flavor', 'twist'];
  private array $powersDB;
  private array $power_data = [];
  private array $power_json_data = ["save_name" => "", "notes" => ["flavor" => "", "type" => "", "twist" => ""], "power" => ["flavor" => [], "type" => [], "twist" => []]];

  public function __construct(ContainerInterface $container) {
    $this->container = $container;
    $this->loadPowersDB();
  }

  public function withName(string $name): self {
    $this->power_data['save_name'] = $name;
    return $this;
  }

  private function loadPowersDB(): void {
    $location = $this->container->get('settings')['powersDB']['location'];
    if (\file_exists($location)) {
      $this->powersDB = \json_decode(\file_get_contents($location), true);
    } else {
      throw new ErrorException('Powers DB Not found!', 1);
    }
  }

  public function with(String $selector, String $choice = 'random'): self {
    if (!\in_array($selector, self::validSelectors)) {
      throw new \Exception("Invalid selector: $selector (must be one of" . \implode('\', \'', self::validSelectors) .  '\').', 1);
    }
    if ($choice == 'random') {
      $min = 0;
      $max = \count($this->powersDB[$selector]) - 1;
      $rand = \mt_rand($min, $max);
      $result = \array_slice($this->powersDB[$selector], $rand, 1)[0];
    } else {
      $out = $this->powersDB[$selector][$choice] ?? $this->getArrayKeyFromField($choice, $this->powersDB[$selector], 'short');
      if (\is_null($out)) {
        throw new \Exception("Can't find $selector ($choice)", 1);
      }
      $result = $out;
    }
    $this->power_data[$selector][] = $result;
    return $this;
  }

  /** @return Power  */
  public function withAllData(): self {
    for ($sel = 0; $sel < count(self::validSelectors); $sel++) {
      $selector = self::validSelectors[$sel];
      $this->power_data[$selector] = $this->powersDB[$selector];
    }
    return $this;
  }

  public function withNotes(string $selector, String $notes = ''): self {
    if (!\in_array($selector, self::validSelectors)) {
      throw new \Exception("Invalid selector: $selector (must be one of" . \implode('\', \'', self::validSelectors) .  '\').', 1);
    }

    $this->power_data['notes'][$selector] = $notes;

    return $this;
  }

  /* Syntactical Sugar */

  public function withTwist(String $twist = 'random'): self {
    return $this->with('twist', $twist);
  }

  public function withFlavor(String $flavor = 'random'): self {
    return $this->with('flavor', $flavor);
  }

  public function withType(String $type = 'random'): self {
    return $this->with('type', $type);
  }

  public function withRandomTwist(): self {
    return $this->withTwist('random');
  }

  public function withRandomFlavor(): self {
    return $this->withFlavor('random');
  }

  public function withRandomType(): self {
    return $this->withType('random');
  }

  /* Return functions */

  public function getPowerData(): array {
    return $this->power_data;
  }

  /** @return string  */
  public function getPowerForLog(): string {
    $text = 'save_name: ' . ($this->power_data['save_name'] ?? '<not set>') . '; ';
    for ($sel = 0; $sel < count(self::validSelectors); $sel++) {
      $selector = self::validSelectors[$sel];
      $$selector = '';
      for ($i = 0; $i < count($this->power_data[$selector]); $i++) {
        $$selector .= $this->power_data[$selector][$i]['short'];
      }
      $text .= $selector . '(s): ' . $$selector . '; ';
    }
    return $text;
  }

  /** @return string */
  public function getJSONforLocalStorage(): string {
    return json_encode($this->getDataForLocalStorage()['localVars']);
  }

  public function getDataForLocalStorage(): array {
    /**
     * Flattens $power_data into a localStorage-compatible version
     */
    $ls = [
      'save_name' => $this->power_data['save_name'] ?? '',
      'notes.flavor' => $this->power_data['notes']['flavor'] ?? '',
      'notes.type' => $this->power_data['notes']['type'] ?? '',
      'notes.twist' => $this->power_data['notes']['twist'] ?? ''
    ];
    foreach ($this->power_data['type'] as $key => $value) {
      $ls['power.type.' . $key] = $value['short'];
    }
    foreach ($this->power_data['flavor'] as $key => $value) {
      $ls['power.flavor.' . $key] = $value['short'];
    }
    foreach ($this->power_data['twist'] as $key => $value) {
      $ls['power.twist.' . $key] = $value['short'];
    }

    return ['localVars' => $ls];
  }

  public function getDataForDynamoDB(): array {
    $out = [
      'save_name' => [
        'S' => $this->power_data['save_name']
      ],
      'notes' => [
        'M' => [
          'type' => [
            'S' => $this->power_data['notes']['type']
          ],
          'flavor' => [
            'S' => $this->power_data['notes']['flavor']
          ],
          'twist' => [
            'S' => $this->power_data['notes']['twist']
          ]
        ]
      ],
      'power' => [
        'M' => [
          'type' => [
            'L' => []
          ],
          'flavor' => [
            'L' => []
          ],
          'twist' => [
            'L' => []
          ]
        ]
      ]
    ];
    foreach (self::validSelectors as $sel) {
      for ($i = 0; $i < count($this->power_data[$sel]); $i++) {
        $out['power']['M'][$sel]['L'][] = ['S' => $this->power_data[$sel][$i]['short']];
      }
    }
    $this->container->get('logger')->debug('Formatted Data:', $out);
    return $out;
  }

  public function fromInputArray(array $sentData): self {
    $this
      ->withNotes('type', $sentData['notes_type'] ?? '')
      ->withNotes('flavor', $sentData['notes_flavor'] ?? '')
      ->withNotes('twist', $sentData['notes_twist'] ?? '');

    foreach ($sentData as $key => $value) {
      if (\str_starts_with($key, 'power_')) {
        $exploded = \explode('_', $key);
        $this->with($exploded[1], $value);
      }
    }
    return $this;
  }

  /**
   * getArrayKeyFromField:
   * I don't recall how this works, or (really) what it does.
   * It's some sort of fast lookup that I copied from (probably) StackOverflow several years ago.
   * 
   * @param string $needle 
   * @param array $haystack 
   * @param string $keyField 
   * @return mixed 
   */
  private static function getArrayKeyFromField(string $needle, array $haystack, string $keyField) {
    $out = \array_reduce($haystack, function ($carry, $item) use ($keyField, $needle) {
      if ($item[$keyField] == $needle) {
        $carry = $item;
      }
      return $carry;
    });

    return $out;
  }
}
