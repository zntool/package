<?php

namespace ZnTool\Package\Domain\Helpers;

class VersionHelper
{

    public static function possibleVersionList(string $lastVersion = null): array
    {
        if($lastVersion == null) {
            return ['0.0.1'];
        }
        preg_match('/(\d+)\.(\d+)\.(\d+)/i', $lastVersion, $matches);
        $ver = [
            'major' => $matches[1],
            'minor' => $matches[2],
            'patch' => $matches[3],
        ];
        $possible = [];
        $possible['major'] = ($ver['major'] + 1) . '.0.0';
        $possible['minor'] = '0.' . ($ver['minor'] + 1) . '.0';
        $possible['patch'] = '0.0.' . ($ver['patch'] + 1);
        return $possible;
    }
}
