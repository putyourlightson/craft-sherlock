<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\events;

use craft\events\CancelableEvent;
use GuzzleHttp\Client;

/**
 * @since 4.4.0
 */
class RunTestsEvent extends CancelableEvent
{
    public Client $client;
}
