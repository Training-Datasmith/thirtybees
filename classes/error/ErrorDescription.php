<?php

declare(strict_types=1);
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\Error;

use Encryptor;
use PrestaShopException;

/**
 * class ErrorDescription
 */
class ErrorDescriptionCore
{
    private string $phpVersion;

    private string $codeBuildFor;

    private string $codeRevision;

    /**
     * @var string
     */
    protected $errorName;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var string
     */
    protected $sourceType;

    /**
     * @var string
     */
    protected $sourceFile;

    /**
     * @var int
     */
    protected $sourceLine;

    /**
     * @var array
     */
    protected $sourceFileContent = [];

    /**
     * @var string
     */
    protected $realSourceFile = '';

    /**
     * @var int
     */
    protected $realSourceLine = 0;

    /**
     * @var array
     */
    protected $realSourceContent = [];

    /**
     * @var array
     */
    protected $extraSections = [];

    /**
     * @var array
     */
    protected $stackTrace = [];

    /**
     * @var ErrorDescription
     */
    protected $cause;

    /**
     * ErrorDescription constructor
     */
    public function __construct()
    {
        $this->phpVersion = phpversion();
        $this->codeBuildFor = static::resolveCodeBuildFor();
        $this->codeRevision = static::resolveCodeRevision();
    }

