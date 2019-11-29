<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Testify\Helper;

use ArrayObject;
use Codeception\Configuration;
use Codeception\Module;
use Codeception\Stub;
use Codeception\TestInterface;
use Exception;
use ReflectionClass;
use ReflectionProperty;
use Spryker\Shared\Config\Config;
use Spryker\Shared\Kernel\AbstractBundleConfig;

class ConfigHelper extends Module
{
    protected const CONFIG_CLASS_NAME_PATTERN = '\%1$s\%2$s\%3$s\%3$sConfig';
    protected const SHARED_CONFIG_CLASS_NAME_PATTERN = '\%1$s\Shared\%2$s\%2$sConfig';
    protected const MODULE_NAME_POSITION = 2;

    /**
     * @var array
     */
    protected $configCache;

    /**
     * @var \Spryker\Shared\Kernel\AbstractBundleConfig[]
     */
    protected $configStubs = [];

    /**
     * @var array
     */
    protected $mockedConfigMethods = [];

    /**
     * @var \Spryker\Shared\Kernel\AbstractBundleConfig[]
     */
    protected $sharedConfigStubs = [];

    /**
     * @var array
     */
    protected $mockedSharedConfigMethods = [];

    /**
     * @return void
     */
    public function _initialize(): void
    {
        Config::init();
        $reflectionProperty = $this->getConfigReflectionProperty();
        $this->configCache = $reflectionProperty->getValue()->getArrayCopy();
    }

    /**
     * @return \ReflectionProperty
     */
    protected function getConfigReflectionProperty(): ReflectionProperty
    {
        $reflection = new ReflectionClass(Config::class);
        $reflectionProperty = $reflection->getProperty('config');
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty;
    }

    /**
     * @param string $key
     * @param array|bool|float|int|string $value
     *
     * @return void
     */
    public function setConfig(string $key, $value): void
    {
        $configProperty = $this->getConfigReflectionProperty();
        $config = $configProperty->getValue();
        $config[$key] = $value;
        $configProperty->setValue($config);
    }

    /**
     * @param string $key
     * @param array|bool|float|int|string $value
     *
     * @return void
     */
    public function mockEnvironmentConfig(string $key, $value): void
    {
        $configProperty = $this->getConfigReflectionProperty();
        $config = $configProperty->getValue();
        $config[$key] = $value;
        $configProperty->setValue($config);
    }

    /**
     * @param string $methodName
     * @param mixed $return
     * @param string|null $moduleName
     *
     * @throws \Exception
     *
     * @return \Spryker\Shared\Kernel\AbstractBundleConfig|null
     */
    public function mockConfigMethod(string $methodName, $return, ?string $moduleName = null): ?AbstractBundleConfig
    {
        $moduleName = $this->getModuleName($moduleName);
        $className = $this->getConfigClassName($moduleName);

        if (!method_exists($className, $methodName)) {
            throw new Exception(sprintf('You tried to mock a not existing method "%s". Available methods are "%s"', $methodName, implode(', ', get_class_methods($className))));
        }

        if (!isset($this->mockedConfigMethods[$moduleName])) {
            $this->mockedConfigMethods[$moduleName] = [];
        }

        $this->mockedConfigMethods[$moduleName][$methodName] = $return;

        /** @var \Spryker\Shared\Kernel\AbstractBundleConfig $configStub */
        $configStub = Stub::make($className, $this->mockedConfigMethods[$moduleName]);
        $this->configStubs[$moduleName] = $configStub;

        return $this->configStubs[$moduleName];
    }

    /**
     * @param string|null $moduleName
     *
     * @return string
     */
    protected function getModuleName(?string $moduleName = null): string
    {
        if ($moduleName) {
            return $moduleName;
        }

        $config = Configuration::config();
        $namespaceParts = explode('\\', $config['namespace']);

        return $namespaceParts[static::MODULE_NAME_POSITION];
    }

    /**
     * @param string $methodName
     * @param mixed $return
     * @param string|null $moduleName
     *
     * @throws \Exception
     *
     * @return \Spryker\Shared\Kernel\AbstractBundleConfig|null
     */
    public function mockSharedConfigMethod(string $methodName, $return, ?string $moduleName = null): ?AbstractBundleConfig
    {
        $moduleName = $this->getModuleName($moduleName);
        $className = $this->getSharedConfigClassName($moduleName);

        if (!method_exists($className, $methodName)) {
            throw new Exception(sprintf('You tried to mock a not existing method "%s". Available methods are "%s"', $methodName, implode(', ', get_class_methods($className))));
        }

        if (!isset($this->mockedSharedConfigMethods[$moduleName])) {
            $this->mockedSharedConfigMethods[$moduleName] = [];
        }

        $this->mockedSharedConfigMethods[$methodName] = $return;

        /** @var \Spryker\Shared\Kernel\AbstractSharedConfig $sharedConfigStub */
        $sharedConfigStub = Stub::make($className, $this->mockedSharedConfigMethods[$moduleName]);
        $this->sharedConfigStubs[$moduleName] = $sharedConfigStub;

        return $this->sharedConfigStubs[$moduleName];
    }

