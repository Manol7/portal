<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261030193000 extends AbstractMigration
{
    private const EN = [
        'nav.aria' => 'Breadcrumbs',
        'nav.login' => 'Sign in',
        'nav.portal' => 'Portal',
        'nav.users' => 'Users',
        'nav.translations' => 'Translations',
        'header.admin' => 'Admin',
        'header.logout' => 'Log out',
        'header.login' => 'Sign in',
        'lang.to_pl' => 'Switch to Polish',
        'lang.to_en' => 'Switch to English',
        'toast.aria' => 'Notifications',
        'toast.close' => 'Close notification',
        'footer.status' => 'Status: OK',
        'modal.close' => 'Close',

        'role.ROLE_USER' => 'User',
        'role.ROLE_ADMIN' => 'Administrator',
        'role.ROLE_SUPER_ADMIN' => 'Super administrator',

        'login.title' => 'Enter the portal',
        'login.hero_aria' => 'Portal',
        'login.hero_label' => 'Good to see you :)',
        "login.hero_title" => "Your own\nspace.",
        'login.hero_text' => 'Everything that matters, in one calm place. No noise. No unnecessary steps.',
        'login.label' => 'Sign in',
        'login.heading' => 'Come on in.',
        'login.intro' => 'Sign in to continue your work.',
        'login.logged_as' => 'Signed in as {user}.',
        'login.logout' => 'Log out',
        'login.email' => 'Email address',
        'login.password' => 'Password',
        'login.show' => 'Show',
        'login.hide' => 'Hide',
        'login.show_aria' => 'Show password',
        'login.hide_aria' => 'Hide password',
        'login.submit' => 'Enter the portal',
        'login.secure' => 'End-to-end encrypted connection',
        'login.error' => 'Invalid email address or password.',

        'home.title' => 'Portal',
        'home.label' => 'Main panel',
        'home.heading' => "Welcome\nback.",
        'home.text' => 'Your workspace. Simple and to the point.',
        'home.center' => 'Hub',
        'home.quick' => 'Quick actions',
        'home.users' => 'Users',
        'home.translations' => 'Translations',
        'home.more' => 'More features',
        'home.soon' => 'Coming soon',

        'users.title' => 'Users — Portal',
        'users.label' => 'Administration panel',
        'users.heading' => 'Users.',
        'users.table_title' => 'Users',
        'users.search_placeholder' => 'Search by ID, email or role',
        'users.search_aria' => 'Search users',
        'users.count' => 'Number of users: {count}',
        'users.add_aria' => 'Add user',
        'users.col_id' => 'ID',
        'users.col_email' => 'Email',
        'users.col_role' => 'Role',
        'users.col_actions' => 'Actions',
        'users.role_aria' => 'Change role of user {email}',
        'users.edit' => 'Edit details',
        'users.delete' => 'Delete',
        'users.none' => 'No users.',
        'users.no_results' => 'No results.',

        'create.label' => 'New entry',
        'create.title' => 'Add user',
        'create.submit' => 'Create account',
        'delete.label' => 'Warning',
        'delete.title' => 'Delete account?',
        'delete.text_before' => 'The account',
        'delete.text_after' => 'will be permanently deleted. This cannot be undone.',
        'delete.cancel' => 'Cancel',
        'delete.confirm' => 'Delete account',
        'edit.label' => 'Account',
        'edit.title' => 'Edit details',
        'edit.email' => 'Email address',
        'edit.password' => 'New password (optional)',
        'edit.password_confirm' => 'Repeat password',
        'edit.submit' => 'Save changes',

        'form.email' => 'Email address',
        'form.role' => 'Role',
        'form.password' => 'Password',
        'form.password_repeat' => 'Repeat password',
        'form.password_mismatch' => 'The passwords must match.',
        'form.password_min' => 'The password must be at least {{ limit }} characters long.',
        'form.email_taken' => 'An account with this email address already exists.',

        'flash.created' => 'User account created.',
        'flash.role_changed' => 'User role changed.',
        'flash.saved' => 'User changes saved.',
        'flash.deleted' => 'User account deleted.',
        'flash.csrf' => 'Invalid CSRF token.',
        'flash.own_role' => 'You cannot change your own role.',
        'flash.role_invalid' => 'The selected role is invalid.',
        'flash.role_failed' => 'Could not change the role.',
        'flash.role_failed_refresh' => 'Could not change the role. Refresh the page and try again.',
        'flash.own_delete' => 'You cannot delete your own account.',
        'flash.bad_data' => 'The submitted data is invalid.',
        'flash.email_blank' => 'Enter an email address.',
        'flash.email_invalid' => 'The email address is invalid.',
        'flash.email_long' => 'The email address is too long.',
        'flash.email_used' => 'This email address is already in use.',
        'flash.password_mismatch' => 'The passwords do not match.',

        'tr.title' => 'Translations — Portal',
        'tr.label' => 'Administration panel',
        'tr.heading' => 'Translations.',
        'tr.search' => 'Search text or key',
        'tr.col_key' => 'Key',
        'tr.col_pl' => 'Polski',
        'tr.col_en' => 'English',
        'tr.fallback' => 'Missing — Polish will be used',
        'tr.no_results' => 'No results.',
        'tr.save' => 'Save translations',
        'tr.saved' => 'Translations saved.',
    ];

    public function getDescription(): string
    {
        return 'Angielskie tłumaczenia interfejsu.';
    }

    public function up(Schema $schema): void
    {
        // INSERT IGNORE nie nadpisuje tłumaczeń już zapisanych ręcznie.
        foreach (self::EN as $key => $value) {
            $this->addSql(
                'INSERT IGNORE INTO translation (locale, message_key, value) VALUES (?, ?, ?)',
                ['en', $key, $value],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM translation WHERE locale = 'en'");
    }
}
