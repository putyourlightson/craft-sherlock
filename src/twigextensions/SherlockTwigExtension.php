<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\twigextensions;

use putyourlightson\sherlock\Sherlock;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class SherlockTwigExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * Returns globals.
     */
    public function getGlobals(): array
    {
        return [
            'nonce' => Sherlock::$plugin->security->getNonce(),
        ];
    }
}
