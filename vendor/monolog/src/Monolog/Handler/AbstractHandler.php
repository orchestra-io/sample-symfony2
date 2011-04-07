<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog\Handler;

use Monolog\Logger;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;

/**
 * Base Handler class providing the Handler structure
 *
 * Classes extending it should (in most cases) only implement write($record)
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
abstract class AbstractHandler implements HandlerInterface
{
    protected $level;
    protected $bubble;

    protected $formatter;
    protected $processors = array();

    /**
     * @param integer $level The minimum logging level at which this handler will be triggered
     * @param Boolean $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct($level = Logger::DEBUG, $bubble = false)
    {
        $this->level = $level;
        $this->bubble = $bubble;
    }

    /**
     * Checks whether the given record will be handled by this handler
     *
     * This is mostly done for performance reasons, to avoid calling processors for nothing.
     *
     * @param array $record
     * @return Boolean True if the record can be handled.
     */
    public function isHandling(array $record)
    {
        return $record['level'] >= $this->level;
    }

    /**
     * Handles a record
     *
     * The return value of this function controls the bubbling process of the handler stack.
     *
     * @param array $record
     * @return Boolean True means that this handler handled the record, and that bubbling is not permitted.
     *                 False means the record was either not processed or that this handler allows bubbling.
     */
    public function handle(array $record)
    {
        if ($record['level'] < $this->level) {
            return false;
        }

        if ($this->processors) {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record, $this);
            }
        }

        if (!$this->formatter) {
            $this->formatter = $this->getDefaultFormatter();
        }
        $record = $this->formatter->format($record);

        $this->write($record);
        return false === $this->bubble;
    }

    /**
     * Handles a set of records at once
     *
     * @param array $records array of records
     */
    public function handleBatch(array $records)
    {
        foreach ($records as $record) {
            $this->handle($record);
        }
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param array $record
     * @return void
     */
    abstract public function write(array $record);

    /**
     * Closes the log
     *
     * This will be called automatically when the object is destroyed
     */
    public function close()
    {
    }

    public function pushProcessor($callback)
    {
        array_unshift($this->processors, $callback);
    }

    public function popProcessor()
    {
        return array_shift($this->processors);
    }

    public function setFormatter(FormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    public function getFormatter()
    {
        return $this->formatter;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setBubble($bubble)
    {
        $this->bubble = $bubble;
    }

    public function getBubble()
    {
        return $this->bubble;
    }

    public function __destruct()
    {
        $this->close();
    }

    protected function getDefaultFormatter()
    {
        return new LineFormatter();
    }
}
