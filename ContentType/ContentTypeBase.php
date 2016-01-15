<?php
namespace SmarterSolutions\EzComponents\EzContentTypeManagerBundle\ContentType;

use eZ\Publish\Core\SignalSlot\Repository;

class ContentTypeBase
{
    /**
     * @var \eZ\Publish\Core\SignalSlot\Repository
     */
    protected $repository;

    /**
     * @var \eZ\Publish\Core\SignalSlot\ContentTypeService
     */
    protected $service;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;

        $this->service = $this->repository->getContentTypeService();
    }
}
