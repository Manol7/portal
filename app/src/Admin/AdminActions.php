<?php

namespace App\Admin;

use App\Repository\ContactMessageRepository;

/**
 * Lista akcji na stronie /admin. Nowa akcja = kolejny wpis w list() (trasa, klucze tekstów, opcjonalny licznik).
 */
final class AdminActions
{
    public function __construct(private readonly ContactMessageRepository $messages)
    {
    }

    /**
     * @return list<array{route: string, title: string, text: string, badge: int}>
     */
    public function list(): array
    {
        return [
            ['route' => 'app_admin_users', 'title' => 'admin.users.title', 'text' => 'admin.users.text', 'badge' => 0],
            ['route' => 'app_admin_translations', 'title' => 'admin.translations.title', 'text' => 'admin.translations.text', 'badge' => 0],
            ['route' => 'app_admin_messages', 'title' => 'admin.messages.title', 'text' => 'admin.messages.text', 'badge' => $this->messages->countUnread()],
            ['route' => 'app_admin_mailer', 'title' => 'admin.mailer.title', 'text' => 'admin.mailer.text', 'badge' => 0],
        ];
    }
}
