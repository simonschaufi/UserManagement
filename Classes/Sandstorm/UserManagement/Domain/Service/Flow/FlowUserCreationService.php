<?php
namespace Sandstorm\UserManagement\Domain\Service\Flow;

use Sandstorm\UserManagement\Domain\Model\RegistrationFlow;
use Sandstorm\UserManagement\Domain\Repository\UserRepository;
use Sandstorm\UserManagement\Domain\Service\UserCreationServiceInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\Policy\Role;
use Sandstorm\UserManagement\Domain\Model\User;

/**
 * @Flow\Scope("singleton")
 */
class FlowUserCreationService implements UserCreationServiceInterface
{

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @Flow\InjectConfiguration(path="rolesForNewUsers")
     */
    protected $rolesForNewUsers;

    /**
     * In this method, actually create the user / account.
     *
     * NOTE: After this method is called, the $registrationFlow is DESTROYED, so you need to store all attributes
     * in your object as you need them.
     *
     * @param RegistrationFlow $registrationFlow
     * @return void
     */
    public function createUserAndAccount(RegistrationFlow $registrationFlow)
    {
        // Create the account
        $account = new Account();
        $account->setAccountIdentifier($registrationFlow->getEmail());
        $account->setCredentialsSource($registrationFlow->getEncryptedPassword());
        $account->setAuthenticationProviderName('Sandstorm.UserManagement:Login');

        // Assign pre-configured roles
        foreach ($this->rolesForNewUsers as $roleString) {
            $account->addRole(new Role($roleString));
        }

        // Create the user
        $user = new User();
        $user->setAccount($account);
        $user->setEmail($registrationFlow->getEmail());
        if (array_key_exists('salutation', $registrationFlow->getAttributes())) {
            $user->setGender($registrationFlow->getAttributes()['salutation']);
        }
        if (array_key_exists('firstName', $registrationFlow->getAttributes())) {
            $user->setFirstName($registrationFlow->getAttributes()['firstName']);
        }
        if (array_key_exists('lastName', $registrationFlow->getAttributes())) {
            $user->setLastName($registrationFlow->getAttributes()['lastName']);
        }

        // Persist user
        $this->userRepository->add($user);
        $this->persistenceManager->whitelistObject($user);
        $this->persistenceManager->whitelistObject($account);
    }
}
