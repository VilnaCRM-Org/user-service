<?php

declare(strict_types=1);

namespace App\Shared\Application;

final class TempBadCode
{
    public function highComplexityMethod(int $a, int $b, int $c, int $d): int
    {
        if ($a > 0) {
            if ($b > 0) {
                if ($c > 0) {
                    if ($d > 0) {
                        return 1;
                    } elseif ($d < 0) {
                        return 2;
                    } else {
                        return 3;
                    }
                } elseif ($c < 0) {
                    if ($d > 0) {
                        return 4;
                    } elseif ($d < 0) {
                        return 5;
                    } else {
                        return 6;
                    }
                } else {
                    return 7;
                }
            } elseif ($b < 0) {
                if ($c > 0) {
                    return 8;
                } elseif ($c < 0) {
                    return 9;
                } else {
                    return 10;
                }
            } else {
                return 11;
            }
        } elseif ($a < 0) {
            if ($b > 0) {
                if ($c > 0) {
                    return 12;
                } elseif ($c < 0) {
                    return 13;
                } else {
                    return 14;
                }
            } elseif ($b < 0) {
                if ($c > 0) {
                    return 15;
                } elseif ($c < 0) {
                    return 16;
                } else {
                    return 17;
                }
            } else {
                return 18;
            }
        } else {
            return 0;
        }
    }
}
