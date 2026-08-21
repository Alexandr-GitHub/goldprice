<?php
$_lang['goldprice'] = 'Динамическое ценообразование золота';

$_lang['area_goldprice.api'] = 'API котировок';
$_lang['area_goldprice.pricing'] = 'Расчёт цен';
$_lang['area_goldprice.storm'] = 'Шторм';
$_lang['area_goldprice.buyout'] = 'Скупка';
$_lang['area_goldprice.mail'] = 'Почта';

$_lang['setting_goldprice.pf_url'] = 'URL API котировок';
$_lang['setting_goldprice.pf_url_desc'] = 'Адрес сервиса получения котировок (PF).';

$_lang['setting_goldprice.pf_sid'] = 'SID API';
$_lang['setting_goldprice.pf_sid_desc'] = 'Идентификатор сессии API. Задаётся после установки.';

$_lang['setting_goldprice.pf_tickers'] = 'Тикеры';
$_lang['setting_goldprice.pf_tickers_desc'] = 'Список тикеров через точку с запятой, например gold;USDRUB.';

$_lang['setting_goldprice.pf_bind_ip'] = 'Bind IP';
$_lang['setting_goldprice.pf_bind_ip_desc'] = 'IP-адрес для привязки запросов к API. Задаётся после установки.';

$_lang['setting_goldprice.pf_timeout'] = 'Таймаут запроса';
$_lang['setting_goldprice.pf_timeout_desc'] = 'Максимальное время ожидания ответа API, в секундах.';

$_lang['setting_goldprice.quote_max_age'] = 'Макс. возраст котировки';
$_lang['setting_goldprice.quote_max_age_desc'] = 'Котировка золота старше этого значения считается устаревшей, в секундах.';

$_lang['setting_goldprice.usd_max_age'] = 'Макс. возраст курса USD';
$_lang['setting_goldprice.usd_max_age_desc'] = 'Курс доллара не тикает вне валютной сессии, поэтому допустимый возраст у него отдельный (секунды).';

$_lang['setting_goldprice.weight_tolerance'] = 'Допуск веса';
$_lang['setting_goldprice.weight_tolerance_desc'] = 'Допустимое отклонение веса монеты от веса группы, в процентах.';

$_lang['setting_goldprice.vat_pct'] = 'Ставка НДС, %';
$_lang['setting_goldprice.vat_pct_desc'] = 'Начисляется на цену продажи памятных монет; инвестиционные монеты НДС не облагаются.';

$_lang['setting_goldprice.recalc_lock_ttl'] = 'TTL блокировки пересчёта';
$_lang['setting_goldprice.recalc_lock_ttl_desc'] = 'Время блокировки параллельного пересчёта цен, в секундах.';

$_lang['setting_goldprice.cart_ttl'] = 'Резерв цены в корзине';
$_lang['setting_goldprice.cart_ttl_desc'] = 'Сколько держится цена в корзине, в секундах. По истечении товар из корзины удаляется.';

$_lang['setting_goldprice.storm_window'] = 'Окно шторма';
$_lang['setting_goldprice.storm_window_desc'] = 'Окно наблюдения за изменением рынка, в секундах. Изменение сравнивается со стоп-лоссом группы.';

$_lang['setting_goldprice.storm_duration'] = 'Длительность шторма';
$_lang['setting_goldprice.storm_duration_desc'] = 'Сколько держится режим «Шторм» после включения, в секундах. По истечении срока режим снимается автоматически.';


$_lang['setting_goldprice.daily_buyout_limit'] = 'Дневной лимит скупки';
$_lang['setting_goldprice.daily_buyout_limit_desc'] = 'Максимальная сумма скупки за день; 0 — без лимита.';

$_lang['setting_goldprice.deal_buyout_limit'] = 'Лимит одной сделки скупки';
$_lang['setting_goldprice.deal_buyout_limit_desc'] = 'Максимальная сумма одной заявки на выкуп; 0 — без лимита.';

