<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;


use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;

class AppYaml implements \ArrayAccess
{
    private array $app;
    private string $path;
    private LoggerInterface $logger;
    private string $filename;

    /**
     * @param LoggerInterface $logger
     * @param string $path
     * @param string $filename
     */
    public function __construct(LoggerInterface $logger, string $path, string $filename = '.magento.app.yaml')
    {
        $this->app = Yaml::parseFile($path . '/' . $filename);
        $this->path = $path;
        $this->logger = $logger;
        $this->filename = $filename;
    }

    public function addComposer2Support(): void
    {
        $this->app['build']['flavor'] = 'none';
        $this->app['dependencies']['php']['composer/composer'] = '^2.0';
        $this->addComposerInstallToBuild();
    }

    public function addComposerInstallToBuild(): void
    {
        $this->app['hooks']['build'] = 'set -e' . "\n"
            . 'composer --no-ansi --no-interaction install --no-progress --prefer-dist --optimize-autoloader' . "\n"
            . $this->app['hooks']['build'];
    }

    public function addRelationship(string $name, string $value): void
    {
        $this->app['relationships'][$name] = $value;
    }

    public function write(): void
    {
        file_put_contents($this->path . '/' . $this->filename, Yaml::dump($this->app, 50, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->app);
    }

    public function offsetGet($offset)
    {
        return $this->app[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->app[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->app[$offset]);
    }
}
