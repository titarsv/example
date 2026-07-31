<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

Тестирование API локально: ngrok http http://nnn-site.lh:80 --host-header=example.lh

Генерация миниатюр: php artisan queue:work
Проверка загруженности видеокарты: nvidia-smi
Проверка использования ollama: ollama ps

## Backlog / TODO

### Асинхронная генерация миниатюр (не включена локально)

**Проблема:** страница `/search?text=iPhone` при живом тестировании заняла 26 секунд и 519 SQL-запросов (см. секцию про семантический поиск ниже) — из-за синхронной генерации превью изображений для товаров, которые раньше не отображались в данном размере/обрезке.

**Где:** `app/Models/File.php:874`, метод `createThumbnail()`:

```php
if(env('APP_DEBUG')){
    return $image->createThumbnail($size, $crop);   // синхронно, внутри запроса
}else{
    GenerateThumbnailJob::dispatch($image, $size, $crop);   // асинхронно, через очередь
}
```

Асинхронный путь уже реализован (`GenerateThumbnailJob`) и включается при `APP_DEBUG=false`. В текущем `.env` этого окружения `APP_DEBUG=true`, поэтому локально всегда используется синхронная ветка — любая страница, где встречается ранее не сгенерированный размер картинки (для десятков товаров сразу), тормозит на генерации файлов и записи в БД прямо во время рендера.

**Почему это важный backlog-пункт, а не просто dev-неудобство:**
- Конфигурационный риск: если `APP_DEBUG=true` случайно окажется на проде (например, забыли переключить при деплое) — сайт получит те же 20-30-секундные загрузки страниц на любом "холодном" наборе товаров, включая настоящих покупателей.
- Даже в асинхронном режиме (`APP_DEBUG=false`) первый посетитель, запросивший ранее не сгенерированный размер, всё равно увидит заглушку/оригинал, пока джоба не отработает — нет прогрева миниатюр заранее (например, при импорте/создании товара).

**Возможные направления решения** (не проработаны, требуют отдельного обсуждения):
- Прогревать основные размеры миниатюр сразу при загрузке/импорте изображения (через очередь), а не откладывать до первого показа.
- Пересмотреть, действительно ли синхронная генерация в `APP_DEBUG=true` нужна — возможно, для локальной разработки достаточно асинхронного пути с индикатором "миниатюра ещё не готова" вместо блокировки запроса.
- Добавить защиту от случайного `APP_DEBUG=true` на проде на уровне деплоя/чек-листа.

## Рекомендации "Часто покупают вместе" (bought together)

Блок рекомендаций на карточке товара, построенный на основе истории совместных покупок (collaborative filtering), без ML и без внешних API — только Redis.

### Как это работает

1. **Источник данных** — таблица `order_products` (`order_id, product_id, qty, price, sale, data`), отфильтрованная по подтверждённым заказам (`orders.status_id` в `[2,3,4,5,6]`, то есть исключая "Новый" и "Отменён").
   - **Обновление 2026-07-31:** изначально `order_products` в проекте была объявлена (`Order::products()`), но не заполнялась — реальные товары заказа хранились только в JSON-колонке `orders.products`, а расчёт пар делался парсингом JSON в PHP. Теперь `order_products` — структурированный источник: `Order::syncOrderProducts()` (хук в `Order::boot()`, на `created` и на `updated`, если `products` изменился) зеркалирует JSON в таблицу при каждом сохранении заказа. Разовый бэкфилл для уже существующих заказов — `php artisan orders:sync-order-products`.
2. **Расчёт пар** — SQL self-join `order_products` самой на себя по `order_id` (с дедупликацией `(order_id, product_id)` перед join — один товар может встречаться в заказе несколькими строками из-за вариаций, без дедупликации счётчик задваивался бы), группировка по паре id, `COUNT(*)` как сила связи. Больше никакого парсинга JSON в PHP — одна SQL-агрегация вместо цикла по заказам.
3. **Хранение** — счётчики пишутся в Redis sorted set `bought_with_{product_id}`, где member — id связанного товара, score — количество совместных заказов. Тот же подход, что уже используется в проекте для `popularity`/`prices`/`sort_priority` (см. `Product::boot()`).
4. **Обновление данных** — два механизма одновременно:
   - **Инкрементально** — в момент подтверждения заказа (`Order::boot()`, переход `status_id` из `1` в `[2,3,4]`) в очередь ставится `App\Jobs\UpdateBoughtTogetherJob`, которая сразу увеличивает счётчики пар для товаров этого заказа (`zincrby`). Продукты для неё по-прежнему берутся напрямую из уже decoded JSON в том же хуке (данные уже под рукой, лишний поход в `order_products` не нужен) — джоба сама дедуплицирует id.
   - **Полным пересчётом** — ежедневно в 03:00 по расписанию (`app/Console/Kernel.php`) для подстраховки (перезапуски воркеров, ручные правки статусов в админке и т.п.), теперь через SQL по `order_products`.
