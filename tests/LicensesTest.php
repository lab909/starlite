<?php

declare(strict_types=1);

namespace App\Tests;

/**
 * The third-party code the site sends to browsers carries its license: short notices in the built
 * files, the full texts in public/third-party-licenses.txt. Update both when you change fonts, icons
 * or scripts.
 */
final class LicensesTest extends AppTestCase
{
    public function testTheFullTextsAreServed(): void
    {
        $licenses = (string) file_get_contents(self::ROOT . '/public/third-party-licenses.txt');

        foreach (['Copyright © Star Federation', 'Copyright (c) 2026 Lucide Icons and Contributors', 'Copyright (c) 2013-present Cole Bemis', 'SIL OPEN FONT LICENSE Version 1.1', 'Copyright (c) Tailwind Labs, Inc.'] as $notice) {
            self::assertStringContainsString($notice, $licenses);
        }
    }

    public function testTheBuiltFilesKeepTheirNotices(): void
    {
        $this->requireViteBuild($this->app());
        $built = '';
        foreach (glob(self::ROOT . '/public/build/assets/*.{js,css}', GLOB_BRACE) ?: [] as $file) {
            $built .= (string) file_get_contents($file);
        }

        self::assertStringContainsString('/*! Datastar v1.0.2 | MIT License | Copyright © Star Federation', $built, 'kept by the minifier (comments.legal)');
        self::assertStringContainsString('/*! Lucide icons | ISC License', $built);
        self::assertStringContainsString('/*! Inter | SIL Open Font License 1.1', $built);
        self::assertStringContainsString('/*! tailwindcss', $built);
    }
}
