<?php
namespace SmarterSolutions\EzComponents\EzContentTypeManagerBundle\Command;

use SmarterSolutions\EzComponents\EzContentTypeManagerBundle\ContentType\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use eZ\Publish\Core\Persistence\Legacy\Exception\TypeNotFound;
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\API\Repository\Exceptions\ForbiddenException;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Symfony\Component\Console\Question\Question;
use eZ\Publish\SPI\Persistence\Content\Type;

class ContentTypeExportCommand extends ContainerAwareCommand
{
    private $contentTypeExport;
    protected function configure()
    {
        $this
            ->setName( 'ezcomponents:contenttype:export' )
            ->setDescription('Export ContentTypes')
            ->setDefinition(array(
                new InputOption(
                    'contenttype-group',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'The name of the content type group'
                ),
                new InputArgument(
                    'contettypes',
                    InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                    'Who do you want to greet (separate multiple names with a space)?'
                )
            ))
            ->setHelp(<<<EOT
The <info>%command.name%</info> command helps you import ContentTypes to to eZ Publish platform.

By default, the command interacts with the developer to obtain the necessary import parameters.
Any passed option will be used as a default value for the interaction
(<comment>--config-file</comment> is the only one needed if you follow the conventions):
<info>php %command.full_name% --config-file=AcmeBlogBundle:filename</info>
If you want to disable any user interaction, use <comment>--no-interaction</comment>
but don't forget to pass all needed options:
<info>php %command.full_name% --config-file=AcmeBlogBundle:filename --no-interaction</info>

<comment>--config-file</comment> accepts values ​​mutiples.

Configuraación files debne content types to be stored within the Resource / config path , since that is where the search for the same conduct.

EOT
            )
        ;
    }

    public function initialize (InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();
        $this->repository = $container->get('ezpublish.api.repository');
        $this->contentTypeHandler = $container->get('ezpublish.spi.persistence.cache.contenttypehandler');
        $this->languageHandler = $container->get('ezpublish.spi.persistence.cache.contentlanguagehandler');
        // $this->contentTypeUpdateService = $container->get('smartersolutions.ezcontenttypemanager.contentype_update');
        $this->service = $this->repository->getContentTypeService();
        $this->setCurrentUser(14);
        $this->output = $output;
        $this->contentTypeExport = array();
    }

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();
        $questionHelper->writeSection($output, 'Welcome to export process of content types');

        $output->writeln(array(
            '',
            'This command helps you to export your contentypes easily.',
            '',
            'First, you need to give the config file name you want to import.',
            'You must use the shortcut notation like <comment>AcmeBlogBundle:filename</comment>',
            '',
        ));

        $default = $input->getOption('contenttype-group')?$input->getOption('contenttype-group'):'Content';

        $question = new Question(
            $questionHelper->getQuestion(
                'ContentType Group Identifier',
                $default
            ),
            $default
        );

        // $contenttypeGroupIdentifier = $questionHelper->ask($input, $output, $question);

