<?php

declare(strict_types=1);

namespace libReplay\exception;

use RuntimeException;
use Throwable;

/**
 * Class DataEntryException
 * @package libReplay\exception
 *
 * @internal
 */
class DataEntryException extends RuntimeException
{

    private const SEPARATOR = ' | ';

    /**
     * DataEntryException constructor.
     * @param mixed[] $dataDump
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(array $dataDump, $message = '', $code = 0, Throwable $previous = null)
    {
        $exportableDataDump = var_export($dataDump, true);
        $stringCheck = is_string($exportableDataDump);
        if (!$stringCheck) {
            $message = 'Data dump could not be collected.';
            parent::__construct($message, $code, $previous);
        }
        $message .= self::SEPARATOR . $exportableDataDump;
        parent::__construct($message, $code, $previous);
    }

}