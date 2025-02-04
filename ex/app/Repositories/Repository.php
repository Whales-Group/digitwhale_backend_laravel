<?php

namespace App\Repositories;

/**
 * Class Repository
 *
 * This is the base repository class that can be extended by other repository classes.
 * It contains a protected `$description` variable that holds a description of the repository.
 * The description is overridable either via constructor or by extending the class and overriding
 * the property in the child class.
 *
 * @package App\Repositories
 */
class Repository
{
    /**
     * @var string $description
     *
     * This variable holds the description of the repository.
     * It can be overridden by child classes or via the constructor when creating an instance.
     * Default: "Default Repository - (Contains Default Methods and variables)"
     */
    protected string $description = "Default Repository - (Contains Default Methods and variables )";

    protected string $baseUrl = "https://repository-url.com/api/v1";

    protected array $headers = [];

    /**
     * Get the description of the repository.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
