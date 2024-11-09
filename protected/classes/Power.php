<?php

namespace ACWPD\Futhark;

class Power
{
    protected $container;
    private $descriptors;
    const validSelectors = ['type', 'flavor', 'twist'];
    protected $type;
    protected $flavor;
    protected $twist;
    private $powersDB;

    /* Setup methods */
    public function __construct(\Psr\Container\ContainerInterface $container)
    {
        $this->container = $container;
        $this->loadPowersDB();
    }

    public function loadPowersDB(): void
    {
        $location = $this->container->get('settings')['powersDB']['location'];
        if (\file_exists($location)) {
            $this->powersDB = \json_decode(\file_get_contents($location), true);
        } else {
            throw new \Exception('Powers DB Not found!', 1);
        }
    }

    /* Functional methods */
    public function withTwist(String $twist = 'random'): self
    {
        if ($twist == 'random') {
            $min = 0;
            $max = \count($this->powersDB['twist']) - 1;
            $rand = \mt_rand($min, $max);
            $this->twist[] = \array_slice($this->powersDB['twist'], $rand, 1)[0];
        } else {
            $out = $this->powersDB['twist'][$twist] ?? $this->getArrayKeyFromField($twist, $this->powersDB['twist'], 'short');
            if (\is_null($out)) {
                throw new \Exception("Can't find twist $twist", 1);
            }
            $this->twist[] = $out;
        }
        return $this;
    }

    public function withFlavor(String $flavor = 'random'): self
    {
        if ($flavor == 'random') {
            $min = 0;
            $max = \count($this->powersDB['flavor']) - 1;
            $rand = \mt_rand($min, $max);
            $this->flavor[] = \array_slice($this->powersDB['flavor'], $rand, 1)[0];
        } else {
            $out = $this->powersDB['flavor'][$flavor] ?? $this->getArrayKeyFromField($flavor, $this->powersDB['flavor'], 'short');
            if (\is_null($out)) {
                throw new \Exception("Can't find flavor $flavor", 1);
            }
            $this->flavor[] = $out;
        }

        return $this;
    }

    public function withType(String $type = 'random'): self
    {
        if ($type == 'random') {
            $min = 0;
            $max = \count($this->powersDB['type']) - 1;
            $rand = \mt_rand($min, $max);
            $this->type[] = \array_slice($this->powersDB['type'], $rand, 1)[0];
        } else {
            $out = $this->powersDB['type'][$type] ?? $this->getArrayKeyFromField($type, $this->powersDB['type'], 'short');
            if (\is_null($out)) {
                throw new \Exception("Can't find type $type", 1);
            }
            $this->type[] = $out;
        }
        return $this;
    }

    /* Syntactical Sugar */

    public function withRandomTwist(): self
    {
        return $this->withTwist('random');
    }

    public function withRandomFlavor(): self
    {
        return $this->withFlavor('random');
    }

    public function withRandomType(): self
    {
        return $this->withType('random');
    }

    public function with(String $selector, String $choice = 'random'): self
    {
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

    public function getPowerData(): array
    {
        return [
            'type' => $this->type,
            'flavor' => $this->flavor,
            'twist' => $this->twist
        ];
    }

    public function getPowerString(): string
    {
        return 'Type: ' . ($this->type[0]['name'] ?? 'none')
            . '; Flavor: ' . ($this->flavor[0]['name'] ?? 'none')
            . '; Twist: ' . ($this->twist[0]['cardname'] ?? 'none');
    }

    public function withAllData(): self
    {
        $this->type = $this->powersDB['type'];
        $this->flavor = $this->powersDB['flavor'];
        $this->twist = $this->powersDB['twist'];
        return $this;
    }

    public function getJSONforLocalStorage(): string
    {
        $out = [];
        if (is_array($this->type)) {
            foreach ($this->type as $i => $type) {
                $out['power_type_' . time()] = $type['short'];
            }
        }

        if (is_array($this->flavor)) {
            foreach ($this->flavor as $i => $flavor) {
                $out['power_flavor_' . time()] = $flavor['short'];
            }
        }

        if (is_array($this->twist)) {
            foreach ($this->twist as $i => $twist) {
                $out['power_twist_' . time()] = $twist['short'];
            }
        }

        return json_encode($out);
    }

    public function getDataForLocalStorage(): array
    {
        $out = [];
        if (is_array($this->type)) {
            foreach ($this->type as $i => $type) {
                $out['power_type_' . time()] = $type['short'];
            }
        }

        if (is_array($this->flavor)) {
            foreach ($this->flavor as $i => $flavor) {
                $out['power_flavor_' . time()] = $flavor['short'];
            }
        }

        if (is_array($this->twist)) {
            foreach ($this->twist as $i => $twist) {
                $out['power_twist_' . time()] = $twist['short'];
            }
        }

        return $out;
    }

    /* Magic */

    public static function getArrayKeyFromField(string $needle, array $haystack, string $keyField)
    {
        $out = \array_reduce($haystack, function ($carry, $item) use ($keyField, $needle) {
            if ($item[$keyField] == $needle) {
                $carry = $item;
            }
            return $carry;
        });

        return $out;
    }
}
