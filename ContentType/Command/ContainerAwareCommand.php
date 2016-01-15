<?php
namespace SmarterSolutions\EzComponents\EzContentTypeManagerBundle\ContentType\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Debug\Exception\ContextErrorException;
// use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class ContainerAwareCommand extends Command implements ContainerAwareInterface
{
    /**
     * @var \eZ\Publish\Core\SignalSlot\Repository
     */
    protected $repository;
    /**
     * @var \eZ\Publish\Core\SignalSlot\ContentTypeService
     */
    protected $service;
    /**
     * @var \SmarterSolutions\EzComponents\EzContentTypeManagerBundle\ContentType\ContentTypeGroup
     */
    protected $contentTypeGroupService;
    /**
     * @var \SmarterSolutions\EzComponents\EzContentTypeManagerBundle\ContentType\ContentTypeCreate
     */
    protected $contentTypeCreateService;
    /**
     * @var \SmarterSolutions\EzComponents\EzContentTypeManagerBundle\ContentType\ContentTypeUpdate
     */
    protected $contentTypeUpdateService;
    /**
     * @var \eZ\Publish\Core\Repository\Values\User\User
     */
    protected $currentUser;
    /**
     * @var \eZ\Publish\Core\Repository\Values\ContentType\ContentTypeGroup
     */
    protected $currentContentTypeGroup;
    /**
     * @var \Symfony\Component\HttpKernel\Bundle\Bundle
     */
    protected $currentBundle;
    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;
    
    /**
     * Permite definir el usuario actual
     * @param integer $user_id id del usuario a definir
     */
    protected function setCurrentUser($user_id)
    {
        $this->currentUser = $this->repository->getUserService()->loadUser($user_id);
        $this->repository->setCurrentUser($this->currentUser);
        return $this;
    }

    protected function getQuestionHelper()
    {
        $question = $this->getHelperSet()->get('question');
        if (!$question || get_class($question) !== 'Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper') {
            $this->getHelperSet()->set($question = new QuestionHelper());
        }
        return $question;
    }

    protected function isFileConfig($shortcut) {
        list($bundle, $filename) = explode(':', $shortcut);

        try {
            $this->currentBundle = $this->getContainer()->get('kernel')->getBundle($bundle);
            $this->currentConfigFile = Yaml::parse(file_get_contents($this->getConfigFilePath($filename)));
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException(
                sprintf('<error>Bundle "%s" does not exist.</error>', $bundle)
            );
        } catch (ContextErrorException $e ) {
            throw new \InvalidArgumentException(
                sprintf('<error>Config File "%s" does not exist.</error>', $filename)
            );
        }
        return true;
    }

    protected function getConfigFilePath($filename) {
        $format = "%s/Resources/config/%s.yml";
        return sprintf($format, $this->currentBundle->getPath(),$filename);
    }

    protected function getConfigFile() {

    }
    /**
     * @return ContainerInterface
     *
     * @throws \LogicException
     */
    protected function getContainer()
    {
        if (null === $this->container) {
            $application = $this->getApplication();
            if (null === $application) {
                throw new \LogicException('The container cannot be retrieved as the application instance is not yet set.');
            }
            $this->container = $application->getKernel()->getContainer();
        }
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