$_lang['setting_goldprice.mail_from'] = 'E-mail отправителя';
$_lang['setting_goldprice.mail_from_desc'] = 'Если пусто — используется системный emailsender. На Beget без SMTP хостинг подменяет From на noreply@unverified.beget.ru — нужен ящик на домене и SMTP в системных настройках почты MODX.';

$_lang['setting_goldprice.mail_logo_url'] = 'URL логотипа в письме';
$_lang['setting_goldprice.mail_logo_url_desc'] = 'Полный URL PNG. Если пусто — {site_url}assets/templates/images/logo-mail.png.';

// ТЗ п.6.2: обвал останавливает продажу, резкий рост — приём на выкуп
$_lang['goldprice.storm_sale_paused'] = 'Из-за резкого падения рынка продажа временно приостановлена. Цены обновятся после стабилизации.';
$_lang['goldprice.storm_buy_paused'] = 'Из-за резкого роста рынка приём на выкуп временно приостановлен. Цены обновятся после стабилизации.';

$_lang['goldprice.err_quote_stale'] = 'Котировка устарела — цены продажи не обновлены.';
$_lang['goldprice.err_quote_fetch'] = 'Не удалось получить котировку.';
$_lang['goldprice.err_quote_lock'] = 'Обновление котировок уже выполняется.';

$_lang['goldprice.tab'] = 'Ценообразование';
$_lang['goldprice.field_weight'] = 'Вес, г';
$_lang['goldprice.field_weight_help'] = 'Чистый вес изделия в граммах.';
$_lang['goldprice.field_metal'] = 'Металл';
$_lang['goldprice.field_coin_type'] = 'Тип монеты';
$_lang['goldprice.field_group'] = 'Весовая группа';
$_lang['goldprice.field_group_help'] = '«Определить автоматически» — по весу и допуску из настройки goldprice.weight_tolerance.';
$_lang['goldprice.group_auto'] = 'Определить автоматически по весу';
$_lang['goldprice.fieldset_custom'] = 'Наценки товара (вместо группы)';
$_lang['goldprice.fieldset_ignore'] = 'Свои цены (игнорировать рынок)';
$_lang['goldprice.field_use_custom'] = 'Собственная надбавка';
$_lang['goldprice.field_custom_pct'] = 'Надбавка продажи, % к базе';
$_lang['goldprice.field_custom_pct_help'] = 'Процент к базе, не скидка: 1.3→+30, 0.8→−20.';
$_lang['goldprice.field_custom_buy_pct'] = 'Надбавка выкупа, % к базе';
$_lang['goldprice.field_custom_buy_pct_help'] = 'Процент к базе, не скидка: 1.3→+30, 0.8→−20. У группы buy_discount — вычитаемая скидка.';
$_lang['goldprice.field_custom_fix'] = 'Надбавка продажи, фикс. сумма';
$_lang['goldprice.field_custom_buy_fix'] = 'Надбавка выкупа, фикс. сумма';
$_lang['goldprice.field_custom_buy_fix_help'] = 'Рубли к цене выкупа: плюс — платим больше, минус — меньше. Процент и сумма оба 0 — формула группы.';
$_lang['goldprice.field_ignore_market'] = 'Игнорировать рынок';
$_lang['goldprice.field_ignore_market_help'] = 'Свои цены: «Фиксированная цена» — продажа, «Цена выкупа» — выкуп. Наценки группы и котировка не применяются.';
$_lang['goldprice.field_fixed_price'] = 'Фиксированная цена';
$_lang['goldprice.field_fixed_price_help'] = 'Цена продажи в рублях при включённом «Игнорировать рынок».';
$_lang['goldprice.field_buyout_price'] = 'Цена выкупа';
$_lang['goldprice.field_buyout_price_help'] = 'Цена выкупа в рублях при «Игнорировать рынок». Пусто или 0 — выкуп не предлагается.';
$_lang['goldprice.metal_gold'] = 'золото';
$_lang['goldprice.metal_silver'] = 'серебро';
$_lang['goldprice.coin_investment'] = 'инвестиционные';
$_lang['goldprice.coin_commemorative'] = 'памятные';