        $this->setCurrentContentTypeGroup(
            $questionHelper->ask(
                $input,
                $output,
                $question
            )
        );
        // try {
        //     $contentTypeGroup = $this->service->loadContentTypeGroupByIdentifier($contenttypeGroupIdentifier);
        //     $this->contentTypeExport[$contenttypeGroupIdentifier] = array();
        //     var_dump($this->contentTypeHandler->loadContentTypes($contentTypeGroup->id));
        //     //loadContentTypes
        // } catch (NotFoundException $e) {
        //     throw new \InvalidArgumentException(
        //         sprintf('<error>ContentType Group "%s" does not exist.</error>', $contenttypeGroupIdentifier)
        //     );
        // }
        // while (true) {
        //     $question = new Question(
        //         $questionHelper->getQuestion(
        //             'Config file name',
        //             $input->getOption('contenttype-group')
        //         ),
        //         'Content'
        //     );
        //     // $question->setValidator(
        //     //     array(
        //     //         'SmarterSolutions\EzComponents\EzContentTypeManagerBundle\ContentType\Command\Validators',
        //     //         'validateShortcutNotation'
        //     //     )
        //     // );
        //     $contenttypeGroup = $questionHelper->ask($input, $output, $question);
        //
        //     if ($this->isFileConfig($shortcut)) {
        //         break;
        //     }
        // }
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $contenttypeGroupId = $this->currentContentTypeGroup->id;
        $contenttypeGroupIdentifier = $this->currentContentTypeGroup->identifier;
        $contenttypeGroupContentTypes = $this->contentTypeHandler->loadContentTypes($contenttypeGroupId);
        $this->contentTypeExport[$contenttypeGroupIdentifier] = array();
        foreach ($contenttypeGroupContentTypes as $contentType) {
            $this->setContentType($contenttypeGroupIdentifier, $contentType);
        }
        var_dump($this->contentTypeExport);exit;
    }

    /**
     * Permite definir el ContentTypeGroup
     * @param string $identifier Cadena de texto con el identificador del ContentTypeGroup
     * @return \eZ\Publish\Core\Repository\Values\ContentType\ContentTypeGroup
     */
    private function setCurrentContentTypeGroup($identifier)
    {
        try {
            $this->currentContentTypeGroup = $this->service->loadContentTypeGroupByIdentifier($identifier);
        } catch (NotFoundException $e) {
            throw new \InvalidArgumentException(
                sprintf('<error>ContentType Group "%s" does not exist.</error>', $identifier)
            );
        }
    }

    private function setContentType($contenttypeGroupIdentifier, Type $contentType) {
        $this->contentTypeExport[$contenttypeGroupIdentifier][$contentType->identifier] = array(
            'mainLanguageCode' => $this->getMainLanguage($contentType->initialLanguageId),
            'nameSchema' => $contentType->nameSchema,
            'urlAliasSchema' => $contentType->urlAliasSchema,
            'isContainer' => $contentType->isContainer,
            'defaultSortField' => $contentType->sortField,
            'defaultSortOrder' => $contentType->sortOrder,
            'defaultAlwaysAvailable' => $contentType->defaultAlwaysAvailable,
            'names' => $contentType->name,
            'descriptions' => $contentType->description
        );
    }

    private function getMainLanguage($languageId) {
        $language = $this->languageHandler->load($languageId);
        return $language->languageCode;
    }

    /**
     * Permite definir el ContentType
     * @param string $identifier Cadena de texto con el identificador ContentType
     */
    // private function createUpdateContentType($identifier,$contentTypeConfig)
    // {
    //     try {
    //         $contentType = $this->service->loadContentTypeByIdentifier($identifier);
    //
    //         $contentTypeDraft = $this->contentTypeUpdateService->getContentTypeDraft($contentType,$contentTypeConfig);
    //     }
    //     catch (TypeNotFound $e) {
    //         $contentTypeDraft = $this->contentTypeCreateService->getContentTypeDraft(
    //             $identifier,
    //             $contentTypeConfig,
    //             array($this->currentContentTypeGroup)
    //         );
    //     }
    //
    //     $this->saveContentType($identifier,$contentTypeDraft);
    // }

    /**
     * Permite guardar un contentType
     * @param  string $identifier       identificador del ContenType
     * @param  \eZ\Publish\API\Repository\Values\ContentType\ContentTypeDraft $contentTypeDraft
     * @return void
     */
    // private function saveContentType($identifier, $contentTypeDraft)
    // {
    //     try {
    //         $this->service->publishContentTypeDraft($contentTypeDraft);
    //         $this->output->writeln("<info>Content type created/update '$identifier' with ID $contentTypeDraft->id</info>");
    //     }
    //     catch (UnauthorizedException $e) {
    //         $this->output->writeln("<error>".$e->getMessage()."</error>");
    //     }
    //     catch (ForbiddenException $e) {
    //         $this->output->writeln("<error>".$e->getMessage()."</error>");
    //     }
    // }
}
