<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\events;

use craft\events\CancelableEvent;

/**
 * @since 4.3.0
 */
class IntegrationConfigEvent extends CancelableEvent
{
    /**
     * @var mixed
     */
    public mixed $config = null;
}
