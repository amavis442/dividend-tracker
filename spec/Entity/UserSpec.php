<?php

namespace spec\App\Entity;

use App\Entity\User;
use PhpSpec\ObjectBehavior;

class UserSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(User::class);
    }

    function it_should_allow_set_and_get_email()
    {
        $this->setEmail('test@test.nl');
        $this->getEmail()->shouldReturn('test@test.nl');
    }

    function it_should_allow_set_and_get_password()
    {
        $this->setPassword('123password');
        $this->getPassword()->shouldReturn('123password');
    }

    function it_should_allow_set_and_get_roles()
    {
        $this->setRoles(['role1','role2']);
        $this->getRoles()->shouldReturn(['role1','role2','ROLE_USER']);
    }
}