5. **Отображение** — `Product::getBoughtTogether($limit, $only_in_stock)` читает id из Redis (`zrevrange`, уже отсортированы по релевантности), подгружает товары через Eloquent с фильтром `visible=1`. Используется в `ProductsController::showAction` → `resources/views/public/product.blade.php`, блок "Frequently bought together" под "You may also like". Блок скрывается сам, если для товара ещё нет накопленных пар.

### Консольные команды

- `php artisan orders:sync-order-products` — разовый бэкфилл: заполняет `order_products` из JSON `orders.products` для заказов, созданных до этого изменения. Новые/изменённые заказы синхронизируются автоматически хуком в `Order::boot()`, повторный запуск не требуется.
  - `--chunk=500` — размер чанка при постраничной выборке заказов.
- `php artisan recommendations:bought-together` — полный пересчёт всех пар с нуля (очищает старые ключи `bought_with_*` в Redis и строит заново по `order_products`). Требует, чтобы `order_products` уже был заполнен (бэкфилл выше) — иначе посчитает по пустой/неполной таблице.
- Команда зарегистрирована в планировщике (`php artisan schedule:run`, должен быть настроен cron) — пересчитывается ежедневно в 03:00, отдельно запускать руками для этого не нужно.

### Файлы

- `app/Models/Order.php` (`syncOrderProducts()`, хуки `created`/`updated` в `boot()`) — заполнение `order_products` из JSON.
- `app/Console/Commands/SyncOrderProducts.php` — разовый бэкфилл для существующих заказов.
- `app/Console/Commands/BuildBoughtTogetherRecommendations.php` — команда полного пересчёта (SQL по `order_products`).
- `app/Jobs/UpdateBoughtTogetherJob.php` — инкрементальное обновление в очереди.
- `app/Models/Product.php` (`getBoughtTogether()`) — чтение рекомендаций.
- `app/Http/Controllers/ProductsController.php` (`showAction()`) — передача данных во вью.
- `resources/views/public/product.blade.php` — блок отображения.

## Семантический поиск по товарам (эмбеддинги, PoC)

Поиск по смыслу запроса, а не только по буквальному совпадению слов (в отличие от текущего `Product::search()`, который использует `LIKE '%...%'`). Реализовано как PoC без новой инфраструктуры — эмбеддинги строятся локально через уже используемый в проекте Ollama и хранятся в MySQL, похожесть считается косинусным сходством в PHP (brute-force). Это сознательный компромисс для проверки качества на реальных данных, прежде чем вкладываться в отдельную векторную БД (Qdrant/Meilisearch) — см. секцию ниже про масштабирование.

### Как это работает

1. **Модель эмбеддингов** — `bge-m3` через Ollama (`OLLAMA_EMBEDDING_MODEL` в `.env`), выбрана осознанно вместо уже имевшегося `nomic-embed-text`, потому что каталог мультиязычный (в проекте минимум `ru`/`ua`), а `bge-m3` качественно работает с русским/украинским, в отличие от англоцентричных моделей. Для Gemini как альтернативного провайдера (`AI_PROVIDER=gemini`) используется `text-embedding-004`.
2. **Документ товара** — `Product::getEmbeddingText()` собирает название + категорию + видимые атрибуты (название: значение) + описание (обрезано до 2000 символов) **для текущей локали приложения**. Эмбеддинг строится отдельно на каждую локаль из `config('app.locales')`, а не один общий — так точнее учитываются языковые нюансы формулировок.
3. **Хранилище** — таблица `product_embeddings` (`product_id`, `locale`, `model`, `dimensions`, `vector` — JSON-массив чисел в `LONGTEXT`). Уникальность по `(product_id, locale)` — при смене модели эмбеддингов нужен полный пересчёт (старые векторы несовместимы по размерности/смыслу с новыми).
4. **Индексация**:
   - **Инкрементально** — хук в `Product::boot()` (`created`) ставит `App\Jobs\GenerateProductEmbeddingJob` в очередь для каждой локали при создании нового товара. Джоба асинхронная, поэтому не мешает то, что в контроллере локализация (название/описание) сохраняется отдельным вызовом уже после `$product->save()` — к моменту, когда воркер очереди возьмёт задачу, данные уже в базе.
   - **Правки существующих товаров** (перевод, атрибуты, описание) под хук не подпадают — это осознанное упрощение PoC. После массовых правок/импорта нужно вручную перезапустить индексацию (см. команды ниже).
   - **Подстраховка по расписанию** — `embeddings:build-products --only-missing` еженедельно (`app/Console/Kernel.php`), чтобы подхватить товары, для которых джоба почему-то не отработала (воркер был выключен и т.п.). Это дешёвая проверка "докатить недостающее", а не полный пересчёт (полный пересчёт стоит реального времени инференса на каждый товар, гонять его каждую ночь как `bought-together` не нужно).
