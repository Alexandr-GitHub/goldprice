<?php
$_lang['goldprice'] = 'Dynamic gold pricing';

$_lang['area_goldprice.api'] = 'Quote API';
$_lang['area_goldprice.pricing'] = 'Pricing';
$_lang['area_goldprice.storm'] = 'Storm';
$_lang['area_goldprice.buyout'] = 'Buyout';
$_lang['area_goldprice.mail'] = 'Mail';

$_lang['setting_goldprice.pf_url'] = 'Quote API URL';
$_lang['setting_goldprice.pf_url_desc'] = 'Endpoint for the quote provider (PF).';

$_lang['setting_goldprice.pf_sid'] = 'API SID';
$_lang['setting_goldprice.pf_sid_desc'] = 'API session identifier. Set after install.';

$_lang['setting_goldprice.pf_tickers'] = 'Tickers';
$_lang['setting_goldprice.pf_tickers_desc'] = 'Semicolon-separated tickers, e.g. gold;USDRUB.';

$_lang['setting_goldprice.pf_bind_ip'] = 'Bind IP';
$_lang['setting_goldprice.pf_bind_ip_desc'] = 'Source IP for API requests. Set after install.';

$_lang['setting_goldprice.pf_timeout'] = 'Request timeout';
$_lang['setting_goldprice.pf_timeout_desc'] = 'Maximum API response wait time, in seconds.';

$_lang['setting_goldprice.quote_max_age'] = 'Quote max age';
$_lang['setting_goldprice.quote_max_age_desc'] = 'Gold quotes older than this are stale, in seconds.';

$_lang['setting_goldprice.usd_max_age'] = 'USD quote max age';
$_lang['setting_goldprice.usd_max_age_desc'] = 'USD/RUB does not tick outside the FX session, so it has a separate max age, in seconds.';

$_lang['setting_goldprice.weight_tolerance'] = 'Weight tolerance';
$_lang['setting_goldprice.weight_tolerance_desc'] = 'Allowed coin weight deviation from the group weight, in percent.';

$_lang['setting_goldprice.vat_pct'] = 'VAT rate, %';
$_lang['setting_goldprice.vat_pct_desc'] = 'Added to the sale price of commemorative coins; investment coins are VAT-free.';

$_lang['setting_goldprice.recalc_lock_ttl'] = 'Recalc lock TTL';
$_lang['setting_goldprice.recalc_lock_ttl_desc'] = 'Lock duration for concurrent recalculation, in seconds.';

$_lang['setting_goldprice.cart_ttl'] = 'Cart price reservation';
$_lang['setting_goldprice.cart_ttl_desc'] = 'How long a cart line keeps its price, in seconds. After that the product is removed from the cart.';

$_lang['setting_goldprice.storm_window'] = 'Storm window';
$_lang['setting_goldprice.storm_window_desc'] = 'Observation window for market moves, in seconds. The move is compared with the group stop-loss.';

$_lang['setting_goldprice.storm_duration'] = 'Storm duration';
$_lang['setting_goldprice.storm_duration_desc'] = 'How long storm mode holds once triggered, in seconds. It is released automatically when it expires.';


$_lang['setting_goldprice.daily_buyout_limit'] = 'Daily buyout limit';
$_lang['setting_goldprice.daily_buyout_limit_desc'] = 'Maximum daily buyout amount; 0 means no limit.';

$_lang['setting_goldprice.deal_buyout_limit'] = 'Per-deal buyout limit';
$_lang['setting_goldprice.deal_buyout_limit_desc'] = 'Maximum amount of a single buyout request; 0 means no limit.';

$_lang['setting_goldprice.mail_from'] = 'Sender e-mail';
$_lang['setting_goldprice.mail_from_desc'] = 'If empty, the system emailsender is used. On Beget, PHP mail() rewrites From to noreply@unverified.beget.ru unless SMTP is used with a mailbox on the domain.';

$_lang['setting_goldprice.mail_logo_url'] = 'Mail logo URL';
$_lang['setting_goldprice.mail_logo_url_desc'] = 'Full PNG URL. If empty — {site_url}assets/templates/images/logo-mail.png.';

// ТЗ п.6.2: a crash pauses selling, a spike pauses buying from customers
$_lang['goldprice.storm_sale_paused'] = 'Selling is paused because of a sharp market drop. Prices will update once the market settles.';
$_lang['goldprice.storm_buy_paused'] = 'Buyout is paused because of a sharp market rise. Prices will update once the market settles.';

