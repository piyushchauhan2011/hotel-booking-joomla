<?php

namespace Learn\Component\Hotelbooking\Site\Helper;

\defined('_JEXEC') or die;

/**
 * Decodes the JSON a repeatable `subform` field stores, e.g.
 * {"gallery0":{"gallery_item":{"image":"...","caption":"..."}}}
 * into a flat list of row arrays: [['image' => '...', 'caption' => '...']].
 */
class SubformHelper
{
    public static function decodeRows(?string $json, string $itemKey): array
    {
        if (!$json) {
            return [];
        }

        $decoded = json_decode($json, true);

        if (!\is_array($decoded)) {
            return [];
        }

        $rows = [];

        foreach ($decoded as $row) {
            if (\is_array($row) && isset($row[$itemKey]) && \is_array($row[$itemKey])) {
                $rows[] = $row[$itemKey];
            }
        }

        return $rows;
    }
}
