<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Newsletter\Communication\Plugin;

use Spryker\Zed\Installer\Dependency\Plugin\InstallerPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Spryker\Zed\Newsletter\Business\NewsletterFacadeInterface getFacade()
 * @method \Spryker\Zed\Newsletter\Communication\NewsletterCommunicationFactory getFactory()
 * @method \Spryker\Zed\Newsletter\NewsletterConfig getConfig()
 * @method \Spryker\Zed\Newsletter\Persistence\NewsletterQueryContainerInterface getQueryContainer()
 */
class NewsletterInstallerPlugin extends AbstractPlugin implements InstallerPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return void
     */
    public function install()
    {
        $this->getFacade()->install();
    }
}
