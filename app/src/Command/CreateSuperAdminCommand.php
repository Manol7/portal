<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-super-admin',
    description: 'Tworzy konto superadministratora.'
)]
final class CreateSuperAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        if (!$input->isInteractive()) {
            $io->error('Uruchom polecenie w trybie interaktywnym.');

            return Command::INVALID;
        }

        $email = $io->ask('Adres e-mail', null, static function ($value): string {
            $email = strtolower(trim((string) $value));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)
                || strlen($email) > 180) {
                throw new \RuntimeException('Podaj poprawny adres e-mail.');
            }

            return $email;
        });

        if ($this->users->findOneBy(['email' => $email])) {
            $io->error('Konto z tym adresem już istnieje.');

            return Command::FAILURE;
        }

        $question = new Question('Hasło (minimum 12 znaków): ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $question->setValidator(static function ($value): string {
            $password = (string) $value;

            if (mb_strlen($password) < 12) {
                throw new \RuntimeException('Hasło musi mieć minimum 12 znaków.');
            }

            return $password;
        });

        $password = $io->askQuestion($question);

        $confirmation = new Question('Powtórz hasło: ');
        $confirmation->setHidden(true);
        $confirmation->setHiddenFallback(false);

        if ($password !== $io->askQuestion($confirmation)) {
            $io->error('Hasła są różne. Uruchom polecenie ponownie.');

            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $password)
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Utworzono konto superadministratora: '.$email);

        return Command::SUCCESS;
    }
}