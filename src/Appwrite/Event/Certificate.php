<?php

namespace Appwrite\Event;

use Resque;
use Utopia\Database\Document;

/**
 * Certificate Event Class
 *
 * This class represents an event related to certificates and is used to trigger certificate-related tasks in a queue.
 */
class Certificate extends Event
{
    protected bool $skipRenewCheck = false;
    protected ?Document $domain = null;

    /**
     * Constructor for Certificate Event
     *
     * Initializes the Certificate event with the appropriate queue name and class name.
     */
    public function __construct()
    {
        parent::__construct(Event::CERTIFICATES_QUEUE_NAME, Event::CERTIFICATES_CLASS_NAME);
    }

    /**
     * Set the domain for this certificate event.
     *
     * @param Document $domain The domain document associated with the certificate.
     * @return self
     */
    public function setDomain(Document $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get the domain associated with this certificate event.
     *
     * @return null|Document The domain document, or null if not set.
     */
    public function getDomain(): ?Document
    {
        return $this->domain;
    }

    /**
     * Set whether the certificate needs to be renewed and validated.
     *
     * @param bool $skipRenewCheck Set to true to skip renewal check, false otherwise.
     * @return self
     */
    public function setSkipRenewCheck(bool $skipRenewCheck): self
    {
        $this->skipRenewCheck = $skipRenewCheck;

        return $this;
    }

    /**
     * Check if the certificate renewal needs to be skipped.
     *
     * @return bool True if renewal check is skipped, false otherwise.
     */
    public function getSkipRenewCheck(): bool
    {
        return $this->skipRenewCheck;
    }

    /**
     * Trigger the certificate event and send it to the certificates worker queue for processing.
     *
     * @return string|bool Returns the result of the enqueue operation, or false if there's an issue.
     * @throws \InvalidArgumentException If there's a problem with the enqueue operation.
     */
    public function trigger(): string|bool
    {
        return Resque::enqueue($this->queue, $this->class, [
            'project' => $this->project,
            'domain' => $this->domain,
            'skipRenewCheck' => $this->skipRenewCheck
        ]);
    }
}