$_lang['goldprice.menu'] = 'GoldPrice';
$_lang['goldprice.menu_desc'] = 'Котировки, цены, шторм и заявки на выкуп';

$_lang['goldprice.recalculate'] = 'Пересчитать сейчас';
$_lang['goldprice.recalculate_ok'] = 'Пересчёт завершён.';
$_lang['goldprice.saved'] = 'Сохранено.';
$_lang['goldprice.err_save'] = 'Не удалось сохранить.';
$_lang['goldprice.err_init'] = 'Не удалось инициализировать компонент.';
$_lang['goldprice.err_recalculate'] = 'Не удалось пересчитать цены.';
$_lang['goldprice.search'] = 'Поиск';

$_lang['goldprice.tab_quotes'] = 'Котировки';
$_lang['goldprice.tab_quotes_intro'] = 'История котировок, новые сверху. Сырой ответ API не показывается.';
$_lang['goldprice.quote_created'] = 'Когда';
$_lang['goldprice.quote_gold'] = 'Золото, ₽/г';
$_lang['goldprice.quote_gold_delta'] = 'Δ золото';
$_lang['goldprice.quote_xau_usd'] = 'XAU/USD';
$_lang['goldprice.quote_usd_rub'] = 'USD/RUB';
$_lang['goldprice.quote_usd_delta'] = 'Δ USD';
$_lang['goldprice.quote_bid'] = 'Bid';
$_lang['goldprice.quote_ask'] = 'Ask';
$_lang['goldprice.quote_netchange_pct'] = 'Netchange, %';
$_lang['goldprice.quote_source'] = 'Источник';

$_lang['goldprice.tab_groups'] = 'Весовые группы';
$_lang['goldprice.tab_groups_intro'] = 'Параметры четырёх весовых групп. После изменения любого поля цены пересчитываются.';
$_lang['goldprice.tab_prices'] = 'Цены';
$_lang['goldprice.tab_prices_intro'] = 'Текущие рассчитанные цены. Ноль в выкупе означает, что выкуп не предлагается.';
$_lang['goldprice.tab_settings'] = 'Параметры';
$_lang['goldprice.tab_settings_intro'] = 'Системные настройки компонента. Изменения пишутся в журнал.';
$_lang['goldprice.tab_recipients'] = 'Уведомления';
$_lang['goldprice.tab_recipients_intro'] = 'Получатели писем по событиям: шторм, лимит, ошибка API, новая заявка.';
$_lang['goldprice.tab_requests'] = 'Заявки';
$_lang['goldprice.tab_requests_intro'] = 'Заявки на выкуп. Можно сменить статус; дневной лимит пишется в журнал при превышении.';
$_lang['goldprice.tab_log'] = 'Журнал';
$_lang['goldprice.tab_log_intro'] = 'Журнал событий компонента. Экспорт в CSV в кодировке UTF-8 для Excel.';

$_lang['goldprice.group_weight'] = 'Вес, г';
$_lang['goldprice.group_title'] = 'Группа';
$_lang['goldprice.group_sale_markup'] = 'Надбавка продажи, %';
$_lang['goldprice.group_sale_fix'] = 'Фикс продажи';
$_lang['goldprice.group_buy_discount'] = 'Скидка выкупа, %';
$_lang['goldprice.group_buy_fix'] = 'Фикс выкупа';
$_lang['goldprice.group_price_step'] = 'Шаг цены';
$_lang['goldprice.group_stoploss'] = 'Стоп-лосс, %';
$_lang['goldprice.group_min_margin'] = 'Мин. маржа';
$_lang['goldprice.err_group_title'] = 'Укажите название группы.';
$_lang['goldprice.err_group_weight'] = 'Вес группы должен быть больше нуля.';
$_lang['goldprice.err_group_number'] = 'Некорректное число в поле [[+field]].';
$_lang['goldprice.log_group_update'] = 'Изменена весовая группа «[[+title]]».';

