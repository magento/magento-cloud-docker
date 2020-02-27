<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CloudDocker\Test\Functional\Codeception;

use Codeception\Module\Db;
use Codeception\Step;
use Codeception\TestInterface;

/**
 * Module for the extending of the module Db Codeception
 */
class MagentoDb extends Db
{
    /** @var \ReflectionClass */
    private $reflection;

    /**
     * Deletes data from DB
     *
     * @param $table
     * @param $criteria
     * @throws \Codeception\Exception\ModuleException
     */
    public function deleteFromDatabase($table, array $criteria = [])
    {
        $this->_getDriver()
            ->deleteQueryByCriteria($table, $criteria);
    }

    /**
     * Retrieves assoc array with data from DB
     *
     * @param $table
     * @param $columns
     * @param array $criteria
     * @return array
     * @throws \Exception
     */
    public function grabColumnsFromDatabase($table, $columns, array $criteria = [])
    {
        $query = $this->_getDriver()->select($columns, $table, $criteria);
        $parameters = array_values($criteria);
        $this->debugSection('Query', $query);
        $this->debugSection('Parameters', $parameters);
        $sth = $this->_getDriver()->executeQuery($query, $parameters);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Returns exposed port to connect to DB from host machine
     *
     * @return string
     */
    public function getExposedPort(): string
    {
        return (string)$this->_getConfig('exposed_port');
    }

    /**
     * Checks if there is a connection and reconnects if there is no connection
     *
     * @param Step $step
     * @throws \ReflectionException
     */
    public function _beforeStep(Step $step)
    {
        if (!isset($this->reflection)) {
            $this->reflection = new \ReflectionClass($this);
        }

        if ($step->getAction() === 'getExposedPort' || !$this->reflection->hasMethod($step->getAction())) {
            return;
        }

        $class = $this->reflection->getMethod($step->getAction())->getDeclaringClass();

        if ($class != $this->reflection && $class != $this->reflection->getParentClass()) {
            return;
        }

        if (!isset($this->drivers[self::DEFAULT_DATABASE]) || !isset($this->drivers[self::DEFAULT_DATABASE])) {
            $this->reconnectDatabases();
        }
    }

    /**
     * @inheritdoc
     */
    public function _after(TestInterface $test)
    {
        $this->disconnectDatabases();
    }

    /**
     * This method is overridden to avoid connection attempts before running Docker
     * {@inheritdoc}
     */
    public function _initialize() {}

    /**
     * This method is overridden to avoid connection attempts before running Docker
     * {@inheritdoc}
     */
    public function _beforeSuite($settings = []) {}

    /**
     * This method is overridden to avoid connection attempts before running Docker
     * {@inheritdoc}
     */
    public function _before(TestInterface $test) {}
}
