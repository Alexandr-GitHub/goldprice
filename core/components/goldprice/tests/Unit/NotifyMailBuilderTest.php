<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Notify\NotifyMailBuilder;
use PHPUnit\Framework\TestCase;

final class NotifyMailBuilderTest extends TestCase
{
    /** @var array<string,string> */
    private $lex;

    protected function setUp(): void
    {
        $this->lex = [
            'goldprice.mail_title_storm_on' => 'Шторм включён',
            'goldprice.mail_title_storm_off' => 'Шторм снят',
            'goldprice.mail_title_request_new' => 'Новая заявка на выкуп',
            'goldprice.mail_title_api_error' => 'Ошибка API котировок',
            'goldprice.mail_title_daily_limit' => 'Дневной лимит скупки превышен',
            'goldprice.mail_label_event' => 'Событие',
            'goldprice.mail_label_time' => 'Время',
            'goldprice.mail_label_change_pct' => 'Изменение котировки',
            'goldprice.mail_label_groups' => 'Группы',
            'goldprice.mail_label_frozen' => 'Заморожено',
            'goldprice.mail_label_request_id' => '№ заявки',
            'goldprice.mail_label_product' => 'Товар',
            'goldprice.mail_label_price' => 'Цена',
            'goldprice.mail_label_count' => 'Количество',
            'goldprice.mail_label_amount' => 'Сумма',
            'goldprice.mail_label_name' => 'Имя',
            'goldprice.mail_label_phone' => 'Телефон',
            'goldprice.mail_label_email' => 'E-mail',
            'goldprice.mail_label_mgr_link' => 'Ссылка в менеджере',
            'goldprice.mail_label_message' => 'Сообщение',
            'goldprice.mail_freeze_sale' => 'продажа',
            'goldprice.mail_freeze_buy' => 'выкуп',
            'goldprice.mail_freeze_none' => 'режимы сняты',
        ];
    }

    public function testFourGroupStormProducesOneHtmlListingAllTitles(): void
    {
        $groups = [
            ['title' => '1 г', 'mode' => 'crash', 'freeze_sale' => true, 'freeze_buy' => false],
            ['title' => '5 г', 'mode' => 'crash', 'freeze_sale' => true, 'freeze_buy' => false],
            ['title' => '10 г', 'mode' => 'spike', 'freeze_sale' => false, 'freeze_buy' => true],
            ['title' => '31.1 г', 'mode' => 'spike', 'freeze_sale' => false, 'freeze_buy' => true],
        ];
        $mail = NotifyMailBuilder::build('storm_on', [
            'change_pct' => '-8.6957',
            'time' => '2026-08-20 02:00:00',
            'groups' => $groups,
        ], $this->lex, [
            'logo_url' => 'https://example.test/logo-mail.png',
            'logo_alt' => 'GP',
            'site_name' => 'Golden Pearl',
        ]);

        $this->assertStringContainsString('Шторм включён', $mail['subject']);
        $this->assertStringContainsString('1 г', $mail['html']);
        $this->assertStringContainsString('5 г', $mail['html']);
        $this->assertStringContainsString('10 г', $mail['html']);
        $this->assertStringContainsString('31.1 г', $mail['html']);
        $this->assertStringContainsString('продажа', $mail['html']);
        $this->assertStringContainsString('выкуп', $mail['html']);
        // One body (not four separate mails): all four titles in the same HTML.
        $this->assertLessThanOrEqual(2, substr_count($mail['html'], '<html'));
        $this->assertStringContainsString('1 г', $mail['text']);
        $this->assertStringContainsString('31.1 г', $mail['text']);
    }

    public function testRequestPayloadAndEscaping(): void
    {
        $mail = NotifyMailBuilder::build('request_new', [
            'id' => 42,
            'product' => 'Монета <script>alert(1)</script>',
            'price' => '1000.50',
            'count' => 2,
            'amount' => '2001.00',
            'name' => 'Иван<script>',
            'phone' => '+7999',
            'email' => 'a@b.c',
            'mgr_url' => 'https://example.test/manager/?a=home&namespace=goldprice',
        ], $this->lex, [
            'logo_url' => 'https://example.test/logo.png',
            'logo_alt' => 'Logo',
            'site_name' => 'GP',
            'mgr_url' => 'https://example.test/manager/?a=home&namespace=goldprice',
        ]);

        $this->assertStringContainsString('#42', $mail['subject']);
        $this->assertStringContainsString('Монета &lt;script&gt;alert(1)&lt;/script&gt;', $mail['html']);
        $this->assertStringContainsString('Иван&lt;script&gt;', $mail['html']);
        $this->assertStringNotContainsString('<script>alert', $mail['html']);
        $this->assertStringContainsString('2001.00', $mail['html']);
        $this->assertStringContainsString('a@b.c', $mail['text']);
    }

    public function testApiErrorRows(): void
    {
        $mail = NotifyMailBuilder::build('api_error', [
            'time' => '2026-08-20 03:00:00',
            'message' => 'Transport failed',
        ], $this->lex);

        $this->assertSame('Ошибка API котировок', $mail['subject']);
        $this->assertStringContainsString('Transport failed', $mail['html']);
        $this->assertStringContainsString('2026-08-20 03:00:00', $mail['text']);
    }

    public function testEventFlagMap(): void
    {
        $this->assertSame('new_request', NotifyMailBuilder::$eventFlags['request_new']);
        $this->assertSame('storm_on', NotifyMailBuilder::$eventFlags['storm_on']);
        $this->assertSame('api_error', NotifyMailBuilder::$eventFlags['api_error']);
    }
}