$_lang['goldprice.price_product'] = 'Товар';
$_lang['goldprice.price_weight'] = 'Вес, г';
$_lang['goldprice.price_group'] = 'Группа';
$_lang['goldprice.price_cost'] = 'Себестоимость';
$_lang['goldprice.price_sale'] = 'Продажа';
$_lang['goldprice.price_buy'] = 'Выкуп';
$_lang['goldprice.price_sale_frozen'] = 'Продажа заморожена';
$_lang['goldprice.price_buy_frozen'] = 'Выкуп заморожен';
$_lang['goldprice.price_updated'] = 'Обновлено';
$_lang['goldprice.buy_not_offered'] = 'выкуп не предлагается';
$_lang['goldprice.cart_price_unavailable'] = 'Цена временно недоступна. Попробуйте позже.';
$_lang['goldprice.cart_out_of_stock'] = 'Товара нет в наличии.';

$_lang['goldprice.settings_saved'] = 'Настройки сохранены.';
$_lang['goldprice.err_settings'] = 'Настройки компонента не найдены.';
$_lang['goldprice.err_setting_number'] = 'Некорректное число в настройке [[+key]].';
$_lang['goldprice.err_setting_save'] = 'Не удалось сохранить настройку [[+key]].';
$_lang['goldprice.log_setting_change'] = '[[+user]] [[+when]] изменил настройки: [[+changes]]';

$_lang['goldprice.recipient_create'] = 'Добавить получателя';
$_lang['goldprice.recipient_update'] = 'Изменить получателя';
$_lang['goldprice.recipient_remove_confirm'] = 'Удалить этого получателя?';
$_lang['goldprice.recipient_email'] = 'E-mail';
$_lang['goldprice.recipient_name'] = 'Имя';
$_lang['goldprice.recipient_active'] = 'Активен';
$_lang['goldprice.recipient_storm_on'] = 'Шторм включён';
$_lang['goldprice.recipient_storm_off'] = 'Шторм снят';
$_lang['goldprice.recipient_daily_limit'] = 'Дневной лимит';
$_lang['goldprice.recipient_api_error'] = 'Ошибка API';
$_lang['goldprice.recipient_new_request'] = 'Новая заявка';
$_lang['goldprice.err_email'] = 'Укажите корректный e-mail.';
$_lang['goldprice.err_email_unique'] = 'Получатель с таким e-mail уже есть.';
$_lang['goldprice.mail_test'] = 'Отправить тестовое письмо';
$_lang['goldprice.mail_test_ok'] = 'Тестовое письмо отправлено на [[+email]].';
$_lang['goldprice.err_mail_test'] = 'Не удалось отправить тестовое письмо.';

$_lang['goldprice.mail_title_storm_on'] = 'Шторм включён';
$_lang['goldprice.mail_title_storm_off'] = 'Шторм снят';
$_lang['goldprice.mail_title_daily_limit'] = 'Дневной лимит скупки превышен';
$_lang['goldprice.mail_title_api_error'] = 'Ошибка API котировок';
$_lang['goldprice.mail_title_request_new'] = 'Новая заявка на выкуп';
$_lang['goldprice.mail_title_mail_test'] = 'Тестовое письмо GoldPrice';
$_lang['goldprice.mail_test_body'] = 'Это тестовое уведомление. Если вы его видите — почта настроена.';
$_lang['goldprice.mail_label_event'] = 'Событие';
$_lang['goldprice.mail_label_time'] = 'Время';
$_lang['goldprice.mail_label_date'] = 'Дата';
$_lang['goldprice.mail_label_change_pct'] = 'Изменение котировки';
$_lang['goldprice.mail_label_groups'] = 'Группы';
$_lang['goldprice.mail_label_frozen'] = 'Заморожено';
$_lang['goldprice.mail_label_sum'] = 'Сумма';
$_lang['goldprice.mail_label_limit'] = 'Лимит';
$_lang['goldprice.mail_label_message'] = 'Сообщение';
$_lang['goldprice.mail_label_request_id'] = '№ заявки';
$_lang['goldprice.mail_label_product'] = 'Товар';
$_lang['goldprice.mail_label_price'] = 'Цена';
$_lang['goldprice.mail_label_count'] = 'Количество';
$_lang['goldprice.mail_label_amount'] = 'Сумма';
$_lang['goldprice.mail_label_name'] = 'Имя';
$_lang['goldprice.mail_label_phone'] = 'Телефон';
$_lang['goldprice.mail_label_email'] = 'E-mail';
$_lang['goldprice.mail_label_mgr_link'] = 'Ссылка в менеджере';
$_lang['goldprice.mail_freeze_sale'] = 'продажа';
$_lang['goldprice.mail_freeze_buy'] = 'выкуп';
$_lang['goldprice.mail_freeze_none'] = 'режимы сняты';

