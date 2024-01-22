<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    private array $specifiers;

    protected array $defaultTypes = [
        'string',
        'int',
        'NULL',
        'double',
        'boolean'
    ];

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;

        $this->specifiers = [
            SpecifiersEnum::INT->value,
            SpecifiersEnum::FLOAT->value,
            SpecifiersEnum::MIXED->value,
            SpecifiersEnum::ARR->value,
            SpecifiersEnum::ID->value,
        ];
    }

    /**
     * @throws Exception
     */
    public function buildQuery(string $query, array $args = []): string
    {
        $foundPosition = -1;
        $step = 0;

        $positionAndNeedle = strpos_arr($query, $this->specifiers, $foundPosition);

        while(count($positionAndNeedle)) {

            $position = $positionAndNeedle[0];

            $foundPosition = $position;

            $specifier = $positionAndNeedle[1];

            $replaceBindingValue = $this->getReplaceValueString($args[$step], $specifier);

            if ($block = $this->checkIsCondition($position, $query)) {

                $replacePosition = strpos($query, $block);

                if ($replaceBindingValue === 'skip') {
                    $query = substr_replace($query, '', $replacePosition, strlen($block));
                } else {
                    $replace = substr($block, 1, strlen($block) - 2);

                    $query = substr_replace($query, $replace, $replacePosition, strlen($block));
                }

                $position--;
                $foundPosition--;
            }

            if ($replaceBindingValue !== 'skip') {
                $query = substr_replace($query, $replaceBindingValue, $position, strlen($specifier));
            }

            $positionAndNeedle = strpos_arr($query, $this->specifiers, $foundPosition);

            $step++;
        }

        return $query;
    }

    public function skip(): string
    {
        return 'skip';
    }

    private function checkIsCondition(int $position, $query): bool|string
    {
        preg_match_all('/\{(.*?)\}/', $query, $matches);

        $matches = $matches[0];

        if (count($matches)) {
            foreach ($matches as $match) {
                $existsPosition = strpos($query, $match);

                $existsPositionEnd = $query[$existsPosition + strlen($match) - 1];

                if($position > $existsPosition && $position < $existsPositionEnd)
                    return $match;
            }
        }

        return false;
    }

    /**
     * @throws Exception
     */
    private function getReplaceValueString(mixed $value, string $specifier): mixed
    {
        if ($value === 'skip') {
            return 'skip';
        }

        switch($specifier) {
            case SpecifiersEnum::MIXED->value:
                $this->checkIsValueInTypes($this->defaultTypes, $value);

                if (gettype($value) === 'boolean') {
                    return (int)$value;
                }

                if (gettype($value) === 'NULL') {
                    return "NULL";
                }

                if (gettype($value) === 'string') {
                    return "'$value'";
                }

                return $value;
            case SpecifiersEnum::INT->value: return (int)$value;

            case SpecifiersEnum::FLOAT->value:return (float)$value;

            case SpecifiersEnum::ARR->value:
                $this->checkIsValueInTypes(['array'], $value);

                $result = "";

                if ($this->checkAssoc($value)) {

                    foreach ($value as $name => $keyData) {
                        if ((gettype($keyData) === 'NULL')) {
                            $keyData = 'NULL';
                        } else if (gettype($keyData) === 'string') {
                            $keyData = "'$keyData'";
                        }

                        $result .= "`$name` = $keyData, ";
                    }
                    return substr($result, 0, -2);
                }

                foreach ($value as $val) {
                    if (gettype($val) === 'string') {
                        $result .= "`$val`, ";
                    } else {
                        $result .= "$val, ";
                    }
                }

                return substr($result, 0, -2);
            case SpecifiersEnum::ID->value:
                $this->checkIsValueInTypes(['array', 'integer', 'string'], $value);

                if (gettype($value) === 'array') {
                    $result = "";

                    foreach ($value as $val) {
                        if (gettype($val) === 'string') {
                            $result .= "`$val`, ";
                        } else {
                            $result .= "$val, ";
                        }
                    }

                    return substr($result, 0, -2);
                }

                if (gettype($value) === 'string') {
                    return "`$value`";
                }

                return $value;
            default:
                throw new Exception('Непредвиденный спецификатор');
        }
    }

    /**
     * @throws Exception
     */
    private function checkIsValueInTypes($types, $value): void
    {
        $pass = false;

        foreach ($types as $type) {
            if (gettype($value) === $type) {
                $pass = true;
                break;
            }
        }

        if (!$pass) {
            throw new Exception("Ошибка типов " . implode(',', $value) . " для значения $value");
        }
    }

    private function checkAssoc(array $array): bool
    {
        $assoc = true;

        foreach ($array as $key => $element)
        {
            if (is_numeric($key)) $assoc = false;
            break;
        }

        return $assoc;
    }
}
