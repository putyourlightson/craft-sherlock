<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\services;

use craft\base\Component;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Component as ComponentHelper;
use putyourlightson\sherlock\integrations\BugsnagIntegration;
use putyourlightson\sherlock\integrations\IntegrationInterface;
use putyourlightson\sherlock\integrations\RollbarIntegration;
use putyourlightson\sherlock\integrations\SentryIntegration;
use putyourlightson\sherlock\Sherlock;
use yii\base\Event;

/**
 * @property-read string[] $allTypes
 * @property-read string[] $enabledTypes
 */
class IntegrationsService extends Component
{
    /**
     * @event RegisterComponentTypesEvent
     */
    public const EVENT_REGISTER_INTEGRATION_TYPES = 'registerIntegrationTypes';

    /**
     * @var string[] The available integration types.
     */
    public array $integrationTypes = [
        BugsnagIntegration::class,
        RollbarIntegration::class,
        SentryIntegration::class,
    ];

    /**
     * Returns all integration types.
     *
     * @return string[]
     */
    public function getAllTypes(): array
    {
        $integrationTypes = array_unique(array_merge(
            $this->integrationTypes,
            Sherlock::$plugin->settings->integrationTypes
        ), SORT_REGULAR);

        foreach ($integrationTypes as $key => $integrationType) {
            if (!class_exists($integrationType)) {
                unset($integrationTypes[$key]);
            }
        }

        $event = new RegisterComponentTypesEvent([
            'types' => $integrationTypes,
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_INTEGRATION_TYPES, $event);

        return $event->types;
    }

    /**
     * Returns enabled integration types.
     *
     * @return string[]
     */
    public function getEnabledTypes(): array
    {
        $integrationSettings = Sherlock::$plugin->settings->integrationSettings;
        $integrationTypes = [];

        foreach ($this->getAllTypes() as $type) {
            if (!empty($integrationSettings[$type]['enabled'])) {
                $integrationTypes[] = $type;
            }
        }

        return $integrationTypes;
    }

    /**
     * Creates and returns all integration types.
     *
     * @return IntegrationInterface[]
     */
    public function createAllTypes(): array
    {
        $integrationSettings = Sherlock::$plugin->settings->integrationSettings;
        $integrations = [];

        foreach ($this->getAllTypes() as $type) {
            $integrations[] = $this->createType($type, $integrationSettings[$type] ?? []);
        }

        return $integrations;
    }

    /**
     * Creates and returns enabled integration types.
     *
     * @return IntegrationInterface[]
     */
    public function createEnabledTypes(): array
    {
        $integrationSettings = Sherlock::$plugin->settings->integrationSettings;
        $integrations = [];

        foreach ($this->getEnabledTypes() as $type) {
            $integrations[] = $this->createType($type, $integrationSettings[$type] ?? []);
        }

        return $integrations;
    }

    /**
     * Creates and returns an integration type with the optional provided settings.
     */
    public function createType(string $type, array $settings = []): IntegrationInterface
    {
        /** @var IntegrationInterface $integration */
        $integration = ComponentHelper::createComponent([
            'type' => $type,
            'settings' => $settings,
        ], IntegrationInterface::class);

        return $integration;
    }

    /**
     * Runs enabled integrations.
     */
    public function runEnabledIntegrations(): void
    {
        // Return if not pro
        if (!Sherlock::$plugin->getIsPro()) {
            return;
        }

        foreach (self::createEnabledTypes() as $integration) {
            $integration->run();
        }
    }
}
