<?php
/*
 * Copyright 2023 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Auth;

/**
 * Trait containing helper methods required for enabling
 * observability metrics in the library.
 *
 * @internal
 */
trait MetricsTrait
{
    /**
     * @var string The version of the auth library php.
     */
    private static $version;

    /**
     * @var string The header key for the observability metrics.
     */
    protected static $metricMetadataKey = 'x-goog-api-client';

    /**
     * @param string $credType [Optional] The credential type.
     *        Empty value will not add any credential type to the header.
     *        Should be one of `'sa'`, `'jwt'`, `'imp'`, `'mds'`, `'u'`.
     * @param string $authRequestType [Optional] The auth request type.
     *        Empty value will not add any auth request type to the header.
     *        Should be one of `'at'`, `'it'`, `'mds'`.
     * @return string The header value for the observability metrics.
     */
    protected static function getMetricsHeader(
        $credType = '',
        $authRequestType = ''
    ): string {
        $value = sprintf(
            'gl-php/%s auth/%s',
            PHP_VERSION,
            self::getVersion()
        );

        if (!empty($authRequestType)) {
            $value .= ' auth-request-type/' . $authRequestType;
        }

        if (!empty($credType)) {
            $value .= ' cred-type/' . $credType;
        }

        return $value;
    }

    /**
     * @param array<mixed> $metadata The metadata to update and return.
     * @return array<mixed> The updated metadata.
     */
    protected function applyServiceApiUsageMetrics($metadata)
    {
        if ($credType = $this->getCredType()) {
            // Add service api usage observability metrics info to metadata
            $metricsHeader = self::getMetricsHeader($credType);
            if (!isset($metadata[self::$metricMetadataKey])) {
                $metadata[self::$metricMetadataKey] = [$metricsHeader];
            } elseif (is_array($metadata[self::$metricMetadataKey])) {
                $metadata[self::$metricMetadataKey][0] .= ' ' . $metricsHeader;
            } else {
                $metadata[self::$metricMetadataKey] .= ' ' . $metricsHeader;
            }
        }

        return $metadata;
    }

    /**
     * @param array<mixed> $metadata The metadata to update and return.
     * @param string $authRequestType The auth request type. Possible values are
     *        `'at'`, `'it'`, `'mds'`.
     * @return array<mixed> The updated metadata.
     */
    protected function applyTokenEndpointMetrics($metadata, $authRequestType)
    {
        $metricsHeader = $this->getMetricsHeader($this->getCredType(), $authRequestType);
        if (!isset($metadata[self::$metricMetadataKey])) {
            $metadata[self::$metricMetadataKey] = $metricsHeader;
        }
        return $metadata;
    }

    protected static function getVersion(): string
    {
        if (is_null(self::$version)) {
            $versionFilePath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'VERSION']);
            self::$version = trim((string) file_get_contents($versionFilePath));
        }
        return self::$version;
    }

    public function getCredType(): string
    {
        return '';
    }
}
