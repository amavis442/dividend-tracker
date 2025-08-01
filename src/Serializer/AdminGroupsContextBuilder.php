<?php

// src/Serializer/AdminGroupsContextBuilder.php

namespace App\Serializer;

use ApiPlatform\State\SerializerContextBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use App\Entity\Ticker;

final class AdminGroupsContextBuilder implements
	SerializerContextBuilderInterface
{
	private $decorated;
	private $authorizationChecker;

	public function __construct(
		SerializerContextBuilderInterface $decorated,
		AuthorizationCheckerInterface $authorizationChecker
	) {
		$this->decorated = $decorated;
		$this->authorizationChecker = $authorizationChecker;
	}

	public function createFromRequest(
		Request $request,
		bool $normalization,
		?array $extractedAttributes = null
	): array {
		$context = $this->decorated->createFromRequest(
			$request,
			$normalization,
			$extractedAttributes
		);
		$resourceClass = $context['resource_class'] ?? null;

		if (
			$resourceClass === Ticker::class &&
			isset($context['groups']) &&
			$this->authorizationChecker->isGranted('ROLE_ADMIN') &&
			false === $normalization
		) {
			$context['groups'][] = 'admin:input';
		}

		return $context;
	}
}
