<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\FileManager\Business\Model;

use Generated\Shared\Transfer\FileInfoTransfer;
use Spryker\Zed\FileManager\Exception\FileInfoNotFoundException;
use Spryker\Zed\FileManager\Persistence\FileManagerEntityManagerInterface;
use Spryker\Zed\FileManager\Persistence\FileManagerRepositoryInterface;

class FileRollback implements FileRollbackInterface
{
    /**
     * @var \Spryker\Zed\FileManager\Business\Model\FileVersionInterface
     */
    protected $fileVersion;

    /**
     * @var \Spryker\Zed\FileManager\Persistence\FileManagerEntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var \Spryker\Zed\FileManager\Persistence\FileManagerRepositoryInterface
     */
    protected $repository;

    /**
     * @param \Spryker\Zed\FileManager\Persistence\FileManagerEntityManagerInterface $entityManager
     * @param \Spryker\Zed\FileManager\Persistence\FileManagerRepositoryInterface $repository
     * @param \Spryker\Zed\FileManager\Business\Model\FileVersionInterface $fileVersion
     */
    public function __construct(
        FileManagerEntityManagerInterface $entityManager,
        FileManagerRepositoryInterface $repository,
        FileVersionInterface $fileVersion
    ) {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
        $this->fileVersion = $fileVersion;
    }

    /**
     * @param int $idFileInfo
     *
     * @return void
     */
    public function rollback(int $idFileInfo)
    {
        $targetFileInfo = $this->repository->getFileInfo($idFileInfo);
        $this->addFileVersion($targetFileInfo);
    }

    /**
     * @param \Generated\Shared\Transfer\FileInfoTransfer $fileInfoTransfer
     *
     * @return void
     */
    protected function addFileVersion(FileInfoTransfer $fileInfoTransfer)
    {
        $fileInfoTransfer->setIdFileInfo(null);
        $nextVersion = $this->fileVersion->getNextVersionNumber($fileInfoTransfer->getFkFile());
        $nextVersionName = $this->fileVersion->getNextVersionName($nextVersion);
        $fileInfoTransfer->setVersion($nextVersion);
        $fileInfoTransfer->setVersionName($nextVersionName);

        $this->entityManager->saveFileInfo($fileInfoTransfer);
    }

    /**
     * @param int $idFileInfo
     *
     * @throws \Spryker\Zed\FileManager\Exception\FileInfoNotFoundException
     *
     * @return \Generated\Shared\Transfer\FileInfoTransfer
     */
    protected function getFileInfo(int $idFileInfo)
    {
        $fileInfoTransfer = $this->repository->getFileInfo($idFileInfo);

        if ($fileInfoTransfer === null) {
            throw new FileInfoNotFoundException(sprintf('Target file info with id %s not found', $idFileInfo));
        }

        return $fileInfoTransfer;
    }
}