$_lang['goldprice.request_created'] = 'Создана';
$_lang['goldprice.request_product'] = 'Товар';
$_lang['goldprice.request_price'] = 'Цена';
$_lang['goldprice.request_count'] = 'Кол-во';
$_lang['goldprice.request_amount'] = 'Сумма';
$_lang['goldprice.request_name'] = 'Имя';
$_lang['goldprice.request_phone'] = 'Телефон';
$_lang['goldprice.request_email'] = 'E-mail';
$_lang['goldprice.request_comment'] = 'Комментарий';
$_lang['goldprice.request_status'] = 'Статус';
$_lang['goldprice.request_status_all'] = 'Все статусы';
$_lang['goldprice.request_status_new'] = 'Новая';
$_lang['goldprice.request_status_processing'] = 'В работе';
$_lang['goldprice.request_status_done'] = 'Выполнена';
$_lang['goldprice.request_status_cancelled'] = 'Отменена';
$_lang['goldprice.err_request_status'] = 'Неизвестный статус заявки.';
$_lang['goldprice.log_request_status'] = 'Заявка #[[+id]]: статус [[+status]].';
$_lang['goldprice.log_request_new'] = 'Новая заявка на выкуп #[[+id]], сумма [[+amount]].';
$_lang['goldprice.log_daily_limit'] = 'Достижение дневного лимита скупки: [[+date]] [[+time]], сумма [[+sum]].';
$_lang['goldprice.request_ok'] = 'Заявка принята. Мы свяжемся с вами.';
$_lang['goldprice.err_deal_limit'] = 'Сумма заявки превышает лимит одной сделки.';
$_lang['goldprice.err_request_name'] = 'Укажите имя.';
$_lang['goldprice.err_request_phone'] = 'Укажите телефон.';
$_lang['goldprice.err_request_email'] = 'Укажите корректный e-mail.';
$_lang['goldprice.err_request_count'] = 'Количество должно быть от 1 до 1000.';
$_lang['goldprice.err_request_token'] = 'Сессия устарела. Обновите страницу и попробуйте снова.';
$_lang['goldprice.err_request_product'] = 'Товар не указан.';
$_lang['goldprice.err_request_buy_unavailable'] = 'Выкуп по этому товару сейчас недоступен.';
$_lang['goldprice.buyout_modal_title'] = 'Заявка на продажу';
$_lang['goldprice.buyout_submit'] = 'Отправить заявку';
$_lang['goldprice.buyout_product'] = 'Товар';
$_lang['goldprice.buyout_price'] = 'Цена выкупа';

$_lang['goldprice.log_created'] = 'Когда';
$_lang['goldprice.log_event'] = 'Событие';
$_lang['goldprice.log_user'] = 'Пользователь';
$_lang['goldprice.log_message'] = 'Сообщение';
$_lang['goldprice.log_event_all'] = 'Все события';
$_lang['goldprice.log_date_start'] = 'С даты';
$_lang['goldprice.log_date_end'] = 'По дату';
$_lang['goldprice.log_filter'] = 'Фильтр';
$_lang['goldprice.log_export'] = 'Экспорт CSV';