    public function setErrorName(string $errorName): void
    {
        $this->errorName = $errorName;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getErrorName(): string
    {
        return $this->errorName;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setSource(string $sourceType, string $file, int $line, array $content): void
    {
        $this->sourceType = $sourceType;
        $this->sourceFile = $file;
        $this->sourceLine = $line;
        $this->sourceFileContent = $content;
    }

    public function setRealSource(string $file, int $line, array $content): void
    {
        $this->realSourceFile = $file;
        $this->realSourceLine = $line;
        $this->realSourceContent = $content;
    }

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    public function getSourceFile(): string
    {
        return $this->sourceFile;
    }

    public function getSourceLine(): int
    {
        return (int)$this->sourceLine;
    }

    public function getSourceFileContent(): array
    {
        return $this->sourceFileContent;
    }

    public function hasSourceFileContent(): bool
    {
        return !!$this->sourceFileContent;
    }

    public function getRealSourceFileContent(): array
    {
        return $this->realSourceContent;
    }

    public function hasRealSourceFileContent(): bool
    {
        return !!$this->realSourceContent;
    }

    public function setExtraSections(array $extraSections): void
    {
        $this->extraSections = $extraSections;
    }

    public function getExtraSections(): array
    {
        return $this->extraSections;
    }

    public function setStackTrace(array $stacktrace): void
    {
        $this->stackTrace = $stacktrace;
    }

    public function getStackTrace(): array
    {
        return $this->stackTrace;
    }

    public function setCause(ErrorDescription $errorDescription): void
    {
        $this->cause = $errorDescription;
    }

    /**
     * @return ErrorDescription | null
     */
    public function getCause()
    {
        return $this->cause;
    }

    public function hasCause(): bool
    {
        return !is_null($this->cause);
    }

    /**
     * Return the content of the Exception
     * @return string content of the exception.
     */
    public function getExtendedMessage(): string
    {
        if ($this->getSourceType() === 'smarty') {
            return $this->getErrorName() . ': ' . $this->getMessage() . ' in template file ' . ErrorUtils::getRelativeFile($this->getSourceFile());
        }
        return $this->getErrorName() . ': ' . $this->getMessage() . ' at line ' . $this->getSourceLine() . ' in file ' . ErrorUtils::getRelativeFile($this->getSourceFile());
    }

    public function getTraceAsString(): string
    {
        $result = '';
        $stackTrace = $this->getStackTrace();
        if ($stackTrace) {
            $total = count($stackTrace) + 1;
            $separatorLen = strlen("$total") + 1;
            $separator = str_repeat(' ', $separatorLen - 1);
            $result .= '#0' . $separator . ErrorUtils::getRelativeFile($this->getSourceFile()) . '(' . $this->getSourceLine() . ")\n";
            $cnt = 1;
            foreach ($stackTrace as $trace) {
                $len = strlen("$cnt");
                $separator = str_repeat(' ', $separatorLen - $len);
                $result .= '#' . $cnt . $separator . $trace['fileName'] . '(' . $trace['line'] . '): ';
                $result .= $trace['class'] . $trace['type'] . $trace['function'] . '(';
                if ($trace['args']) {
                    $args = array_map(fn ($param) => strtok($param, "\n"), $trace['args']);
                    $result .= implode(', ', $args);
                }
                $result .= ')';
                $result .= "\n";
                $cnt++;
            }
        }
        return $result;
    }

    /**
     * @return string
     */
    public function getPhpVersion()
    {
        return $this->phpVersion;
    }

    /**
     * @param string $phpVersion
     */
    public function setPhpVersion($phpVersion): void
    {
        $this->phpVersion = $phpVersion;
    }

    public function getCodeBuildFor(): string
    {
        return $this->codeBuildFor;
    }

    public function setCodeBuildFor(string $codeBuildFor): void
    {
        $this->codeBuildFor = $codeBuildFor;
    }

    public function getCodeRevision(): string
    {
        return $this->codeRevision;
    }

    public function setCodeRevision(string $codeRevision): void
    {
        $this->codeRevision = $codeRevision;
    }

    public function getRealSourceFile(): string
    {
        return $this->realSourceFile;
    }

    public function setRealSourceFile(string $realSourceFile): void
    {
        $this->realSourceFile = $realSourceFile;
    }

    public function getRealSourceLine(): int
    {
        return $this->realSourceLine;
    }

    public function setRealSourceLine(int $realSourceLine): void
    {
        $this->realSourceLine = $realSourceLine;
    }

    public function toArray(): array
    {
        $source = [
            'type' => $this->getSourceType(),
            'file' => $this->getSourceFile(),
            'line' => $this->getSourceLine(),
            'content' => $this->getSourceFileContent(),
        ];
        if ($this->realSourceFile) {
            $source['realFile'] = $this->realSourceFile;
            $source['realLine'] = $this->realSourceLine;
            $source['realContent'] = $this->getRealSourceFileContent();
        }
        $data = [
            'phpVersion' => $this->getPhpVersion(),
            'codeBuildFor' => $this->getCodeBuildFor(),
            'codeRevision' => $this->getCodeRevision(),
            'errorName' => $this->getErrorName(),
            'message' => $this->getMessage(),
            'source' => $source,
            'stackTrace' => $this->getStackTrace(),
            'extra' => $this->getExtraSections(),
        ];
        if ($this->cause) {
            $data['cause'] = $this->cause->toArray();
        }
        return $data;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    public function encrypt()
    {
        return Encryptor::getInstance()->encrypt($this->serialize());
    }

    /**
     * @param string $encrypted
     * @return ErrorDescription
     * @throws PrestaShopException
     */
    public static function decrypt($encrypted)
    {
        $decrypted = Encryptor::getInstance()->decrypt($encrypted);
        if (!$decrypted) {
            throw new PrestaShopException('Failed to decrypt content');
        }
        $array = json_decode((string) $decrypted, true);
        if (!is_array($array) || !$array) {
            throw new PrestaShopException('Failed to parse content');
        }
        return static::deserialize($array);
    }

    /**
     * @param array $array
     * @throws PrestaShopException
     */
    public static function deserialize($array): \Thirtybees\Core\Error\ErrorDescription
    {
        $description = new ErrorDescription();
        $description->setPhpVersion(static::getProperty('phpVersion', $array, false, 'unknown'));
        $description->setCodeBuildFor(static::getProperty('codeBuildFor', $array, false, 'unknown'));
        $description->setCodeRevision(static::getProperty('codeRevision', $array, false, 'unknown'));

        $description->setErrorName(static::getProperty('errorName', $array));
        $description->setMessage(static::getProperty('message', $array));
        $source = static::getProperty('source', $array);
        $description->setSource(
            static::getProperty('type', $source),
            static::getProperty('file', $source),
            (int)static::getProperty('line', $source),
            static::getProperty('content', $source)
        );
        if (array_key_exists('realContent', $source)) {
            $description->setRealSource(
                static::getProperty('realFile', $source),
                (int)static::getProperty('realLine', $source),
                static::getProperty('realContent', $source)
            );
        }
        $description->setStackTrace(static::getProperty('stackTrace', $array));
        $description->setExtraSections(static::getProperty('extra', $array));
        $cause = static::getProperty('cause', $array, false);
        if ($cause) {
            $description->setCause(static::deserialize($cause));
        }
        return $description;
    }

    /**
     * @param string $name
     * @param boolean $required
     * @return mixed
     * @throws PrestaShopException
     */
    protected static function getProperty($name, array $array, $required = true, $default = '')
    {
        if (array_key_exists($name, $array)) {
            return $array[$name];
        }
        if ($required) {
            throw new PrestaShopException("Missing key '$name'");
        }
        return $default;
    }

    /**
     * @return string
     */
    protected static function resolveCodeBuildFor()
    {
        if (defined('_TB_BUILD_PHP_')) {
            return _TB_BUILD_PHP_;
        }
        if (defined('_TB_INSTALL_BUILD_PHP_')) {
            return _TB_INSTALL_BUILD_PHP_;
        }
        return 'unknown';
    }

    /**
     * @return string
     */
    protected static function resolveCodeRevision()
    {
        if (defined('_TB_REVISION_')) {
            return _TB_REVISION_;
        }
        if (defined('_TB_INSTALL_REVISION_')) {
            return _TB_INSTALL_REVISION_;
        }
        return 'unknown';
    }
}