5. **Поиск** — `Product::semanticSearch($query, $limit, $onlyInStock)`: строит эмбеддинг запроса, берёт все эмбеддинги товаров текущей локали (с фильтром `visible=1`), считает косинусное сходство, сортирует. Для PoC-масштаба (сотни-первые тысячи товаров) это быстро; при росте каталога нужно переходить на векторный индекс (см. ниже).

### Консольные команды

- `php artisan embeddings:build-products` — (пере)строить эмбеддинги для всех видимых товаров, по всем локалям. Без флагов пересчитывает всё — нужно после смены модели эмбеддингов или массовых правок контента.
  - `--only-missing` — ставить в очередь только товары, для которых эмбеддинга ещё нет (быстрая донасыпка, используется в еженедельном расписании).
  - `--limit=N` — ограничить количество товаров (для тестового прогона).
- `php artisan products:semantic-search "запрos" --locale=ru --limit=10` — прогон поиска прямо из консоли для проверки качества результатов без похода во фронтенд.

Обеим командам, как и джобе, приходится вручную регистрировать `Relation::morphMap()` в начале выполнения — `AppServiceProvider::boot()` пропускает эту регистрацию в консольном контексте (`runningInConsole()`), это уже существующий паттерн в проекте (см. `BatchGenerateMetadata` и другие консольные команды), без него `$product->name`/`->description` будут пустыми при обращении из artisan/очереди.

### Проверено на реальных данных

На 20 товарах (iPhone разных модификаций) семантический поиск корректно улавливает смысл, которого нет в тексте буквально: запрос "самый мощный флагман с лучшей камерой" поднял наверх Pro Max модели, "белый айфон с большим объёмом памяти" — белые/титановые варианты с 512GB/1TB. Ожидаемое ограничение: эмбеддинг не видит цену (это не часть текста), поэтому запросы вида "бюджетный вариант" работают слабее — сортировку по цене нужно оставлять на структурных фильтрах (Redis `prices` zset), а не ждать этого от семантики.

### Проблемы качества, найденные на полном каталоге (2026-07-31)

Первая проверка была на 20 товарах одной категории (iPhone) — картина оказалась неполной. После индексации всего каталога (2878 эмбеддингов) реальный запрос **"самая крутая мобила"** вернул в топе клавиатуры и другую периферию, а айфоны оказались в конце списка. Разобрал на реальных данных, нашлись две разные причины:

1. **Мусорные эмбеддинги у непереведённых товаров.** 32 товара (в основном клавиатуры/аксессуары) не имели `ru`-перевода названия — в текст эмбеддинга попадало только название категории ("Apple"), и такой огрызок текста давал непредсказуемо высокое сходство со случайными запросами. **Исправлено**: `GenerateProductEmbeddingJob` теперь пропускает индексацию, если у товара нет названия на данной локали (`app/Jobs/GenerateProductEmbeddingJob.php`); 32 уже существовавших мусорных эмбеддинга удалены вручную.
2. **Шаблонный маркетинговый текст в описаниях забивал специфичный сигнал.** У 39% товаров (`ru`) и 21% (`ua`) описание представляет собой **целиком** повторяющийся рекламный шаблон без уникального контента ("Заказывай на сайте ➦ забирай сегодня! Доступная цена на [товар] ✔ Кэшбек ✔ Доставка..."/укр. аналог). Включение этого текста в эмбеддинг сближало по смыслу совершенно разные товары на основе общего "рекламного тона", а не реальных характеристик. **Исправлено**: `Product::stripMarketingBoilerplate()` (`app/Models/Product.php`) вырезает известные шаблонные фразы (RU/UA) перед построением эмбеддинга; 302 товара переиндексированы.

**Результат после обоих фиксов** — тот же запрос "самая крутая мобила": было — клавиатура на #1 и #6, айфоны вперемешку; стало — топ-3 позиции чисто iPhone, дальше аксессуары к телефону (мышь, чехол), что уже разумная деградация, а не случайная периферия. Контрольный запрос "самый крутой смартфон" (без сленга) как был, так и остался почти полностью из iPhone.

