<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Buyout;

/**
 * Public buyout form fields (no $modx).
 */
final class BuyoutRequestValidator
{
    public const COUNT_MIN = 1;
    public const COUNT_MAX = 1000;
    public const NAME_MAX = 128;
    public const PHONE_MAX = 32;
    public const COMMENT_MAX = 2000;

    /**
     * @param array<string,mixed> $input
     * @return array{ok:bool,errors:string[],data:array<string,mixed>}
     */
    public static function validate(array $input): array
    {
        $errors = [];

        $honeypot = trim((string) ($input['website'] ?? ''));
        if ($honeypot !== '') {
            return ['ok' => false, 'errors' => ['honeypot'], 'data' => []];
        }

        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > self::NAME_MAX) {
            $errors[] = 'name';
        }

        $phone = trim((string) ($input['phone'] ?? ''));
        if ($phone === '' || mb_strlen($phone) > self::PHONE_MAX) {
            $errors[] = 'phone';
        }

        $email = trim((string) ($input['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'email';
        }

        $count = (int) ($input['count'] ?? 0);
        if ($count < self::COUNT_MIN || $count > self::COUNT_MAX) {
            $errors[] = 'count';
        }

        $comment = trim((string) ($input['comment'] ?? ''));
        if (mb_strlen($comment) > self::COMMENT_MAX) {
            $comment = mb_substr($comment, 0, self::COMMENT_MAX);
        }

        if ($errors) {
            return ['ok' => false, 'errors' => $errors, 'data' => []];
        }

        return [
            'ok' => true,
            'errors' => [],
            'data' => [
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'count' => $count,
                'comment' => $comment,
            ],
        ];
    }
}
