<?php

namespace Nelmio\JsLoggerBundle;

use Symfony\Component\HttpKernel\Log\LoggerInterface;

class Logger
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $allowedLevels;

    /**
     * @var array
     */
    private $levelToMethod = array(
        'emergency' => 'emerg',
        'alert' => 'alert',
        'critical' => 'crit',
        'error' => 'err',
        'warning' => 'warn',
        'notice' => 'notice',
        'info' => 'info',
        'debug' => 'debug',
    );

    /**
     * @var array
     */
    private $ignoredMessages;

    /**
     * @var array
     */
    private $ignoredURLs;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param array           $allowedLevels
     * @param array           $ignoredMessages
     * @param array           $ignoredURLs
     */
    public function __construct(LoggerInterface $logger, array $allowedLevels, array $ignoredMessages = array(), array $ignoredURLs = array())
    {
        $this->logger = $logger;
        $this->allowedLevels = $allowedLevels;
        $this->ignoredMessages = $ignoredMessages;
        $this->ignoredURLs = $ignoredURLs;
    }

    public function write($level, $message, array $context = array())
    {
        if (!$message) {
            return false;
        }

        $level = strtolower($level);
        if (!in_array($level, $this->allowedLevels)) {
            return false;
        }
        
        if ($this->isIgnoredFile($context['file']) || $this->isIgnoredFile($context['file'])) {
            return false;
        }

        foreach ($this->ignoredMessages as $errorToIgnore) {
            if (false !== strpos($message, $errorToIgnore)) {
                return false;
            }
        }

        $this->logger->{$this->levelToMethod[$level]}($message, $context);

        return true;
    }
    
    private function isIgnoredFile($filename) 
    {
        if (!empty($filename)) {
            foreach ($this->ignoredURLs as $scriptToIgnore) {
                if (strpos($filename, $scriptToIgnore) === 0) {
                    return true;
                }
            }
        }
        return false;
    }
}
