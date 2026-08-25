<?php

declare(strict_types=1);

namespace Mailyte\EmailTemplates\Deliverability;

use Mailyte\EmailTemplates\Rendering\RenderedEmail;

/**
 * Writes a rendered message as an RFC 5322 file.
 *
 * The point is to hand the message to something that scores it properly. This
 * package can tell you the HTML is 40KB and the links resolve; it cannot tell
 * you what SpamAssassin makes of the whole message, and it certainly cannot
 * tell you what Gmail thinks of your domain. An .eml can be uploaded to
 * mail-tester.com, piped through `spamassassin -t`, or opened in a real client.
 *
 * The headers here are deliberately placeholders: a file on disk has no
 * envelope, no DKIM signature and no sending IP, which is most of the score.
 */
class EmlWriter
{
    public function write(RenderedEmail $email, string $from = 'sender@example.com', string $to = 'recipient@example.com'): string
    {
        $boundary = 'mailyte-'.substr(hash('sha256', $email->html), 0, 24);
        $date = gmdate('D, d M Y H:i:s O');
        $id = '<'.substr(hash('sha256', $email->slug.$email->html), 0, 32).'@mailyte.local>';

        $headers = [
            'From: Example Sender <'.$from.'>',
            'To: <'.$to.'>',
            'Subject: '.$this->encodeHeader($email->subject),
            'Date: '.$date,
            'Message-ID: '.$id,
            'MIME-Version: 1.0',
            'X-Mailyte-Template: '.$email->slug.($email->templateVersion !== '' ? '@'.$email->templateVersion : ''),
        ];

        foreach ($email->suggestedHeaders as $name => $value) {
            $headers[] = $name.': '.$this->encodeHeader((string) $value);
        }

        $headers[] = 'Content-Type: multipart/alternative; boundary="'.$boundary.'"';

        $body = [
            '',
            'This is a multi-part message in MIME format.',
            '',
            '--'.$boundary,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            '',
            quoted_printable_encode($email->text),
            '',
            '--'.$boundary,
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            '',
            quoted_printable_encode($email->html),
            '',
            '--'.$boundary.'--',
            '',
        ];

        return implode("\r\n", $headers)."\r\n".implode("\r\n", $body);
    }

    /**
     * A subject line with an em dash or a non-ASCII name needs encoding, or the
     * file is not a valid message and the scorer rejects it.
     */
    private function encodeHeader(string $value): string
    {
        $value = str_replace(["\r", "\n"], ' ', $value);

        if (preg_match('/^[\x20-\x7E]*$/', $value) === 1) {
            return $value;
        }

        return '=?UTF-8?B?'.base64_encode($value).'?=';
    }
}