    /**
     * @param string|null $moduleName
     *
     * @return \Spryker\Shared\Kernel\AbstractBundleConfig
     */
    public function getModuleConfig(?string $moduleName = null): AbstractBundleConfig
    {
        $moduleName = $this->getModuleName($moduleName);

        if (isset($this->configStubs[$moduleName])) {
            $this->configStubs[$moduleName] = $this->injectSharedConfig($this->configStubs[$moduleName], $moduleName);

            return $this->configStubs[$moduleName];
        }

        $moduleConfig = $this->createConfig($moduleName);
        $moduleConfig = $this->injectSharedConfig($moduleConfig, $moduleName);

        return $moduleConfig;
    }

    /**
     * @param string $moduleName
     *
     * @return \Spryker\Yves\Kernel\AbstractBundleConfig|\Spryker\Zed\Kernel\AbstractBundleConfig|\Spryker\Glue\Kernel\AbstractBundleConfig|\Spryker\Client\Kernel\AbstractBundleConfig
     */
    protected function createConfig(string $moduleName)
    {
        $moduleConfigClassName = $this->getConfigClassName($moduleName);

        return new $moduleConfigClassName();
    }

    /**
     * @param string $moduleName
     *
     * @return string
     */
    protected function getConfigClassName(string $moduleName): string
    {
        $config = Configuration::config();
        $namespaceParts = explode('\\', $config['namespace']);

        $classNameCandidate = sprintf(static::CONFIG_CLASS_NAME_PATTERN, 'Spryker', $namespaceParts[1], $moduleName);

        if ($namespaceParts[0] === 'SprykerShopTest' && class_exists($classNameCandidate)) {
            return $classNameCandidate;
        }

        return sprintf(static::CONFIG_CLASS_NAME_PATTERN, rtrim($namespaceParts[0], 'Test'), $namespaceParts[1], $moduleName);
    }

    /**
     * @param \Spryker\Shared\Kernel\AbstractBundleConfig $moduleConfig
     * @param string $moduleName
     *
     * @return \Spryker\Shared\Kernel\AbstractBundleConfig
     */
    protected function injectSharedConfig(AbstractBundleConfig $moduleConfig, string $moduleName): AbstractBundleConfig
    {
        if (!method_exists($moduleConfig, 'setSharedConfig')) {
            return $moduleConfig;
        }

        $sharedConfig = $this->getSharedConfig($moduleName);
        if ($sharedConfig === null) {
            return $moduleConfig;
        }

        $moduleConfig->setSharedConfig($sharedConfig);

        return $moduleConfig;
    }

    /**
     * @param string $moduleName
     *
     * @return \Spryker\Shared\Kernel\AbstractBundleConfig|null
     */
    protected function getSharedConfig(string $moduleName): ?AbstractBundleConfig
    {
        if (isset($this->sharedConfigStubs[$moduleName])) {
            return $this->sharedConfigStubs[$moduleName];
        }

        return $this->createSharedConfig($moduleName);
    }

    /**
     * @param string $moduleName
     *
     * @return \Spryker\Shared\Kernel\AbstractBundleConfig|null
     */
    protected function createSharedConfig(string $moduleName): ?AbstractBundleConfig
    {
        $sharedConfigClassName = $this->getSharedConfigClassName($moduleName);
        if (!class_exists($sharedConfigClassName)) {
            return null;
        }

        return new $sharedConfigClassName();
    }

    /**
     * @param string $moduleName
     *
     * @return string
     */
    protected function getSharedConfigClassName(string $moduleName): string
    {
        $config = Configuration::config();
        $namespaceParts = explode('\\', $config['namespace']);

        $classNameCandidate = sprintf(static::SHARED_CONFIG_CLASS_NAME_PATTERN, 'Spryker', $moduleName);

        if ($namespaceParts[0] === 'SprykerShopTest' && class_exists($classNameCandidate)) {
            return $classNameCandidate;
        }

        return sprintf(static::SHARED_CONFIG_CLASS_NAME_PATTERN, rtrim($namespaceParts[0], 'Test'), $moduleName);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function removeConfig(string $key): void
    {
        $configProperty = $this->getConfigReflectionProperty();
        $config = $configProperty->getValue();
        unset($config[$key]);
        $configProperty->setValue($config);
    }

    /**
     * @param \Codeception\TestInterface $test
     *
     * @return void
     */
    public function _before(TestInterface $test): void
    {
        $this->configStubs = [];
        $this->mockedConfigMethods = [];

        $this->sharedConfigStubs = [];
        $this->mockedSharedConfigMethods = [];
    }

    /**
     * @param \Codeception\TestInterface $test
     *
     * @return void
     */
    public function _after(TestInterface $test): void
    {
        $this->resetConfig();
        $this->configStubs = [];
        $this->mockedConfigMethods = [];
        $this->sharedConfigStubs = [];
        $this->mockedSharedConfigMethods = [];
    }

    /**
     * @return void
     */
    public function _afterSuite(): void
    {
        $this->resetConfig();
    }

    /**
     * @return void
     */
    private function resetConfig(): void
    {
        $reflectionProperty = $this->getConfigReflectionProperty();
        $reflectionProperty->setValue(new ArrayObject($this->configCache));
    }
}
