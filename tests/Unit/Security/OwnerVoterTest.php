<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\Journal;
use App\Entity\User;
use App\Security\Voter\OwnerVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class OwnerVoterTest extends TestCase
{
    public function testTheOwnerIsGranted(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($this->user(1), $this->journalOf($this->user(1))));
    }

    public function testAnotherUserIsDenied(): void
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($this->user(1), $this->journalOf($this->user(2))));
    }

    public function testAnObjectWithoutOwnerIsDenied(): void
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($this->user(1), $this->journalOf(null)));
    }

    public function testAnAnonymousUserIsDenied(): void
    {
        $vote = (new OwnerVoter())->vote(new NullToken(), $this->journalOf($this->user(1)), [OwnerVoter::OWNER]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    public function testOtherAttributesAreIgnored(): void
    {
        $token = new UsernamePasswordToken($this->user(1), 'main', ['ROLE_USER']);

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            (new OwnerVoter())->vote($token, $this->journalOf($this->user(1)), ['EDIT'])
        );
    }

    private function vote(User $user, object $subject): int
    {
        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);

        return (new OwnerVoter())->vote($token, $subject, [OwnerVoter::OWNER]);
    }

    private function user(int $id): User
    {
        $user = new User();
        (new \ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }

    private function journalOf(?User $user): Journal
    {
        return $this->createConfiguredStub(Journal::class, ['getUser' => $user]);
    }
}
