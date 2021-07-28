<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Command;

use Magento\Deployer\Model\CloudCloner;
use Magento\Deployer\Model\Config\ComposerResolver;
use Magento\Deployer\Model\Config\PathResolver;
use Magento\Deployer\Model\Config\PrepareConfig;
use Magento\Deployer\Model\ObjectManager\Factory;
use Magento\Deployer\Model\Prepare;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class InitCommand extends Command
{
    protected static $defaultName = 'project:init';
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var PathResolver
     */
    private $pathResolver;
    /**
     * @var CloudCloner
     */
    private $cloudCloner;

    /**
     * @param LoggerInterface $logger
     * @param PathResolver $pathResolver
     * @param CloudCloner $cloudCloner
     */
    public function __construct(
        LoggerInterface $logger,
        PathResolver $pathResolver,
        CloudCloner $cloudCloner
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->pathResolver = $pathResolver;
        $this->cloudCloner = $cloudCloner;
    }

    protected function configure()
    {
        $this->addArgument(
            'directory',
            InputArgument::OPTIONAL,
            'The directory to operate in. Default is the current directory.'
        );
        $this->addOption(
            'cloud-branch',
            null,
            InputOption::VALUE_REQUIRED,
            'Specify the branch of magento-cloud to clone as a base.',
            'master'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $this->pathResolver->resolveNewProjectWithUserInput($input->getArgument('directory'));
        chdir($path);

        $template = $this->getTemplate();
        $helper = $this->getHelper('question');
        $question = new Question('Github Token: ');
        $token = $helper->ask($input, $output, $question);
        if (!preg_match('/^[A-Z0-9_]+$/i', $token)) {
            $this->logger->error('Invalid token');
            exit;
        }
        $this->logger->info('<fg=blue>Writing .magento.env.yaml');
        file_put_contents($path . '/.magento.env.yaml', str_replace('{TOKEN}', $token, $template));

        $this->cloudCloner->cloneToCwd($input->getOption('cloud-branch'), true);

        return 0;
    }

    private function getTemplate(): string
    {
        return file_get_contents(BP . '/etc/env-template.yaml');
    }
}
