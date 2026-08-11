<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Action;
use Cartalyst\Sentinel\Native\Facades\Sentinel;

/**
 * История изменений схемы полей и HTML шаблона страницы/блока — поверх уже
 * существующей таблицы `actions` (общий аудит-лог CRUD по сущностям в этом
 * проекте), а не отдельного нового хранилища. Шаблон — не Eloquent-модель
 * (гибрид blade-файла на диске и JSON в settings), поэтому не проходит через
 * готовые Action::createEntity()/updateEntity() (которые ждут $entity->id и
 * $entity->entity_type) — пишем в ту же таблицу напрямую, с $entity_id = имя
 * шаблона (entity_id — varchar, не завязан на числовой id).
 */
trait RecordsTemplateRevisions
{
    /**
     * @param string $entity 'page_template_fields' | 'page_template_html' | 'block_template_fields' | 'block_template_html'
     * @param string $entity_id полное имя шаблона, например 'public.layouts.pages.about'
     * @param mixed $old_content предыдущее содержимое (объект схемы или HTML-строка), null если не было
     * @param mixed $new_content новое содержимое
     * @return void
     */
    protected function recordTemplateRevision(string $entity, string $entity_id, $old_content, $new_content): void {
        $user = Sentinel::check();

        if(!$user){
            return;
        }

        Action::create([
            'user_id' => $user->id,
            'action' => 'update',
            'entity' => $entity,
            'entity_id' => $entity_id,
            'old_data' => !empty($old_content) ? json_encode($old_content, JSON_UNESCAPED_UNICODE) : null,
            'new_data' => json_encode($new_content, JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * Последние revision'ы схемы полей и HTML шаблона вместе, новые сверху.
     *
     * @param string $fields_entity 'page_template_fields' | 'block_template_fields'
     * @param string $html_entity 'page_template_html' | 'block_template_html'
     * @param string $entity_id полное имя шаблона
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    protected function templateRevisions(string $fields_entity, string $html_entity, string $entity_id, int $limit = 30) {
        return Action::whereIn('entity', [$fields_entity, $html_entity])
            ->where('entity_id', $entity_id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}