**Вывод на будущее**: словарный сленг/разговорная лексика в запросах — это всё ещё более слабое место модели `bge-m3`, чем стандартная лексика (сравнение "мобила" vs "смартфон" показало разницу). Осталось не исправлено осознанно (см. обсуждение вариантов — нормализация запроса через LLM была отклонена в пользу более общего фикса с шаблонным текстом). Также стоит проверить каталог на другие похожие маркетинговые шаблоны за пределами найденных двух формулировок — регэксп в `stripMarketingBoilerplate()` не претендует на исчерпывающий охват.

### Подключено к публичному поиску (гибрид)

Реальная страница `/search` использует не `Product::search()`, а `Filter::getRedisProducts()` (Redis-путь захардкожен как активный, `$enable_redis = true`) — кандидаты по ключевым словам (`$search_ids`) там пересекаются (`array_intersect`) с уже отфильтрованным по Redis-битмапам списком товаров. Это и есть точка внедрения: `Filter::getSemanticSearchIds()` (`app/Models/Filter.php`) добавляет семантические id к `$search_ids` **только когда буквальных совпадений мало** (`< 5`) — так самые частые запросы (которые и так находятся по ключевым словам) не платят задержку похода в Ollama, а семантика подключается именно там, где keyword-поиск проседает. При недоступности/таймауте AI-сервиса (таймаут снижен до 4 сек специально для веб-контекста, `embed($text, $timeoutSeconds)`) поиск по ключевым словам продолжает работать как раньше — деградация мягкая.

`livesearch()` (автодополнение, дёргается на каждое нажатие клавиши) семантику сознательно не получил — задержка похода в AI-сервис неприемлема для live-typing UX, там и так работает быстрый LIKE.

**Важная находка при тестировании на реальном трафике (debugbar, `storage/debugbar/*.json`):** страница `/search?text=iPhone` (без семантики — по ключевым словам нашлось 417 совпадений, порог `<5` не сработал) заняла 26 секунд и 519 SQL-запросов. Источник — **не мой код**: 180 `SELECT`+120 `INSERT` в таблицу `images` — это синхронная генерация превью на лету для товаров, у которых ещё нет закэшированного варианта картинки под нужный размер (при том, что в проекте это должно уходить в очередь — см. заметку "Генерация миниатюр: php artisan queue:work" выше). Семантический поиск просто впервые вывел в список товары, которые раньше не отображались в этом контексте — и вскрыл существующую проблему, не связанную с эмбеддингами. Изолированный тест самого `Filter::getProducts()` (без HTTP/рендера) на том же запросе занял 249 мс. Стоит завести отдельную задачу на прогрев/асинхронную генерацию миниатюр — вне рамок этой фичи.

### Что дальше

- **Масштабирование** — brute-force cosine в PHP по всем эмбеддингам локали работает, пока товаров не десятки тысяч. При росте каталога — миграция на Qdrant или Meilisearch (обсуждалось ранее): те же векторы, тот же `getEmbeddingText()`, меняется только слой хранения/поиска.
- **Порог `< 5` для включения семантики** подобран на глаз, не откалиброван на реальных данных — возможно, стоит сделать настраиваемым или заменить на что-то более умное (например, учитывать релевантность keyword-совпадений, а не только их количество).
- **Персонализация** (лог просмотров → рекомендации с учётом истории конкретного пользователя, с fallback на популярность для холодного старта) — отдельный следующий шаг, ещё не сделан.

### Файлы

- `app/Services/AiServiceInterface.php`, `OllamaService.php`, `GeminiService.php` (`embed()`) — построение вектора текста.
- `config/services.php` (`ollama.embedding_model`, `gemini.embedding_model`).
- `database/migrations/2026_07_31_000000_create_product_embeddings_table.php`.
- `app/Models/ProductEmbedding.php`.
- `app/Jobs/GenerateProductEmbeddingJob.php` — построение и сохранение эмбеддинга одного товара.
- `app/Console/Commands/BuildProductEmbeddings.php`, `TestSemanticSearch.php`.
- `app/Models/Product.php` (`getEmbeddingText()`, `semanticSearch()`, `cosineSimilarity()`, хук в `boot()`).
- `app/Models/Filter.php` (`getSemanticSearchIds()`, вызов внутри `getRedisProducts()`) — подключение семантики к реальной странице `/search`.

## Похожие товары через эмбеддинги (fallback для "You may also like")

На карточке товара уже был блок "You may also like" на основе вручную подобранных `similar_products` (`Product::similar()`, `belongsToMany`) — но у большинства товаров эта подборка не заполнена (администратор физически не успевает проставить её на весь каталог), поэтому блок обычно просто не показывался (`@if($similar->count())`).

