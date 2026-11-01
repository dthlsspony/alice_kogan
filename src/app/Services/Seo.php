<?php

namespace App\Services;

/**
 * SEO-данные текущей страницы: контроллеры заполняют, layout рендерит
 * title/description/canonical/og. Пустые значения заменяются дефолтными.
 */
class Seo
{
    public ?string $title = null;

    public ?string $description = null;

    public ?string $image = null;

    public function set(?string $title, ?string $description = null, ?string $image = null): static
    {
        $this->title = $title !== null ? trim(html_entity_decode($title, ENT_QUOTES, 'UTF-8')) : null;
        $this->description = $description !== null
            ? trim(html_entity_decode(strip_tags($description), ENT_QUOTES, 'UTF-8'))
            : null;
        $this->image = $image;

        return $this;
    }

    public function title(): string
    {
        return $this->title !== null && $this->title !== ''
            ? $this->title
            : 'Сайт посвящён И. М. Когану и исследованиям в области парапсихологии';
    }

    public function description(): string
    {
        return $this->description !== null && $this->description !== ''
            ? mb_substr($this->description, 0, 300)
            : 'Исследования И. М. Когана в области парапсихологии и биоэнергоинформатики: статьи, библиотека, семинары, конференции, система БИОЛ.';
    }
}
