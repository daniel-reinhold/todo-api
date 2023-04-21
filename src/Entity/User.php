<?php

namespace App\Entity;

use App\Enum\FilterType;
use App\Enum\SortOrder;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $first_name = null;

    #[ORM\Column(length: 255)]
    private ?string $last_name = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $email_address = null;

    #[ORM\Column(length: 1024)]
    private ?string $password = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_of_birth = null;

    #[ORM\Column]
    private ?bool $deleted = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deleted_at = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Todo::class, orphanRemoval: true)]
    private Collection $todos;

    public function __construct()
    {
        $this->todos = new ArrayCollection();
    }

    // <editor-fold desc="Getters & Setters" defaultstate="collapsed">

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): self
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): self
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getEmailAddress(): ?string
    {
        return $this->email_address;
    }

    public function setEmailAddress(string $email_address): self
    {
        $this->email_address = $email_address;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->date_of_birth;
    }

    public function setDateOfBirth(\DateTimeInterface $date_of_birth): self
    {
        $this->date_of_birth = $date_of_birth;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getRoles(): array
    {
        return [];
    }

    public function eraseCredentials() {}

    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(?bool $deleted): void
    {
        $this->deleted = $deleted;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deleted_at;
    }

    public function setDeletedAt(?\DateTimeInterface $deleted_at): void
    {
        $this->deleted_at = $deleted_at;
    }

    // </editor-fold>

    // <editor-fold desc="To Do relationship methods" defaultstate="collapsed">

    public function getTodos(
        FilterType $showDeleted = FilterType::Not,
        FilterType $showDone = FilterType::Both,
        ?\DateTime $startTime = null,
        ?\DateTime $endTime = null,
        string $sortBy = "created_at",
        SortOrder $sortOrder = SortOrder::DESC
    ): Collection
    {
        $criteria = Criteria::create();

        // Add criteria to only show deleted todos
        if ($showDeleted == FilterType::Only) {
            $criteria->andWhere($criteria::expr()->eq('deleted', true));
        }

        // Add criteria to not show deleted todos
        if ($showDeleted == FilterType::Not) {
            $criteria->andWhere($criteria::expr()->eq('deleted', false));
        }

        // Add criteria to only show done todos
        if ($showDone == FilterType::Only) {
            $criteria->andWhere($criteria::expr()->eq('done', true));
        }

        // Add criteria to not show done todos
        if ($showDone == FilterType::Not) {
            $criteria->andWhere($criteria::expr()->eq('done', false));
        }

        // Add criteria to not show any todos which don't have a time set
        if ($startTime != null || $endTime != null) {
            $criteria->andWhere($criteria::expr()->neq('time', null));
        }

        // Add criteria to only show todos which time is between startTime and endTime
        if ($startTime != null && $endTime != null) {
            $criteria->andWhere(
                $criteria::expr()->gte('time', $startTime)
            );
            $criteria->andWhere(
                $criteria::expr()->lte('time', $endTime)
            );
        }

        // Add criteria to only show todos which time is greater than or equal to start time
        if ($startTime != null && $endTime == null) {
            $criteria->andWhere(
                $criteria::expr()->gte('time', $startTime)
            );
        }

        // Add criteria to only show todos which time is less than or equal to end time
        if ($endTime != null && $startTime == null) {
            $criteria->andWhere(
                $criteria::expr()->lte('time', $endTime)
            );
        }

        $criteria->orderBy([$sortBy => $sortOrder->toString()]);

        return $this->todos->matching($criteria);
    }

    public function addTodo(Todo $todo): self
    {
        if (!$this->todos->contains($todo)) {
            $this->todos->add($todo);
            $todo->setUser($this);
        }

        return $this;
    }

    public function removeTodo(Todo $todo): self
    {
        if ($this->todos->removeElement($todo)) {
            // set the owning side to null (unless already changed)
            if ($todo->getUser() === $this) {
                $todo->setUser(null);
            }
        }

        return $this;
    }

    // </editor-fold>

    public function asJsonObject(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'email_address' => $this->email_address,
            'date_of_birth' => $this->date_of_birth?->getTimestamp(),
            'created_at' => $this->created_at?->getTimestamp(),
            'updated_at' => $this->updated_at?->getTimestamp()
        ];
    }
}
