<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\GlossaryStorage\Communication\Plugin\Publisher\GlossaryTranslation;

use Spryker\Shared\GlossaryStorage\GlossaryStorageConfig;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\PublisherExtension\Dependency\Plugin\PublisherPluginInterface;

/**
 * @method \Spryker\Zed\GlossaryStorage\Communication\GlossaryStorageCommunicationFactory getFactory()
 * @method \Spryker\Zed\GlossaryStorage\Business\GlossaryStorageFacadeInterface getFacade()
 * @method \Spryker\Zed\GlossaryStorage\GlossaryStorageConfig getConfig()
 * @method \Spryker\Zed\GlossaryStorage\Persistence\GlossaryStorageQueryContainerInterface getQueryContainer()
 */
class GlossaryWritePublisherPlugin extends AbstractPlugin implements PublisherPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\EventEntityTransfer[] $eventTransfers
     * @param string $eventName
     *
     * @return void
     */
    public function handleBulk(array $eventTransfers, $eventName)
    {
        $this->getFacade()->writeCollectionByGlossaryTranslationEvents($eventTransfers);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return string[]
     */
    public function getSubscribedEvents(): array
    {
        return [
            GlossaryStorageConfig::GLOSSARY_KEY_PUBLISH,
            GlossaryStorageConfig::ENTITY_SPY_GLOSSARY_TRANSLATION_CREATE,
            GlossaryStorageConfig::ENTITY_SPY_GLOSSARY_TRANSLATION_UPDATE,
        ];
    }
}
