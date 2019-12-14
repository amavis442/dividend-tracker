<?php

namespace spec\App\Entity;

use App\Entity\Branch;
use App\Entity\User;
use PhpSpec\ObjectBehavior;

class BranchSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Branch::class);
    }

    function it_should_default_to_zero_for_tickers()
    {
        $this->getTickers()->count()->shouldReturn(0);
    }

    function it_should_allow_to_set_user()
    {
        $user = new User();
        $user
            ->setEmail('test@test.nl')
            ->setPassword('123password');

        $this->setUser($user);
        
        $this->getUser()->shouldHaveType(User::class);
    }
}
