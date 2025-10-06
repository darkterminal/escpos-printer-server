<?php
declare(strict_types=1);

/**
 * Convert argv array to associative array
 *
 * @param array $args argv array
 * @return array associative array
 */
function argv2assoc(array $args): array
{
    $result = [];
    $count = count($args);
    if ($count === 0) {
        return $result;
    }

    $first = $args[0];
    $index = 0;
    if (substr($first, 0, 1) !== '-') {
        $result[$first] = null;
        $index = 1;
    }

    while ($index < $count) {
        $arg = $args[$index];
        if (substr($arg, 0, 1) === '-') {
            $key = $arg;
            $value = null;
            if (isset($args[$index + 1]) && substr($args[$index + 1], 0, 1) !== '-') {
                $value = $args[$index + 1];
                $index += 2;
            } else {
                $index += 1;
            }
            $result[$key] = $value;
        } else {
            $result[$arg] = null;
            $index += 1;
        }
    }

    return $result;
}
