<?php
namespace Gis14\Layers\Infrastructure\Http\Upstream;

final class RealUpstreamClient implements UpstreamClientInterface
{
    public function __construct(private int $timeoutMs = 8000) {}

    public function request(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT_MS     => $this->timeoutMs,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        if ($headers) {
            $out = [];
            foreach ($headers as $k => $v) $out[] = $k.': '.$v;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $out);
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return ['status' => 502, 'headers' => ['Content-Type' => 'application/json'], 'body' => json_encode(['error'=>'Bad Gateway','detail'=>$err])];
        }

        $status     = curl_getinfo($ch, CURLINFO_RESPONSE_CODE) ?: 200;
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE) ?: 0;
        $hdrBlock   = substr($raw, 0, $headerSize);
        $bodyOut    = substr($raw, $headerSize);
        curl_close($ch);

        $headersOut = [];
        foreach (explode("\r\n", trim($hdrBlock)) as $line) {
            if ($line === '' || str_starts_with($line, 'HTTP/')) continue;
            [$name, $val] = array_map('trim', explode(':', $line, 2));
            $lname = strtolower($name);
            if (in_array($lname, ['transfer-encoding','connection','keep-alive','proxy-authenticate','proxy-authorization','te','trailer','upgrade'], true)) continue;
            $headersOut[$name] = $val;
        }
        if (!isset($headersOut['Content-Type'])) $headersOut['Content-Type'] = 'application/octet-stream';

        return ['status'=>$status, 'headers'=>$headersOut, 'body'=>$bodyOut];
    }
}
