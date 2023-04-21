<?php

namespace App\Entity;

use App\Repository\TodoRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TodoRepository::class)]
class Todo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 1024)]
    private ?string $title = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $time = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $deleted = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $done = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deleted_at = null;

    #[ORM\ManyToOne(inversedBy: 'todos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'todo', targetEntity: Notification::class, orphanRemoval: true)]
    private Collection $notifications;

    public function __construct()
    {
        $this->notifications = new ArrayCollection();
    }

    const AVAILABLE_FILTERS = ['title', 'description', 'time', 'created_at'];

    // <editor-fold desc="Getter & Setter" defaultstate="collapsed">

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(?\DateTimeInterface $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(?bool $deleted): void
    {
        $this->deleted = $deleted;
    }

    public function getDone(): ?bool
    {
        return $this->done;
    }

    public function setDone(?bool $done): void
    {
        $this->done = $done;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): void
    {
        $this->updated_at = $updated_at;
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

    // <editor-fold desc="User relationship methods" defaulstate="collapsed">

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    // </editor-fold>

    // <editor-fold desc="Notification relationship methods" defaultstate="collapsed">

    public function getNotifications(): Collection
    {
        $criteria = Criteria::create();

        $criteria->where($criteria::expr()->eq('deleted', false));
        $criteria->andWhere($criteria::expr()->eq('sent', false));
        $criteria->andWhere($criteria::expr()->gte('send_at', new DateTime()));

        return $this->notifications->matching($criteria);
    }

    public function addNotification(Notification $notification): self
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setTodo($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): self
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getTodo() === $this) {
                $notification->setTodo(null);
            }
        }

        return $this;
    }

    // </editor-fold>

    public function asJsonObject(): array {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'time' => $this->time?->getTimestamp(),
            'done' => $this->done,
            'deleted' => $this->deleted,
            'created_at' => $this->created_at?->getTimestamp(),
            'updated_at' => $this->updated_at?->getTimestamp(),
            'deleted_at' => $this->deleted_at?->getTimestamp(),
            'notifications' => $this->getNotifications()->map(function (Notification $notification) {
                return $notification->asJsonObject();
            })->toArray()
        ];
    }
}
