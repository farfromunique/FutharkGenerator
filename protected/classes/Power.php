<?php

namespace ACWPD\Futhark;

use Psr\Container\ContainerExceptionInterface;
use Exception;
use \Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Power {
  protected ContainerInterface $container;
  const validSelectors = ['type', 'flavor', 'twist'];
  protected array $type = [];
  protected array $flavor = [];
  protected array $twist = [];
  protected string $type_notes = '';
  protected string $flavor_notes = '';
  protected string $twist_notes = '';
  private array $powersDB;

  /* Setup methods */
  /**
   * @param ContainerInterface $container 
   * @return void 
   * @throws NotFoundExceptionInterface 
   * @throws ContainerExceptionInterface 
   * @throws Exception 
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
    $this->loadPowersDB();
  }

  /**
   * @return void 
   * @throws NotFoundExceptionInterface 
   * @throws ContainerExceptionInterface 
   * @throws Exception 
   */
  public function loadPowersDB(): void {
    $location = $this->container->get('settings')['powersDB']['location'];
    if (\file_exists($location)) {
      $this->powersDB = \json_decode(\file_get_contents($location), true);
    } else {
      throw new \Exception('Powers DB Not found!', 1);
    }
  }

  /* Functional methods */
  /**
   * @param string $twist 
   * @return Power 
   * @throws Exception 
   */
  public function withTwist(String $twist = 'random'): self {
    if ($twist == 'random') {
      $min = 0;
      $max = \count($this->powersDB['twist']) - 1;
      $rand = \mt_rand($min, $max);
      $this->twist[] = \array_slice($this->powersDB['twist'], $rand, 1)[0];
    } else {
      $out = $this->powersDB['twist'][$twist] ?? $this->getArrayKeyFromField($twist, $this->powersDB['twist'], 'short');
      if (\is_null($out)) {
        throw new \Exception("Can't find twist ($twist)", 1);
      }
      $this->twist[] = $out;
    }
    return $this;
  }

  /**
   * @param string $flavor 
   * @return Power 
   * @throws Exception 
   */
  public function withFlavor(String $flavor = 'random'): self {
    if ($flavor == 'random') {
      $min = 0;
      $max = \count($this->powersDB['flavor']) - 1;
      $rand = \mt_rand($min, $max);
      $this->flavor[] = \array_slice($this->powersDB['flavor'], $rand, 1)[0];
    } else {
      $out = $this->powersDB['flavor'][$flavor] ?? $this->getArrayKeyFromField($flavor, $this->powersDB['flavor'], 'short');
      if (\is_null($out)) {
        throw new \Exception("Can't find flavor ($flavor)", 1);
      }
      $this->flavor[] = $out;
    }

    return $this;
  }

  /**
   * @param string $type 
   * @return Power 
   * @throws Exception 
   */
  public function withType(String $type = 'random'): self {
    if ($type == 'random') {
      $min = 0;
      $max = \count($this->powersDB['type']) - 1;
      $rand = \mt_rand($min, $max);
      $this->type[] = \array_slice($this->powersDB['type'], $rand, 1)[0];
    } else {
      $out = $this->powersDB['type'][$type] ?? $this->getArrayKeyFromField($type, $this->powersDB['type'], 'short');
      if (\is_null($out)) {
        throw new \Exception("Can't find type ($type)", 1);
      }
      $this->type[] = $out;
    }
    return $this;
  }

  /**
   * @param string $selector 
   * @param string $notes 
   * @return Power 
   * @throws Exception 
   */
  public function withNotes(string $selector, String $notes = ''): self {
    switch ($selector) {
      case 'type':
        $this->type_notes = $notes;
        break;

      case 'flavor':
        $this->flavor_notes = $notes;
        break;

      case 'twist':
        $this->twist_notes = $notes;
        break;

      default:
        throw new \Exception('Invalid selector: ' . $selector . ' (must be one of' . \implode('\', \'', self::validSelectors) .  '\').', 1);
        break;
    }

    return $this;
  }

  /* Syntactical Sugar */

  /**
   * @return Power 
   * @throws Exception 
   */
  public function withRandomTwist(): self {
    return $this->withTwist('random');
  }

  /**
   * @return Power 
   * @throws Exception 
   */
  public function withRandomFlavor(): self {
    return $this->withFlavor('random');
  }

  /**
   * @return Power 
   * @throws Exception 
   */
  public function withRandomType(): self {
    return $this->withType('random');
  }

  /**
   * @param string $selector 
   * @param string $choice 
   * @return Power 
   * @throws Exception 
   */
  public function with(String $selector, String $choice = 'random'): self {
    switch ($selector) {
      case 'type':
        return $this->withType($choice);
        break;

      case 'flavor':
        return $this->withFlavor($choice);
        break;

      case 'twist':
        return $this->withTwist($choice);
        break;

      default:
        throw new \Exception('Invalid selector: ' . $selector . ' (must be one of' . \implode('\', \'', self::validSelectors) .  '\').', 1);
        break;
    }
  }

  /* Return functions */

  /** @return array  */
  public function getPowerData(): array {
    return [
      'type' => $this->type,
      'flavor' => $this->flavor,
      'twist' => $this->twist,
      'notes' => [
        'type' => $this->type_notes,
        'flavor' => $this->flavor_notes,
        'twist' => $this->twist_notes
      ]
    ];
  }

  /** @return string  */
  public function getPowerString(): string {
    return 'Type: ' . ($this->type[0]['name'] ?? 'none')
      . '; Flavor: ' . ($this->flavor[0]['name'] ?? 'none')
      . '; Twist: ' . ($this->twist[0]['cardname'] ?? 'none');
  }

  /** @return Power  */
  public function withAllData(): self {
    $this->type = $this->powersDB['type'];
    $this->flavor = $this->powersDB['flavor'];
    $this->twist = $this->powersDB['twist'];
    return $this;
  }

  public function getJSONforLocalStorage(): string {
    $out = [];
    if (is_array($this->type)) {
      foreach ($this->type as $i => $type) {
        $out['power_type_' . $i] = $type['short'];
      }
    }

    if (is_array($this->flavor)) {
      foreach ($this->flavor as $i => $flavor) {
        $out['power_flavor_' . $i] = $flavor['short'];
      }
    }

    if (is_array($this->twist)) {
      foreach ($this->twist as $i => $twist) {
        $out['power_twist_' . $i] = $twist['short'];
      }
    }

    return json_encode($out);
  }

  public function getDataForLocalStorage(): array {
    $out = [];

    foreach ($this->type as $i => $type) {
      $out['power_type_' . $i] = $type['short'];
    }

    foreach ($this->flavor as $i => $flavor) {
      $out['power_flavor_' . $i] = $flavor['short'];
    }

    foreach ($this->twist as $i => $twist) {
      $out['power_twist_' . $i] = $twist['short'];
    }

    $out['type_notes'] = $this->type_notes;
    $out['flavor_notes'] = $this->flavor_notes;
    $out['twist_notes'] = $this->twist_notes;

    return ['localVars' => $out];
  }

  /* Magic */

  public static function getArrayKeyFromField(string $needle, array $haystack, string $keyField) {
    $out = \array_reduce($haystack, function ($carry, $item) use ($keyField, $needle) {
      if ($item[$keyField] == $needle) {
        $carry = $item;
      }
      return $carry;
    });

    return $out;
  }
}
