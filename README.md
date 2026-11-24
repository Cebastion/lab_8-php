# Лабораторна робота № 8

**Тема:** Laravel. Маршрутизація, контролери, Form Requests та API Resources
**Проєкт:** REST API для системи управління завданнями (Task Tracker)

## Мета

Навчитися робити повноцінний RESTful API у Laravel: налаштувати API-маршрути, написати CRUD-контролери, винести валідацію в класи Form Request та оформити відповіді сервера за допомогою API Resources.

## Структура файлів

```
routes/
    api.php                         (змінив)
app/Http/Controllers/
    ProjectController.php
    TaskController.php
    CommentController.php
app/Http/Requests/
    StoreProjectRequest.php   UpdateProjectRequest.php
    StoreTaskRequest.php      UpdateTaskRequest.php
    StoreCommentRequest.php   UpdateCommentRequest.php
app/Http/Resources/
    ProjectResource.php   TaskResource.php   CommentResource.php
```

Моделі, міграції, фабрики та сідери залишились з попередньої лабораторної роботи.

## Хід роботи

### Крок 0. Підготовка

Перед початком перевірив, що в моделях `Project`, `Task` та `Comment` задано `$fillable`, а база заповнена тестовими даними:

```
php artisan migrate:fresh --seed
```

Якщо в проєкті на Laravel 11+ немає файлу `routes/api.php`, його можна створити командою `php artisan install:api`.

### Крок 1. API-маршрути

У `routes/api.php` зареєстрував маршрути через `apiResource`:

```php
Route::apiResource('projects', ProjectController::class);
Route::apiResource('tasks', TaskController::class);
Route::apiResource('tasks.comments', CommentController::class)->shallow();
```

Метод `shallow()` робить вкладені маршрути коротшими: щоб отримати список коментарів або додати новий, потрібен `{task}`, а для перегляду, оновлення та видалення конкретного коментаря достатньо його `{comment}`.

Перевірив список маршрутів:

```
php artisan route:list --path=api
```

Для коментарів вийшло так:

| Метод       | URI                          | Дія                          |
| ----------- | ---------------------------- | ---------------------------- |
| GET         | `api/tasks/{task}/comments`  | `CommentController@index`    |
| POST        | `api/tasks/{task}/comments`  | `CommentController@store`    |
| GET         | `api/comments/{comment}`     | `CommentController@show`     |
| PUT / PATCH | `api/comments/{comment}`     | `CommentController@update`   |
| DELETE      | `api/comments/{comment}`     | `CommentController@destroy`  |

Для `projects` і `tasks` з'явилось по 5 стандартних маршрутів (index, store, show, update, destroy).

### Крок 2. Контролери

Створив контролери:

```
php artisan make:controller ProjectController --api
php artisan make:controller TaskController --api
php artisan make:controller CommentController --api
```

У кожному реалізував 5 методів:

- `index()` — повертає список із пагінацією (`paginate(10)`);
- `store()` — створює запис і повертає його зі статусом `201`;
- `show()` — повертає один запис за ID;
- `update()` — оновлює запис;
- `destroy()` — видаляє запис і повертає `204 No Content`.

Для коментарів `index()` і `store()` приймають `Task $task` з URL, тому коментар одразу прив'язується до потрібної задачі:

```php
$comment = $task->comments()->create($request->validated());
```

У `TaskController` зробив підтримку параметра `?include=`, щоб клієнт сам вибирав, які зв'язки підвантажувати. Дозволені лише `project`, `author`, `assignee`, `comments` та `comments.user`, усе інше ігнорується:

```php
private function includes(Request $request): array
{
    $requested = explode(',', (string) $request->query('include'));

    return array_values(array_intersect($requested, self::ALLOWED_INCLUDES));
}
```

### Крок 3. Form Requests

Створив класи для валідації:

```
php artisan make:request StoreProjectRequest
php artisan make:request UpdateProjectRequest
php artisan make:request StoreTaskRequest
php artisan make:request UpdateTaskRequest
php artisan make:request StoreCommentRequest
```

У всіх класах `authorize()` повертає `true`, а правила записані в `rules()`:

