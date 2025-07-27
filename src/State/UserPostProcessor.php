<?php
// src/State/UserPostProcesser.php

namespace App\State;

use App\Entity\User;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPostProcessor implements ProcessorInterface
{
	public function __construct(
		#[
			Autowire(
				service: 'api_platform.doctrine.orm.state.persist_processor'
			)
		]
		private ProcessorInterface $persistProcessor,
		#[
			Autowire(
				service: 'api_platform.doctrine.orm.state.remove_processor'
			)
		]
		private ProcessorInterface $removeProcessor,
		private UserPasswordHasherInterface $passwordHasher
	) {
	}

	/**
	 * Processes User data for POST/PUT and DELETE operations.
	 * - Hashes the password before persisting if it's a User instance.
	 * - Delegates persistence to the persist processor.
	 * - Delegates deletion to the remove processor.
	 *
	 * @param mixed $data The resource being processed.
	 * @param Operation $operation The API Platform operation metadata.
	 * @param array $uriVariables Path or identifier variables.
	 * @param array $context Additional context about the request.
	 *
	 * @return mixed The processed data, typically the persisted or removed resource.
	 */
	public function process(
		mixed $data,
		Operation $operation,
		array $uriVariables = [],
		array $context = []
	): mixed {
		if ($operation instanceof DeleteOperationInterface) {
			return $this->removeProcessor->process(
				$data,
				$operation,
				$uriVariables,
				$context
			);
		}

		if ($data instanceof User) {
			$hashedPassword = $this->passwordHasher->hashPassword(
				$data,
				$data->getPassword()
			);
			$data->setPassword($hashedPassword);
		}
		return $this->persistProcessor->process(
			$data,
			$operation,
			$uriVariables,
			$context
		);
	}
}
