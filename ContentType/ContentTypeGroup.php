<?php
namespace SmarterSolutions\EzComponents\EzContentTypeManagerBundle\ContentType;

use eZ\Publish\API\Repository\Values\ContentType\ContentTypeGroupCreateStruct;
use eZ\Publish\Core\Repository\Values\ContentType\ContentTypeGroup as ContentTypeGroupCore;

/**
 * Clase que permite
 */
class ContentTypeGroup extends ContentTypeBase
{
    /**
     * @param  string $identifier    Cadena de texto con el identificador del nuevo ContentType
     * @return \eZ\Publish\Core\Repository\Values\ContentType\ContentTypeGroup
     */
    public function create($identifier)
    {
        $contentGroupStruct = $this->service->newContentTypeGroupCreateStruct($identifier);

        return $this->service->createContentTypeGroup($contentGroupStruct);
    }

    /**
     * [update description]
     * @param  \eZ\Publish\Core\Repository\Values\ContentType\ContentTypeGroup $contentTypeGroup
     * @return \eZ\Publish\Core\Repository\Values\ContentType\ContentTypeGroup
     */
    public function update(ContentTypeGroupCore $contentTypeGroup)
    {
        $contentGroupUpdateStruct = $this->service->newContentTypeGroupUpdateStruct();

        $this->service->updateContentTypeGroup($contentTypeGroup,$contentGroupUpdateStruct);

        return $this->service->loadContentTypeGroup($contentTypeGroup->id);
    }
}
