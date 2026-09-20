<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CasClient
{
    public function loginUrl(string $service): string
    {
        return $this->serverUrl().'/login?'.http_build_query(['service' => $service]);
    }

    public function logoutUrl(string $service): string
    {
        return $this->serverUrl().'/logout?'.http_build_query(['service' => $service]);
    }

    /** @return array{subject: string, attributes: array<string, string|array<int, string>>} */
    public function validateTicket(string $service, string $ticket): array
    {
        $response = Http::accept('application/xml')
            ->timeout((int) config('cas.timeout', 10))
            ->get($this->serverUrl().'/serviceValidate', [
                'service' => $service,
                'ticket' => $ticket,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('统一身份认证服务暂时不可用。');
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $loaded = $document->loadXML($response->body(), LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new RuntimeException('统一身份认证返回了无法识别的数据。');
        }

        $xpath = new DOMXPath($document);
        $success = $xpath->query('//*[local-name()="authenticationSuccess"]')->item(0);
        $subject = trim((string) $xpath->evaluate('string(./*[local-name()="user"][1])', $success));

        if (! $success instanceof DOMElement || $subject === '') {
            $message = trim((string) $xpath->evaluate('string(//*[local-name()="authenticationFailure"][1])'));
            throw new RuntimeException($message ?: '统一身份认证票据校验失败。');
        }

        $attributes = [];
        $attributeNodes = $xpath->query('./*[local-name()="attributes"]/*', $success);
        foreach ($attributeNodes as $node) {
            $name = $node->localName;
            $value = trim($node->textContent);
            if ($name === '' || $value === '') {
                continue;
            }

            if (! array_key_exists($name, $attributes)) {
                $attributes[$name] = $value;
            } elseif (is_array($attributes[$name])) {
                $attributes[$name][] = $value;
            } else {
                $attributes[$name] = [$attributes[$name], $value];
            }
        }

        return ['subject' => $subject, 'attributes' => $attributes];
    }

    private function serverUrl(): string
    {
        $serverUrl = (string) config('cas.server_url');
        if ($serverUrl === '') {
            throw new RuntimeException('尚未配置统一身份认证服务地址。');
        }

        return rtrim($serverUrl, '/');
    }
}