$_lang['goldprice.err_quote_stale'] = 'Quote is stale — sale prices were not updated.';
$_lang['goldprice.err_quote_fetch'] = 'Failed to fetch quote.';
$_lang['goldprice.err_quote_lock'] = 'Quote update already in progress.';

$_lang['goldprice.tab'] = 'Pricing';
$_lang['goldprice.field_weight'] = 'Weight, g';
$_lang['goldprice.field_weight_help'] = 'Pure metal weight in grams.';
$_lang['goldprice.field_metal'] = 'Metal';
$_lang['goldprice.field_coin_type'] = 'Coin type';
$_lang['goldprice.field_group'] = 'Weight group';
$_lang['goldprice.field_group_help'] = 'Auto uses weight and goldprice.weight_tolerance.';
$_lang['goldprice.group_auto'] = 'Detect automatically by weight';
$_lang['goldprice.fieldset_custom'] = 'Product markups (instead of the group)';
$_lang['goldprice.fieldset_ignore'] = 'Manual prices (ignore market)';
$_lang['goldprice.field_use_custom'] = 'Custom markup';
$_lang['goldprice.field_custom_pct'] = 'Sale markup, % of base';
$_lang['goldprice.field_custom_pct_help'] = 'Percent of base, not a discount: 1.3→+30, 0.8→−20.';
$_lang['goldprice.field_custom_buy_pct'] = 'Buyout markup, % of base';
$_lang['goldprice.field_custom_buy_pct_help'] = 'Percent of base, not a discount: 1.3→+30, 0.8→−20. Group buy_discount is a subtractive discount.';
$_lang['goldprice.field_custom_fix'] = 'Sale markup, fixed amount';
$_lang['goldprice.field_custom_buy_fix'] = 'Buyout markup, fixed amount';
$_lang['goldprice.field_custom_buy_fix_help'] = 'Rubles added to buyout: plus pays more, minus pays less. Both percent and amount 0 — group formula.';
$_lang['goldprice.field_ignore_market'] = 'Ignore market';
$_lang['goldprice.field_ignore_market_help'] = 'Manual prices: «Fixed price» is sale, «Buyout price» is buyout. Group markups and the quote are not used.';
$_lang['goldprice.field_fixed_price'] = 'Fixed price';
$_lang['goldprice.field_fixed_price_help'] = 'Sale price in rubles when «Ignore market» is on.';
$_lang['goldprice.field_buyout_price'] = 'Buyout price';
$_lang['goldprice.field_buyout_price_help'] = 'Buyout in rubles when «Ignore market» is on. Empty or 0 — buyout is not offered.';
$_lang['goldprice.metal_gold'] = 'gold';
$_lang['goldprice.metal_silver'] = 'silver';
$_lang['goldprice.coin_investment'] = 'investment';
$_lang['goldprice.coin_commemorative'] = 'commemorative';

$_lang['goldprice.menu'] = 'GoldPrice';
$_lang['goldprice.menu_desc'] = 'Quotes, prices, storm mode and buyout requests';

$_lang['goldprice.recalculate'] = 'Recalculate now';
$_lang['goldprice.recalculate_ok'] = 'Recalculation finished.';
$_lang['goldprice.saved'] = 'Saved.';
$_lang['goldprice.err_save'] = 'Could not save.';
$_lang['goldprice.err_init'] = 'Could not initialize the component.';
$_lang['goldprice.err_recalculate'] = 'Could not recalculate prices.';
$_lang['goldprice.search'] = 'Search';

$_lang['goldprice.tab_quotes'] = 'Quotes';
$_lang['goldprice.tab_quotes_intro'] = 'Saved quote history, newest first. The raw API payload is not shown.';
$_lang['goldprice.quote_created'] = 'When';
$_lang['goldprice.quote_gold'] = 'Gold, ₽/g';
$_lang['goldprice.quote_gold_delta'] = 'Gold delta';
$_lang['goldprice.quote_xau_usd'] = 'XAU/USD';
$_lang['goldprice.quote_usd_rub'] = 'USD/RUB';
$_lang['goldprice.quote_usd_delta'] = 'USD delta';
$_lang['goldprice.quote_bid'] = 'Bid';
$_lang['goldprice.quote_ask'] = 'Ask';
$_lang['goldprice.quote_netchange_pct'] = 'Netchange, %';
$_lang['goldprice.quote_source'] = 'Source';

