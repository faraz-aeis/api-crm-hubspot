<?php

/**
 * To store debug log
 *
 * @category Trait
 * @author   Anoop Singh <asingh@aeis.com>
 * Date: 06-02-2024
 */

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait ApiLog
{
    public function debugLog($channel, $fileName,  $lineNo,  $exception = null)
    {
        $message = $exception->getLine() . ' - ' . $exception->getMessage();
        $strLog = "\n500 Error : FileName: {$fileName}:{$lineNo}\nLine: {$lineNo}\nmessage: {$message}\n";
        Log::channel($channel)->debug($strLog);
    }

    public function infoLog($channel,  $fileName, $lineNo,  $message)
    {
        $strLog = "\nFileName: {$fileName}:{$lineNo}\nLine: {$lineNo}\nmessage: {$message}:\n";
        Log::channel($channel)->info($strLog);
    }
}
