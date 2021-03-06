<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource( 
 * itemOperations={"get"={"security"="is_granted('ROLE_ADMIN')"}},
 * collectionOperations={"get"},
 * normalizationContext={"groups"={"user:read"}} 
 * )
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(fields={"email"}, message="There is already an account with this email")
 */
class User implements UserInterface {

    /**
     * @Groups({"user:read"})
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Groups({"user:read"})
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\YearPlan", mappedBy="user")
     */
    private $yearPlans;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\YearPlan")
     */
    private $choosedYearPlan;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserPlant", mappedBy="user", orphanRemoval=true,cascade={"persist"})
     */
    private $userPlants;

    public function __construct() {
        $this->yearPlans = new ArrayCollection();
        $this->userPlants = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getEmail(): ?string {
        return $this->email;
    }

    public function setEmail(string $email): self {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string {
        return (string) $this->password;
    }

    public function setPassword(string $password): self {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt() {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials() {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection|YearPlan[]
     */
    public function getYearPlans(): Collection {
        return $this->yearPlans;
    }

    public function addYearPlan(YearPlan $yearPlan): self {
        if (!$this->yearPlans->contains($yearPlan)) {
            $this->yearPlans[] = $yearPlan;
            $yearPlan->setUser($this);
        }

        return $this;
    }

    public function removeYearPlan(YearPlan $yearPlan): self {
        if ($this->yearPlans->contains($yearPlan)) {
            $this->yearPlans->removeElement($yearPlan);
            // set the owning side to null (unless already changed)
            if ($yearPlan->getUser() === $this) {
                $yearPlan->setUser(null);
            }
        }

        return $this;
    }

    public function getChoosedYearPlan(): ?yearPlan {
        return $this->choosedYearPlan;
    }

    public function setChoosedYearPlan(?yearPlan $choosedYearPlan): self {
        $this->choosedYearPlan = $choosedYearPlan;

        return $this;
    }

    /**
     * @return Collection|UserPlant[]
     */
    public function getUserPlants(): Collection {
        return $this->userPlants;
    }

    public function getUserPlantsPlant(): Collection {
        $plants = new ArrayCollection();
        foreach ($this->userPlants as $userPlant) {
            $plants->add($userPlant->getPlant());
        }
        return $plants;
    }

    public function addUserPlant(UserPlant $userPlant): self {
        if (!$this->userPlants->contains($userPlant)) {
            $this->userPlants[] = $userPlant;
            $userPlant->setUser($this);
        }

        return $this;
    }

    public function removeUserPlant(UserPlant $userPlant): self {
        if ($this->userPlants->contains($userPlant)) {
            $this->userPlants->removeElement($userPlant);
            // set the owning side to null (unless already changed)
            if ($userPlant->getUser() === $this) {
                $userPlant->setUser(null);
            }
        }

        return $this;
    }

    // Api

    public function getYearPlanById($yearPlanId) {
        foreach ($this->yearPlans as $yearPlan) {
            if ($yearPlan->getId() == $yearPlanId)
                return $yearPlan;
        }
    }

}
