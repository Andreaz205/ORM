<?php

use FpDbTest\Database;
use FpDbTest\DatabaseTest;

spl_autoload_register(function ($class) {
    $a = array_slice(explode('\\', $class), 1);
    if (!$a) {
        throw new Exception();
    }
    $filename = implode('/', [__DIR__, ...$a]) . '.php';
    require_once $filename;
});

if (!function_exists('strpos_arr')) {
    function strpos_arr($haystack, $needle, $threshold = -1) {
        if(!is_array($needle)) $needle = array($needle);

        usort($needle, fn ($a,$b) => strlen($a) - strlen($b));

        $positionsData = [];

        foreach($needle as $what) {
            if(($pos = strpos($haystack, $what))!==false) {
                if ($pos > $threshold) {
                    $positionsData[$pos] = $what;
                }
            }
        }

        if (count($positionsData)) {
            $positions = array_keys($positionsData);

            $resultNeedle = $positionsData[$positions[0]];
            $resultPosition = $positions[0];

            foreach ($positions as $position) {
                if ($position <= $resultPosition) {
                    $resultPosition = $position;
                    $resultNeedle = $positionsData[$position];
                }
            }

            return [$resultPosition, $resultNeedle];
        }

        return [];
    }
}

$mysqli = @new mysqli('localhost', 'root', 'password', 'database', 3306);
if ($mysqli->connect_errno) {
    throw new Exception($mysqli->connect_error);
}

$db = new Database($mysqli);
$test = new DatabaseTest($db);
$test->testBuildQuery();

exit('OK');
