<?php
namespace SmarterSolutions\EzComponents\EzContentTypeManagerBundle\ContentType;

use eZ\Publish\Core\Repository\Values\ContentType\ContentType;
use eZ\Publish\Core\Persistence\Legacy\Exception\TypeNotFound;

class ContentTypeUpdate extends ContentTypeBase
{
    /**
     * @var \eZ\Publish\Core\Repository\Values\ContentType\ContentTypeUpdateStruct
     */
    private $contentTypeStruct;

    /**
     * @var \eZ\Publish\API\Repository\Values\ContentType\ContentTypeDraft
     */
    private $contentTypeDraft;

    /**
     * @var \eZ\Publish\Core\Repository\Values\ContentType\ContentType
     */
    private $contentType;

    /**
     * Permite obtener el borrador del ContentType que va aa ser publicado.
     * @param  \eZ\Publish\Core\Repository\Values\ContentType\ContentType $contentType
     * @param  array $contentTypeConfig  Lista de valores a configuraar en el ContentType
     * @return \eZ\Publish\API\Repository\Values\ContentType\ContentTypeDraft
     */
    public function getContentTypeDraft(ContentType $contentType,$contentTypeConfig)
    {
        $this->contentType = $contentType;

        $this->setContentTypeDraft();

        $this->contentTypeStruct = $this->service->newContentTypeUpdateStruct();

        $this->contentTypeStruct->identifier = $this->contentType->identifier;

        $this->setContentTypeStructValues($contentTypeConfig);

        $this->service->updateContentTypeDraft($this->contentTypeDraft,$this->contentTypeStruct);

        return $this->contentTypeDraft;
    }

    /**
     * Permite definir los valores del ContentType
     * @param array $contentTypeConfig Lista de vaalores del ContentType
     * @return void
     */
    private function setContentTypeStructValues($contentTypeConfig)
    {
        $type_fields = $contentTypeConfig['fields'];

        unset($contentTypeConfig['fields']);

        foreach ($contentTypeConfig as $key => $value)
        {
            $this->contentTypeStruct->{$key} = $value;
        }

        $this->setContentTypeStructFields($type_fields);
    }

    /**
     * Permite deninir los fieldDefinitions del ContenType
     * @param array $type_fields Lista de fieldDefinitions a procesar
     * @return void
     */
    private function setContentTypeStructFields($type_fields)
    {
        $fieldDefinitions = $this->contentType->getFieldDefinitions();

        $this->createUpdateRemoveFieldDefinitions($type_fields,$fieldDefinitions);
    }

    /**
     * Permite crear, actualizar po eliminar unfieldDefinition
     * @param  array $type_fields Lista de fieldDefinitions a procesar
     * @param  array $fieldDefinitions Lista de fieldDefinitions del ContentType
     * @return void
     */
    private function createUpdateRemoveFieldDefinitions($type_fields,$fieldDefinitions)
    {
        $keys = array_keys($type_fields);

        foreach ($fieldDefinitions as $fieldDefinition)
        {
            if(!in_array($fieldDefinition->identifier,$keys))
            {
                $this->service->removeFieldDefinition($this->contentTypeDraft,$fieldDefinition);
            }
            else
            {
                $field = $this->service->newFieldDefinitionUpdateStruct();

                $field_values = $type_fields[$fieldDefinition->identifier];

                $fieldDefinitionStruct = $this->setFieldDefinitionValues($field,$field_values);

                $this->service->updateFieldDefinition($this->contentTypeDraft, $fieldDefinition, $fieldDefinitionStruct);

                unset($type_fields[$fieldDefinition->identifier]);
            }
        }

        foreach ($type_fields as $field_id => $field_values)
        {
            $fieldDefinitionStruct = $this->getFieldDefinitionStruct($field_id, $field_values);

            $this->service->addFieldDefinition($this->contentTypeDraft, $fieldDefinitionStruct);
        }
    }

    /**
     * Permite definir la estructura de un nuevo FieldDefinition
     * @param  string $field_id     Identificador del FieldDefinitions
     * @param  array $field_values Lista de valoes para configurar el FieldDefinitions
     * @return \eZ\Publish\API\Repository\Values\ContentType\FieldDefinitionCreateStruct
     */
    private function getFieldDefinitionStruct($field_id, $field_values)
    {
        $field = $this->service->newFieldDefinitionCreateStruct($field_id, $field_values['type']);

        $field->fieldGroup = $this->contentTypeStruct->identifier;

        return  $this->setFieldDefinitionValues($field,$field_values);
    }

    /**
     * Permite definir la configuración de unfieldDefinition
     * @param \eZ\Publish\API\Repository\Values\ContentType\FieldDefinitionCreateStruct|\eZ\Publish\API\Repository\Values\ContentType\FieldDefinitionUpdateStruct $field FieldDefinition a procesar
     * @param  array $field_values Lista de valoes para configurar el FieldDefinitions
     * @return \eZ\Publish\API\Repository\Values\ContentType\FieldDefinitionCreateStruct|\eZ\Publish\API\Repository\Values\ContentType\FieldDefinitionUpdateStruct
     */
    private function setFieldDefinitionValues($field,$field_values)
    {
        unset($field_values['type']);

        foreach ($field_values as $key => $value)
        {
            $field->{$key} = $value;
        }

        return $field;
    }

    /**
     * Permite definir el borradoor del ContentType
     */
    private function setContentTypeDraft()
    {
        try
        {
            $this->contentTypeDraft = $this->service->loadContentTypeDraft($this->contentType->id);
        }
        catch (TypeNotFound $e)
        {
            $this->contentTypeDraft = $this->service->createContentTypeDraft($this->contentType);
        }
    }
}
