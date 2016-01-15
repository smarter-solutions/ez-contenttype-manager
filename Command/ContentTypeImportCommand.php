<?php
namespace SmarterSolutions\EzComponents\EzContentTypeManagerBundle\Command;

use SmarterSolutions\EzComponents\EzContentTypeManagerBundle\ContentType\Command\ContainerAwareCommand;
use SmarterSolutions\EzComponents\EzContentTypeManagerBundle\ContentType\Command\Validators;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use eZ\Publish\Core\Persistence\Legacy\Exception\TypeNotFound;
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\API\Repository\Exceptions\ForbiddenException;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Symfony\Component\Console\Question\Question;

class ContentTypeImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName( 'ezcomponents:contenttype:import' )
            ->setDescription('Import ContentTypes')
            ->setDefinition(array(
                new InputOption(
                    'config-file',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'The name of the configuration file containing the content types'
                ),
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
        $this->contentTypeGroupService = $container->get('smartersolutions.ezcontenttypemanager.contentype_group');
        $this->contentTypeCreateService = $container->get('smartersolutions.ezcontenttypemanager.contentype_create');
        $this->contentTypeUpdateService = $container->get('smartersolutions.ezcontenttypemanager.contentype_update');
        $this->service = $this->repository->getContentTypeService();
        $this->setCurrentUser(14);
        $this->output = $output;
    }

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();
        $questionHelper->writeSection($output, 'Welcome to import process of content types');

        $output->writeln(array(
            '',
            'This command helps you to import your contentypes easily.',
            '',
            'First, you need to give the config file name you want to import.',
            'You must use the shortcut notation like <comment>AcmeBlogBundle:filename</comment>',
            '',
        ));

        while (true) {
            $question = new Question(
                $questionHelper->getQuestion(
                    'Config file name',
                    $input->getOption('config-file')
                ),
                $input->getOption('config-file')
            );
            $question->setValidator(
                array(
                    'SmarterSolutions\EzComponents\EzContentTypeManagerBundle\ContentType\Command\Validators',
                    'validateShortcutNotation'
                )
            );
            $shortcut = $questionHelper->ask($input, $output, $question);

            if ($this->isFileConfig($shortcut)) {
                break;
            }
        }
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        foreach ($this->currentConfigFile as $groupId => $contentTypes) {
            $this->setCurrentContentTypeGroup($groupId);
            foreach ($contentTypes as $contentTypeId => $contentTypeConfig)
            {
                $this->createUpdateContentType($contentTypeId,$contentTypeConfig);
            }
        }
    }

    /**
     * Permite definir el ContentTypeGroup
     * @param string $identifier Cadena de texto con el identificador del ContentTypeGroup
     * @return \eZ\Publish\Core\Repository\Values\ContentType\ContentTypeGroup
     */
    private function setCurrentContentTypeGroup($identifier)
    {
        try {
            // $this->currentContentTypeGroup = $this->contentTypeGroupService->update(
            //     $this->service->loadContentTypeGroupByIdentifier($identifier)
            // );
            $this->currentContentTypeGroup = $this->service->loadContentTypeGroupByIdentifier($identifier);
        } catch (NotFoundException $e) {
            $this->currentContentTypeGroup = $this->contentTypeGroupService->create($identifier);
        }
    }

    /**
     * Permite definir el ContentType
     * @param string $identifier Cadena de texto con el identificador ContentType
     */
    private function createUpdateContentType($identifier,$contentTypeConfig)
    {
        try {
            $contentType = $this->service->loadContentTypeByIdentifier($identifier);

            $contentTypeDraft = $this->contentTypeUpdateService->getContentTypeDraft($contentType,$contentTypeConfig);
        }
        catch (TypeNotFound $e) {
            $contentTypeDraft = $this->contentTypeCreateService->getContentTypeDraft(
                $identifier,
                $contentTypeConfig,
                array($this->currentContentTypeGroup)
            );
        }

        $this->saveContentType($identifier,$contentTypeDraft);
    }

    /**
     * Permite guardar un contentType
     * @param  string $identifier       identificador del ContenType
     * @param  \eZ\Publish\API\Repository\Values\ContentType\ContentTypeDraft $contentTypeDraft
     * @return void
     */
    private function saveContentType($identifier, $contentTypeDraft)
    {
        try {
            $this->service->publishContentTypeDraft($contentTypeDraft);
            $this->output->writeln("<info>Content type created/update '$identifier' with ID $contentTypeDraft->id</info>");
        }
        catch (UnauthorizedException $e) {
            $this->output->writeln("<error>".$e->getMessage()."</error>");
        }
        catch (ForbiddenException $e) {
            $this->output->writeln("<error>".$e->getMessage()."</error>");
        }
    }
}