**Решение** — не третий отдельный блок, а fallback внутри уже существующего: если у товара нет вручную подобранных `similar_products`, `ProductsController::showAction()` (`app/Http/Controllers/ProductsController.php`) подставляет туда `Product::getSimilarByEmbedding(8)` — ближайшие по косинусному сходству товары на основе тех же эмбеддингов, что уже строятся для семантического поиска. Разметка/JS блока не менялась, дублирования UI нет.

Общая логика ранжирования (взять вектор → найти ближайшие эмбеддинги той же локали → отфильтровать по `visible` → отсортировать по косинусному сходству → подгрузить товары) вынесена в `Product::rankProductsByVector()` — переиспользуется и в `semanticSearch()` (вектор запроса), и в `getSimilarByEmbedding()` (вектор самого товара, с исключением себя из выдачи).

Проверено на реальных данных: товар без ручной подборки (Apple iPhone 16 Pro Max 256GB Desert Titanium) теперь показывает в "You may also like" другие модели той же линейки Pro/Pro Max — семантически корректно.

### Файлы

- `app/Models/Product.php` (`getSimilarByEmbedding()`, `rankProductsByVector()`).
- `app/Http/Controllers/ProductsController.php` (`showAction()`) — fallback-логика при пустой ручной подборке.

## Персональные рекомендации на главной ("Shop Customer Favorites")

Секция на главной странице существовала в вёрстке, но была закомментирована и ничем не заполнялась (`resources/views/public/layouts/pages/home.blade.php`, оба `@foreach` — по `$favorites` и по `$fields['favorites']` — были в блоке `{{-- --}}`).

**Источник сигнала — уже существующий механизм**, не новая инфраструктура: на странице товара (`ProductsController::showAction()`) давно пишется cookie `viewed` (последние просмотренные id, использовалась только для виджета "Недавно просмотренные"). Эта же cookie теперь читается на главной (`PagesController::homeHtml()`) для построения профиля интересов.

**Как это работает:**
1. `Product::getPersonalizedRecommendations($viewedIds, $limit)` (`app/Models/Product.php`) — берёт эмбеддинги просмотренных товаров текущей локали, усредняет их в один "вектор интересов", находит ближайшие к нему товары через уже существующий `rankProductsByVector()`, исключая сами просмотренные.
2. **Холодный старт** — если cookie пустая (первый визит) или эмбеддингов для просмотренных товаров ещё нет (не проиндексированы), метод возвращает пустую коллекцию, и `PagesController::homeHtml()` откатывается на прежнее поведение — топ по популярности (`orderBy('popularity', 'desc')`), которое и было изначально запланировано для этой секции.
3. Вьюха просто раскомментирована — переменная `$favorites` та же, что и была, изменилась только реализация за ней.

**Важный нюанс при тестировании:** cookie `viewed` устанавливается через `cookie()->forever(...)`, то есть проходит через `EncryptCookies` middleware Laravel — при проверке через curl нельзя просто подставить сырое значение (`-b "viewed=..."`), оно не расшифруется и будет проигнорировано. Тестировать нужно через cookie jar с реальным визитом страницы товара (`curl -c cookies.txt` → потом `curl -b cookies.txt`), как это делает браузер.

**Проверено на реальных данных:** после визита товара "Apple iPhone 14 128GB Темная ночь" (id 371) блок на главной показал именно iPhone 14-варианты (256GB Midnight, 14 Plus Midnight, 128GB Starlight и т.д.) — совпало и при прямом вызове метода, и сквозным HTTP-тестом с cookie jar.

Кэш главной страницы (`cache()->remember('home_page', ...)`) персонализации не мешает — он применяется только для `Chrome-Lighthouse` (`Helper::isLighthouse()`), обычные посетители всегда получают свежий рендер.

### Что дальше

- Сейчас профиль строится только по `viewed` (что открыл), без сигналов "что купил"/"что добавил в корзину" — можно усилить, взвешивая покупки выше просмотров.
- Порог/количество просмотренных товаров, участвующих в профиле, не ограничено и не свежее последних — cookie хранит только последние ~8 (see `showAction()`), этого достаточно для PoC.

### Файлы

- `app/Models/Product.php` (`getPersonalizedRecommendations()`).
- `app/Http/Controllers/PagesController.php` (`homeHtml()`) — чтение cookie, вызов рекомендаций, fallback на популярность.
- `resources/views/public/layouts/pages/home.blade.php` — раскомментирован вывод `$favorites`.
