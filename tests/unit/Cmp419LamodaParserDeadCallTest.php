<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use app\backend\modules\procurement\models\Buyout;
use app\backend\modules\procurement\services\BuyoutUrlParserService;
use app\backend\modules\procurement\services\parsers\LamodaParser;

/**
 * Регрессионные тесты CMP-419: procurement\LamodaParser делегировал парсинг в
 * app\backend\modules\admin\services\import\LamodaParser — класс, удалённый в CMP-414
 * как дублирующий. Вызов был сломан ещё до CMP-414 (BaseParser::__construct($source)
 * требует обязательный аргумент) и молча уходил в try/catch(\Throwable), маскируя это
 * под успешный парсинг с выдуманными данными ('Lamoda товар', null-поля).
 *
 * Решение CEO (CMP-419, вариант 2): автопарсинг Lamoda для приёмки не реализован —
 * мёртвый вызов и маскирующий catch убраны, parse() честно возвращает null.
 */
class Cmp419LamodaParserDeadCallTest extends TestCase
{
    public function testLamodaParserNoLongerReferencesDeletedImportClass(): void
    {
        $this->assertFalse(
            class_exists('app\\backend\\modules\\admin\\services\\import\\LamodaParser', false),
            'Удалённый в CMP-414 класс не должен присутствовать в автозагрузке'
        );
    }

    public function testParseHonestlyReturnsNullInsteadOfFakeStub(): void
    {
        $parser = new LamodaParser();

        $this->assertTrue($parser->supports('https://www.lamoda.by/p/some-item/'));
        $this->assertNull($parser->parse('https://www.lamoda.by/p/some-item/'));
        $this->assertSame(Buyout::SOURCE_LAMODA, $parser->getSourceKey());
    }

    public function testServiceDetectsLamodaSourceEvenThoughParsingIsUnsupported(): void
    {
        $service = new BuyoutUrlParserService();
        $url = 'https://www.lamoda.by/p/some-item/';

        $this->assertSame(Buyout::SOURCE_LAMODA, $service->detectSource($url));
        $this->assertNull($service->parse($url));
    }
}
