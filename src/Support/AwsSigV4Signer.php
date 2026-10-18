<?php

namespace DanPascu\FilamentEmailCampaigns\Support;

/**
 * A minimal AWS Signature Version 4 signer (request signing only, no SDK)
 * for calling AWS HTTP APIs directly. Signs just 'host' and 'x-amz-date';
 * every AWS service accepts additional unsigned headers as long as the
 * signed ones match what was actually sent.
 */
class AwsSigV4Signer
{
    public function __construct(
        protected string $accessKeyId,
        protected string $secretAccessKey,
        protected string $region,
        protected string $service,
    ) {}

    /**
     * @return array<string, string> headers to add to the request
     */
    public function sign(string $method, string $host, string $path, string $body): array
    {
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');

        $headers = [
            'host' => $host,
            'x-amz-date' => $amzDate,
        ];

        $sortedHeaderKeys = array_keys($headers);
        sort($sortedHeaderKeys);

        $canonicalHeaders = '';

        foreach ($sortedHeaderKeys as $key) {
            $canonicalHeaders .= $key.':'.trim($headers[$key])."\n";
        }

        $signedHeaders = implode(';', $sortedHeaderKeys);

        $canonicalRequest = implode("\n", [
            $method,
            $path,
            '',
            $canonicalHeaders,
            $signedHeaders,
            hash('sha256', $body),
        ]);

        $credentialScope = "{$dateStamp}/{$this->region}/{$this->service}/aws4_request";

        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4'.$this->secretAccessKey, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', $this->service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        return [
            'Authorization' => "AWS4-HMAC-SHA256 Credential={$this->accessKeyId}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}",
            'X-Amz-Date' => $amzDate,
            'Host' => $host,
        ];
    }
}
