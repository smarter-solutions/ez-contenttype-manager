<?php
namespace SmarterSolutions\EzComponents\EzContentTypeManagerBundle\ContentType;

class ContentTypeCreate extends ContentTypeBase
{
    /**
     * @var \eZ\Publish\API\Repository\Values\ContentType\ContentTypeCreateStruct
     */
    private $contentTypeStruct;

    /**
     * [getContentTypeDraft description]
     * @param  string $identifier    Cadena de texto con el identificador del nuevo ContentType
     * @param  array $contentTypeConfig   Lista de valores a configuraar en el ContentType
     * @param  array $contentTypeGroup
     * @return \eZ\Publish\API\Repository\Values\ContentType\ContentTypeDraft
     */
    public function getContentTypeDraft($identifier,$contentTypeConfig, $contentTypeGroup)
    {
        $this->contentTypeStruct = $this->service->newContentTypeCreateStruct($identifier);

        $this->setContentTypeStructValues($contentTypeConfig);

        return $this->service->createContentType($this->contentTypeStruct, $contentTypeGroup);
    }

    /**
     * Permite definir los valores del ContentType
     * @param array $contentTypeConfig Lista de vaalores del ContentType
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
        foreach ($type_fields as $field_id => $field_values)
        {
            $field = $this->getContentTypeFieldStruct($field_id, $field_values);

            $this->contentTypeStruct->addFieldDefinition($field);
        }
    }

    /**
     * Permite obtener unnuevo ContentType
     * @param  string $field_id     Cadena de texto con el identificador del fieldDefinition
     * @param  array $field_values Lista de valoes para configurar el FieldDefinitions
     * @return \eZ\Publish\API\Repository\Values\ContentType\FieldDefinitionCreateStruct
     */
    private function getContentTypeFieldStruct($field_id, $field_values)
    {
        $field = $this->service->newFieldDefinitionCreateStruct($field_id, $field_values['type']);

        $field->fieldGroup = $this->contentTypeStruct->identifier;

        return  $this->setContentypeFieldsValues($field,$field_values);
    }

    /**
     * Permite definir la configuración de unfieldDefinition
     * @param \eZ\Publish\API\Repository\Values\ContentType\FieldDefinitionCreateStruct|\eZ\Publish\API\Repository\Values\ContentType\FieldDefinitionUpdateStruct $field FieldDefinition a procesar
     * @param  array $field_values Lista de valoes para configurar el FieldDefinitions
     * @return \eZ\Publish\API\Repository\Values\ContentType\FieldDefinitionCreateStruct|\eZ\Publish\API\Repository\Values\ContentType\FieldDefinitionUpdateStruct
     */
    private function setContentypeFieldsValues($field,$field_values)
    {
        unset($field_values['type']);

        foreach ($field_values as $key => $value)
        {
            $field->{$key} = $value;
        }

        return $field;
    }
}
