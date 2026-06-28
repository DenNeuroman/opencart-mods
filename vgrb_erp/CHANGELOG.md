# Changelog — VGRB ERP Sync

## [2026-06-28] — vgrb_erp v1.0.0

### Added
- Новий модуль: VGRB ERP Sync — інтеграція OpenCart з VGRB AI-ERP API
- Реєстрація події `catalog/model/checkout/order/addOrder/after`
- Надсилання даних замовлення до VGRB ERP через HTTP POST
- Сторінка налаштувань в адмінці (endpoint, таймаут, статус)
- Мовні файли: uk-ua, en-gb
- Логування успішних запитів і помилок до error.log OpenCart