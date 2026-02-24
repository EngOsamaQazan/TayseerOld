<?php

namespace backend\helpers;

require_once dirname(__DIR__, 2) . '/arabic-name/src/Data/Prefixes.php';
require_once dirname(__DIR__, 2) . '/arabic-name/src/Data/Suffixes.php';
require_once dirname(__DIR__, 2) . '/arabic-name/src/Normalizer.php';
require_once dirname(__DIR__, 2) . '/arabic-name/src/CompoundDetector.php';
require_once dirname(__DIR__, 2) . '/arabic-name/src/Shortener.php';

use OsamaQazan\ArabicName\Shortener;

/**
 * Arabic name helper — delegates to osamaqazan/arabic-name library.
 */
class NameHelper
{
    /**
     * Shorten a full Arabic name to first + last logical name.
     * Handles compound prefixes (عبد، أبو) and suffixes (الدين، الله، الإسلام).
     */
    public static function short(string $full): string
    {
        return Shortener::shorten($full);
    }
}
