<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Kernel\Transfer;

use ArrayAccess;
use ArrayObject;
use Countable;
use Exception;
use InvalidArgumentException;
use Serializable;
use Spryker\Service\UtilEncoding\Model\Json;
use Spryker\Shared\Kernel\Transfer\Exception\ArrayAccessReadyOnlyException;
use Spryker\Shared\Kernel\Transfer\Exception\NullValueException;
use Spryker\Shared\Kernel\Transfer\Exception\RequiredTransferPropertyException;
use Spryker\Shared\Kernel\Transfer\Exception\TransferUnserializationException;

abstract class AbstractTransfer implements TransferInterface, Serializable, ArrayAccess
{
    /**
     * @var array
     */
    protected $modifiedProperties = [];

    /**
     * @var array
     */
    protected $transferMetadata = [];

    /**
     * @var string[]
     */
    protected $transferPropertyNameMap = [];

    public function __construct()
    {
        $this->initCollectionProperties();
    }

    /**
     * @param bool $isRecursive
     * @param bool $camelCasedKeys Set to true for camelCased keys, defaults to under_scored keys.
     *
     * @return array
     */
    public function toArray($isRecursive = true, $camelCasedKeys = false)
    {
        return $this->propertiesToArray($this->getPropertyNames(), $isRecursive, 'toArray', $camelCasedKeys);
    }

    /**
     * @param bool $isRecursive
     * @param bool $camelCasedKeys
     *
     * @return array
     */
    public function modifiedToArray($isRecursive = true, $camelCasedKeys = false)
    {
        return $this->propertiesToArray(array_keys($this->modifiedProperties), $isRecursive, 'modifiedToArray', $camelCasedKeys);
    }

    /**
     * @param string $propertyName
     *
     * @return bool
     */
    public function isPropertyModified($propertyName)
    {
        return isset($this->modifiedProperties[$propertyName]);
    }

    /**
     * @return void
     */
    protected function initCollectionProperties()
    {
        foreach ($this->transferMetadata as $property => $metaData) {
            if ($metaData['is_collection'] && $this->$property === null) {
                $this->$property = new ArrayObject();
            }
        }
    }

    /**
     * @param array $properties
     * @param bool $isRecursive
     * @param string $childConvertMethodName
     * @param bool $camelCasedKeys
     *
     * @return array
     */
    private function propertiesToArray(array $properties, $isRecursive, $childConvertMethodName, $camelCasedKeys = false)
    {
        $values = [];

        foreach ($properties as $property) {
            $value = $this->$property;

            $arrayKey = $this->getArrayKey($property, $camelCasedKeys);

            if (is_object($value) && $isRecursive) {
                if ($value instanceof TransferInterface) {
                    $values[$arrayKey] = $value->$childConvertMethodName($isRecursive, $camelCasedKeys);

                    continue;
                }

                if ($this->transferMetadata[$property]['is_collection'] && ($value instanceof Countable) && count($value) >= 1) {
                    $values = $this->addValuesToCollection($value, $values, $arrayKey, $isRecursive, $childConvertMethodName, $camelCasedKeys);

                    continue;
                }
            }

            $values[$arrayKey] = $value;
        }

        return $values;
    }

    /**
     * @param string $propertyName
     * @param bool $camelCasedKeys
     *
     * @return string
     */
    protected function getArrayKey(string $propertyName, bool $camelCasedKeys): string
    {
        if ($camelCasedKeys) {
            return $propertyName;
        }

        return $this->transferMetadata[$propertyName]['name_underscore'];
    }

    /**
     * @return array
     */
    protected function getPropertyNames(): array
    {
        return array_keys($this->transferMetadata);
    }

    /**
     * @param array $data
     * @param bool $ignoreMissingProperty
     *
     * @return $this
     */
    public function fromArray(array $data, $ignoreMissingProperty = false)
    {
        foreach ($data as $property => $value) {
            if ($this->hasProperty($property, $ignoreMissingProperty) === false) {
                continue;
            }

            $property = $this->transferPropertyNameMap[$property];

            if ($this->transferMetadata[$property]['is_collection']) {
                $elementType = $this->transferMetadata[$property]['type'];
                $value = $this->processArrayObject($elementType, $value, $ignoreMissingProperty);
            } elseif ($this->transferMetadata[$property]['is_transfer']) {
                $value = $this->initializeNestedTransferObject($property, $value, $ignoreMissingProperty);
            }

            $this->$property = $value;
            $this->modifiedProperties[$property] = true;
        }

        return $this;
    }

    /**
     * @param string $propertyName
     * @param mixed|null $value
     *
     * @return void
     */
    protected function assignValueObject(string $propertyName, $value): void
    {
        $propertySetterMethod = $this->getSetterMethod($propertyName);
        $this->$propertySetterMethod($value);
    }

