<?php
// src/Util/Dot.php
namespace Gis14\Layers\Util;

final class Dot
{
    /** @return mixed|null */
    public static function get(array $data, string $path)
    {
        $cur = $data;
        foreach (explode('.', $path) as $seg) {
            if ($seg === '') continue;
            if (!is_array($cur) || !array_key_exists($seg, $cur)) return null;
            $cur = $cur[$seg];
        }
        return $cur;
    }

    /** @param mixed $value */
    public static function set(array &$data, string $path, $value): void
    {
        $ref =& $data;
        $parts = explode('.', $path);
        foreach ($parts as $i => $seg) {
            if ($i === count($parts) - 1) {
                $ref[$seg] = $value; return;
            }
            if (!isset($ref[$seg]) || !is_array($ref[$seg])) $ref[$seg] = [];
            $ref =& $ref[$seg];
        }
    }
}