$_lang['goldprice.tab_groups'] = 'Weight groups';
$_lang['goldprice.tab_groups_intro'] = 'Parameters of the four weight groups. Prices are recalculated after any change.';
$_lang['goldprice.tab_prices'] = 'Prices';
$_lang['goldprice.tab_prices_intro'] = 'Current calculated prices. A zero buyout price means buyout is not offered.';
$_lang['goldprice.tab_settings'] = 'Settings';
$_lang['goldprice.tab_settings_intro'] = 'Component system settings. Changes are written to the log.';
$_lang['goldprice.tab_recipients'] = 'Notifications';
$_lang['goldprice.tab_recipients_intro'] = 'Mail recipients for storm, limit, API error, and new request events.';
$_lang['goldprice.tab_requests'] = 'Requests';
$_lang['goldprice.tab_requests_intro'] = 'Buyout requests. Status can be changed; daily limit excess is written to the log.';
$_lang['goldprice.tab_log'] = 'Log';
$_lang['goldprice.tab_log_intro'] = 'Component event log. CSV export is UTF-8 for Excel.';

$_lang['goldprice.group_weight'] = 'Weight, g';
$_lang['goldprice.group_title'] = 'Group';
$_lang['goldprice.group_sale_markup'] = 'Sale markup, %';
$_lang['goldprice.group_sale_fix'] = 'Sale fix';
$_lang['goldprice.group_buy_discount'] = 'Buyout discount, %';
$_lang['goldprice.group_buy_fix'] = 'Buyout fix';
$_lang['goldprice.group_price_step'] = 'Price step';
$_lang['goldprice.group_stoploss'] = 'Stop-loss, %';
$_lang['goldprice.group_min_margin'] = 'Min. margin';
$_lang['goldprice.err_group_title'] = 'Enter a group title.';
$_lang['goldprice.err_group_weight'] = 'Group weight must be greater than zero.';
$_lang['goldprice.err_group_number'] = 'Invalid number in field [[+field]].';
$_lang['goldprice.log_group_update'] = 'Weight group "[[+title]]" was updated.';

$_lang['goldprice.price_product'] = 'Product';
$_lang['goldprice.price_weight'] = 'Weight, g';
$_lang['goldprice.price_group'] = 'Group';
$_lang['goldprice.price_cost'] = 'Cost';
$_lang['goldprice.price_sale'] = 'Sale';
$_lang['goldprice.price_buy'] = 'Buyout';
$_lang['goldprice.price_sale_frozen'] = 'Sale frozen';
$_lang['goldprice.price_buy_frozen'] = 'Buyout frozen';
$_lang['goldprice.price_updated'] = 'Updated';
$_lang['goldprice.buy_not_offered'] = 'buyout not offered';
$_lang['goldprice.cart_price_unavailable'] = 'The price is temporarily unavailable. Please try again later.';
$_lang['goldprice.cart_out_of_stock'] = 'This item is out of stock.';

$_lang['goldprice.settings_saved'] = 'Settings saved.';
$_lang['goldprice.err_settings'] = 'Component settings were not found.';
$_lang['goldprice.err_setting_number'] = 'Invalid number in setting [[+key]].';
$_lang['goldprice.err_setting_save'] = 'Could not save setting [[+key]].';
$_lang['goldprice.log_setting_change'] = '[[+user]] [[+when]] changed settings: [[+changes]]';

$_lang['goldprice.recipient_create'] = 'Add recipient';
$_lang['goldprice.recipient_update'] = 'Edit recipient';
$_lang['goldprice.recipient_remove_confirm'] = 'Delete this recipient?';
$_lang['goldprice.recipient_email'] = 'E-mail';
$_lang['goldprice.recipient_name'] = 'Name';
$_lang['goldprice.recipient_active'] = 'Active';
$_lang['goldprice.recipient_storm_on'] = 'Storm on';
$_lang['goldprice.recipient_storm_off'] = 'Storm off';
$_lang['goldprice.recipient_daily_limit'] = 'Daily limit';
$_lang['goldprice.recipient_api_error'] = 'API error';
$_lang['goldprice.recipient_new_request'] = 'New request';
$_lang['goldprice.err_email'] = 'Enter a valid e-mail.';
$_lang['goldprice.err_email_unique'] = 'A recipient with this e-mail already exists.';
$_lang['goldprice.mail_test'] = 'Send test e-mail';
$_lang['goldprice.mail_test_ok'] = 'Test e-mail sent to [[+email]].';
$_lang['goldprice.err_mail_test'] = 'Could not send the test e-mail.';