    /**
     * @param string $elementType
     * @param array|\ArrayObject $arrayObject
     * @param bool $ignoreMissingProperty
     *
     * @return \ArrayObject|\Spryker\Shared\Kernel\Transfer\TransferInterface[]
     */
    protected function processArrayObject($elementType, $arrayObject, $ignoreMissingProperty = false): ArrayObject
    {
        $result = new ArrayObject();
        foreach ($arrayObject as $key => $arrayElement) {
            if (!is_array($arrayElement)) {
                $result->offsetSet($key, new $elementType());

                continue;
            }

            if ($arrayElement) {
                /** @var \Spryker\Shared\Kernel\Transfer\TransferInterface $transferObject */
                $transferObject = new $elementType();
                $transferObject->fromArray($arrayElement, $ignoreMissingProperty);
                $result->offsetSet($key, $transferObject);
            }
        }

        return $result;
    }

    /**
     * @param string $property
     *
     * @throws \Spryker\Shared\Kernel\Transfer\Exception\RequiredTransferPropertyException
     *
     * @return void
     */
    protected function assertPropertyIsSet($property): void
    {
        if ($this->$property === null) {
            throw new RequiredTransferPropertyException(sprintf(
                'Missing required property "%s" for transfer %s.',
                $property,
                static::class
            ));
        }
    }

    /**
     * @param string $property
     *
     * @throws \Spryker\Shared\Kernel\Transfer\Exception\RequiredTransferPropertyException
     *
     * @return void
     */
    protected function assertCollectionPropertyIsSet($property): void
    {
        /** @var \ArrayObject $collection */
        $collection = $this->$property;
        if ($collection->count() === 0) {
            throw new RequiredTransferPropertyException(sprintf(
                'Empty required collection property "%s" for transfer %s.',
                $property,
                static::class
            ));
        }
    }

    /**
     * @param string $property
     * @param mixed $value
     * @param bool $ignoreMissingProperty
     *
     * @return \Spryker\Shared\Kernel\Transfer\TransferInterface
     */
    protected function initializeNestedTransferObject($property, $value, $ignoreMissingProperty = false)
    {
        $type = $this->transferMetadata[$property]['type'];

        /** @var \Spryker\Shared\Kernel\Transfer\TransferInterface $transferObject */
        $transferObject = new $type();

        if (is_array($value)) {
            $transferObject->fromArray($value, $ignoreMissingProperty);
            $value = $transferObject;
        }

        return $value;
    }

    /**
     * @param string $propertyName
     *
     * @return string
     */
    protected function getSetterMethod(string $propertyName): string
    {
        return 'set' . ucfirst($propertyName);
    }

    /**
     * @param string $property
     * @param bool $ignoreMissingProperty
     *
     * @throws \InvalidArgumentException
     *
     * @return bool
     */
    protected function hasProperty($property, $ignoreMissingProperty)
    {
        if (isset($this->transferPropertyNameMap[$property])) {
            return true;
        }

        if ($ignoreMissingProperty) {
            return false;
        }

        throw new InvalidArgumentException(
            sprintf('Missing property "%s" in "%s"', $property, static::class)
        );
    }

    /**
     * @param mixed $value
     * @param array $values
     * @param string $arrayKey
     * @param bool $isRecursive
     * @param string $childConvertMethodName
     * @param bool $camelCasedKeys
     *
     * @return array
     */
    private function addValuesToCollection($value, $values, $arrayKey, $isRecursive, $childConvertMethodName, $camelCasedKeys = false)
    {
        foreach ($value as $elementKey => $arrayElement) {
            if (is_array($arrayElement) || is_scalar($arrayElement)) {
                $values[$arrayKey][$elementKey] = $arrayElement;

                continue;
            }
            $values[$arrayKey][$elementKey] = $arrayElement->$childConvertMethodName($isRecursive, $camelCasedKeys);
        }

        return $values;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        $jsonUtil = new Json();

        return $jsonUtil->encode($this->modifiedToArray());
    }

    /**
     * @param string $serialized
     *
     * @throws \Spryker\Shared\Kernel\Transfer\Exception\TransferUnserializationException
     *
     * @return void
     */
    public function unserialize($serialized)
    {
        try {
            $jsonUtil = new Json();
            $this->fromArray($jsonUtil->decode($serialized, true), true);
            $this->initCollectionProperties();
        } catch (Exception $exception) {
            throw new TransferUnserializationException(
                sprintf(
                    'Failed to unserialize %s. Updating or clearing your data source may solve this problem: %s',
                    static::class,
                    $exception->getMessage()
                ),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->transferMetadata[$offset]);
    }

    /**
     * @param string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * @param mixed $offset
     *
     * @throws \Spryker\Shared\Kernel\Transfer\Exception\ArrayAccessReadyOnlyException
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        throw new ArrayAccessReadyOnlyException('Transfer object as an array is available only for read');
    }

    /**
     * @param string $propertyName
     *
     * @throws \Spryker\Shared\Kernel\Transfer\Exception\NullValueException
     *
     * @return void
     */
    protected function throwNullValueException(string $propertyName): void
    {
        throw new NullValueException(
            sprintf('Property "%s" of transfer `%s` is null.', $propertyName, static::class)
        );
    }
}
