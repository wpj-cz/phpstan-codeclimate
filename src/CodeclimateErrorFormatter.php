<?php

declare(strict_types=1);

namespace KupShop\DevelopmentBundle\Util\PHPStan;

use Nette\Utils\Json;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;

class CodeclimateErrorFormatter implements ErrorFormatter
{
    public function formatErrors(AnalysisResult $analysisResult, Output $style): int
    {
        $errorsArray = [];

        $root = rtrim(dirname($analysisResult->getProjectConfigFile()), '/');

        foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
            $error = [
                'type' => 'issue',
                'check_name' => 'PHPStan',
                'description' => $fileSpecificError->getMessage(),
                'categories' => ['Style'],
                'location' => [
                    'path' => str_replace(
                        $root . '/',
                        '',
                        $fileSpecificError->getFile()
                    ),
                    'lines' => [
                        'begin' => $fileSpecificError->getLine(),
                    ],
                ],
                'fingerprint' => hash(
                    'sha256',
                    implode(
                        [
                            $fileSpecificError->getFile(),
                            $fileSpecificError->getLine(),
                            $fileSpecificError->getMessage(),
                        ]
                    )
                ),
            ];

            if (!$fileSpecificError->canBeIgnored()) {
                $error['severity'] = 'blocker';
            }

            $errorsArray[] = $error;
        }

        foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
            $errorsArray[] = [
                'type' => 'issue',
                'check_name' => 'PHPStan',
                'description' => $notFileSpecificError,
                'categories' => ['Style'],
                'location' => [
                    'path' => $root . "/.",
                    'positions' => [
                        'offset' => 0,
                    ],
                ],
                'fingerprint' => hash(
                    'sha256',
                    $notFileSpecificError
                ),
            ];
        }

        $json = Json::encode($errorsArray, Json::PRETTY);

        $style->writeRaw($json);

        return $analysisResult->hasErrors() ? 1 : 0;
    }

}