$_lang['goldprice.mail_title_storm_on'] = 'Storm mode on';
$_lang['goldprice.mail_title_storm_off'] = 'Storm mode off';
$_lang['goldprice.mail_title_daily_limit'] = 'Daily buyout limit exceeded';
$_lang['goldprice.mail_title_api_error'] = 'Quote API error';
$_lang['goldprice.mail_title_request_new'] = 'New buyout request';
$_lang['goldprice.mail_title_mail_test'] = 'GoldPrice test e-mail';
$_lang['goldprice.mail_test_body'] = 'This is a test notification. Seeing it means mail delivery works.';
$_lang['goldprice.mail_label_event'] = 'Event';
$_lang['goldprice.mail_label_time'] = 'Time';
$_lang['goldprice.mail_label_date'] = 'Date';
$_lang['goldprice.mail_label_change_pct'] = 'Quote change';
$_lang['goldprice.mail_label_groups'] = 'Groups';
$_lang['goldprice.mail_label_frozen'] = 'Frozen';
$_lang['goldprice.mail_label_sum'] = 'Sum';
$_lang['goldprice.mail_label_limit'] = 'Limit';
$_lang['goldprice.mail_label_message'] = 'Message';
$_lang['goldprice.mail_label_request_id'] = 'Request #';
$_lang['goldprice.mail_label_product'] = 'Product';
$_lang['goldprice.mail_label_price'] = 'Price';
$_lang['goldprice.mail_label_count'] = 'Qty';
$_lang['goldprice.mail_label_amount'] = 'Amount';
$_lang['goldprice.mail_label_name'] = 'Name';
$_lang['goldprice.mail_label_phone'] = 'Phone';
$_lang['goldprice.mail_label_email'] = 'E-mail';
$_lang['goldprice.mail_label_mgr_link'] = 'Manager link';
$_lang['goldprice.mail_freeze_sale'] = 'sale';
$_lang['goldprice.mail_freeze_buy'] = 'buyout';
$_lang['goldprice.mail_freeze_none'] = 'modes released';

$_lang['goldprice.request_created'] = 'Created';
$_lang['goldprice.request_product'] = 'Product';
$_lang['goldprice.request_price'] = 'Price';
$_lang['goldprice.request_count'] = 'Qty';
$_lang['goldprice.request_amount'] = 'Amount';
$_lang['goldprice.request_name'] = 'Name';
$_lang['goldprice.request_phone'] = 'Phone';
$_lang['goldprice.request_email'] = 'E-mail';
$_lang['goldprice.request_comment'] = 'Comment';
$_lang['goldprice.request_status'] = 'Status';
$_lang['goldprice.request_status_all'] = 'All statuses';
$_lang['goldprice.request_status_new'] = 'New';
$_lang['goldprice.request_status_processing'] = 'In progress';
$_lang['goldprice.request_status_done'] = 'Done';
$_lang['goldprice.request_status_cancelled'] = 'Cancelled';
$_lang['goldprice.err_request_status'] = 'Unknown request status.';
$_lang['goldprice.log_request_status'] = 'Request #[[+id]]: status [[+status]].';
$_lang['goldprice.log_request_new'] = 'New buyout request #[[+id]], amount [[+amount]].';
$_lang['goldprice.log_daily_limit'] = 'Daily buyout limit reached: [[+date]] [[+time]], sum [[+sum]].';
$_lang['goldprice.request_ok'] = 'Request accepted. We will contact you.';
$_lang['goldprice.err_deal_limit'] = 'The request amount exceeds the per-deal limit.';
$_lang['goldprice.err_request_name'] = 'Enter your name.';
$_lang['goldprice.err_request_phone'] = 'Enter your phone number.';
$_lang['goldprice.err_request_email'] = 'Enter a valid e-mail.';
$_lang['goldprice.err_request_count'] = 'Quantity must be between 1 and 1000.';
$_lang['goldprice.err_request_token'] = 'Session expired. Refresh the page and try again.';
$_lang['goldprice.err_request_product'] = 'Product is missing.';
$_lang['goldprice.err_request_buy_unavailable'] = 'Buyout is not available for this product right now.';
$_lang['goldprice.buyout_modal_title'] = 'Buyout request';
$_lang['goldprice.buyout_submit'] = 'Submit request';
$_lang['goldprice.buyout_product'] = 'Product';
$_lang['goldprice.buyout_price'] = 'Buyout price';

$_lang['goldprice.log_created'] = 'When';
$_lang['goldprice.log_event'] = 'Event';
$_lang['goldprice.log_user'] = 'User';
$_lang['goldprice.log_message'] = 'Message';
$_lang['goldprice.log_event_all'] = 'All events';
$_lang['goldprice.log_date_start'] = 'From';
$_lang['goldprice.log_date_end'] = 'To';
$_lang['goldprice.log_filter'] = 'Filter';
$_lang['goldprice.log_export'] = 'Export CSV';
