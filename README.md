# GoldPrice

Компонент [MODX Revolution](https://modx.com/) + [miniShop2](https://modstore.pro/packages/ecommerce/minishop2): котировки золота и USD/RUB, расчёт цен продажи и выкупа, корзина с серверной ценой, заявки на скупку, режим «Шторм».

Лицензия: [MIT](LICENSE). Готовый пакет для установки — в [Releases](https://github.com/Alexandr-GitHub/goldprice/releases): `goldprice-1.0.0-pl.transport.zip`.

## Требования

- PHP 7.4
- MODX Revolution 2.8.x
- miniShop2
- MySQL 5.7 / MariaDB (совместимый)
- исходящий IP, с которого Profinance отдаёт котировки (настройка `goldprice.pf_bind_ip`)

## Установка

1. Скачайте `goldprice-1.0.0-pl.transport.zip` из релиза.
2. Положите файл в `core/packages/` сайта.
3. В админке: **Приложения → Установщик → Искать пакеты локально → Установить**.
4. Заполните системные настройки (пустые в пакете специально):
   - `goldprice.pf_url` — URL запросного API;
   - `goldprice.pf_sid` — SID;
   - `goldprice.pf_bind_ip` — исходящий IP (`CURLOPT_INTERFACE`), если провайдер фильтрует по адресу.
5. Cron каждые 5 минут (на Beget указывайте явный бинарь PHP 7.4):

```cron
*/5 * * * * cd /path/to/modx && /usr/local/bin/php7.4 core/components/goldprice/cron/recalculate.php
```

SID и IP в git и в zip не кладутся.

## Как считается цена

```
₽/г   = (XAU/USD ÷ 31.1) × USD/RUB
cost  = ₽/г × weight
sale  = cost × (1 + markup%) + fix
buy   = cost × (1 − discount%) − fix
```

Индивидуально у товара: `custom_pct` / `custom_fix` (продажа) и `custom_buy_pct` / `custom_buy_fix` (выкуп). Оба поля выкупа 0 — формула группы. Суммы на витрине — **целые рубли**.

`ignore_market` — ручные `fixed_price` / `buyout_price`, без свежести биржи.

Ночью и в выходные тик старше `goldprice.quote_max_age` (900 с) — это не ошибка API: cron пишет `quote=paused (market closed)` и выходит 0, цены не пересчитываются.

## Корзина

Плагин подставляет `goldprice_price.sale_price` на `msOnGetProductPrice` (приоритет **100**, после msOptionsPrice). `POST newprice` игнорируется.

Цена в корзине резервируется на `goldprice.cart_ttl` секунд (по умолчанию 3600). Дальше строка удаляется. Метка времени не кладётся в `options` товара.

Добавление режется, если товар не в наличии, продажа заморожена штормом или котировка протухла (кроме `ignore_market`).

## Витрина

В карточке товара miniShop2 вкладка **«Ценообразование»**.

Сниппеты (без кэша):

- `[[!gpPrice? &id=`123`]]` — JSON продажи/выкупа;
- `[[!gpBuyoutForm]]` — CSRF для формы выкупа;
- `[[!gpQuotes]]` — котировки для виджета.

Плагин `goldprice`:

| Событие | Приоритет |
|---|---|
| OnDocFormPrerender / OnBeforeDocFormSave / OnDocFormSave | 20 |
| msOnGetProductPrice | 100 |
| msOnBeforeAddToCart | 0 |
| msOnAddToCart | 0 |
| OnHandleRequest | 0 |

## Настройки

| Ключ | Смысл |
|---|---|
| `goldprice.pf_url` / `pf_sid` / `pf_tickers` / `pf_timeout` / `pf_bind_ip` | API котировок |
| `goldprice.quote_max_age` | Свежесть золота, сек (900) |
| `goldprice.usd_max_age` | Свежесть USD/RUB, сек |
| `goldprice.vat_pct` | НДС для памятных монет, % |
| `goldprice.cart_ttl` | Резерв цены в корзине, сек (3600) |
| `goldprice.storm_window` / `storm_duration` | Окно и длительность шторма |
| `goldprice.daily_buyout_limit` / `deal_buyout_limit` | Лимиты скупки |
| `goldprice.mail_from` / `mail_logo_url` | Письма |

Группы: наценка/скидка, фикс, шаг цены, стоп-лосс, мин. маржа. Админка: **Компоненты → GoldPrice**.

## Серебро и евро

Евро на витрине отключено сознательно. Тикер серебра в пересчёт не входит: нет живого API. Ручные цены серебра — `ignore_market`. Чтобы включить позже: тикер в `goldprice.pf_tickers` и отдельная группа/формула.

## Сборка zip из исходников

Нужна установленная копия MODX 2.8. Скопируйте `_build/build.config.php.sample` в `build.config.php` и укажите пути:

```bash
php7.4 core/components/goldprice/_build/build.transport.php
```

Пакет пишется в `core/packages/` этой установки. В zip не попадают `tests/`, `vendor/`, `_build`.

Тесты исходников (не входят в zip):

```bash
cd core/components/goldprice
composer install
vendor/bin/phpunit
```

## Удаление

MODX снимает объекты пакета и файлы. Восемь таблиц `goldprice_*` и данные **могут остаться**. Бэкап до установки и удаления обязателен; таблицы чистите вручную, когда данные точно не нужны.

## Структура репозитория

```
assets/components/goldprice/     # CMP JS/CSS, connector
core/components/goldprice/       # PHP, модель, _build, тесты
goldprice-1.0.0-pl.transport.zip # готовый пакет (также в GitHub Release)
```