| Сутність | Поле          | Правила                                          |
| -------- | ------------- | ------------------------------------------------ |
| Project  | `name`        | `required\|string\|max:255`                      |
|          | `description` | `nullable\|string`                               |
|          | `user_id`     | `required\|integer\|exists:users,id`             |
| Task     | `title`       | `required\|string\|max:255`                      |
|          | `description` | `nullable\|string`                               |
|          | `status`      | `required\|in:new,in_progress,done`              |
|          | `project_id`  | `required\|exists:projects,id`                   |
|          | `author_id`   | `required\|exists:users,id`                      |
|          | `assigned_to` | `nullable\|exists:users,id`                      |
|          | `due_date`    | `nullable\|date\|after_or_equal:today`           |
| Comment  | `content`     | `required\|string\|max:1000`                     |
|          | `user_id`     | `required\|exists:users,id`                      |

У `Update...Request` до обов'язкових полів додав `sometimes`. Інакше запит `PATCH /api/tasks/1` лише з `status` не пройшов би валідацію, бо б вимагав усі поля.

Для оновлення коментаря створив ще й `UpdateCommentRequest` за тим самим принципом.

### Крок 4. API Resources

Створив ресурси:

```
php artisan make:resource ProjectResource
php artisan make:resource TaskResource
php artisan make:resource CommentResource
```

У `toArray()` кожного ресурсу описав поля відповіді. Зв'язки додаються тільки якщо вони вже завантажені, для цього використовується `whenLoaded`, тому зайвих запитів до бази немає:

```php
'project'  => new ProjectResource($this->whenLoaded('project')),
'comments' => CommentResource::collection($this->whenLoaded('comments')),
```

Дату `created_at` виводжу через `toDateTimeString()`, щоб вона була у вигляді `2026-11-24 12:00:00`.

### Крок 5. Тестування

Запустив сервер (`php artisan serve`) і перевірив запити в Postman. Обов'язково додав заголовок `Accept: application/json`, інакше при помилці валідації Laravel повертає редирект замість JSON.

| № | Запит                                    | Що перевіряв                         | Результат          |
| - | ---------------------------------------- | ------------------------------------ | ------------------ |
| 1 | `GET /api/tasks`                         | список задач із пагінацією           | `200 OK`           |
| 2 | `POST /api/tasks`                        | створення задачі з коректними даними | `201 Created`      |
| 3 | `POST /api/tasks` (некоректні дані)      | спрацювання валідації                | `422 Unprocessable`|
| 4 | `GET /api/tasks/1?include=comments`      | задача з коментарями                 | `200 OK`           |
| 5 | `PATCH /api/tasks/1`                     | зміна статусу                        | `200 OK`           |
| 6 | `DELETE /api/tasks/1`                    | видалення                            | `204 No Content`   |

Тіло для створення задачі:

```json
{
    "title": "Підготувати звіт",
    "description": "Квартальний звіт по проєкту",
    "status": "new",
    "project_id": 1,
    "author_id": 1,
    "assigned_to": 2,
    "due_date": "2026-12-01"
}
```

Для перевірки валідації відправив неправильні дані:

```json
{
    "title": "",
    "status": "wrong",
    "project_id": 999,
    "author_id": 1
}
```

У відповідь прийшла помилка `422` зі списком полів, які не пройшли перевірку:

```json
{
    "message": "The title field is required. (and 2 more errors)",
    "errors": {
        "title": ["The title field is required."],
        "status": ["The selected status is invalid."],
        "project_id": ["The selected project id is invalid."]
    }
}
```

Для зміни статусу достатньо було відправити:

```json
{
    "status": "in_progress"
}
```

Відповідь на `GET /api/tasks/1?include=comments` має таку структуру (скорочено):

```json
{
    "data": {
        "id": 1,
        "title": "Підготувати звіт",
        "status": "new",
        "due_date": "2026-12-01",
        "comments": [
            { "id": 1, "task_id": 1, "user_id": 2, "content": "...", "created_at": "..." }
        ],
        "created_at": "2026-11-24 12:00:00"
    }
}
```

Без `?include=comments` поля `comments` у відповіді немає, бо зв'язок не був завантажений.

Список `GET /api/tasks` крім масиву `data` містить ще блоки `links` та `meta` з даними пагінації (поточна сторінка, кількість записів тощо). Наступну сторінку можна отримати через `?page=2`.

## Висновки

У цій лабораторній роботі я зробив повноцінний REST API для проєкту Task Tracker. Налаштував маршрути через `apiResource` (включно з вкладеними `shallow`-маршрутами для коментарів), реалізував CRUD-контролери з пагінацією, виніс валідацію з контролерів у Form Request, а відповіді сервера оформив через API Resources з умовним завантаженням зв'язків. Усе це перевірив у Postman: коректні запити повертають потрібні статуси та JSON, а некоректні — помилку `422` з описом полів.
