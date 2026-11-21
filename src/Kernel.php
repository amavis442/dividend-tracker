<?php

declare(strict_types=1);

namespace App;

use App\Doctrine\Type\EncryptedStringType;
use App\Security\SecretBox;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    public function boot(): void
    {
        parent::boot();

        // Doctrine instantiates column types itself; hand the encryption service to the type.
        // A container compiled before SecretBox existed (stale cache during a deploy) must
        // still boot, so cache:clear can rebuild it.
        $container = $this->getContainer();
        if ($container->has(SecretBox::class)) {
            EncryptedStringType::setSecretBox($container->get(SecretBox::class));
        }
    }

    public function process(ContainerBuilder $container): void
    {
        if ('test' === $this->environment) {
            // prevents the security token to be cleared
            $container->getDefinition('security.token_storage')->clearTag('kernel.reset');

            // prevents Doctrine entities to be detached
            $container->getDefinition('doctrine')->clearTag('kernel.reset');
        }
    }
}
